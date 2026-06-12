<?php

namespace App\Console\Commands;

use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;

class SetupDemoDatabase extends Command
{
    protected $signature = 'db:demo {--fresh : Drop all tables and re-migrate (default: true)}';

    protected $description = 'Reset the database and seed it with demo data';

    public function handle(): int
    {
        if (! app()->isLocal() && ! $this->confirm('This will wipe the database. Continue?')) {
            return self::FAILURE;
        }

        $this->call('migrate:fresh', ['--force' => true]);
        $this->call('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]);
        $this->call('db:seed', ['--class' => DemoSeeder::class, '--force' => true]);

        $this->info('Demo database ready.');

        return self::SUCCESS;
    }
}
