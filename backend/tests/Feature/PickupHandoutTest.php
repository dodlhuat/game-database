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
use App\Notifications\PickupEventReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** At the pickup event: packing list, hand-out, and what a loan cannot do before it. */
class PickupHandoutTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $member;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->travelTo(now()->setDate(2026, 12, 1)->setTime(10, 0));
        LoanSetting::factory()->create(['deposit_pct_very_good' => 0, 'pickup_event_lead_hours' => 24]);
        $this->admin = User::factory()->admin()->create();
        $this->member = User::factory()->member()->create(['name' => 'Mia Muster', 'tokens' => 50]);
        $location = PickupLocation::factory()->create(['name' => 'Vereinslokal', 'is_personal' => true]);
        $this->event = Event::factory()->pickupAt($location, 10)->create([
            'date' => '2026-12-10',
            'time' => '18:00',
            'end_time' => '20:00',
        ]);
    }

    private function bookGame(string $title = 'Azul'): Loan
    {
        $game = Game::factory()->create(['title' => $title, 'deposit_tokens' => 0]);
        $copy = Copy::factory()->create(['game_id' => $game->id, 'condition' => 'NEW']);
        $this->actingAs($this->member)
            ->postJson('/api/loans', ['copy_id' => $copy->id, 'pickup_event_id' => $this->event->id])
            ->assertCreated();

        return Loan::where('copy_id', $copy->id)->firstOrFail();
    }

    private function bookPackage(): PackageLoan
    {
        $package = Package::factory()->create(['name' => 'Familie', 'is_active' => true]);
        foreach (['Carcassonne', 'Dixit'] as $title) {
            $game = Game::factory()->create(['title' => $title, 'deposit_tokens' => 0]);
            Copy::factory()->create(['game_id' => $game->id, 'condition' => 'NEW']);
            $package->games()->attach($game->id);
        }
        $this->actingAs($this->member)
            ->postJson('/api/package-loans', ['package_id' => $package->id, 'pickup_event_id' => $this->event->id])
            ->assertCreated();

        return PackageLoan::firstOrFail();
    }

    private function handOut(string $path, bool $out = true): TestResponse
    {
        return $this->actingAs($this->admin)->patchJson($path, ['handed_out' => $out]);
    }

    public function test_event_loans_wait_for_hand_out_and_regular_loans_do_not(): void
    {
        $eventLoan = $this->bookGame();
        $this->assertNull($eventLoan->handed_out_at);
        $this->assertTrue($eventLoan->isAwaitingPickup());

        $copy = Copy::factory()->create(['condition' => 'NEW']);
        $this->actingAs($this->member)->postJson('/api/loans', ['copy_id' => $copy->id])->assertCreated();
        $this->assertNotNull(Loan::where('copy_id', $copy->id)->firstOrFail()->handed_out_at);
    }

    public function test_packing_list_groups_games_per_booking(): void
    {
        $this->bookGame('Azul');
        $this->bookPackage();

        $response = $this->actingAs($this->admin)
            ->getJson("/api/admin/events/{$this->event->id}/packing-list")
            ->assertOk()
            ->assertJsonPath('event.time', '18:00')
            ->assertJsonPath('event.location.name', 'Vereinslokal')
            ->assertJsonCount(2, 'bookings');

        $package = collect($response->json('bookings'))->firstWhere('type', 'package');
        $this->assertSame('Familie', $package['package_name']);
        $this->assertSame(['Carcassonne', 'Dixit'], collect($package['items'])->pluck('game_title')->sort()->values()->all());
        $this->assertSame('Mia Muster', $package['member']['name']);
    }

    public function test_hand_out_and_undo(): void
    {
        $loan = $this->bookGame();

        $this->handOut("/api/admin/loans/{$loan->id}/hand-out")->assertOk();
        $this->assertNotNull($loan->fresh()?->handed_out_at);

        $this->handOut("/api/admin/loans/{$loan->id}/hand-out", false)->assertOk();
        $this->assertNull($loan->fresh()?->handed_out_at);
    }

    public function test_package_hand_out_covers_every_game(): void
    {
        $packageLoan = $this->bookPackage();

        $this->handOut("/api/admin/package-loans/{$packageLoan->id}/hand-out")->assertOk();

        $this->assertNotNull($packageLoan->fresh()?->handed_out_at);
        $this->assertSame(0, $packageLoan->loans()->whereNull('handed_out_at')->count());
    }

    public function test_single_game_of_a_package_is_handed_out_with_its_package(): void
    {
        $packageLoan = $this->bookPackage();
        $loan = $packageLoan->loans()->firstOrFail();

        $this->handOut("/api/admin/loans/{$loan->id}/hand-out")->assertUnprocessable();
    }

    public function test_nothing_can_be_extended_or_returned_before_the_pickup(): void
    {
        $loan = $this->bookGame();
        $packageLoan = $this->bookPackage();

        $this->actingAs($this->member)
            ->postJson("/api/loans/{$loan->id}/extend", ['requested_due_date' => '2026-12-30'])
            ->assertUnprocessable()->assertJsonPath('reason', 'awaiting_pickup');
        $this->actingAs($this->member)
            ->postJson("/api/loans/{$loan->id}/return", ['return_condition' => 'GOOD'])
            ->assertUnprocessable()->assertJsonPath('reason', 'awaiting_pickup');
        $this->actingAs($this->member)
            ->postJson("/api/package-loans/{$packageLoan->id}/return", ['return_condition' => 'GOOD'])
            ->assertUnprocessable()->assertJsonPath('reason', 'awaiting_pickup');

        $this->actingAs($this->member)->getJson('/api/dashboard')
            ->assertJsonPath('active_loans.0.awaiting_pickup', true)
            ->assertJsonPath('active_loans.0.extension_blocked_reason', 'awaiting_pickup');

        $this->handOut("/api/admin/loans/{$loan->id}/hand-out");
        $this->actingAs($this->member)
            ->postJson("/api/loans/{$loan->id}/return", ['return_condition' => 'GOOD'])
            ->assertOk();
    }

    public function test_loans_not_handed_out_are_never_marked_overdue(): void
    {
        $waiting = $this->bookGame('Azul');
        $out = $this->bookGame('Catan');
        $this->handOut("/api/admin/loans/{$out->id}/hand-out");

        $this->travelTo(now()->setDate(2027, 1, 15));
        $this->artisan('loans:mark-overdue')->assertSuccessful();

        $this->assertSame('ACTIVE', $waiting->fresh()?->status);
        $this->assertSame('OVERDUE', $out->fresh()?->status);
    }

    public function test_reminder_goes_out_once_the_day_before_with_all_games(): void
    {
        $this->bookGame('Azul');
        $this->bookPackage();

        $this->travelTo(now()->setDate(2026, 12, 8));
        $this->artisan('loans:send-pickup-reminders')->assertSuccessful();
        Notification::assertNotSentTo($this->member, PickupEventReminder::class);

        $this->travelTo(now()->setDate(2026, 12, 9));
        $this->artisan('loans:send-pickup-reminders')->assertSuccessful();
        $this->artisan('loans:send-pickup-reminders')->assertSuccessful();

        Notification::assertSentToTimes($this->member, PickupEventReminder::class, 1);
        Notification::assertSentTo($this->member, PickupEventReminder::class, function (PickupEventReminder $mail) {
            $message = $mail->toMail($this->member);
            $body = implode(' ', array_map('strval', $message->introLines));

            return str_contains($message->subject, 'Vereinslokal')
                && str_contains($body, '18:00–20:00 Uhr')
                && str_contains($body, 'Azul') && str_contains($body, 'Carcassonne') && str_contains($body, 'Dixit');
        });
    }
}
