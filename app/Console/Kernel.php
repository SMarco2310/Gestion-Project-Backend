<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Send task due soon notifications every day at 9 AM
        $schedule->command('notifications:send-due-soon')
            ->dailyAt('09:00')
            ->timezone('UTC');

        // You can also run it every hour:
        // $schedule->command('notifications:send-due-soon')->hourly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
