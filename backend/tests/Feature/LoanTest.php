<?php

namespace Tests\Feature;

use App\Models\Copy;
use App\Models\Extension;
use App\Models\Game;
use App\Models\Loan;
use App\Models\LoanSetting;
use App\Models\Reservation;
use App\Models\User;
use App\Services\TokenWallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LoanTest extends TestCase
{
    use RefreshDatabase;

    private function makeMember(int $tokens = 20): User
    {
        return User::factory()->member()->create(['tokens' => $tokens]);
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/loans')->assertUnauthorized();
    }

    public function test_index_returns_user_loans(): void
    {
        $user = $this->makeMember();
        Loan::factory()->count(2)->create(['user_id' => $user->id]);
        Loan::factory()->create(); // another user's loan

        $this->actingAs($user)
            ->getJson('/api/loans')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_store_creates_loan_for_member(): void
    {
        LoanSetting::factory()->create(['deposit_pct_very_good' => 0]);
        $user = $this->makeMember(10);
        $copy = Copy::factory()->create(['condition' => 'NEW']);

        $this->actingAs($user)
            ->postJson('/api/loans', ['copy_id' => $copy->id])
            ->assertCreated()
            ->assertJsonPath('data.status', 'ACTIVE');
    }

    public function test_store_creates_loan_for_admin(): void
    {
        LoanSetting::factory()->create(['deposit_pct_very_good' => 0]);
        $admin = User::factory()->admin()->create(['tokens' => 0]);
        $copy = Copy::factory()->create(['condition' => 'NEW']);

        $this->actingAs($admin)
            ->postJson('/api/loans', ['copy_id' => $copy->id])
            ->assertCreated()
            ->assertJsonPath('data.status', 'ACTIVE');
    }

    public function test_store_fails_for_non_member(): void
    {
        LoanSetting::factory()->create();
        $user = User::factory()->create(['role' => 'USER', 'status' => 'ACTIVE']);
        $copy = Copy::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/loans', ['copy_id' => $copy->id])
            ->assertStatus(403)
            ->assertJsonPath('reason', 'membership_required');
    }

    public function test_store_fails_for_locked_copy(): void
    {
        LoanSetting::factory()->create();
        $user = $this->makeMember(10);
        $copy = Copy::factory()->locked()->create();

        $this->actingAs($user)
            ->postJson('/api/loans', ['copy_id' => $copy->id])
            ->assertStatus(422);
    }

    public function test_store_fails_when_copy_already_borrowed(): void
    {
        LoanSetting::factory()->create();
        $user = $this->makeMember(10);
        $copy = Copy::factory()->create();
        Loan::factory()->create(['copy_id' => $copy->id, 'status' => 'ACTIVE']);

        $this->actingAs($user)
            ->postJson('/api/loans', ['copy_id' => $copy->id])
            ->assertStatus(422);
    }

    public function test_store_fails_with_insufficient_tokens(): void
    {
        LoanSetting::factory()->create(['loan_cost_medium' => 10]);
        $user = $this->makeMember(2);
        $copy = Copy::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/loans', ['copy_id' => $copy->id])
            ->assertStatus(402)
            ->assertJsonPath('reason', 'insufficient_tokens');
    }

    public function test_show_returns_own_loan(): void
    {
        $user = $this->makeMember();
        $loan = Loan::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->getJson("/api/loans/{$loan->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $loan->id);
    }

    public function test_show_denies_other_users_loan(): void
    {
        $user = $this->makeMember();
        $loan = Loan::factory()->create(); // other user

        $this->actingAs($user)
            ->getJson("/api/loans/{$loan->id}")
            ->assertForbidden();
    }

    public function test_return_marks_loan_as_returned(): void
    {
        $user = $this->makeMember();
        $copy = Copy::factory()->create();
        $loan = Loan::factory()->create([
            'user_id' => $user->id,
            'copy_id' => $copy->id,
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($user)
            ->postJson("/api/loans/{$loan->id}/return", [
                'return_condition' => 'GOOD',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'RETURNED');
    }

    public function test_return_fails_for_already_returned_loan(): void
    {
        $user = $this->makeMember();
        $loan = Loan::factory()->returned()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson("/api/loans/{$loan->id}/return", [
                'return_condition' => 'GOOD',
            ])
            ->assertStatus(422);
    }

    /** @return array<string, array{string, int}> */
    public static function sizeCategories(): array
    {
        return [
            'klein' => ['SMALL', 3],
            'mittel' => ['MEDIUM', 6],
            'groß' => ['LARGE', 10],
        ];
    }

    #[DataProvider('sizeCategories')]
    public function test_loan_price_follows_size_category(string $category, int $cost): void
    {
        LoanSetting::factory()->create();
        $user = $this->makeMember(0);
        app(TokenWallet::class)->grantTokens($user, 20, 'PURCHASE', 50);
        $game = Game::factory()->create(['size_category' => $category]);
        $copy = Copy::factory()->create(['game_id' => $game->id]);

        $this->actingAs($user)
            ->postJson('/api/loans', ['copy_id' => $copy->id])
            ->assertCreated();

        $this->assertSame(20 - $cost, $user->fresh()->tokens);
        $this->assertDatabaseHas('token_transactions', ['user_id' => $user->id, 'type' => 'BORROW', 'amount' => -$cost]);
    }

    public function test_first_extension_happens_immediately_and_is_free(): void
    {
        LoanSetting::factory()->create();
        $user = $this->makeMember(0);
        $loan = Loan::factory()->create(['user_id' => $user->id, 'status' => 'ACTIVE']);
        $newDue = $loan->due_date->copy()->addWeek()->toDateString();

        $this->extend($user, $loan)
            ->assertCreated()
            ->assertJsonPath('data.status', 'APPROVED')
            ->assertJsonPath('data.tokens_charged', 0);

        $loan->refresh();
        $this->assertSame('EXTENDED', $loan->status);
        $this->assertSame($newDue, $loan->due_date->toDateString());
        $this->assertDatabaseMissing('token_transactions', ['type' => 'EXTENSION']);
    }

    public function test_second_extension_spends_bonus_tokens_first(): void
    {
        LoanSetting::factory()->create();
        $user = $this->makeMember(0);
        app(TokenWallet::class)->grantBonus($user, 5, 'MEMBERSHIP', now()->addYear());
        $loan = $this->extendedLoan($user);

        $this->extend($user, $loan)
            ->assertCreated()
            ->assertJsonPath('data.tokens_charged', 2);

        $this->assertSame(3, $user->fresh()->bonus_tokens);
        $this->assertDatabaseHas('token_transactions', ['user_id' => $user->id, 'type' => 'EXTENSION', 'amount' => -2]);
    }

    public function test_second_extension_fails_without_tokens_and_changes_nothing(): void
    {
        LoanSetting::factory()->create();
        $user = $this->makeMember(1);
        $loan = $this->extendedLoan($user);
        $due = $loan->due_date->toDateString();

        $this->extend($user, $loan)
            ->assertStatus(402)
            ->assertJsonPath('reason', 'insufficient_tokens');

        $this->assertSame($due, $loan->fresh()->due_date->toDateString());
        $this->assertSame(1, $user->fresh()->tokens);
        $this->assertDatabaseCount('extensions', 1);
    }

    public function test_extension_cost_cannot_use_blocked_deposit_tokens(): void
    {
        LoanSetting::factory()->create();
        $user = $this->makeMember(2);
        $user->update(['tokens_blocked' => 1]);

        $this->extend($user, $this->extendedLoan($user))->assertStatus(402);
    }

    public function test_extension_is_blocked_when_reserved_by_someone_else(): void
    {
        LoanSetting::factory()->create();
        $user = $this->makeMember(5);
        $loan = Loan::factory()->create(['user_id' => $user->id, 'status' => 'ACTIVE']);
        $due = $loan->due_date->toDateString();
        Reservation::factory()->create(['game_id' => $loan->copy->game_id]);

        $this->extend($user, $loan)
            ->assertStatus(409)
            ->assertJsonPath('reason', 'game_reserved');

        $this->assertDatabaseCount('extensions', 0);
        $this->assertSame($due, $loan->fresh()->due_date->toDateString());
    }

    public function test_own_reservation_does_not_block_extension(): void
    {
        LoanSetting::factory()->create();
        $user = $this->makeMember(5);
        $loan = Loan::factory()->create(['user_id' => $user->id, 'status' => 'ACTIVE']);
        Reservation::factory()->create(['game_id' => $loan->copy->game_id, 'user_id' => $user->id]);

        $this->extend($user, $loan)->assertCreated();
    }

    public function test_third_extension_is_refused_with_a_message(): void
    {
        LoanSetting::factory()->create();
        $user = $this->makeMember(5);
        $loan = Loan::factory()->create(['user_id' => $user->id, 'status' => 'ACTIVE']);

        $this->extend($user, $loan)->assertCreated();
        $this->extend($user, $loan->fresh())->assertCreated();

        $this->extend($user, $loan->fresh())
            ->assertStatus(422)
            ->assertJsonPath('reason', 'max_extensions_reached')
            ->assertJsonPath('message', 'Dieses Spiel wurde bereits 2-mal verlängert. Eine weitere Verlängerung ist erst möglich, wenn du es zurückgibst und neu ausborgst.');

        $this->assertSame(3, $user->fresh()->tokens);
    }

    public function test_extension_date_is_limited_relative_to_the_due_date(): void
    {
        LoanSetting::factory()->create();
        $user = $this->makeMember(5);
        $loan = Loan::factory()->create(['user_id' => $user->id, 'status' => 'ACTIVE']);

        $this->extend($user, $loan, $loan->due_date->toDateString())->assertStatus(422);
        $this->extend($user, $loan, $loan->due_date->copy()->addDays(15)->toDateString())->assertStatus(422);
        $this->extend($user, $loan, $loan->due_date->copy()->addDays(14)->toDateString())->assertCreated();
    }

    public function test_max_extensions_message_follows_the_setting(): void
    {
        LoanSetting::factory()->create(['max_extensions' => 1]);
        $user = $this->makeMember(5);
        $loan = Loan::factory()->create(['user_id' => $user->id, 'status' => 'ACTIVE']);

        $this->extend($user, $loan)->assertCreated();

        $this->extend($user, $loan->fresh())
            ->assertStatus(422)
            ->assertJsonPath('reason', 'max_extensions_reached')
            ->assertJsonPath('message', fn (string $message) => str_contains($message, 'bereits 1-mal verlängert'));
    }

    public function test_extension_length_follows_the_loan_duration_setting(): void
    {
        LoanSetting::factory()->create(['loan_duration_weeks' => 3]);
        $user = $this->makeMember(5);
        $loan = Loan::factory()->create(['user_id' => $user->id, 'status' => 'ACTIVE']);

        $this->extend($user, $loan, $loan->due_date->copy()->addDays(22)->toDateString())
            ->assertStatus(422)
            ->assertJsonPath('errors.requested_due_date.0', 'Eine Verlängerung ist um maximal 3 Wochen möglich.');
        $this->extend($user, $loan, $loan->due_date->copy()->addDays(21)->toDateString())->assertCreated();
    }

    public function test_overdue_loan_is_extended_from_today(): void
    {
        LoanSetting::factory()->create();
        $user = $this->makeMember(5);
        $loan = Loan::factory()->create([
            'user_id' => $user->id,
            'status' => 'OVERDUE',
            'due_date' => now()->subDays(3)->toDateString(),
        ]);

        $this->extend($user, $loan, now()->addWeek()->toDateString())->assertCreated();

        $this->assertSame('EXTENDED', $loan->fresh()->status);
    }

    public function test_cannot_extend_foreign_loan(): void
    {
        LoanSetting::factory()->create();
        $loan = Loan::factory()->create(['status' => 'ACTIVE']);

        $this->extend($this->makeMember(5), $loan)->assertForbidden();
    }

    public function test_index_exposes_extension_state(): void
    {
        LoanSetting::factory()->create();
        $user = $this->makeMember(5);
        $free = Loan::factory()->create(['user_id' => $user->id, 'status' => 'ACTIVE']);
        $paid = Loan::factory()->create(['user_id' => $user->id, 'status' => 'EXTENDED']);
        Extension::factory()->approved()->create(['loan_id' => $paid->id]);
        $reserved = Loan::factory()->create(['user_id' => $user->id, 'status' => 'ACTIVE']);
        Reservation::factory()->create(['game_id' => $reserved->copy->game_id]);

        $loans = collect($this->actingAs($user)->getJson('/api/loans')->assertOk()->json('data'))->keyBy('id');

        $this->assertTrue($loans[$free->id]['can_extend']);
        $this->assertSame(0, $loans[$free->id]['next_extension_cost']);
        $this->assertTrue($loans[$paid->id]['can_extend']);
        $this->assertSame(2, $loans[$paid->id]['next_extension_cost']);
        $this->assertFalse($loans[$reserved->id]['can_extend']);
        $this->assertSame('reserved', $loans[$reserved->id]['extension_blocked_reason']);

        $this->assertSame(0, $loans[$free->id]['extensions_used']);
        $this->assertSame(1, $loans[$paid->id]['extensions_used']);
        $this->assertSame(2, $loans[$paid->id]['max_extensions']);
        $this->assertSame($free->due_date->copy()->addDay()->toDateString(), $loans[$free->id]['extension_earliest_due_date']);
        $this->assertSame($free->due_date->copy()->addWeeks(2)->toDateString(), $loans[$free->id]['extension_latest_due_date']);
    }

    private function extendedLoan(User $user): Loan
    {
        $loan = Loan::factory()->create(['user_id' => $user->id, 'status' => 'EXTENDED']);
        Extension::factory()->approved()->create(['loan_id' => $loan->id]);

        return $loan;
    }

    private function extend(User $user, Loan $loan, ?string $date = null): TestResponse
    {
        return $this->actingAs($user)->postJson("/api/loans/{$loan->id}/extend", [
            'requested_due_date' => $date ?? $loan->due_date->copy()->addWeek()->toDateString(),
        ]);
    }
}
