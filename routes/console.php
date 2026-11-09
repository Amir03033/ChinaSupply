<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('suppliers:refresh-verification')->dailyAt('02:00')->withoutOverlapping();
