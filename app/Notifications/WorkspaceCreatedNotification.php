<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Workspace;
use App\Models\User;

class WorkspaceCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $workspace;
    public $creator;

    /**
     * Create a new notification instance.
     */
    public function __construct(Workspace $workspace, User $creator)
    {
        $this->workspace = $workspace;
        $this->creator = $creator;
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
        $this->workspace->loadMissing('organization');
        $orgName = $this->workspace->organization->name ?? 'l\'organisation';
        $workspaceName = $this->workspace->name;
        $creatorName = $this->creator->name ?? 'Un membre';

        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));
        $workspaceUrl = rtrim($frontendUrl, '/') . "/organization/{$this->workspace->organization_id}/workspace/{$this->workspace->id}";

        return (new MailMessage)
            ->subject('Nouvel espace de travail : ' . $workspaceName)
            ->greeting('Bonjour ' . ($notifiable->name ?? '') . '!')
            ->line("{$creatorName} a créé un nouvel espace de travail nommé \"{$workspaceName}\" dans {$orgName}.")
            ->action('Voir l\'espace de travail', $workspaceUrl)
            ->line('Merci d\'utiliser notre application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $this->workspace->loadMissing('organization');
        $orgName = $this->workspace->organization->name ?? 'l\'organisation';
        $workspaceName = $this->workspace->name;
        $creatorName = $this->creator->name ?? 'Un membre';

        return [
            'type' => 'workspace_created',
            'workspace_id' => $this->workspace->id,
            'organization_id' => $this->workspace->organization_id,
            'title' => 'Nouvel espace de travail',
            'message' => "{$creatorName} a créé l'espace de travail {$workspaceName} dans {$orgName}.",
            'url' => "/organization/{$this->workspace->organization_id}/workspace/{$this->workspace->id}"
        ];
    }
}
