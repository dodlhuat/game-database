<?php

namespace Tests\Feature\Admin;

use App\Models\Loan;
use App\Models\PackageLoan;
use App\Models\User;
use App\Notifications\UserUnsuspended;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Suspending, unlocking, deleting (softly) and restoring users. */
class UserLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    private function member(array $attrs = []): User
    {
        return User::factory()->member()->create(['email' => 'mia@example.at', 'password' => 'geheim-123', ...$attrs]);
    }

    // ── Suspend / unlock ─────────────────────────────────────────

    public function test_suspending_ends_every_login_at_once(): void
    {
        $user = $this->member();
        $user->createToken('spa');

        $this->actingAs($this->admin)->patchJson("/api/admin/users/{$user->id}/suspend")->assertOk();

        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_unlocking_reactivates_and_tells_the_member(): void
    {
        Notification::fake();
        $user = $this->member(['status' => 'SUSPENDED']);

        $this->actingAs($this->admin)->patchJson("/api/admin/users/{$user->id}/unsuspend")
            ->assertOk()->assertJsonPath('user.status', 'ACTIVE');

        Notification::assertSentTo($user, UserUnsuspended::class);
        $this->postJson('/api/auth/login', ['email' => 'mia@example.at', 'password' => 'geheim-123'])->assertOk();
    }

    public function test_only_suspended_and_not_anonymized_accounts_can_be_unlocked(): void
    {
        $active = $this->member();
        $anonymized = User::factory()->create(['status' => 'SUSPENDED', 'anonymized_at' => now()]);

        $this->actingAs($this->admin)->patchJson("/api/admin/users/{$active->id}/unsuspend")->assertUnprocessable();
        $this->actingAs($this->admin)->patchJson("/api/admin/users/{$anonymized->id}/unsuspend")
            ->assertUnprocessable()->assertJsonPath('reason', 'anonymized');
    }

    // ── Delete ───────────────────────────────────────────────────

    public function test_suspended_or_rejected_users_are_deleted_softly(): void
    {
        $suspended = $this->member(['status' => 'SUSPENDED']);
        $rejected = User::factory()->create(['status' => 'REJECTED']);
        $suspended->createToken('spa');

        $this->actingAs($this->admin)->deleteJson("/api/admin/users/{$suspended->id}")->assertOk();
        $this->actingAs($this->admin)->deleteJson("/api/admin/users/{$rejected->id}")->assertOk();

        $this->assertSoftDeleted($suspended);
        $this->assertSoftDeleted($rejected);
        $this->assertSame(0, $suspended->tokens()->count());
    }

    public function test_active_users_cannot_be_deleted(): void
    {
        $user = $this->member();

        $this->actingAs($this->admin)->deleteJson("/api/admin/users/{$user->id}")
            ->assertUnprocessable()->assertJsonPath('reason', 'not_blocked');
        $this->assertNotSoftDeleted($user);
    }

    public function test_not_while_games_are_still_out(): void
    {
        $withLoan = $this->member(['status' => 'SUSPENDED']);
        Loan::factory()->create(['user_id' => $withLoan->id, 'status' => 'OVERDUE']);
        $withPackage = User::factory()->create(['status' => 'SUSPENDED']);
        PackageLoan::factory()->create(['user_id' => $withPackage->id, 'status' => 'ACTIVE']);

        foreach ([$withLoan, $withPackage] as $user) {
            $this->actingAs($this->admin)->deleteJson("/api/admin/users/{$user->id}")
                ->assertUnprocessable()->assertJsonPath('reason', 'has_loans');
        }
    }

    public function test_a_deleted_user_cannot_log_in_and_keeps_the_email(): void
    {
        $user = $this->member(['status' => 'SUSPENDED']);
        $this->actingAs($this->admin)->deleteJson("/api/admin/users/{$user->id}")->assertOk();
        auth()->forgetGuards();

        $this->postJson('/api/auth/login', ['email' => 'mia@example.at', 'password' => 'geheim-123'])->assertUnauthorized();
        // the address stays taken: restoring is the way back
        $this->postJson('/api/auth/register', ['name' => 'Mia', 'email' => 'mia@example.at'])
            ->assertJsonValidationErrors('email');
    }

    public function test_history_still_names_a_deleted_user(): void
    {
        $user = $this->member(['name' => 'Mia Muster', 'status' => 'SUSPENDED']);
        $loan = Loan::factory()->returned()->create(['user_id' => $user->id]);
        $this->actingAs($this->admin)->deleteJson("/api/admin/users/{$user->id}")->assertOk();

        $this->assertSame('Mia Muster', $loan->fresh()?->user?->name);
    }

    // ── List and restore ─────────────────────────────────────────

    public function test_the_list_shows_deleted_users_only_on_request(): void
    {
        $user = $this->member(['status' => 'SUSPENDED']);
        $this->actingAs($this->admin)->deleteJson("/api/admin/users/{$user->id}")->assertOk();

        $normal = collect($this->actingAs($this->admin)->getJson('/api/admin/users')->json('data'))->pluck('id');
        $deleted = $this->actingAs($this->admin)->getJson('/api/admin/users?deleted=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $user->id);

        $this->assertNotContains($user->id, $normal);
        $this->assertNotNull($deleted->json('data.0.deleted_at'));
    }

    public function test_restoring_brings_the_user_back_still_suspended(): void
    {
        $user = $this->member(['status' => 'SUSPENDED']);
        $this->actingAs($this->admin)->deleteJson("/api/admin/users/{$user->id}")->assertOk();

        $this->actingAs($this->admin)->patchJson("/api/admin/users/{$user->id}/restore")
            ->assertOk()->assertJsonPath('user.status', 'SUSPENDED');

        $this->assertNotSoftDeleted($user);
        $this->actingAs($this->admin)->patchJson("/api/admin/users/{$user->id}/restore")->assertUnprocessable();
    }

    public function test_anonymized_users_cannot_be_restored(): void
    {
        $user = User::factory()->create(['status' => 'SUSPENDED', 'anonymized_at' => now()]);
        $user->delete();

        $this->actingAs($this->admin)->patchJson("/api/admin/users/{$user->id}/restore")
            ->assertUnprocessable()->assertJsonPath('reason', 'anonymized');
    }

    public function test_members_cannot_use_any_of_it(): void
    {
        $member = User::factory()->member()->create();
        $target = User::factory()->create(['status' => 'SUSPENDED']);

        $this->actingAs($member)->patchJson("/api/admin/users/{$target->id}/unsuspend")->assertForbidden();
        $this->actingAs($member)->deleteJson("/api/admin/users/{$target->id}")->assertForbidden();
        $this->actingAs($member)->patchJson("/api/admin/users/{$target->id}/restore")->assertForbidden();
    }

    // ── Grace period ─────────────────────────────────────────────

    public function test_deleted_users_are_anonymized_after_thirty_days(): void
    {
        $old = $this->member(['status' => 'SUSPENDED', 'name' => 'Alt']);
        $recent = User::factory()->create(['status' => 'SUSPENDED', 'name' => 'Neu']);
        $old->delete();
        $this->travel(31)->days();
        $recent->delete();

        $this->artisan('accounts:anonymize-deleted')->assertSuccessful();

        $old = User::withTrashed()->findOrFail($old->id);
        $this->assertNotNull($old->anonymized_at);
        $this->assertSame('Gelöschtes Konto', $old->name);
        $this->assertNull(User::withTrashed()->findOrFail($recent->id)->anonymized_at);
    }
}
