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

        // Send project start/end reminders every day at 8 AM
        $schedule->command('app:send-project-reminders')
            ->dailyAt('08:00')
            ->timezone('UTC');

        // Send project start/end reminders every day at 10 PM
        $schedule->command('app:send-project-reminders')
            ->dailyAt('22:00')
            ->timezone('UTC');

        // Send overdue task alerts every day at 9:30 AM
        $schedule->command('app:send-overdue-task-alerts')
            ->dailyAt('09:30')
            ->timezone('UTC');
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
