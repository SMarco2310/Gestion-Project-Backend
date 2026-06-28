<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\Tache;
use App\Models\Projet;
use App\Models\User;
use App\Notifications\TaskDueSoonNotification;
use App\Notifications\ProjectDueSoonNotification;
use Carbon\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule task to send notifications for tasks and projects due soon
Artisan::command('notifications:send-due-soon', function () {
    // Target date is exactly 2 days from today
    $targetDateStart = Carbon::today()->addDays(2)->startOfDay();
    $targetDateEnd = Carbon::today()->addDays(2)->endOfDay();

    // 1. Process Tasks
    $upcomingTaches = Tache::whereIn('status', ['à faire', 'en cours'])
        ->whereBetween('due_date', [$targetDateStart, $targetDateEnd])
        ->get();

    foreach ($upcomingTaches as $tache) {
        $user = $tache->projet->user;
        
        if ($user) {
            $alreadyNotified = $user->notifications()
                ->where('type', 'App\\Notifications\\TaskDueSoonNotification')
                ->whereJsonContains('data->task_id', $tache->id)
                ->exists();

            if (!$alreadyNotified) {
                $user->notify(new TaskDueSoonNotification($tache));
            }
        }
    }

    // 2. Process Projects
    $upcomingProjets = Projet::whereIn('status', ['à faire', 'en cours'])
        ->whereBetween('end_date', [$targetDateStart, $targetDateEnd])
        ->get();

    foreach ($upcomingProjets as $projet) {
        $user = $projet->user;
        
        if ($user) {
            $alreadyNotified = $user->notifications()
                ->where('type', 'App\\Notifications\\ProjectDueSoonNotification')
                ->whereJsonContains('data->projet_id', $projet->id)
                ->exists();

            if (!$alreadyNotified) {
                $user->notify(new ProjectDueSoonNotification($projet));
            }
        }
    }

    $this->info('Task and Project due soon notifications sent successfully!');
})->purpose('Send notifications for tasks and projects due in exactly 2 days');
