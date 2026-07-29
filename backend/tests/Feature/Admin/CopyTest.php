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
            ->assertOk()
            ->assertJsonPath('copy.condition', 'DAMAGED');

        $this->assertDatabaseHas('copies', ['id' => $copy->id, 'condition' => 'DAMAGED']);
    }

    public function test_approve_accepts_explicit_condition(): void
    {
        Notification::fake();
        LoanSetting::factory()->create();
        $copy = Copy::factory()->create(['condition' => 'REVIEW', 'borrow_count' => 0]);

        $this->actingAs($this->admin())
            ->postJson("/api/admin/copies/{$copy->id}/approve", ['condition' => 'VERY_GOOD'])
            ->assertOk()
            ->assertJsonPath('copy.condition', 'VERY_GOOD');
    }

    public function test_lookup_finds_copy_by_qr_code(): void
    {
        $copy = Copy::factory()->create(['qr_code' => 'ABCD1234']);

        $this->actingAs($this->admin())
            ->getJson('/api/admin/copies/lookup?qr_code=ABCD1234')
            ->assertOk()
            ->assertJsonPath('data.id', $copy->id);
    }

    public function test_lookup_returns_404_for_unknown_code(): void
    {
        $this->actingAs($this->admin())
            ->getJson('/api/admin/copies/lookup?qr_code=NOPE0000')
            ->assertStatus(404);
    }

    public function test_lookup_trims_and_normalizes_case(): void
    {
        $copy = Copy::factory()->create(['qr_code' => 'ABCD1234']);

        $this->actingAs($this->admin())
            ->getJson('/api/admin/copies/lookup?qr_code='.urlencode('  abcd1234  '))
            ->assertOk()
            ->assertJsonPath('data.id', $copy->id);
    }

    public function test_index_review_orders_oldest_return_first(): void
    {
        $recent = Copy::factory()->create(['condition' => 'REVIEW']);
        Loan::factory()->create([
            'copy_id' => $recent->id,
            'status' => 'RETURNED',
            'returned_at' => now()->subDay(),
        ]);

        $oldest = Copy::factory()->create(['condition' => 'REVIEW']);
        Loan::factory()->create([
            'copy_id' => $oldest->id,
            'status' => 'RETURNED',
            'returned_at' => now()->subWeek(),
        ]);

        $noLoan = Copy::factory()->create(['condition' => 'REVIEW']);

        $response = $this->actingAs($this->admin())
            ->getJson('/api/admin/copies?condition=REVIEW')
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertSame([$oldest->id, $recent->id, $noLoan->id], $ids);
    }
}
