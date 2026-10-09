<?php

namespace Tests\Feature\Admin;

use App\Models\TokenTransaction;
use App\Models\User;
use App\Notifications\UserRejected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_index_requires_admin(): void
    {
        $user = User::factory()->member()->create();
        $this->actingAs($user)->getJson('/api/admin/users')->assertForbidden();
    }

    public function test_index_returns_paginated_users(): void
    {
        User::factory()->count(3)->create();

        $this->actingAs($this->admin())
            ->getJson('/api/admin/users')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_show_returns_user(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->admin())
            ->getJson("/api/admin/users/{$target->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $target->id);
    }

    public function test_token_transactions_returns_paginated_meta(): void
    {
        $target = User::factory()->create();
        TokenTransaction::factory()->count(2)->create(['user_id' => $target->id]);

        $this->actingAs($this->admin())
            ->getJson("/api/admin/users/{$target->id}/token-transactions")
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.last_page', 1)
            ->assertJsonCount(2, 'data');
    }

    public function test_store_creates_user(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/api/admin/users', [
                'name' => 'New User',
                'email' => 'newuser@example.com',
                'password' => 'password123',
                'role' => 'USER',
                'status' => 'PENDING',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
    }

    public function test_update_modifies_user(): void
    {
        $target = User::factory()->create();

        $this->actingAs($this->admin())
            ->putJson("/api/admin/users/{$target->id}", [
                'name' => 'Updated Name',
                'email' => $target->email,
                'role' => 'MEMBER',
            ])
            ->assertOk()
            ->assertJsonPath('data.role', 'MEMBER');
    }

    public function test_approve_activates_user(): void
    {
        Notification::fake();
        $target = User::factory()->pending()->create();

        $this->actingAs($this->admin())
            ->patchJson("/api/admin/users/{$target->id}/approve")
            ->assertOk();

        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'ACTIVE']);
    }

    public function test_reject_sets_rejected_status(): void
    {
        Notification::fake();
        $target = User::factory()->pending()->create();

        $this->actingAs($this->admin())
            ->patchJson("/api/admin/users/{$target->id}/reject", ['reason' => 'Not eligible'])
            ->assertOk();

        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'REJECTED']);
    }

    public function test_suspend_sets_suspended_status(): void
    {
        $target = User::factory()->create(['status' => 'ACTIVE']);

        $this->actingAs($this->admin())
            ->patchJson("/api/admin/users/{$target->id}/suspend")
            ->assertOk();

        $this->assertDatabaseHas('users', ['id' => $target->id, 'status' => 'SUSPENDED']);
    }

    public function test_reject_without_reason_sends_mail_without_reason_line(): void
    {
        Notification::fake();
        $target = User::factory()->pending()->create();

        $this->actingAs($this->admin())
            ->patchJson("/api/admin/users/{$target->id}/reject")
            ->assertOk();

        Notification::assertSentTo($target, UserRejected::class, function (UserRejected $notification) use ($target): bool {
            return ! collect($notification->toMail($target)->introLines)
                ->contains(fn ($line) => str_contains((string) $line, 'Grund'));
        });
    }

    public function test_reject_with_reason_includes_reason_in_mail(): void
    {
        Notification::fake();
        $target = User::factory()->pending()->create();

        $this->actingAs($this->admin())
            ->patchJson("/api/admin/users/{$target->id}/reject", ['reason' => 'Not eligible'])
            ->assertOk();

        Notification::assertSentTo($target, UserRejected::class, function (UserRejected $notification) use ($target): bool {
            return collect($notification->toMail($target)->introLines)
                ->contains(fn ($line) => str_contains((string) $line, 'Not eligible'));
        });
    }

    public function test_index_search_matches_name_or_email_and_empty_search_returns_all(): void
    {
        // Distinctive values, because the factory admin gets a random fake name and email
        User::factory()->create(['name' => 'Zora Suchtest', 'email' => 'zora@alpha-suchtest.test']);
        User::factory()->create(['name' => 'Yves Beispiel', 'email' => 'yves@beta-suchtest.test']);
        $admin = $this->admin();

        $emails = fn (string $query): array => collect(
            $this->actingAs($admin)->getJson('/api/admin/users?'.$query)->assertOk()->json('data')
        )->pluck('email')->all();

        $byName = $emails('search=Suchtest');
        $this->assertContains('zora@alpha-suchtest.test', $byName);
        $this->assertContains('yves@beta-suchtest.test', $byName);

        $byEmail = $emails('search=beta-suchtest');
        $this->assertContains('yves@beta-suchtest.test', $byEmail);
        $this->assertNotContains('zora@alpha-suchtest.test', $byEmail);

        $this->assertSame([], $emails('search=gibt-es-nicht-xyz'));

        $all = $emails('search=');
        $this->assertContains('zora@alpha-suchtest.test', $all);
        $this->assertContains('yves@beta-suchtest.test', $all);
        $this->assertContains($admin->email, $all);
    }
}
