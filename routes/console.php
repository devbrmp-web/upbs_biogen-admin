<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// =====================================================
// SCHEDULED TASKS
// =====================================================

// Cleanup expired unpaid orders every minute
Schedule::command('orders:cleanup-pending')->everyMinute();
