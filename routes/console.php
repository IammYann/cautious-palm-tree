<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Automatically process refunds for orders marked refund_required
// due to concurrent stock depletion
Schedule::command('orders:process-refunds')->everyFifteenMinutes();
