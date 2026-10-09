<?php

namespace Tests\Feature\Admin;

use App\Exports\GameExport;
use App\Models\Game;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class GameImportExportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_export_requires_admin(): void
    {
        $this->actingAs(User::factory()->member()->create())
            ->get('/api/admin/games/export')
            ->assertForbidden();
    }

    public function test_import_requires_admin(): void
    {
        $this->actingAs(User::factory()->member()->create())
            ->postJson('/api/admin/games/import')
            ->assertForbidden();
    }

    public function test_export_downloads_xlsx(): void
    {
        Game::factory()->create(['title' => 'Catan']);

        $response = $this->actingAs($this->admin())->get('/api/admin/games/export');

        $response->assertOk();
        $this->assertStringContainsString('spiele.xlsx', $response->headers->get('content-disposition'));
    }

    public function test_import_requires_valid_file(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/api/admin/games/import', ['file' => UploadedFile::fake()->create('x.pdf', 10)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');
    }

    public function test_import_csv_creates_new_games_with_relations(): void
    {
        $csv = "title,min_players,difficulty,language,tags,mechanics,is_active\n"
            ."Catan,3,medium,\"Deutsch, English\",\"Familie, Handel\",Würfeln,1\n"
            .",2,easy,,,,1\n";
        $file = UploadedFile::fake()->createWithContent('games.csv', $csv);

        $this->actingAs($this->admin())
            ->postJson('/api/admin/games/import', ['file' => $file])
            ->assertOk()
            ->assertJson(['new' => 1, 'updated' => 0, 'total' => 1]);

        $game = Game::where('title', 'Catan')->firstOrFail();
        $this->assertSame('catan', $game->slug);
        $this->assertSame(3, $game->min_players);
        $this->assertSame('MEDIUM', $game->difficulty);
        $this->assertEqualsCanonicalizing(['Deutsch', 'English'], $game->languages->pluck('name')->all());
        $this->assertEqualsCanonicalizing(['Familie', 'Handel'], $game->tags->pluck('name')->all());
        $this->assertSame(['Würfeln'], $game->mechanics->pluck('name')->all());
    }

    public function test_import_handles_zero_flags_blank_cells_and_invalid_difficulty(): void
    {
        $csv = "title,min_players,max_players,difficulty,year,language,is_active\n"
            ."Hidden,2,,impossible,,   ,0\n"
            ."Visible,2,4,hard,2020,,\n";
        $file = UploadedFile::fake()->createWithContent('games.csv', $csv);

        $this->actingAs($this->admin())
            ->postJson('/api/admin/games/import', ['file' => $file])
            ->assertOk()
            ->assertJson(['new' => 2, 'updated' => 0]);

        $hidden = Game::where('title', 'Hidden')->firstOrFail();
        $this->assertFalse($hidden->is_active);
        $this->assertNull($hidden->max_players);
        $this->assertNull($hidden->year);
        $this->assertNull($hidden->difficulty);
        $this->assertCount(0, $hidden->languages);

        $visible = Game::where('title', 'Visible')->firstOrFail();
        $this->assertTrue($visible->is_active);
        $this->assertSame('HARD', $visible->difficulty);
        $this->assertSame(2020, $visible->year);
    }

    public function test_export_then_import_updates_existing_games(): void
    {
        $game = Game::factory()->create(['title' => 'Original']);

        $xlsx = Excel::raw(new GameExport, ExcelWriter::XLSX);
        $path = tempnam(sys_get_temp_dir(), 'games').'.xlsx';
        file_put_contents($path, $xlsx);
        $file = new UploadedFile($path, 'games.xlsx', null, null, true);

        $game->update(['title' => 'Changed']);

        $this->actingAs($this->admin())
            ->postJson('/api/admin/games/import', ['file' => $file])
            ->assertOk()
            ->assertJson(['new' => 0, 'updated' => 1, 'total' => 1]);

        $this->assertSame('Original', $game->fresh()->title);
        $this->assertDatabaseCount('games', 1);
    }
}
