<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\Tache;
use App\Models\User;
use App\Notifications\TaskDueSoonNotification;
use Carbon\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule task to send notifications for tasks due soon
Artisan::command('notifications:send-due-soon', function () {
    // Get tasks that are due in the next 3 days (you can adjust this)
    $upcomingTaches = Tache::whereBetween('due_date', [
        Carbon::now()->startOfDay(),
        Carbon::now()->addDays(3)->endOfDay()
    ])->get();

    foreach ($upcomingTaches as $tache) {
        // Get the project owner to notify
        $user = $tache->projet->user;
        
        // Check if we haven't already sent a notification for this task today
        $alreadyNotified = $user->notifications()
            ->where('type', 'App\\Notifications\\TaskDueSoonNotification')
            ->whereDate('created_at', Carbon::today())
            ->whereJsonContains('data->task_id', $tache->id)
            ->exists();

        if (!$alreadyNotified) {
            $user->notify(new TaskDueSoonNotification($tache));
        }
    }

    $this->info('Task due soon notifications sent successfully!');
})->purpose('Send notifications for tasks due soon');
