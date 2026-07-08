<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use App\Models\Tache;
use App\Models\User;
use App\Notifications\TaskOverdueNotification;
use Carbon\Carbon;

#[Signature('app:send-overdue-task-alerts')]
#[Description('Send alerts for tasks that are past their due date and not yet completed')]
class SendOverdueTaskAlerts extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $overdueTasks = Tache::where('status', '!=', 'terminé')
            ->whereNotNull('due_date')
            ->where('due_date', '<', Carbon::today())
            ->with(['projet', 'assignee'])
            ->get();

        $notifiedCount = 0;

        foreach ($overdueTasks as $tache) {
            if (!$tache->projet) {
                continue;
            }

            // Determine recipient: assignee first, then project owner
            $recipient = $tache->assignee ?? $tache->projet->user;

            if (!$recipient) {
                continue;
            }

            // Deduplication: don't send if already notified
            $alreadyNotified = $recipient->notifications()
                ->where('type', 'App\\Notifications\\TaskOverdueNotification')
                ->whereJsonContains('data->task_id', $tache->id)
                ->exists();

            if (!$alreadyNotified) {
                $recipient->notify(new TaskOverdueNotification($tache, $tache->projet));
                $notifiedCount++;
            }
        }

        $this->info("Overdue task alerts sent: {$notifiedCount} notification(s).");
    }
}
