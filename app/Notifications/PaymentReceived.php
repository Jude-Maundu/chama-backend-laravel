<?php

namespace App\Notifications;

use App\Models\Contribution;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceived extends Notification
{
    use Queueable;

    public $contribution;

    public function __construct(Contribution $contribution)
    {
        $this->contribution = $contribution;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Payment Received')
            ->greeting('Hello ' . $notifiable->name)
            ->line('Your contribution of ' . number_format($this->contribution->total_amount, 2) . ' has been received.')
            ->line('Receipt Number: ' . $this->contribution->receipt_number)
            ->action('View Details', url('/contributions/' . $this->contribution->id))
            ->line('Thank you for your contribution!');
    }

    public function toArray($notifiable)
    {
        return [
            'contribution_id' => $this->contribution->id,
            'amount' => $this->contribution->total_amount,
            'receipt_number' => $this->contribution->receipt_number,
        ];
    }
}
