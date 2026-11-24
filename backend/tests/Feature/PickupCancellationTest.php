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
use App\Models\Reservation;
use App\Models\TokenLot;
use App\Models\User;
use App\Notifications\LoanCancelledNoShow;
use App\Notifications\PickupEventMoved;
use App\Notifications\ReservationAvailable;
use App\Services\TokenWallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** No-shows are cancelled and refunded exactly; moved events take their bookings along. */
class PickupCancellationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $member;

    private Event $event;

    private TokenWallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->travelTo(now()->setDate(2026, 12, 1)->setTime(10, 0));
        LoanSetting::factory()->create([
            'deposit_pct_very_good' => 50,
            'loan_cost_small' => 3,
            'loan_duration_weeks' => 2,
            'pickup_event_lead_hours' => 24,
        ]);
        $this->wallet = app(TokenWallet::class);
        $this->admin = User::factory()->admin()->create();
        $this->member = User::factory()->member()->create(['tokens' => 0]);
        $location = PickupLocation::factory()->create(['name' => 'Vereinslokal', 'is_personal' => true]);
        $this->event = Event::factory()->pickupAt($location, 10)->create([
            'title' => 'Abholabend',
            'date' => '2026-12-10',
            'time' => '18:00',
            'end_time' => '20:00',
        ]);
    }

    private function bookGame(string $title = 'Azul'): Loan
    {
        $game = Game::factory()->create(['title' => $title, 'size_category' => 'SMALL', 'deposit_tokens' => 4]);
        $copy = Copy::factory()->create(['game_id' => $game->id, 'condition' => 'NEW']);
        $this->actingAs($this->member)
            ->postJson('/api/loans', ['copy_id' => $copy->id, 'pickup_event_id' => $this->event->id])
            ->assertCreated();

        return Loan::where('copy_id', $copy->id)->firstOrFail();
    }

    public function test_spend_receipt_records_bonus_and_normal_tokens(): void
    {
        $this->wallet->grantBonus($this->member, 2, 'TEST', now()->addMonths(6));
        $this->wallet->grantTokens($this->member, 10, 'TEST', 50);

        $loan = $this->bookGame();

        $receipt = collect($loan->spend_receipt);
        $this->assertSame(3, $receipt->sum('amount'));
        $bonusLot = TokenLot::where('kind', TokenLot::KIND_BONUS)->firstOrFail();
        $this->assertSame(2, $receipt->firstWhere('lot_id', $bonusLot->id)['amount']);
    }

    public function test_no_show_refunds_bonus_as_bonus_and_releases_the_deposit(): void
    {
        $this->wallet->grantBonus($this->member, 2, 'TEST', now()->addMonths(6));
        $this->wallet->grantTokens($this->member, 10, 'TEST', 50);
        $loan = $this->bookGame();
        $this->member->refresh();
        $this->assertSame(0, $this->member->bonus_tokens);
        $this->assertSame(9, $this->member->tokens);
        $blocked = $this->member->tokens_blocked;
        $this->assertGreaterThan(0, $blocked);

        $this->actingAs($this->admin)->postJson("/api/admin/loans/{$loan->id}/cancel-pickup")->assertOk();

        $this->member->refresh();
        $this->assertSame(2, $this->member->bonus_tokens, 'bonus comes back as bonus');
        $this->assertSame(10, $this->member->tokens);
        $this->assertSame(0, $this->member->tokens_blocked);
        $this->assertSame('CANCELLED', $loan->fresh()?->status);
        $this->assertSame(0, $loan->copy->fresh()?->borrow_count);
        Notification::assertSentTo($this->member, LoanCancelledNoShow::class);
    }

    public function test_bonus_that_expired_in_the_meantime_is_not_restored(): void
    {
        $this->wallet->grantBonus($this->member, 3, 'TEST', now()->addDays(3));
        $this->wallet->grantTokens($this->member, 10, 'TEST', 50); // covers the deposit
        $loan = $this->bookGame();

        $this->travelTo(now()->addDays(5));
        $this->wallet->expireBonus();
        $this->actingAs($this->admin)->postJson("/api/admin/loans/{$loan->id}/cancel-pickup")->assertOk();

        $this->assertSame(0, $this->member->fresh()?->bonus_tokens);
    }

    public function test_cancelled_booking_frees_the_place_and_the_copy(): void
    {
        $this->wallet->grantTokens($this->member, 20, 'TEST', 50);
        $loan = $this->bookGame();
        $waiting = User::factory()->member()->create();
        Reservation::create(['user_id' => $waiting->id, 'game_id' => $loan->copy->game_id, 'position' => 1]);
        $this->assertSame(1, $this->event->bookingsCount());

        $this->actingAs($this->admin)->postJson("/api/admin/loans/{$loan->id}/cancel-pickup")->assertOk();

        $this->assertSame(0, $this->event->bookingsCount());
        $this->assertFalse($loan->copy->activeLoans()->exists());
        Notification::assertSentTo($waiting, ReservationAvailable::class);
    }

    public function test_package_no_show_cancels_every_game_and_refunds_once(): void
    {
        $this->wallet->grantTokens($this->member, 20, 'TEST', 50);
        $package = Package::factory()->create(['is_active' => true]);
        foreach (['Carcassonne', 'Dixit'] as $title) {
            $game = Game::factory()->create(['title' => $title, 'deposit_tokens' => 0]);
            Copy::factory()->create(['game_id' => $game->id, 'condition' => 'NEW']);
            $package->games()->attach($game->id);
        }
        $this->actingAs($this->member)
            ->postJson('/api/package-loans', ['package_id' => $package->id, 'pickup_event_id' => $this->event->id])
            ->assertCreated();
        $packageLoan = PackageLoan::firstOrFail();
        $before = $this->member->fresh()?->tokens;

        $this->actingAs($this->admin)->postJson("/api/admin/package-loans/{$packageLoan->id}/cancel-pickup")->assertOk();

        $this->assertSame('CANCELLED', $packageLoan->fresh()?->status);
        $this->assertSame(0, $packageLoan->loans()->where('status', '!=', 'CANCELLED')->count());
        $this->assertSame($before + LoanSetting::instance()->package_loan_cost, $this->member->fresh()?->tokens);
    }

    public function test_handed_out_or_regular_loans_cannot_be_cancelled(): void
    {
        $this->wallet->grantTokens($this->member, 20, 'TEST', 50);
        $loan = $this->bookGame();
        $this->actingAs($this->admin)->patchJson("/api/admin/loans/{$loan->id}/hand-out", ['handed_out' => true]);

        $this->actingAs($this->admin)->postJson("/api/admin/loans/{$loan->id}/cancel-pickup")->assertUnprocessable();

        $regular = Loan::factory()->create();
        $this->actingAs($this->admin)->postJson("/api/admin/loans/{$regular->id}/cancel-pickup")->assertUnprocessable();
    }

    public function test_moving_the_event_moves_bookings_and_tells_members(): void
    {
        $this->wallet->grantTokens($this->member, 20, 'TEST', 50);
        $loan = $this->bookGame();
        $loan->update(['pickup_reminder_sent_at' => now()]);

        $this->actingAs($this->admin)
            ->putJson("/api/admin/events/{$this->event->id}", [
                'title' => 'Abholabend',
                'date' => '2026-12-12',
                'time' => '17:00',
                'end_time' => '19:00',
                'pickup_location_id' => $this->event->pickup_location_id,
            ])
            ->assertOk();

        $loan->refresh();
        $this->assertSame('2026-12-12', $loan->start_date?->toDateString());
        $this->assertSame('2026-12-26', $loan->due_date->toDateString());
        $this->assertNull($loan->pickup_reminder_sent_at);
        Notification::assertSentTo($this->member, PickupEventMoved::class, function (PickupEventMoved $mail) {
            $body = implode(' ', array_map('strval', $mail->toMail($this->member)->introLines));

            return str_contains($body, '12.12.2026, 17:00–19:00 Uhr') && str_contains($body, '10.12.2026, 18:00–20:00 Uhr');
        });
    }

    public function test_event_cannot_move_once_games_were_handed_out(): void
    {
        $this->wallet->grantTokens($this->member, 20, 'TEST', 50);
        $loan = $this->bookGame();
        $this->actingAs($this->admin)->patchJson("/api/admin/loans/{$loan->id}/hand-out", ['handed_out' => true]);

        $this->actingAs($this->admin)
            ->putJson("/api/admin/events/{$this->event->id}", [
                'title' => 'Abholabend',
                'date' => '2026-12-12',
                'time' => '18:00',
                'end_time' => '20:00',
                'pickup_location_id' => $this->event->pickup_location_id,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('reason', 'handed_out');
    }

    public function test_event_with_only_cancelled_bookings_can_be_deleted(): void
    {
        $this->wallet->grantTokens($this->member, 20, 'TEST', 50);
        $loan = $this->bookGame();

        $this->actingAs($this->admin)->deleteJson("/api/admin/events/{$this->event->id}")->assertUnprocessable();

        $this->actingAs($this->admin)->postJson("/api/admin/loans/{$loan->id}/cancel-pickup")->assertOk();
        $this->actingAs($this->admin)->deleteJson("/api/admin/events/{$this->event->id}")->assertNoContent();
        $this->assertNull($loan->fresh()?->pickup_event_id);
    }
}
