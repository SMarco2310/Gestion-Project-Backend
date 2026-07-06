<?php

namespace App\Notifications;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RoleUpdatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Organization $organization,
        public string $newRole,
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Mise à jour de votre rôle',
            'message' => "Votre rôle dans l'organisation \"{$this->organization->name}\" a été changé en \"{$this->newRole}\".",
            'type' => 'role_updated',
            'organization_id' => $this->organization->id,
            'new_role' => $this->newRole,
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Mise à jour de votre rôle — {$this->organization->name}")
            ->view('emails.workspace.role_updated', [
                'user' => $notifiable,
                'organization' => $this->organization,
                'newRole' => $this->newRole,
            ]);
    }
}
