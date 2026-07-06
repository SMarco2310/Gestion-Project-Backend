<?php

namespace App\Notifications;

use App\Models\Tache;
use App\Models\Projet;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskUnblockedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Tache $parentTask,
        public Projet $project,
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => "Toutes les sous-tâches terminées : {$this->parentTask->title}",
            'message' => "L'ensemble des sous-tâches de \"{$this->parentTask->title}\" sont terminées. Vous pouvez clôturer la tâche principale.",
            'type' => 'task_unblocked',
            'task_id' => $this->parentTask->id,
            'projet_id' => $this->project->id,
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Tâche prête pour clôture : {$this->parentTask->title}")
            ->view('emails.tasks.unblocked', [
                'user' => $notifiable,
                'parentTask' => $this->parentTask,
                'project' => $this->project,
            ]);
    }
}
