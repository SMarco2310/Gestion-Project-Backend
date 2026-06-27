<?php
// app/Notifications/TaskDueSoonNotification.php

namespace App\Notifications;

use App\Models\Tache;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskDueSoonNotification extends Notification
{
    use Queueable;

    public function __construct(public Tache $tache)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toDatabase($notifiable): array
    {
        $daysRemaining = now()->diffInDays($this->tache->due_date, false);

        return [
            'title' => "Rappel : \"{$this->tache->title}\" arrive à échéance",
            'message' => "La tâche \"{$this->tache->title}\" du projet \"{$this->tache->projet->name}\" arrive à échéance dans {$daysRemaining} jours.",
            'type' => 'task_due_soon',
            'task_id' => $this->tache->id,
            'projet_id' => $this->tache->projet->id,
            'days_remaining' => $daysRemaining,
            'due_date' => $this->tache->due_date,
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        $daysRemaining = now()->diffInDays($this->tache->due_date, false);

        return (new MailMessage)
            ->subject("Rappel : \"{$this->tache->title}\" arrive à échéance")
            ->view('emails.due-date-reminder', [
                // These keys match exactly what due-date-reminder.blade.php expects
                'daysRemaining' => $daysRemaining,
                'projectName' => $this->tache->projet->name,
                'taskTitle' => $this->tache->title,
                'taskDescription' => $this->tache->description,
                'priority' => $this->tache->priority,
                'dueDate' => $this->tache->due_date->format('d/m/Y'),
                'taskUrl' => url((env('APP_URL'))."/taches/{$this->tache->id}"),
            ]);
    }
}
