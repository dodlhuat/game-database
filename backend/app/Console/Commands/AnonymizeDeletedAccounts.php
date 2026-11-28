<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AccountAnonymizer;
use Illuminate\Console\Command;

/**
 * Deleted users can be restored for a while; after that their personal
 * data goes for good (data protection: no keeping it indefinitely).
 */
class AnonymizeDeletedAccounts extends Command
{
    protected $signature = 'accounts:anonymize-deleted {--dry-run : Only list what would happen}';

    protected $description = 'Anonymizes users deleted more than GRACE_DAYS ago';

    public const GRACE_DAYS = 30;

    public function __construct(private AccountAnonymizer $anonymizer)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $users = User::onlyTrashed()
            ->whereNull('anonymized_at')
            ->where('deleted_at', '<=', now()->subDays(self::GRACE_DAYS))
            ->get();

        foreach ($users as $user) {
            $this->line("Anonymize: #{$user->id}");
            if (! $this->option('dry-run')) {
                $this->anonymizer->anonymize($user);
            }
        }

        $this->info(($this->option('dry-run') ? '[dry run] ' : '')."{$users->count()} deleted account(s) anonymized.");

        return self::SUCCESS;
    }
}
