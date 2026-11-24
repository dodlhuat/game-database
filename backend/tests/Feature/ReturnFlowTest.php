<?php

namespace Tests\Feature;

use App\Models\Copy;
use App\Models\Game;
use App\Models\Loan;
use App\Models\PackageLoan;
use App\Models\PickupLocation;
use App\Models\TermsVersion;
use App\Models\User;
use App\Notifications\LoanReturnConfirmation;
use App\Notifications\LoanReturnedAdmin;
use App\Notifications\PickupDigestAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** A member confirms the return at the pickup location; admins learn what to collect where. */
class ReturnFlowTest extends TestCase
{
    use RefreshDatabase;

    private function openLoan(User $user, PickupLocation $location, array $attributes = []): Loan
    {
        return Loan::factory()->create([
            'user_id' => $user->id,
            'pickup_location_id' => $location->id,
            ...$attributes,
        ]);
    }

    public function test_member_return_mails_receipt_to_member_and_heads_up_to_admins(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $member = User::factory()->member()->create();
        $loan = $this->openLoan($member, PickupLocation::factory()->create());

        $this->actingAs($member)
            ->postJson("/api/loans/{$loan->id}/return", ['return_condition' => 'WORN'])
            ->assertOk()
            ->assertJsonPath('data.status', 'RETURNED');

        $this->assertSame('REVIEW', $loan->copy->fresh()->condition);
        $this->assertSame('WORN', $loan->fresh()->return_condition);
        Notification::assertSentTo($member, LoanReturnConfirmation::class);
        Notification::assertSentTo($admin, LoanReturnedAdmin::class);
        Notification::assertNotSentTo($member, LoanReturnedAdmin::class);
    }

    public function test_admin_booking_a_return_only_mails_the_member(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $member = User::factory()->member()->create();
        $loan = $this->openLoan($member, PickupLocation::factory()->create());

        $this->actingAs($admin)
            ->postJson("/api/admin/loans/{$loan->id}/return", ['return_condition' => 'GOOD'])
            ->assertOk();

        Notification::assertSentTo($member, LoanReturnConfirmation::class);
        Notification::assertNothingSentTo($admin);
    }

    public function test_return_mails_name_the_game_and_the_location(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->member()->create(['name' => 'Mia Muster']);
        $location = PickupLocation::factory()->create(['name' => 'Café Kranz']);
        $game = Game::factory()->create(['title' => 'Azul']);
        $loan = $this->openLoan($member, $location, ['copy_id' => Copy::factory()->create(['game_id' => $game->id])->id]);
        $loan->update(['status' => 'RETURNED', 'returned_at' => now(), 'return_condition' => 'DAMAGED']);
        $loan->load(['copy.game', 'user', 'pickupLocation']);

        $receipt = (new LoanReturnConfirmation($loan))->toMail($member);
        $this->assertStringContainsString('Azul', $receipt->subject);
        $this->assertStringContainsString('Café Kranz', implode(' ', array_map('strval', $receipt->introLines)));

        $headsUp = (new LoanReturnedAdmin($loan))->toMail($admin);
        $this->assertStringContainsString('Café Kranz', $headsUp->subject);
        $body = implode(' ', array_map('strval', $headsUp->introLines));
        $this->assertStringContainsString('Mia Muster', $body);
        $this->assertStringContainsString('Beschädigt', $body);
    }

    public function test_single_game_of_a_package_cannot_be_returned_alone(): void
    {
        Notification::fake();
        $member = User::factory()->member()->create();
        $location = PickupLocation::factory()->create();
        $packageLoan = PackageLoan::factory()->create(['user_id' => $member->id, 'pickup_location_id' => $location->id]);
        $loan = $this->openLoan($member, $location, ['package_loan_id' => $packageLoan->id]);

        $this->actingAs($member)
            ->postJson("/api/loans/{$loan->id}/return", ['return_condition' => 'GOOD'])
            ->assertUnprocessable();

        $this->assertSame('ACTIVE', $loan->fresh()->status);
        Notification::assertNothingSent();
    }

