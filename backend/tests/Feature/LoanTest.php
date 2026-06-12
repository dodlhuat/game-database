<?php

namespace Tests\Feature;

use App\Models\Copy;
use App\Models\Loan;
use App\Models\LoanSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        LoanSetting::factory()->create(['loan_cost' => 2, 'deposit_pct_very_good' => 0]);
        $user = $this->makeMember(10);
        $copy = Copy::factory()->create(['condition' => 'NEW']);

        $this->actingAs($user)
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
        LoanSetting::factory()->create(['loan_cost' => 10]);
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
}
