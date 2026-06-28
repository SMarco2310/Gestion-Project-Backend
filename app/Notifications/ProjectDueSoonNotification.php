<?php
// app/Notifications/ProjectDueSoonNotification.php

namespace App\Notifications;

use App\Models\Projet;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectDueSoonNotification extends Notification
{
    use Queueable;

    public function __construct(public Projet $projet)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toDatabase($notifiable): array
    {
        $daysRemaining = now()->diffInDays($this->projet->end_date, false);

        return [
            'title' => "Rappel : Le projet \"{$this->projet->name}\" se termine bientôt",
            'message' => "Le projet \"{$this->projet->name}\" arrive à échéance dans {$daysRemaining} jours.",
            'type' => 'project_due_soon',
            'projet_id' => $this->projet->id,
            'days_remaining' => $daysRemaining,
            'end_date' => $this->projet->end_date,
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        $daysRemaining = now()->diffInDays($this->projet->end_date, false);

        return (new MailMessage)
            ->subject("Rappel : Le projet \"{$this->projet->name}\" se termine bientôt")
            ->view('emails.project-due-date-reminder', [
                'daysRemaining' => $daysRemaining,
                'projectName' => $this->projet->name,
                'projectDescription' => $this->projet->description,
                'endDate' => $this->projet->end_date->format('d/m/Y'),
                'projectUrl' => url((env('APP_URL'))."/projets/{$this->projet->id}"),
            ]);
    }
}
