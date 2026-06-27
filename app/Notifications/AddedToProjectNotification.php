<?php
// app/Notifications/AddedToProjectNotification.php

namespace App\Notifications;

use App\Models\Projet;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AddedToProjectNotification extends Notification
{
    use Queueable;

    /**
     * Needs both the Projet (what they were added to) and the inviter
     * (who added them) — neither of these is $notifiable, since
     * $notifiable is always the person RECEIVING the notification.
     */
    public function __construct(
        public Projet $projet,
        public string $inviterName,
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => "Vous avez été ajouté au projet \"{$this->projet->name}\"",
            'message' => "{$this->inviterName} vous a ajouté au projet \"{$this->projet->name}\".",
            'type' => 'added_to_project',
            'projet_id' => $this->projet->id,
            'inviter_name' => $this->inviterName,
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Vous avez été ajouté au projet \"{$this->projet->name}\"")
            ->view('emails.added-to-project', [
                'inviterName' => $this->inviterName,
                'projectName' => $this->projet->name,
                'projectDescription' => $this->projet->description,
                'taskCount' => $this->projet->taches()->count(),
                'projectUrl' => url((env('APP_URL'))."/projets/{$this->projet->id}"),
            ]);
    }
}
