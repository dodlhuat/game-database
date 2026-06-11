<?php

namespace Tests\Feature\Admin;

use App\Models\Extension;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExtensionTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_index_returns_extensions(): void
    {
        Extension::factory()->count(2)->create(['status' => 'PENDING']);

        $this->actingAs($this->admin())
            ->getJson('/api/admin/extensions')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_index_requires_admin(): void
    {
        $this->actingAs(User::factory()->member()->create())
            ->getJson('/api/admin/extensions')
            ->assertForbidden();
    }

    public function test_approve_sets_approved_status(): void
    {
        $loan = Loan::factory()->create(['status' => 'ACTIVE']);
        $extension = Extension::factory()->create([
            'loan_id' => $loan->id,
            'status'  => 'PENDING',
        ]);

        $this->actingAs($this->admin())
            ->patchJson("/api/admin/extensions/{$extension->id}/approve")
            ->assertOk()
            ->assertJsonPath('data.status', 'APPROVED');

        $this->assertDatabaseHas('loans', ['id' => $loan->id, 'status' => 'EXTENDED']);
    }

    public function test_approve_fails_when_already_processed(): void
    {
        $extension = Extension::factory()->approved()->create();

        $this->actingAs($this->admin())
            ->patchJson("/api/admin/extensions/{$extension->id}/approve")
            ->assertStatus(422);
    }

    public function test_reject_sets_rejected_status(): void
    {
        $extension = Extension::factory()->create(['status' => 'PENDING']);

        $this->actingAs($this->admin())
            ->patchJson("/api/admin/extensions/{$extension->id}/reject")
            ->assertOk()
            ->assertJsonPath('data.status', 'REJECTED');
    }

    public function test_reject_fails_when_already_processed(): void
    {
        $extension = Extension::factory()->rejected()->create();

        $this->actingAs($this->admin())
            ->patchJson("/api/admin/extensions/{$extension->id}/reject")
            ->assertStatus(422);
    }
}
