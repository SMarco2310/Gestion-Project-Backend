<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Projet;

class ProjectStartingNotification extends Notification
{
    use Queueable;

    public $projet;

    /**
     * Create a new notification instance.
     */
    public function __construct(Projet $projet)
    {
        $this->projet = $projet;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));
        $url = rtrim($frontendUrl, '/') . '/projets/' . $this->projet->id;

        return (new MailMessage)
            ->subject('Reminder: Project Starting Soon - ' . $this->projet->name)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('This is a reminder that the project "' . $this->projet->name . '" is scheduled to start on ' . $this->projet->start_date->format('M d, Y') . '.')
            ->action('View Project', $url)
            ->line('Get ready to start working!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'projet_id' => $this->projet->id,
            'title' => 'Project Starting Soon',
            'message' => 'The project "' . $this->projet->name . '" is scheduled to start on ' . $this->projet->start_date->format('M d, Y') . '.',
            'type' => 'project_start'
        ];
    }
}
