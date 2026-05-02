<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeMember extends Notification
{
    use Queueable;

    public $user;
    public $password;

    public function __construct(User $user, $password = null)
    {
        $this->user = $user;
        $this->password = $password;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $mail = (new MailMessage)
            ->subject('Welcome to Chama System')
            ->greeting('Hello ' . $this->user->name)
            ->line('Welcome to the Chama System! Your account has been created successfully.')
            ->line('Email: ' . $this->user->email)
            ->line('Phone: ' . $this->user->phone);
        
        if ($this->password) {
            $mail->line('Temporary Password: ' . $this->password)
                 ->line('Please change your password after first login.');
        }
        
        return $mail->action('Login to Your Account', url('/login'))
                    ->line('Thank you for joining our Chama!');
    }

    public function toArray($notifiable)
    {
        return [
            'user_id' => $this->user->id,
            'message' => 'Welcome to the Chama system!',
        ];
    }
}
