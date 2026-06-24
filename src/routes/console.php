<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('monitor:poll')->everyMinute()->withoutOverlapping();
