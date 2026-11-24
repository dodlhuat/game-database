<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Models\User;
use App\Notifications\LoanDueSoon;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Throwable;

class SendDueSoonReminders extends Command
{
    protected $signature = 'loans:send-due-soon-reminders';

    protected $description = 'Sends reminder emails for loans due in 2 days';

    public function handle(): int
    {
        $targetDate = Carbon::today()->addDays(2);

        $loans = Loan::with(['user', 'copy.game'])
            ->whereIn('status', ['ACTIVE', 'EXTENDED'])
            ->handedOut()
            ->whereDate('due_date', $targetDate)
            ->get();

        $sent = 0;
        foreach ($loans as $loan) {
            /** @var User $user */
            $user = $loan->user;
            try {
                // Uses the "loan_due_soon" template editable in the admin.
                $user->notify(new LoanDueSoon($loan));
                $sent++;
            } catch (Throwable $e) {
                // One broken address must not stop the remaining reminders.
                report($e);
            }
        }

        $this->info("Sent {$sent} due-soon reminder(s).");

        return self::SUCCESS;
    }
}
