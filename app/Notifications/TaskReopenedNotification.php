<?php

namespace App\Notifications;

use App\Models\Tache;
use App\Models\Projet;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskReopenedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Tache $task,
        public Projet $project,
        public User $actor,
        public string $newStatus,
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => "Tâche réouverte : {$this->task->title}",
            'message' => "{$this->actor->name} a réouvert la tâche \"{$this->task->title}\" et l'a remise en \"{$this->newStatus}\".",
            'type' => 'task_reopened',
            'task_id' => $this->task->id,
            'projet_id' => $this->project->id,
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Révision requise : {$this->task->title}")
            ->view('emails.tasks.reopened', [
                'user' => $notifiable,
                'task' => $this->task,
                'project' => $this->project,
                'actor' => $this->actor,
                'newStatus' => $this->newStatus,
            ]);
    }
}
