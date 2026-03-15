<?php

namespace App\Console\Commands;

use App\Models\Loan;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class MarkOverdueLoans extends Command
{
    protected $signature = 'loans:mark-overdue';

    protected $description = 'Marks all active/extended loans past their due date as OVERDUE';

    public function handle(): int
    {
        $count = Loan::whereIn('status', ['ACTIVE', 'EXTENDED'])
            ->where('due_date', '<', Carbon::today())
            ->update(['status' => 'OVERDUE']);

        $this->info("Marked {$count} loan(s) as OVERDUE.");

        return self::SUCCESS;
    }
}
