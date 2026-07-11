<?php

namespace App\Notifications;

use App\Models\Projet;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectOverdueNotification extends Notification
{
    use Queueable;

    public function __construct(
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
            'title' => "Projet en retard : {$this->project->name}",
            'message' => "Le projet \"{$this->project->name}\" a dépassé son échéance.",
            'type' => 'project_overdue',
            'projet_id' => $this->project->id,
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("🚨 Projet en retard : {$this->project->name}")
            ->view('emails.alerts.project_overdue', [
                'user' => $notifiable,
                'project' => $this->project,
            ]);
    }
}
