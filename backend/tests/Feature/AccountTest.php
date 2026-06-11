<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_requires_auth(): void
    {
        $this->patchJson('/api/account', ['name' => 'New Name'])->assertUnauthorized();
    }

    public function test_update_name(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/api/account', ['name' => 'Updated Name'])
            ->assertOk()
            ->assertJsonPath('user.name', 'Updated Name');
    }

    public function test_update_password_with_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('OldPass1')]);

        $this->actingAs($user)
            ->patchJson('/api/account', [
                'current_password'          => 'OldPass1',
                'new_password'              => 'NewPass1',
                'new_password_confirmation' => 'NewPass1',
            ])
            ->assertOk();

        $this->assertTrue(Hash::check('NewPass1', $user->fresh()->password));
    }

    public function test_update_password_fails_with_wrong_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('OldPass1')]);

        $this->actingAs($user)
            ->patchJson('/api/account', [
                'current_password'          => 'WrongPass',
                'new_password'              => 'NewPass1',
                'new_password_confirmation' => 'NewPass1',
            ])
            ->assertStatus(422);
    }

    public function test_update_email_to_unique_value(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/api/account', ['email' => 'newemail@example.com'])
            ->assertOk()
            ->assertJsonPath('user.email', 'newemail@example.com');
    }

    public function test_update_email_fails_if_taken(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/api/account', ['email' => 'taken@example.com'])
            ->assertStatus(422);
    }
}
