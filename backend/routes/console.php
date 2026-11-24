<?php

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Laravel\Sanctum\PersonalAccessToken;

// Sessions unused for 30 days no longer authenticate (see
// AppServiceProvider); this just removes their rows.
Artisan::command('auth:prune-idle-sessions', function () {
    $cutoff = now()->subDays(AppServiceProvider::SESSION_IDLE_DAYS);
    $deleted = PersonalAccessToken::query()
        ->where(fn ($q) => $q->where('last_used_at', '<', $cutoff)
            ->orWhere(fn ($q) => $q->whereNull('last_used_at')->where('created_at', '<', $cutoff)))
        ->delete();
    $this->info("Deleted {$deleted} idle sessions.");
})->purpose('Delete login sessions unused for 30 days');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('loans:mark-overdue')->dailyAt('01:00');
Schedule::command('loans:send-pickup-digest')->dailyAt('07:30');
Schedule::command('loans:send-due-soon-reminders')->dailyAt('08:00');
Schedule::command('members:send-renewal-reminders')->dailyAt('09:00');
Schedule::command('loans:send-pickup-reminders')->dailyAt('10:00');
Schedule::command('tokens:expire-bonus')->dailyAt('02:00');
Schedule::command('model:prune')->dailyAt('03:00');
Schedule::command('accounts:prune-inactive')->dailyAt('03:30');
Schedule::command('auth:prune-idle-sessions')->dailyAt('03:45');
