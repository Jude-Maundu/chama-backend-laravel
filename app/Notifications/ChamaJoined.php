<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChamaJoined extends Notification
{
    use Queueable;

    public $chamaName;
    public $role;
    public $temporaryPassword;

    public function __construct(string $chamaName, string $role, ?string $temporaryPassword = null)
    {
        $this->chamaName = $chamaName;
        $this->role = $role;
        $this->temporaryPassword = $temporaryPassword;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $mail = (new MailMessage)
            ->subject('You have joined a Chama')
            ->greeting('Hello ' . $notifiable->name)
            ->line('You have successfully joined the Chama: ' . $this->chamaName . '.')
            ->line('Role assigned: ' . ucfirst($this->role))
            ->line('You can now log in and start participating in the group.');

        if ($this->temporaryPassword) {
            $mail->line('A temporary password has been created for you: ' . $this->temporaryPassword)
                 ->line('Please change your password after your first login.');
        }

        return $mail->action('Login to your account', url('/login'))
                    ->line('Thank you for joining our Chama community!');
    }

    public function toArray($notifiable)
    {
        return [
            'message' => 'You have joined the chama ' . $this->chamaName,
            'chama_name' => $this->chamaName,
            'role' => $this->role,
        ];
    }
}