    public function test_package_return_closes_every_game_with_the_reported_condition(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $member = User::factory()->member()->create();
        $location = PickupLocation::factory()->create();
        $packageLoan = PackageLoan::factory()->create(['user_id' => $member->id, 'pickup_location_id' => $location->id]);
        $loans = collect([1, 2])->map(fn () => $this->openLoan($member, $location, ['package_loan_id' => $packageLoan->id]));

        $this->actingAs($member)
            ->postJson("/api/package-loans/{$packageLoan->id}/return", ['return_condition' => 'WORN'])
            ->assertOk()
            ->assertJsonPath('data.status', 'RETURNED');

        foreach ($loans as $loan) {
            $loan->refresh();
            $this->assertSame('RETURNED', $loan->status);
            $this->assertSame('WORN', $loan->return_condition);
            $this->assertSame('REVIEW', $loan->copy->condition);
        }
        Notification::assertSentToTimes($member, LoanReturnConfirmation::class, 1);
        Notification::assertSentTo($admin, LoanReturnedAdmin::class);
    }

    public function test_package_return_requires_a_condition(): void
    {
        $member = User::factory()->member()->create();
        $packageLoan = PackageLoan::factory()->create(['user_id' => $member->id]);

        $this->actingAs($member)
            ->postJson("/api/package-loans/{$packageLoan->id}/return")
            ->assertUnprocessable()
            ->assertJsonValidationErrors('return_condition');
    }

    public function test_review_queue_shows_where_each_copy_was_returned(): void
    {
        $admin = User::factory()->admin()->create();
        $location = PickupLocation::factory()->create(['name' => 'Bücherei']);
        $copy = Copy::factory()->create(['condition' => 'REVIEW']);
        Loan::factory()->returned()->create(['copy_id' => $copy->id, 'pickup_location_id' => $location->id]);

        $this->actingAs($admin)
            ->getJson('/api/admin/copies?condition=REVIEW')
            ->assertOk()
            ->assertJsonPath('data.0.last_return.pickup_location.name', 'Bücherei');

        $this->actingAs($admin)
            ->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('copies.to_review_by_location.0', ['location' => 'Bücherei', 'count' => 1]);
    }

    public function test_pickup_digest_lists_waiting_copies_by_location(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $cafe = PickupLocation::factory()->create(['name' => 'Café Kranz']);
        $library = PickupLocation::factory()->create(['name' => 'Bücherei']);
        foreach ([[$cafe, 'Azul'], [$cafe, 'Carcassonne'], [$library, 'Catan']] as [$location, $title]) {
            $copy = Copy::factory()->create([
                'condition' => 'REVIEW',
                'game_id' => Game::factory()->create(['title' => $title])->id,
            ]);
            Loan::factory()->returned()->create(['copy_id' => $copy->id, 'pickup_location_id' => $location->id]);
        }

        $this->artisan('loans:send-pickup-digest')->assertSuccessful();

        Notification::assertSentTo($admin, PickupDigestAdmin::class, function (PickupDigestAdmin $mail) use ($admin) {
            $message = $mail->toMail($admin);
            $body = implode(' ', array_map('strval', $message->introLines));

            return str_contains($message->subject, '3')
                && preg_match('/Bücherei.*Catan.*Café Kranz.*Azul.*Carcassonne/s', $body) === 1;
        });
    }

    public function test_pickup_digest_stays_quiet_when_nothing_waits(): void
    {
        Notification::fake();
        User::factory()->admin()->create();
        Copy::factory()->create(['condition' => 'GOOD']);

        $this->artisan('loans:send-pickup-digest')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_terms_require_return_at_pickup_location_and_confirmation(): void
    {
        $content = TermsVersion::where('version', '1.3')->value('content');

        $this->assertStringContainsString('an dem Abholort zurückzugeben', $content);
        $this->assertStringContainsString('im Konto zu bestätigen', $content);
        $this->assertStringContainsString('auch nach Ablauf möglich', $content);
    }
}
