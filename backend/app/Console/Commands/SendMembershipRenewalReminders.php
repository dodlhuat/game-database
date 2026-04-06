<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\MembershipRenewalReminderNotification;
use Illuminate\Console\Command;

class SendMembershipRenewalReminders extends Command
{
    protected $signature   = 'members:send-renewal-reminders';
    protected $description = 'Send renewal reminder emails to members whose membership expires within 90 days';

    public function handle(): int
    {
        $users = User::where('role', 'MEMBER')
            ->whereNotNull('membership_expires_at')
            ->whereBetween('membership_expires_at', [now(), now()->addDays(90)])
            ->where(function ($q) {
                $q->whereNull('renewal_reminder_sent_at')
                  ->orWhere('renewal_reminder_sent_at', '<', now()->subDays(90));
            })
            ->get();

        foreach ($users as $user) {
            $user->notify(new MembershipRenewalReminderNotification());
            $user->update(['renewal_reminder_sent_at' => now()]);
            $this->line("Reminder sent to: {$user->email}");
        }

        $this->info("Renewal reminders sent to {$users->count()} members.");

        return self::SUCCESS;
    }
}
