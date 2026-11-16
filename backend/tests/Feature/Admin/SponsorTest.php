<?php

namespace Tests\Feature\Admin;

use App\Models\Sponsor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SponsorTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        // Absolute disk URL like production, so stored paths round-trip through StorageUrl.
        Storage::fake('public', ['url' => 'https://example.test/storage']);
        $this->admin = User::factory()->admin()->create();
    }

    public function test_public_list_contains_only_active_sponsors_in_order(): void
    {
        Sponsor::factory()->create(['name' => 'B', 'sort_order' => 2]);
        Sponsor::factory()->create(['name' => 'A', 'sort_order' => 1]);
        Sponsor::factory()->inactive()->create(['name' => 'Hidden']);
        Sponsor::factory()->create(['name' => 'Deleted'])->delete();

        $this->getJson('/api/sponsors')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'A')
            ->assertJsonPath('data.1.name', 'B');
    }

    public function test_admin_endpoints_require_admin(): void
    {
        $this->getJson('/api/admin/sponsors')->assertUnauthorized();

        $this->actingAs(User::factory()->create(['status' => 'ACTIVE']))
            ->getJson('/api/admin/sponsors')
            ->assertForbidden();
    }

    public function test_admin_list_includes_inactive_but_not_deleted(): void
    {
        Sponsor::factory()->create();
        Sponsor::factory()->inactive()->create();
        Sponsor::factory()->create()->delete();

        $this->actingAs($this->admin)
            ->getJson('/api/admin/sponsors')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_name_is_the_only_required_field(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/admin/sponsors', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->actingAs($this->admin)
            ->postJson('/api/admin/sponsors', ['name' => 'Spielwaren Huber'])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Spielwaren Huber')
            ->assertJsonPath('data.url', null)
            ->assertJsonPath('data.logo_url', null)
            ->assertJsonPath('data.is_active', true);
    }

    public function test_new_sponsors_are_appended_at_the_end(): void
    {
        Sponsor::factory()->create(['sort_order' => 5]);

        $this->actingAs($this->admin)
            ->postJson('/api/admin/sponsors', ['name' => 'Neu'])
            ->assertJsonPath('data.sort_order', 6);
    }

    public function test_url_must_be_http(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/api/admin/sponsors', ['name' => 'X', 'url' => 'javascript:alert(1)'])
            ->assertJsonValidationErrors('url');
    }

    public function test_logo_is_stored_as_path_and_svg_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post('/api/admin/sponsors', [
                'name' => 'SVG',
                'logo' => UploadedFile::fake()->create('logo.svg', 4, 'image/svg+xml'),
            ], ['Accept' => 'application/json'])
            ->assertJsonValidationErrors('logo');

        $this->actingAs($this->admin)
            ->post('/api/admin/sponsors', [
                'name' => 'Logo GmbH',
                'logo' => UploadedFile::fake()->image('logo.png', 200, 80),
                'logo_on_light' => '1',
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('data.logo_on_light', true);

        $stored = DB::table('sponsors')->where('name', 'Logo GmbH')->value('logo_url');
        $this->assertIsString($stored);
        $this->assertStringStartsWith('sponsors/', $stored);
        Storage::disk('public')->assertExists($stored);
    }

    public function test_update_can_replace_and_remove_the_logo(): void
    {
        Storage::disk('public')->put('sponsors/old.png', 'x');
        $sponsor = Sponsor::factory()->create(['logo_url' => 'sponsors/old.png']);

        $this->actingAs($this->admin)
            ->post("/api/admin/sponsors/{$sponsor->id}?_method=PUT", [
                'name' => 'Renamed',
                'logo' => UploadedFile::fake()->image('new.png'),
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Renamed');

        Storage::disk('public')->assertMissing('sponsors/old.png');
        $newPath = DB::table('sponsors')->where('id', $sponsor->id)->value('logo_url');
        $this->assertIsString($newPath);

        $this->actingAs($this->admin)
            ->post("/api/admin/sponsors/{$sponsor->id}?_method=PUT", [
                'name' => 'Renamed',
                'remove_logo' => '1',
            ], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.logo_url', null);

        Storage::disk('public')->assertMissing($newPath);
    }

    public function test_toggle_flips_active_state(): void
    {
        $sponsor = Sponsor::factory()->create();

        $this->actingAs($this->admin)
            ->patchJson("/api/admin/sponsors/{$sponsor->id}/toggle")
            ->assertJsonPath('data.is_active', false);

        $this->actingAs($this->admin)
            ->patchJson("/api/admin/sponsors/{$sponsor->id}/toggle")
            ->assertJsonPath('data.is_active', true);
    }

    public function test_reorder_sets_positions(): void
    {
        $a = Sponsor::factory()->create(['sort_order' => 1]);
        $b = Sponsor::factory()->create(['sort_order' => 2]);
        $c = Sponsor::factory()->create(['sort_order' => 3]);

        $this->actingAs($this->admin)
            ->putJson('/api/admin/sponsors/order', ['ids' => [$c->id, $a->id, $b->id]])
            ->assertNoContent();

        $this->assertSame(
            [$c->id, $a->id, $b->id],
            Sponsor::ordered()->pluck('id')->all(),
        );
    }

    public function test_delete_is_soft_and_keeps_the_logo(): void
    {
        Storage::disk('public')->put('sponsors/keep.png', 'x');
        $sponsor = Sponsor::factory()->create(['logo_url' => 'sponsors/keep.png']);

        $this->actingAs($this->admin)
            ->deleteJson("/api/admin/sponsors/{$sponsor->id}")
            ->assertNoContent();

        $this->assertSoftDeleted($sponsor);
        Storage::disk('public')->assertExists('sponsors/keep.png');
    }
}
