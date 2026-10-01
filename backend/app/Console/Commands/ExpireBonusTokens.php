<?php

namespace App\Console\Commands;

use App\Services\TokenWallet;
use Illuminate\Console\Command;

class ExpireBonusTokens extends Command
{
    protected $signature = 'tokens:expire-bonus';

    protected $description = 'Expire bonus tokens that are past their expiry date';

    public function handle(TokenWallet $wallet): int
    {
        $count = $wallet->expireBonus();

        $this->info("Expired {$count} bonus token lots.");

        return self::SUCCESS;
    }
}
