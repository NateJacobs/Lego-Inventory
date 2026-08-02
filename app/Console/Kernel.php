<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Refresh every owned set's BrickLink price near the end of the month,
        // so the snapshot below records current prices. The batch throttles
        // itself to 2,000 jobs a day and retries for up to three, so starting
        // on the 24th leaves it enough room to finish even in February.
        $schedule->command('collection:refresh-prices')
                 ->monthlyOn(24, '02:00')
                 ->withoutOverlapping();

        // The collection log holds exactly one entry per month: this snapshot,
        // taken late on the last day. Running it at 23:00 keeps the entry dated
        // within the month it covers, since the command dates the snapshot by
        // the day it runs.
        $schedule->command('collection:snapshot')
                 ->lastDayOfMonth('23:00')
                 ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
