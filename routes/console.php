<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ANOM-14: Explicit timezone so the scheduler fires at 01:00 UK time,
// consistent with salon business hours (Europe/London observes BST in summer).
Schedule::command('bookings:complete')->dailyAt('01:00')->timezone('Europe/London');
