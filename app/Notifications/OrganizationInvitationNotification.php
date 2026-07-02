<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Invitation;

class OrganizationInvitationNotification extends Notification
{
    use Queueable;

    public $invitation;

    /**
     * Create a new notification instance.
     */
    public function __construct(Invitation $invitation)
    {
        $this->invitation = $invitation;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Load the relations so we can show names
        $this->invitation->loadMissing(['organization', 'team', 'projet', 'inviter']);

        $orgName = $this->invitation->organization->name;
        $inviterName = $this->invitation->inviter ? $this->invitation->inviter->name : 'Someone';
        
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));
        $inviteUrl = rtrim($frontendUrl, '/') . '/invite?token=' . $this->invitation->token;

        $mailMessage = (new MailMessage)
            ->subject('You have been invited to join ' . $orgName)
            ->greeting('Hello!')
            ->line($inviterName . ' has invited you to join the organization "' . $orgName . '".');

        if ($this->invitation->team) {
            $mailMessage->line('You will also be added to the team "' . $this->invitation->team->name . '".');
        }

        if ($this->invitation->projet) {
            $mailMessage->line('You have been invited to collaborate on the project "' . $this->invitation->projet->name . '".');
        }

        $mailMessage->action('Accept Invitation', $inviteUrl)
            ->line('This invitation will expire in 2 days.')
            ->line('If you do not have an account, you will be prompted to create one first.');

        return $mailMessage;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
