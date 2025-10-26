<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 🕐 Run your custom command every minute
Schedule::command('bookings:auto-delete')->everyMinute();

// Example test log schedule (optional)
Schedule::call(function () {
    \Log::info('Scheduled task ran!');
})->everyMinute();
