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
use App\Models\TermsVersion;
use App\Models\User;
use App\Notifications\LoanReturnConfirmation;
use App\Notifications\LoanReturnedAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Where a game came back: the pickup place, a later event there, or any regular location. */
class PickupReturnTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $member;

    private PickupLocation $main;

    private PickupLocation $personal;

    private Event $pickup;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->travelTo(now()->setDate(2026, 12, 1)->setTime(10, 0));
        LoanSetting::factory()->create(['deposit_pct_very_good' => 0, 'pickup_event_lead_hours' => 24]);
        $this->admin = User::factory()->admin()->create();
        $this->member = User::factory()->member()->create(['tokens' => 50]);
        $this->main = PickupLocation::where('name', 'Hauptstandort')->firstOrFail();
        $this->personal = PickupLocation::factory()->create(['name' => 'Vereinslokal', 'is_personal' => true]);
        $this->pickup = $this->eventOn('2026-12-03');
    }

    private function eventOn(string $date): Event
    {
        return Event::factory()->pickupAt($this->personal)->create(['date' => $date, 'time' => '18:00', 'end_time' => '20:00']);
    }

    /** Booked for the event and handed out there. */
    private function eventLoan(string $title = 'Azul'): Loan
    {
        $game = Game::factory()->create(['title' => $title, 'deposit_tokens' => 0]);
        $copy = Copy::factory()->create(['game_id' => $game->id, 'condition' => 'NEW', 'qr_code' => strtoupper(substr($title, 0, 3)).'123']);
        $this->actingAs($this->member)
            ->postJson('/api/loans', ['copy_id' => $copy->id, 'pickup_event_id' => $this->pickup->id])
            ->assertCreated();
        $loan = Loan::where('copy_id', $copy->id)->firstOrFail();
        $this->travelTo(now()->setDate(2026, 12, 3)->setTime(18, 30));
        $loan->update(['handed_out_at' => now()]);

        return $loan;
    }

    private function returnOptions(Loan $loan): array
    {
        return $this->actingAs($this->member)->getJson("/api/loans/{$loan->id}/return-options")->assertOk()->json();
    }

    public function test_regular_loan_goes_back_to_its_pickup_place_by_default(): void
    {
        $loan = Loan::factory()->create(['user_id' => $this->member->id, 'pickup_location_id' => $this->main->id]);

        $options = $this->returnOptions($loan);
        $this->assertSame(['Hauptstandort'], collect($options['locations'])->pluck('name')->all());
        $this->assertSame([], $options['events']);

        $this->actingAs($this->member)->postJson("/api/loans/{$loan->id}/return", ['return_condition' => 'GOOD'])->assertOk();
        $this->assertSame($this->main->id, $loan->fresh()?->return_location_id);
    }

    public function test_event_loan_offers_regular_locations_and_events_since_the_hand_out(): void
    {
        $loan = $this->eventLoan();
        $this->eventOn('2026-11-20'); // before the hand-out
        $later = $this->eventOn('2026-12-10');
        $this->eventOn('2026-12-20'); // still ahead

        $this->travelTo(now()->setDate(2026, 12, 12));
        $options = $this->returnOptions($loan);

        $this->assertSame(['Hauptstandort'], collect($options['locations'])->pluck('name')->all());
        $this->assertSame([$later->id, $this->pickup->id], collect($options['events'])->pluck('id')->all());
    }

    public function test_event_loan_needs_a_choice_and_records_it(): void
    {
        $loan = $this->eventLoan();
        $later = $this->eventOn('2026-12-10');
        $this->travelTo(now()->setDate(2026, 12, 10)->setTime(19, 0));

        $this->actingAs($this->member)
            ->postJson("/api/loans/{$loan->id}/return", ['return_condition' => 'GOOD'])
            ->assertUnprocessable()->assertJsonPath('reason', 'invalid_return_place');

        $this->actingAs($this->member)
            ->postJson("/api/loans/{$loan->id}/return", ['return_condition' => 'GOOD', 'return_event_id' => $later->id])
            ->assertOk();

        $loan->refresh();
        $this->assertSame($this->personal->id, $loan->return_location_id);
        $this->assertSame($later->id, $loan->return_event_id);
    }

    public function test_event_loan_can_come_back_at_a_regular_location_and_is_collected_there(): void
    {
        $loan = $this->eventLoan();

        $this->actingAs($this->member)
            ->postJson("/api/loans/{$loan->id}/return", ['return_condition' => 'GOOD', 'return_location_id' => $this->main->id])
            ->assertOk();

        $this->assertSame($this->main->id, $loan->fresh()?->return_location_id);
        $this->actingAs($this->admin)
            ->getJson('/api/admin/copies?condition=REVIEW')
            ->assertJsonPath('data.0.last_return.pickup_location.name', 'Hauptstandort');
        Notification::assertSentTo($this->admin, LoanReturnedAdmin::class, function (LoanReturnedAdmin $mail) {
            return str_contains($mail->toMail($this->admin)->subject, 'Hauptstandort');
        });
    }

    public function test_place_outside_the_options_is_refused(): void
    {
        $loan = $this->eventLoan();
        $future = $this->eventOn('2026-12-20');
        $restricted = PickupLocation::factory()->create();
        $restricted->users()->attach($this->admin);

        foreach ([['return_event_id' => $future->id], ['return_location_id' => $restricted->id], ['return_location_id' => $this->personal->id]] as $choice) {
            $this->actingAs($this->member)
                ->postJson("/api/loans/{$loan->id}/return", ['return_condition' => 'GOOD', ...$choice])
                ->assertUnprocessable();
        }
    }

    public function test_admin_scan_at_the_event_books_the_return_there(): void
    {
        $loan = $this->eventLoan('Azul');
        $later = $this->eventOn('2026-12-10');

        $this->actingAs($this->admin)
            ->postJson("/api/admin/events/{$later->id}/returns", ['qr_code' => 'azu123', 'return_condition' => 'WORN'])
            ->assertOk()
            ->assertJsonPath('returns.0.game_title', 'Azul')
            ->assertJsonPath('returns.0.condition', 'WORN');

        $loan->refresh();
        $this->assertSame('RETURNED', $loan->status);
        $this->assertSame($later->id, $loan->return_event_id);
        Notification::assertSentTo($this->member, LoanReturnConfirmation::class);
        Notification::assertNotSentTo($this->admin, LoanReturnedAdmin::class);

        $this->actingAs($this->admin)
            ->getJson("/api/admin/events/{$later->id}/packing-list")
            ->assertJsonPath('returns.0.game_title', 'Azul');
    }

    public function test_scanning_one_game_of_a_package_returns_the_package(): void
    {
        $package = Package::factory()->create(['is_active' => true]);
        foreach (['Carcassonne', 'Dixit'] as $title) {
            $game = Game::factory()->create(['title' => $title, 'deposit_tokens' => 0]);
            Copy::factory()->create(['game_id' => $game->id, 'condition' => 'NEW', 'qr_code' => strtoupper(substr($title, 0, 3)).'777']);
            $package->games()->attach($game->id);
        }
        $this->actingAs($this->member)
            ->postJson('/api/package-loans', ['package_id' => $package->id, 'pickup_event_id' => $this->pickup->id])
            ->assertCreated();
        $packageLoan = PackageLoan::firstOrFail();
        $this->actingAs($this->admin)->patchJson("/api/admin/package-loans/{$packageLoan->id}/hand-out", ['handed_out' => true]);

        $this->actingAs($this->admin)
            ->postJson("/api/admin/events/{$this->pickup->id}/returns", ['qr_code' => 'DIX777'])
            ->assertOk();

        $this->assertSame('RETURNED', $packageLoan->fresh()?->status);
        $this->assertSame(2, Loan::where('return_event_id', $this->pickup->id)->count());
    }

    public function test_scan_of_an_unknown_or_waiting_copy_is_refused(): void
    {
        $this->actingAs($this->admin)
            ->postJson("/api/admin/events/{$this->pickup->id}/returns", ['qr_code' => 'NOPE'])
            ->assertNotFound();

        $game = Game::factory()->create(['deposit_tokens' => 0]);
        $copy = Copy::factory()->create(['game_id' => $game->id, 'condition' => 'NEW', 'qr_code' => 'WAIT01']);
        $later = $this->eventOn('2026-12-10');
        $this->actingAs($this->member)
            ->postJson('/api/loans', ['copy_id' => $copy->id, 'pickup_event_id' => $later->id])
            ->assertCreated();

        $this->actingAs($this->admin)
            ->postJson("/api/admin/events/{$this->pickup->id}/returns", ['qr_code' => 'WAIT01'])
            ->assertUnprocessable()->assertJsonPath('reason', 'awaiting_pickup');
    }

    public function test_terms_explain_personal_pickup_returns_and_no_shows(): void
    {
        $content = TermsVersion::where('version', '1.3')->value('content');

        $this->assertStringContainsString('späteren Abholtermin am selben Ort', $content);
        $this->assertStringContainsString('nicht abgeholt', $content);
    }
}
