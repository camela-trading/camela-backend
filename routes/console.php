<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(\Illuminate\Foundation\Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('users:disable-inactive')
    ->daily();

Schedule::command('customer-reminders:send')
    ->dailyAt('09:00')
    ->withoutOverlapping();
