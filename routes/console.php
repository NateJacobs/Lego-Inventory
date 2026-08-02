<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes & Schedule
|--------------------------------------------------------------------------
*/

// Refresh every owned set's BrickLink price near the end of the month, so the
// snapshot below records current prices. The batch throttles itself to 2,000
// jobs a day and retries for up to three, so starting on the 24th leaves it
// enough room to finish even in February.
Schedule::command('collection:refresh-prices')
    ->monthlyOn(24, '02:00')
    ->withoutOverlapping();

// The collection log holds exactly one entry per month: this snapshot, taken
// late on the last day. Running it at 23:00 keeps the entry dated within the
// month it covers, since the command dates the snapshot by the day it runs.
Schedule::command('collection:snapshot')
    ->lastDayOfMonth('23:00')
    ->withoutOverlapping();
