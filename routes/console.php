<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('payments:monitor')->everyMinute();

Schedule::command('queue:work --stop-when-empty --max-time=50')
    ->everyMinute()
    ->withoutOverlapping()
    ->when(fn (): bool => config('queue.default') === 'database');
