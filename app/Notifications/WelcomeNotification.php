<?php
 
namespace App\Notifications;
 
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
 
class WelcomeNotification extends Notification
{
    use Queueable;
 
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toDatabase($notifiable): array
    {
        return [
            'title' => 'Welcome to ' . env('APP_NAME'),
            'message' => 'Welcome ' . $notifiable->name . '! Glad to have you on board.',
            'type' => 'welcome'
        ];
    }
 
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Bienvenue sur ' . env('APP_NAME'))
            ->view('emails.welcome', [
                'user' => $notifiable,
                'app' => env('APP_NAME'),
                'loginUrl' => url(env('APP_URL') . "/auth/login"),
            ]);
    }
}
