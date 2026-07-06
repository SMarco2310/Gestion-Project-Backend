<?php

namespace App\Notifications;

use App\Models\Tache;
use App\Models\Projet;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PriorityEscalatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Tache $task,
        public Projet $project,
        public User $actor,
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => "Priorité élevée : {$this->task->title}",
            'message' => "{$this->actor->name} a reclassé la tâche \"{$this->task->title}\" en priorité élevée.",
            'type' => 'priority_escalated',
            'task_id' => $this->task->id,
            'projet_id' => $this->project->id,
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("🔥 Priorité élevée : {$this->task->title}")
            ->view('emails.collaboration.priority_escalated', [
                'user' => $notifiable,
                'task' => $this->task,
                'project' => $this->project,
                'actor' => $this->actor,
            ]);
    }
}
