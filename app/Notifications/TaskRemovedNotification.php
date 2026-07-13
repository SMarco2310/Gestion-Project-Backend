<?php

namespace App\Notifications;

use App\Models\Tache;
use App\Models\Projet;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskRemovedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Tache $task,
        public Projet $project,
        public User $remover,
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => "Désassignation : {$this->task->title}",
            'message' => "{$this->remover->name} vous a retiré de la tâche \"{$this->task->title}\" sur le projet \"{$this->project->name}\".",
            'type' => 'task_removed',
            'task_id' => $this->task->id,
            'projet_id' => $this->project->id,
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Désassignation : {$this->task->title}")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("{$this->remover->name} vous a désassigné(e) de la tâche \"{$this->task->title}\" sur le projet \"{$this->project->name}\".")
            ->line("Si vous pensez qu'il s'agit d'une erreur, veuillez contacter l'assignateur.");
    }
}
