<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Workspace;
use App\Models\User;

class WorkspaceAddedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $workspace;
    public $adder;

    /**
     * Create a new notification instance.
     */
    public function __construct(Workspace $workspace, User $adder)
    {
        $this->workspace = $workspace;
        $this->adder = $adder;
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
        $adderName = $this->adder->name ?? 'Un membre';

        $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000'));
        $workspaceUrl = rtrim($frontendUrl, '/') . "/organization/{$this->workspace->organization_id}/workspace/{$this->workspace->id}";

        return (new MailMessage)
            ->subject('Vous avez été ajouté à l\'espace de travail : ' . $workspaceName)
            ->greeting('Bonjour ' . ($notifiable->name ?? '') . '!')
            ->line("{$adderName} vous a ajouté à l'espace de travail \"{$workspaceName}\" dans {$orgName}.")
            ->action('Accéder à l\'espace de travail', $workspaceUrl)
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
        $adderName = $this->adder->name ?? 'Un membre';

        return [
            'type' => 'workspace_added',
            'workspace_id' => $this->workspace->id,
            'organization_id' => $this->workspace->organization_id,
            'title' => 'Ajout à un espace de travail',
            'message' => "{$adderName} vous a ajouté à l'espace de travail {$workspaceName}.",
            'url' => "/organization/{$this->workspace->organization_id}/workspace/{$this->workspace->id}"
        ];
    }
}
