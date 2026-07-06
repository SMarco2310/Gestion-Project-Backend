<?php

namespace App\Notifications;

use App\Models\Team;
use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamAddedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Team $team,
        public Organization $organization,
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => "Bienvenue dans l'équipe \"{$this->team->name}\"",
            'message' => "Vous avez été ajouté(e) à l'équipe \"{$this->team->name}\" de l'organisation \"{$this->organization->name}\".",
            'type' => 'team_added',
            'team_id' => $this->team->id,
            'organization_id' => $this->organization->id,
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Bienvenue dans l'équipe \"{$this->team->name}\"")
            ->view('emails.workspace.team_added', [
                'user' => $notifiable,
                'team' => $this->team,
                'organization' => $this->organization,
            ]);
    }
}
