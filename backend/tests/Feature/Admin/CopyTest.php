<?php

namespace Tests\Feature\Admin;

use App\Models\Copy;
use App\Models\Game;
use App\Models\Loan;
use App\Models\LoanSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CopyTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_index_returns_copies(): void
    {
        Copy::factory()->count(3)->create();

        $this->actingAs($this->admin())
            ->getJson('/api/admin/copies')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_store_creates_copy(): void
    {
        $game = Game::factory()->create();

        $this->actingAs($this->admin())
            ->postJson('/api/admin/copies', [
                'game_id' => $game->id,
                'condition' => 'NEW',
            ])
            ->assertCreated();
    }

    public function test_show_returns_copy(): void
    {
        $copy = Copy::factory()->create();

        $this->actingAs($this->admin())
            ->getJson("/api/admin/copies/{$copy->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $copy->id);
    }

    public function test_update_modifies_copy(): void
    {
        $copy = Copy::factory()->create(['condition' => 'NEW']);

        $this->actingAs($this->admin())
            ->putJson("/api/admin/copies/{$copy->id}", [
                'game_id' => $copy->game_id,
                'condition' => 'GOOD',
            ])
            ->assertOk()
            ->assertJsonPath('data.condition', 'GOOD');
    }

    public function test_destroy_deletes_copy_without_active_loans(): void
    {
        $copy = Copy::factory()->create();

        $this->actingAs($this->admin())
            ->deleteJson("/api/admin/copies/{$copy->id}")
            ->assertOk();

        $this->assertDatabaseMissing('copies', ['id' => $copy->id]);
    }

    public function test_destroy_fails_when_copy_has_active_loan(): void
    {
        $copy = Copy::factory()->create();
        Loan::factory()->create(['copy_id' => $copy->id, 'status' => 'ACTIVE']);

        $this->actingAs($this->admin())
            ->deleteJson("/api/admin/copies/{$copy->id}")
            ->assertStatus(422);
    }

    public function test_approve_releases_copy_from_review(): void
    {
        Notification::fake();
        LoanSetting::factory()->create();
        $copy = Copy::factory()->create(['condition' => 'REVIEW']);

        $this->actingAs($this->admin())
            ->postJson("/api/admin/copies/{$copy->id}/approve")
            ->assertOk();

        $this->assertDatabaseMissing('copies', ['id' => $copy->id, 'condition' => 'REVIEW']);
    }

    public function test_approve_fails_when_not_in_review(): void
    {
        $copy = Copy::factory()->create(['condition' => 'NEW']);

        $this->actingAs($this->admin())
            ->postJson("/api/admin/copies/{$copy->id}/approve")
            ->assertStatus(422);
    }

    public function test_mark_damaged_sets_damaged_condition(): void
    {
        Notification::fake();
        $copy = Copy::factory()->create(['condition' => 'REVIEW']);

        $this->actingAs($this->admin())
            ->postJson("/api/admin/copies/{$copy->id}/mark-damaged")
            ->assertOk();

        $this->assertDatabaseHas('copies', ['id' => $copy->id, 'condition' => 'DAMAGED']);
    }
}
