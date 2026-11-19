<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\InactiveAccountNotice;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Privacy policy §5: accounts without any activity for 3 years are deleted
 * after the member has been notified.
 *
 * Two phases, run daily:
 *  1. Inactive accounts get a notice by email. Logging in (or any visit
 *     while logged in) within the grace period cancels it — see User::markSeen().
 *  2. Accounts still inactive when the grace period ends are anonymized.
 *
 * "Deleted" means anonymized, not a row delete: loans, payments and token
 * transactions must be kept for 7 years (§ 132 BAO) and cascade on the user
 * row. Everything that identifies the person is removed instead.
 *
 * Accounts that still have obligations or value attached (open loans, a
 * running membership, an open cancellation, purchased tokens) are never
 * touched automatically and are listed for manual review instead.
 */
class PruneInactiveAccounts extends Command
{
    protected $signature = 'accounts:prune-inactive {--dry-run : Only list what would happen}';

    protected $description = 'Notify, then anonymize accounts that have been inactive for 3 years';

    public const INACTIVE_YEARS = 3;

    public const GRACE_DAYS = 30;

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->reportBlocked();

        $toNotify = $this->inactive()->whereNull('inactivity_notice_sent_at')->get();
        foreach ($toNotify as $user) {
            $this->line("Notice: #{$user->id}");
            if ($dryRun) {
                continue;
            }
            $user->notify(new InactiveAccountNotice(now()->addDays(self::GRACE_DAYS)));
            $user->forceFill(['inactivity_notice_sent_at' => now()])->saveQuietly();
        }

        $toAnonymize = $this->inactive()
            ->whereNotNull('inactivity_notice_sent_at')
            ->where('inactivity_notice_sent_at', '<=', now()->subDays(self::GRACE_DAYS))
            ->get();
        foreach ($toAnonymize as $user) {
            $this->line("Anonymize: #{$user->id}");
            if (! $dryRun) {
                $this->anonymize($user);
            }
        }

        $prefix = $dryRun ? '[dry run] ' : '';
        $this->info("{$prefix}{$toNotify->count()} notified, {$toAnonymize->count()} anonymized.");

        return self::SUCCESS;
    }

    /** @return Builder<User> */
    private function staleAccounts(): Builder
    {
        return User::query()
            ->where('role', '!=', 'ADMIN')
            ->whereNull('anonymized_at')
            ->where('last_seen_at', '<', now()->subYears(self::INACTIVE_YEARS));
    }

    /** @return Builder<User> */
    private function inactive(): Builder
    {
        return $this->staleAccounts()
            ->where(fn (Builder $q) => $q->whereNull('membership_expires_at')
                ->orWhere('membership_expires_at', '<', now()))
            ->where('tokens', '<=', 0)
            ->where('tokens_blocked', '<=', 0)
            ->whereDoesntHave('loans', fn (Builder $q) => $q->whereIn('status', ['ACTIVE', 'EXTENDED', 'OVERDUE']))
            ->whereDoesntHave('packageLoans', fn (Builder $q) => $q->whereIn('status', ['ACTIVE', 'OVERDUE']))
            ->whereNotExists(fn ($q) => $q->select(DB::raw(1))
                ->from('membership_cancellations')
                ->whereColumn('membership_cancellations.user_id', 'users.id')
                ->where('membership_cancellations.status', 'REQUESTED'));
    }

    private function reportBlocked(): void
    {
        $blocked = $this->staleAccounts()->whereNotIn('id', $this->inactive()->select('id'))->pluck('id');
        if ($blocked->isNotEmpty()) {
            $this->warn('Inactive but skipped (open loans, membership, cancellation or tokens), review manually: #'.$blocked->implode(', #'));
        }
    }

    private function anonymize(User $user): void
    {
        DB::transaction(function () use ($user) {
            $email = $user->email;

            $user->tokens()->delete();
            $user->favorites()->delete();
            $user->reservations()->delete();
            $user->reviews()->delete();
            $user->emailLogs()->delete();
            DB::table('password_reset_tokens')->where('email', $email)->delete();
            DB::table('sessions')->where('user_id', $user->id)->delete();

            $user->forceFill([
                'name' => 'Gelöschtes Konto',
                'email' => "deleted-{$user->id}@anonymized.invalid",
                'address' => null,
                'street' => null,
                'postal_code' => null,
                'city' => null,
                'date_of_birth' => null,
                'password' => Hash::make(Str::random(64)),
                'remember_token' => null,
                'newsletter_opt_in' => false,
                'status' => 'SUSPENDED',
                'anonymized_at' => now(),
            ])->saveQuietly();
        });
    }
}
