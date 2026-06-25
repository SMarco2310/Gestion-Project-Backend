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
        return ['mail'];
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
