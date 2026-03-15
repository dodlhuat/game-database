<?php

namespace App\Console\Commands;

use App\Mail\DueSoonReminderMail;
use App\Models\Loan;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;

class SendDueSoonReminders extends Command
{
    protected $signature = 'loans:send-due-soon-reminders';

    protected $description = 'Sends reminder emails for loans due in 2 days';

    public function handle(): int
    {
        $targetDate = Carbon::today()->addDays(2);

        $loans = Loan::with(['user', 'copy.game'])
            ->whereIn('status', ['ACTIVE', 'EXTENDED'])
            ->whereDate('due_date', $targetDate)
            ->get();

        foreach ($loans as $loan) {
            Mail::to($loan->user->email)->queue(new DueSoonReminderMail($loan));
        }

        $this->info("Sent {$loans->count()} due-soon reminder(s).");

        return self::SUCCESS;
    }
}
