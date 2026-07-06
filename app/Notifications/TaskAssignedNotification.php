<?php

namespace App\Notifications;

use App\Models\Tache;
use App\Models\Projet;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Tache $task,
        public Projet $project,
        public User $assigner,
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => "Nouvelle tâche assignée : {$this->task->title}",
            'message' => "{$this->assigner->name} vous a assigné la tâche \"{$this->task->title}\" sur le projet \"{$this->project->name}\".",
            'type' => 'task_assigned',
            'task_id' => $this->task->id,
            'projet_id' => $this->project->id,
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Tâche assignée : {$this->task->title}")
            ->view('emails.tasks.assigned', [
                'user' => $notifiable,
                'task' => $this->task,
                'project' => $this->project,
                'assigner' => $this->assigner,
            ]);
    }
}
