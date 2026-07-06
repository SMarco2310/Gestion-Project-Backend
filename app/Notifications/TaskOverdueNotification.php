<?php

namespace App\Notifications;

use App\Models\Tache;
use App\Models\Projet;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskOverdueNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Tache $task,
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
            'title' => "Tâche en retard : {$this->task->title}",
            'message' => "La tâche \"{$this->task->title}\" du projet \"{$this->project->name}\" a dépassé son échéance.",
            'type' => 'task_overdue',
            'task_id' => $this->task->id,
            'projet_id' => $this->project->id,
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("🚨 Tâche en retard : {$this->task->title}")
            ->view('emails.alerts.task_overdue', [
                'user' => $notifiable,
                'task' => $this->task,
                'project' => $this->project,
            ]);
    }
}
