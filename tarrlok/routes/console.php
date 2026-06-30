<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('blood:mark-expired')->hourly();
