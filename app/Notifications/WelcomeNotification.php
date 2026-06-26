<?php
 
namespace App\Notifications;
 
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
 
class WelcomeNotification extends Notification
{
    use Queueable;
 
    // No constructor needed here — unlike TaskDueSoonNotification,
    // this notification doesn't need any extra data passed in.
    // $notifiable (the User) already has everything (name, email) it needs.
 
    public function via($notifiable): array
    {
        return ['mail'];
    }
 
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Bienvenue sur !'.env('APP_NAME'))
            ->view('emails.welcome', [
                'user' => $notifiable,
                'app'=>env('APP_NAME'),
                'loginUrl' => url(env('APP_URL')."/auth/login"),
            ]);
    }
}
