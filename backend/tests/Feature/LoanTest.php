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

    public function test_extend_creates_extension_request(): void
    {
        LoanSetting::factory()->create(['max_extensions' => 2]);
        $user = $this->makeMember(5);
        $loan = Loan::factory()->create(['user_id' => $user->id, 'status' => 'ACTIVE']);

        $this->actingAs($user)
            ->postJson("/api/loans/{$loan->id}/extend", [
                'requested_due_date' => now()->addWeek()->toDateString(),
            ])
            ->assertCreated();
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

    public function test_first_extension_is_free_and_needs_no_tokens(): void
    {
        LoanSetting::factory()->create();
        $user = $this->makeMember(0);
        $loan = Loan::factory()->create(['user_id' => $user->id, 'status' => 'ACTIVE']);

        $this->extend($user, $loan)->assertCreated();

        $this->assertSame(0, $user->fresh()->tokens);
    }

    public function test_second_extension_needs_tokens_and_works_on_extended_loan(): void
    {
        LoanSetting::factory()->create();
        $user = $this->makeMember(1);
        $loan = Loan::factory()->create(['user_id' => $user->id, 'status' => 'EXTENDED']);
        Extension::factory()->approved()->create(['loan_id' => $loan->id]);

        $this->extend($user, $loan)
            ->assertStatus(402)
            ->assertJsonPath('reason', 'insufficient_tokens');

        $user->update(['tokens' => 2]);

        $this->extend($user, $loan)->assertCreated();
        $this->assertSame(2, $user->fresh()->tokens, 'Abgebucht wird erst bei der Genehmigung.');
    }

    public function test_extension_cost_cannot_use_blocked_deposit_tokens(): void
    {
        LoanSetting::factory()->create();
        $user = $this->makeMember(2);
        $user->update(['tokens_blocked' => 1]);
        $loan = Loan::factory()->create(['user_id' => $user->id, 'status' => 'EXTENDED']);
        Extension::factory()->approved()->create(['loan_id' => $loan->id]);

        $this->extend($user, $loan)->assertStatus(402);
    }

    public function test_extension_is_blocked_when_reserved_by_someone_else(): void
    {
        LoanSetting::factory()->create();
        $user = $this->makeMember(5);
        $loan = Loan::factory()->create(['user_id' => $user->id, 'status' => 'ACTIVE']);
        Reservation::factory()->create(['game_id' => $loan->copy->game_id]);

        $this->extend($user, $loan)
            ->assertStatus(409)
            ->assertJsonPath('reason', 'game_reserved');

        $this->assertDatabaseCount('extensions', 0);
    }

    public function test_own_reservation_does_not_block_extension(): void
    {
        LoanSetting::factory()->create();
        $user = $this->makeMember(5);
        $loan = Loan::factory()->create(['user_id' => $user->id, 'status' => 'ACTIVE']);
        Reservation::factory()->create(['game_id' => $loan->copy->game_id, 'user_id' => $user->id]);

        $this->extend($user, $loan)->assertCreated();
    }

    public function test_extension_is_blocked_at_maximum(): void
    {
        LoanSetting::factory()->create(['max_extensions' => 2]);
        $user = $this->makeMember(5);
        $loan = Loan::factory()->create(['user_id' => $user->id, 'status' => 'EXTENDED']);
        Extension::factory()->approved()->count(2)->create(['loan_id' => $loan->id]);

        $this->extend($user, $loan)->assertStatus(422);
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
    }

    private function extend(User $user, Loan $loan): TestResponse
    {
        return $this->actingAs($user)->postJson("/api/loans/{$loan->id}/extend", [
            'requested_due_date' => now()->addWeek()->toDateString(),
        ]);
    }
}
