<?php

namespace App\Notifications;

use App\Models\Team;
use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamRemovedNotification extends Notification
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
            'title' => "Retrait de l'équipe \"{$this->team->name}\"",
            'message' => "Vous avez été retiré(e) de l'équipe \"{$this->team->name}\" de l'organisation \"{$this->organization->name}\".",
            'type' => 'team_removed',
            'team_id' => $this->team->id,
            'organization_id' => $this->organization->id,
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Retrait de l'équipe \"{$this->team->name}\"")
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Vous avez été retiré(e) de l'équipe \"{$this->team->name}\" au sein de l'organisation \"{$this->organization->name}\".")
            ->line("Si vous pensez qu'il s'agit d'une erreur, veuillez contacter l'administrateur de votre organisation.");
    }
}
