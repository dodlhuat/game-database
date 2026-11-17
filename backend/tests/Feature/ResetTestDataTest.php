<?php

namespace Tests\Feature;

use App\Console\Commands\ResetTestData;
use App\Models\Event;
use App\Models\Game;
use App\Models\Loan;
use App\Models\LoanSetting;
use App\Models\Mechanic;
use App\Models\Package;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\Sponsor;
use App\Models\Tag;
use App\Models\User;
use App\Services\TokenWallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\PendingCommand;
use Tests\TestCase;

class ResetTestDataTest extends TestCase
{
    use RefreshDatabase;

    private string $mechanics;

    private string $tags;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');

        $this->mechanics = tempnam(sys_get_temp_dir(), 'mech');
        $this->tags = tempnam(sys_get_temp_dir(), 'tags');
        file_put_contents($this->mechanics, "# Kommentar\nWorker Placement\n\nDeckbuilding\n");
        file_put_contents($this->tags, "Familie\nKooperativ\nStrategie\n");
    }

    protected function tearDown(): void
    {
        @unlink($this->mechanics);
        @unlink($this->tags);
        parent::tearDown();
    }

    /** @param  array<string, mixed>  $extra */
    private function reset(array $extra = []): PendingCommand
    {
        return $this->artisan('app:reset-test-data', [
            '--mechanics' => $this->mechanics,
            '--tags' => $this->tags,
            '--force' => true,
            ...$extra,
        ]);
    }

    public function test_wipes_catalogue_and_test_activity_but_keeps_users_and_settings(): void
    {
        $admin = User::factory()->admin()->create(['tokens' => 7]);
        $member = User::factory()->member()->create();
        $supporter = User::factory()->supporter()->create();
        app(TokenWallet::class)->grantTokens($member, 10, 'PURCHASE', 50);

        Loan::factory()->create(['user_id' => $member->id]);
        Reservation::factory()->create();
        Review::factory()->create();
        Package::factory()->create();
        Event::factory()->create();
        Game::factory()->create()->tags()->attach(Tag::factory()->create());
        Mechanic::factory()->create(['name' => 'Alt']);
        DB::table('users')->where('id', $member->id)->update(['tokens_blocked' => 3]);

        LoanSetting::create(['start_date' => now()->toDateString(), 'interval_days' => 14, 'grace_days' => 3, 'loan_duration_weeks' => 2]);
        Sponsor::factory()->create();
        Storage::disk('public')->put('covers/a.jpg', 'x');
        Storage::disk('public')->put('events/b.jpg', 'x');
        Storage::disk('public')->put('sponsors/c.png', 'x');

        $usersBefore = User::count();

        $this->reset()->assertSuccessful();

        foreach (['games', 'copies', 'loans', 'reservations', 'reviews', 'packages', 'events', 'token_lots', 'token_transactions', 'game_tags'] as $table) {
            $this->assertSame(0, DB::table($table)->count(), "{$table} not empty");
        }

        $this->assertSame($usersBefore, User::count());
        $this->assertSame('ADMIN', $admin->fresh()?->role);
        $this->assertSame('USER', $member->fresh()?->role);
        $this->assertSame('USER', $supporter->fresh()?->role);
        $this->assertSame(0, User::sum('tokens') + User::sum('bonus_tokens') + User::sum('tokens_blocked'));
        $this->assertNull($member->fresh()?->membership_expires_at);

        $this->assertSame(1, LoanSetting::count());
        $this->assertSame(1, Sponsor::count());
        Storage::disk('public')->assertMissing('covers/a.jpg');
        Storage::disk('public')->assertMissing('events/b.jpg');
        Storage::disk('public')->assertExists('sponsors/c.png');

        $this->assertSame(['Deckbuilding', 'Worker Placement'], Mechanic::orderBy('name')->pluck('name')->all());
        $this->assertSame(['Familie', 'Kooperativ', 'Strategie'], Tag::orderBy('name')->pluck('name')->all());
        $this->assertSame('worker-placement', Mechanic::where('name', 'Worker Placement')->value('slug'));
    }

    public function test_ids_start_at_one_again(): void
    {
        Game::factory()->count(3)->create();

        $this->reset()->assertSuccessful();

        $this->assertSame(1, Game::factory()->create()->id);
        $this->assertSame(1, Mechanic::orderBy('id')->value('id'));
    }

    public function test_refuses_without_lists_and_changes_nothing(): void
    {
        $game = Game::factory()->create();
        file_put_contents($this->tags, "# nur ein Kommentar\n\n");

        $this->reset()->assertFailed();

        $this->assertModelExists($game);
    }

    public function test_refuses_duplicate_names(): void
    {
        file_put_contents($this->tags, "Familie\nfamilie\n");

        $this->reset()->expectsOutputToContain('doppelt')->assertFailed();
    }

    public function test_declining_the_confirmation_changes_nothing(): void
    {
        $game = Game::factory()->create();

        $this->artisan('app:reset-test-data', ['--mechanics' => $this->mechanics, '--tags' => $this->tags])
            ->expectsConfirmation('Wirklich zurücksetzen?', 'no')
            ->assertFailed();

        $this->assertModelExists($game);
    }

    public function test_production_requires_typing_reset_and_runs_only_once(): void
    {
        $this->app->detectEnvironment(fn () => 'production');
        $game = Game::factory()->create();

        $this->reset()->expectsQuestion('Zum Bestätigen RESET eintippen', 'ja')->assertFailed();
        $this->assertModelExists($game);

        $this->reset()->expectsQuestion('Zum Bestätigen RESET eintippen', 'RESET')->assertSuccessful();
        $this->assertModelMissing($game);
        Storage::disk('local')->assertExists(ResetTestData::MARKER);

        $this->reset()->expectsOutputToContain('bereits ausgeführt')->assertFailed();
    }
}
