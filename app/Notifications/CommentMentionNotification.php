<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Commentaires;

class CommentMentionNotification extends Notification
{
    use Queueable;

    public $commentaire;
    public $mentionerName;

    /**
     * Create a new notification instance.
     */
    public function __construct(Commentaires $commentaire, string $mentionerName)
    {
        $this->commentaire = $commentaire;
        $this->mentionerName = $mentionerName;
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
        $url = rtrim($frontendUrl, '/') . '/tasks/' . $this->commentaire->tache_id;

        return (new MailMessage)
            ->subject($this->mentionerName . ' mentioned you in a comment')
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line($this->mentionerName . ' mentioned you in a comment on a task.')
            ->line('"' . \Illuminate\Support\Str::limit($this->commentaire->content, 100) . '"')
            ->action('View Comment', $url);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'tache_id' => $this->commentaire->tache_id,
            'commentaire_id' => $this->commentaire->id,
            'title' => 'New Mention',
            'message' => $this->mentionerName . ' mentioned you in a comment.',
            'type' => 'comment_mention'
        ];
    }
}
