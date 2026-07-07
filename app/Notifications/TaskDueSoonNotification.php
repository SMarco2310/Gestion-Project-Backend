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
        $daysRemaining = (int) round(now()->diffInDays($this->tache->due_date, false));
        
        $timeText = $daysRemaining > 0 
            ? "dans {$daysRemaining} jour" . ($daysRemaining > 1 ? 's' : '')
            : ($daysRemaining < 0 ? "et est en retard de " . abs($daysRemaining) . " jour" . (abs($daysRemaining) > 1 ? 's' : '') : "aujourd'hui");

        return [
            'title' => "Rappel : \"{$this->tache->title}\" arrive à échéance",
            'message' => "La tâche \"{$this->tache->title}\" du projet \"{$this->tache->projet->name}\" arrive à échéance {$timeText}.",
            'type' => 'task_due_soon',
            'task_id' => $this->tache->id,
            'projet_id' => $this->tache->projet->id,
            'days_remaining' => $daysRemaining,
            'due_date' => $this->tache->due_date,
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        $daysRemaining = (int) round(now()->diffInDays($this->tache->due_date, false));

        $timeText = $daysRemaining > 0 
            ? "dans {$daysRemaining} jour" . ($daysRemaining > 1 ? 's' : '')
            : ($daysRemaining < 0 ? "et est en retard de " . abs($daysRemaining) . " jour" . (abs($daysRemaining) > 1 ? 's' : '') : "aujourd'hui");

        return (new MailMessage)
            ->subject("Rappel : La tâche \"{$this->tache->title}\" arrive à échéance")
            ->view('emails.alerts.deadline_reminder', [
                'user' => $notifiable,
                'daysRemaining' => $daysRemaining > 0 ? $daysRemaining : 0,
                'timeText' => $timeText,
                'task' => $this->tache,
                'project' => $this->tache->projet,
            ]);
    }
}
