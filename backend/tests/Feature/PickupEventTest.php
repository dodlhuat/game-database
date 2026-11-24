<?php

namespace Tests\Feature;

use App\Models\Copy;
use App\Models\Event;
use App\Models\Game;
use App\Models\Loan;
use App\Models\LoanSetting;
use App\Models\Package;
use App\Models\PackageLoan;
use App\Models\PickupLocation;
use App\Models\User;
use App\Notifications\LoanConfirmation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** Personal pickup locations are only offered through pickup events, which bring date and time. */
class PickupEventTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $member;

    private PickupLocation $main;

    private PickupLocation $personal;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->travelTo(now()->setDate(2026, 12, 1)->setTime(10, 0));
        LoanSetting::factory()->create([
            'deposit_pct_very_good' => 0,
            'loan_duration_weeks' => 2,
            'pickup_event_lead_hours' => 24,
        ]);
        $this->admin = User::factory()->admin()->create();
        $this->member = User::factory()->member()->create(['tokens' => 50]);
        $this->main = PickupLocation::where('name', 'Hauptstandort')->firstOrFail();
        $this->personal = PickupLocation::factory()->create(['name' => 'Vereinslokal', 'is_personal' => true]);
    }

    private function event(array $attributes = [], ?int $max = null): Event
    {
        return Event::factory()->pickupAt($this->personal, $max)->create([
            'title' => 'Abholabend',
            'date' => '2026-12-10',
            'time' => '18:00',
            'end_time' => '20:00',
            ...$attributes,
        ]);
    }

    private function borrow(User $user, array $choice): TestResponse
    {
        $copy = Copy::factory()->create(['condition' => 'NEW']);

        return $this->actingAs($user)->postJson('/api/loans', ['copy_id' => $copy->id, ...$choice]);
    }

    // ── What members are offered ─────────────────────────────────

    public function test_personal_location_is_never_offered_directly(): void
    {
        $this->event();

        $names = collect($this->actingAs($this->member)->getJson('/api/pickup-locations')->json('data'))->pluck('name');
        $this->assertSame(['Hauptstandort'], $names->all());

        $this->borrow($this->member, ['pickup_location_id' => $this->personal->id])
            ->assertUnprocessable()
            ->assertJsonPath('reason', 'invalid_pickup_location');
    }

    public function test_bookable_events_list_place_time_and_free_spots(): void
    {
        $this->event([], 5);

        $this->actingAs($this->member)
            ->getJson('/api/pickup-events')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.date', '2026-12-10')
            ->assertJsonPath('data.0.time', '18:00')
            ->assertJsonPath('data.0.end_time', '20:00')
            ->assertJsonPath('data.0.spots_left', 5)
            ->assertJsonPath('data.0.location.name', 'Vereinslokal');
    }

    public function test_events_after_the_booking_deadline_past_or_full_are_not_offered(): void
    {
        $this->event(['date' => '2026-12-02', 'time' => '09:00']); // closes 24h before: already closed
        $this->event(['date' => '2026-11-20']); // past
        $full = $this->event(['date' => '2026-12-12'], 1);
        Loan::factory()->create(['pickup_event_id' => $full->id, 'pickup_location_id' => $this->personal->id]);
        $this->event(['date' => '2026-12-14']); // bookable

        $dates = collect($this->actingAs($this->member)->getJson('/api/pickup-events')->json('data'))->pluck('date');
        $this->assertSame(['2026-12-14'], $dates->all());
    }

    public function test_events_at_inactive_or_restricted_locations_are_hidden(): void
    {
        $this->event();
        $this->personal->users()->attach(User::factory()->member()->create());

        $this->actingAs($this->member)->getJson('/api/pickup-events')->assertJsonCount(0, 'data');
        $this->actingAs($this->admin)->getJson('/api/pickup-events')->assertJsonCount(1, 'data');

        $this->personal->users()->detach();
        $this->personal->update(['is_active' => false]);
        $this->actingAs($this->member)->getJson('/api/pickup-events')->assertJsonCount(0, 'data');
    }

    // ── Booking ──────────────────────────────────────────────────

    public function test_booking_an_event_uses_its_place_and_date(): void
    {
        $event = $this->event();

        $this->borrow($this->member, ['pickup_event_id' => $event->id])
            ->assertCreated()
            ->assertJsonPath('data.pickup_location.id', $this->personal->id)
            ->assertJsonPath('data.pickup_event.time', '18:00');

        $loan = Loan::firstOrFail();
        $this->assertSame('2026-12-10', $loan->start_date?->toDateString());
        $this->assertSame('2026-12-24', $loan->due_date->toDateString());
        $this->assertSame($event->id, $loan->pickup_event_id);
        Notification::assertSentTo($this->member, LoanConfirmation::class);
    }

    public function test_confirmation_mail_names_the_time_span(): void
    {
        $event = $this->event();
        $this->borrow($this->member, ['pickup_event_id' => $event->id])->assertCreated();
        $loan = Loan::with(['copy.game', 'pickupLocation', 'pickupEvent'])->firstOrFail();

        $mail = (new LoanConfirmation($loan))->toMail($this->member);

        $this->assertStringContainsString('10.12.2026, 18:00–20:00 Uhr', implode(' ', array_map('strval', $mail->introLines)));
    }

    public function test_full_event_rejects_further_bookings_and_charges_nothing(): void
    {
        $event = $this->event([], 1);
        $this->borrow($this->member, ['pickup_event_id' => $event->id])->assertCreated();

        $other = User::factory()->member()->create(['tokens' => 50]);
        $this->borrow($other, ['pickup_event_id' => $event->id])->assertUnprocessable();

        $this->assertSame(1, Loan::count());
        $this->assertSame(50, $other->fresh()?->tokens);
    }

    public function test_booking_closes_at_the_lead_time(): void
    {
        // Now is 01.12. 10:00 — 24 hours ahead is 02.12. 10:00
        $open = $this->event(['date' => '2026-12-02', 'time' => '10:30', 'end_time' => '12:00']);
        $closed = $this->event(['date' => '2026-12-02', 'time' => '09:30', 'end_time' => '12:00']);

        $this->borrow($this->member, ['pickup_event_id' => $closed->id])->assertUnprocessable();
        $this->borrow($this->member, ['pickup_event_id' => $open->id])->assertCreated();
    }

    public function test_package_booking_counts_as_one_and_carries_the_event(): void
    {
        $event = $this->event([], 2);
        $package = Package::factory()->create(['is_active' => true]);
        foreach (range(1, 3) as $_) {
            $game = Game::factory()->create(['deposit_tokens' => 0]);
            Copy::factory()->create(['game_id' => $game->id, 'condition' => 'NEW']);
            $package->games()->attach($game->id);
        }

        $this->actingAs($this->member)
            ->postJson('/api/package-loans', ['package_id' => $package->id, 'pickup_event_id' => $event->id])
            ->assertCreated()
            ->assertJsonPath('data.pickup_event.id', $event->id);

        $this->assertSame(3, Loan::where('pickup_event_id', $event->id)->count());
        $this->assertSame(1, $event->bookingsCount());
        $this->assertSame('2026-12-10', PackageLoan::firstOrFail()->start_date?->toDateString());
    }

    public function test_regular_booking_keeps_the_shared_appointment(): void
    {
        $this->borrow($this->member, ['pickup_location_id' => $this->main->id])
            ->assertCreated()
            ->assertJsonPath('data.pickup_event', null);
    }

    // ── Admin: events ────────────────────────────────────────────

    public function test_pickup_event_needs_a_personal_location_and_a_time(): void
    {
        $payload = ['title' => 'Abholung', 'date' => '2026-12-10', 'time' => '18:00', 'end_time' => '20:00'];

        $this->actingAs($this->admin)
            ->postJson('/api/admin/events', [...$payload, 'pickup_location_id' => $this->main->id])
            ->assertJsonValidationErrors('pickup_location_id');

        $this->actingAs($this->admin)
            ->postJson('/api/admin/events', ['title' => 'Abholung', 'date' => '2026-12-10', 'is_all_day' => true, 'pickup_location_id' => $this->personal->id])
            ->assertJsonValidationErrors('is_all_day');

        $this->actingAs($this->admin)
            ->postJson('/api/admin/events', [...$payload, 'pickup_location_id' => $this->personal->id, 'max_loans' => 8])
            ->assertCreated()
            ->assertJsonPath('data.is_pickup', true)
            ->assertJsonPath('data.pickup_location.name', 'Vereinslokal')
            ->assertJsonPath('data.max_loans', 8);
    }

    public function test_booked_event_keeps_its_place_and_cannot_be_deleted(): void
    {
        $event = $this->event([], 5);
        $this->borrow($this->member, ['pickup_event_id' => $event->id])->assertCreated();
        $base = ['title' => 'Abholabend', 'date' => '2026-12-10', 'time' => '18:00', 'end_time' => '20:00', 'pickup_location_id' => $this->personal->id];

        $other = PickupLocation::factory()->create(['is_personal' => true]);
        $this->actingAs($this->admin)->putJson("/api/admin/events/{$event->id}", [...$base, 'pickup_location_id' => $other->id])
            ->assertUnprocessable()->assertJsonPath('reason', 'has_bookings');
        $this->actingAs($this->admin)->putJson("/api/admin/events/{$event->id}", [...$base, 'max_loans' => 0])
            ->assertUnprocessable();
        $this->actingAs($this->admin)->putJson("/api/admin/events/{$event->id}", [...$base, 'title' => 'Neu', 'max_loans' => 3])
            ->assertOk();
        $this->actingAs($this->admin)->deleteJson("/api/admin/events/{$event->id}")
            ->assertUnprocessable();
    }

    public function test_calendar_marks_pickup_events_and_hides_restricted_ones(): void
    {
        Event::factory()->create(['title' => 'Spieleabend']);
        $this->event();

        $this->actingAs($this->member)->getJson('/api/events')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.1.is_pickup', true)
            ->assertJsonPath('data.1.pickup_location.name', 'Vereinslokal');

        $this->personal->users()->attach($this->admin);
        $this->actingAs($this->member)->getJson('/api/events')->assertJsonCount(1, 'data');
    }

    // ── Admin: locations ─────────────────────────────────────────

    public function test_last_regular_location_cannot_become_personal(): void
    {
        $this->actingAs($this->admin)
            ->putJson("/api/admin/pickup-locations/{$this->main->id}", ['name' => 'Hauptstandort', 'is_personal' => true])
            ->assertUnprocessable()
            ->assertJsonPath('reason', 'last_active_location');
    }

    public function test_personal_location_with_upcoming_events_stays_personal_and_cannot_be_deleted(): void
    {
        $this->event();

        $this->actingAs($this->admin)
            ->putJson("/api/admin/pickup-locations/{$this->personal->id}", ['name' => 'Vereinslokal', 'is_personal' => false])
            ->assertUnprocessable()
            ->assertJsonPath('reason', 'upcoming_pickup_events');

        $this->actingAs($this->admin)
            ->deleteJson("/api/admin/pickup-locations/{$this->personal->id}")
            ->assertUnprocessable();
    }

    public function test_personal_locations_do_not_count_as_the_last_active_one(): void
    {
        $this->actingAs($this->admin)
            ->patchJson("/api/admin/pickup-locations/{$this->main->id}/toggle")
            ->assertUnprocessable()
            ->assertJsonPath('reason', 'last_active_location');
    }

    public function test_lead_hours_are_configurable(): void
    {
        $settings = $this->actingAs($this->admin)->getJson('/api/admin/loan-settings')->json();

        $this->actingAs($this->admin)
            ->patchJson('/api/admin/loan-settings', [...$settings, 'pickup_event_lead_hours' => 48])
            ->assertOk()
            ->assertJsonPath('pickup_event_lead_hours', 48);
    }
}
