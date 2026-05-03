<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Promote past confirmed bookings to 'success' each day at 01:00,
// which unblocks the customer review flow.
Schedule::command('bookings:complete')->dailyAt('01:00');
