<?php

use App\Console\Commands\CheckExpiringContracts;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Check every minute; the notification service handles in-app weekly reminders and restricts email alerts to once
Schedule::command(CheckExpiringContracts::class)->everyMinute();
