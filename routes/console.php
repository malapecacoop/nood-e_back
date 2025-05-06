<?php

use App\Console\Commands\GenerateRecurringEvents;
use Illuminate\Support\Facades\Schedule;

Schedule::command(GenerateRecurringEvents::class)->daily();