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
        $task = $this->commentaire->tache;
        $project = $task?->projet;
        $commenter = \App\Models\User::where('name', $this->mentionerName)->first()
                     ?? (object) ['name' => $this->mentionerName];

        return (new MailMessage)
            ->subject("{$this->mentionerName} vous a mentionné(e) dans un commentaire")
            ->view('emails.collaboration.mentioned', [
                'user' => $notifiable,
                'commenter' => $commenter,
                'task' => $task,
                'project' => $project,
                'commentContent' => \Illuminate\Support\Str::limit($this->commentaire->content, 200),
            ]);
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
