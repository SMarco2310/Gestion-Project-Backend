<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\Organization;
use Carbon\Carbon;
use App\Notifications\ProjectStartingNotification;
use App\Notifications\ProjectEndingNotification;

#[Signature('app:send-project-reminders')]
#[Description('Send reminders for projects starting or ending soon based on organization preferences')]
class SendProjectReminders extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $organizations = Organization::with(['projets.team.users', 'projets' => function ($query) {
            $query->whereIn('status', ['à faire', 'en cours']);
        }])->get();

        foreach ($organizations as $org) {
            $daysBeforeStart = (int) ($org->reminder_days_before_start ?? 2);
            $daysBeforeEnd = (int) ($org->reminder_days_before_end ?? 2);

            foreach ($org->projets as $projet) {
                if (!$projet->start_date || !$projet->end_date) {
                    continue;
                }

                $startDate = Carbon::parse($projet->start_date)->startOfDay();
                $endDate = Carbon::parse($projet->end_date)->startOfDay();
                $today = Carbon::now()->startOfDay();

                // Check for project starting
                if ($today->diffInDays($startDate, false) == $daysBeforeStart) {
                    if ($projet->team && $projet->team->users) {
                        foreach ($projet->team->users as $user) {
                            $user->notify(new ProjectStartingNotification($projet));
                        }
                    } elseif ($projet->user) {
                        // Fallback to project creator if no team
                        $projet->user->notify(new ProjectStartingNotification($projet));
                    }
                }

                // Check for project ending
                if ($today->diffInDays($endDate, false) == $daysBeforeEnd) {
                    if ($projet->team && $projet->team->users) {
                        foreach ($projet->team->users as $user) {
                            $user->notify(new ProjectEndingNotification($projet));
                        }
                    } elseif ($projet->user) {
                         // Fallback to project creator if no team
                         $projet->user->notify(new ProjectEndingNotification($projet));
                    }
                }
            }
        }

        $this->info('Project reminders sent successfully.');
    }
}
