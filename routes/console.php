<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('ukapi:remind-key-rotation')->daily()->onOneServer();
Schedule::command('ukapi:snapshots:prune')->daily()->onOneServer();

Schedule::command('ukapi:health:check')->everyFiveMinutes()->onOneServer()->withoutOverlapping(10);
