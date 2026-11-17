<?php

namespace App\Console\Commands;

use App\Models\Mechanic;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * One-time cut from test operation to live operation: wipes the catalogue and
 * everything the test phase produced, keeps the user accounts, settings and
 * texts, and loads the final mechanics and tags lists.
 */
class ResetTestData extends Command
{
    protected $signature = 'app:reset-test-data
        {--mechanics= : Path to the mechanics list (default: database/data/mechanics.txt)}
        {--tags= : Path to the tags list (default: database/data/tags.txt)}
        {--force : Skip the confirmation (not allowed in production)}
        {--again : Run in production even though the reset already ran once}';

    protected $description = 'Delete all games and test activity, keep users and settings, load mechanics and tags';

    /**
     * Emptied in this order, children before parents, so foreign keys hold
     * on every database without disabling constraints.
     *
     * @var list<string>
     */
    public const TABLES = [
        'extensions',
        'damage_reports',
        'reviews',
        'token_transactions',
        'token_lots',
        'token_purchases',
        'loans',
        'package_loans',
        'package_game',
        'packages',
        'reservations',
        'favorites',
        'game_images',
        'game_language',
        'game_tags',
        'game_mechanics',
        'copies',
        'games',
        'tags',
        'mechanics',
        'membership_payments',
        'membership_cancellations',
        'email_logs',
        'newsletters',
        'events',
        'jobs',
        'failed_jobs',
        'job_batches',
        'cache',
        'cache_locks',
    ];

    /** Upload folders on the public disk that only hold catalogue or test data. */
    public const UPLOAD_DIRS = ['covers', 'game-images', 'events', 'damage-photos'];

    public const MARKER = 'private/test-data-reset.done';

    public function handle(): int
    {
        $production = app()->isProduction();

        if ($production && Storage::disk('local')->exists(self::MARKER) && ! $this->option('again')) {
            $this->error('Der Reset wurde auf diesem Server bereits ausgeführt ('.trim((string) Storage::disk('local')->get(self::MARKER)).').');

            return self::FAILURE;
        }

        $mechanics = $this->readList('mechanics');
        $tags = $this->readList('tags');
        if ($mechanics === null || $tags === null) {
            return self::FAILURE;
        }

        $this->preview($mechanics, $tags);

        if (! $this->confirmed($production)) {
            $this->warn('Abgebrochen, nichts wurde geändert.');

            return self::FAILURE;
        }

        $tables = $this->existingTables();

        DB::transaction(function () use ($tables) {
            foreach ($tables as $table) {
                DB::table($table)->delete();
            }

            User::query()->update([
                'tokens' => 0,
                'bonus_tokens' => 0,
                'tokens_blocked' => 0,
                'membership_expires_at' => null,
                'renewal_reminder_sent_at' => null,
            ]);
            User::whereIn('role', ['MEMBER', 'SUPPORTER'])->update(['role' => 'USER']);
        });

        // Outside the transaction: MySQL commits implicitly on ALTER TABLE.
        foreach ($tables as $table) {
            $this->resetAutoIncrement($table);
        }

        $now = now();
        Mechanic::insert(array_map(fn (string $name) => ['name' => $name, 'slug' => Str::slug($name), 'created_at' => $now, 'updated_at' => $now], $mechanics));
        Tag::insert(array_map(fn (string $name) => ['name' => $name, 'slug' => Str::slug($name), 'created_at' => $now, 'updated_at' => $now], $tags));

        foreach (self::UPLOAD_DIRS as $dir) {
            Storage::disk('public')->deleteDirectory($dir);
        }

        if ($production) {
            Storage::disk('local')->put(self::MARKER, $now->toDateTimeString());
        }

        $this->info(sprintf(
            'Fertig: Spieldaten und Testdaten gelöscht, %d Mechaniken und %d Tags eingespielt, %d User behalten.',
            count($mechanics),
            count($tags),
            User::count(),
        ));

        return self::SUCCESS;
    }

    /**
     * One name per line; blank lines and lines starting with # are ignored.
     *
     * @return list<string>|null
     */
    private function readList(string $name): ?array
    {
        $option = $this->option($name);
        $path = is_string($option) && $option !== '' ? $option : database_path("data/{$name}.txt");
        if (! is_file($path)) {
            $this->error("Liste fehlt: {$path}");

            return null;
        }

        $names = [];
        $slugs = [];
        $problems = [];
        foreach (preg_split('/\R/', (string) file_get_contents($path)) ?: [] as $i => $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $slug = Str::slug($line);
            if ($slug === '') {
                $problems[] = 'Zeile '.($i + 1).": „{$line}“ ergibt keinen gültigen Slug";
            } elseif (isset($slugs[$slug])) {
                $problems[] = 'Zeile '.($i + 1).": „{$line}“ ist doppelt (wie „{$slugs[$slug]}“)";
            } else {
                $slugs[$slug] = $line;
                $names[] = $line;
            }
        }

        if ($problems !== []) {
            $this->error("Fehler in {$path}:");
            foreach ($problems as $problem) {
                $this->line("  - {$problem}");
            }

            return null;
        }

        if ($names === []) {
            $this->error("Liste ist leer: {$path}");

            return null;
        }

        return $names;
    }

    /**
     * @param  list<string>  $mechanics
     * @param  list<string>  $tags
     */
    private function preview(array $mechanics, array $tags): void
    {
        $this->warn('Folgende Daten werden gelöscht:');
        $this->table(['Tabelle', 'Einträge'], array_map(
            fn (string $t) => [$t, DB::table($t)->count()],
            $this->existingTables(),
        ));

        $this->warn('Bei allen Usern:');
        $this->line('  - Token, Bonus-Token und blockierte Token auf 0, Mitgliedschafts-Ablauf gelöscht');
        $this->line('  - '.User::whereIn('role', ['MEMBER', 'SUPPORTER'])->count().' Mitglieder/außerordentliche Mitglieder werden zu registrierten Usern');
        $this->line('  - Bleiben erhalten: '.User::count().' Accounts (Login, Adresse, Admin-Rolle)');
        $this->line('Upload-Ordner werden geleert: '.implode(', ', self::UPLOAD_DIRS));
        $this->line('Neu eingespielt: '.count($mechanics).' Mechaniken, '.count($tags).' Tags');
    }

    /** @return list<string> */
    private function existingTables(): array
    {
        return array_values(array_filter(self::TABLES, fn (string $t) => Schema::hasTable($t)));
    }

    private function confirmed(bool $production): bool
    {
        if ($production) {
            return $this->ask('Zum Bestätigen RESET eintippen') === 'RESET';
        }

        return $this->option('force') || $this->confirm('Wirklich zurücksetzen?');
    }

    private function resetAutoIncrement(string $table): void
    {
        if (! Schema::hasColumn($table, 'id')) {
            return;
        }

        match (DB::getDriverName()) {
            'mysql', 'mariadb' => DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = 1"),
            'pgsql' => DB::statement("SELECT setval(pg_get_serial_sequence('{$table}', 'id'), 1, false) WHERE pg_get_serial_sequence('{$table}', 'id') IS NOT NULL"),
            'sqlite' => Schema::hasTable('sqlite_sequence') ? DB::table('sqlite_sequence')->where('name', $table)->delete() : null,
            default => null,
        };
    }
}
