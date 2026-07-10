<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// FR-005: expire stale group orders so leader notifications fire without
// waiting for a read (lazy expiry on reads is the belt-and-braces half).
Schedule::command('group-orders:expire')->everyMinute();
