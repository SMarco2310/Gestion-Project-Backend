<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\Projet;
use App\Models\User;
use App\Notifications\ProjectOverdueNotification;
use Carbon\Carbon;

#[Signature('app:send-overdue-project-alerts')]
#[Description('Send alerts for projects that are past their due date and not yet completed')]
class SendOverdueProjectAlerts extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $overdueProjects = Projet::where('status', '!=', 'terminé')
            ->whereNotNull('end_date')
            ->where('end_date', '<', Carbon::today())
            ->with(['user', 'teams.users', 'users'])
            ->get();

        $notifiedCount = 0;

        foreach ($overdueProjects as $projet) {
            $recipients = [];

            if ($projet->teams) {
                foreach ($projet->teams as $team) {
                    if ($team->users) {
                        foreach ($team->users as $u) {
                            $recipients[] = $u;
                        }
                    }
                }
            }
            if ($projet->users) {
                foreach ($projet->users as $u) {
                    $recipients[] = $u;
                }
            }

            if (empty($recipients) && $projet->user) {
                // Fallback to project creator
                $recipients[] = $projet->user;
            }

            foreach ($recipients as $recipient) {
                // Deduplication: don't send if already notified
                $alreadyNotified = $recipient->notifications()
                    ->where('type', 'App\\Notifications\\ProjectOverdueNotification')
                    ->whereJsonContains('data->projet_id', $projet->id)
                    ->exists();

                if (!$alreadyNotified) {
                    $recipient->notify(new ProjectOverdueNotification($projet));
                    $notifiedCount++;
                }
            }
        }

        $this->info("Overdue project alerts sent: {$notifiedCount} notification(s).");
    }
}
