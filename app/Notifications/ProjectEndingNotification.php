<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Projet;

class ProjectEndingNotification extends Notification
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
            ->subject('Reminder: Project Ending Soon - ' . $this->projet->name)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('This is a reminder that the project "' . $this->projet->name . '" is scheduled to end on ' . $this->projet->end_date->format('M d, Y') . '.')
            ->action('View Project', $url)
            ->line('Please ensure all tasks are wrapped up.');
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
            'title' => 'Project Ending Soon',
            'message' => 'The project "' . $this->projet->name . '" is scheduled to end on ' . $this->projet->end_date->format('M d, Y') . '.',
            'type' => 'project_end'
        ];
    }
}
