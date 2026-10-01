<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('loans:mark-overdue')->dailyAt('01:00');
Schedule::command('loans:send-due-soon-reminders')->dailyAt('08:00');
Schedule::command('members:send-renewal-reminders')->dailyAt('09:00');
Schedule::command('tokens:expire-bonus')->dailyAt('02:00');
