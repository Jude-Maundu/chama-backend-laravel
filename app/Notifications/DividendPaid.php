<?php

namespace App\Notifications;

use App\Models\Dividend;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DividendPaid extends Notification
{
    use Queueable;

    public $dividend;
    public $amount;

    public function __construct(Dividend $dividend, $amount)
    {
        $this->dividend = $dividend;
        $this->amount = $amount;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Dividend Payment Received')
            ->greeting('Hello ' . $notifiable->name)
            ->line('You have received a dividend payment of ' . number_format($this->amount, 2))
            ->line('Period: ' . $this->dividend->period)
            ->line('The amount has been sent to your registered M-Pesa number.')
            ->action('View Dividend History', url('/dividends'))
            ->line('Thank you for being a valued member!');
    }

    public function toArray($notifiable)
    {
        return [
            'dividend_id' => $this->dividend->id,
            'period' => $this->dividend->period,
            'amount' => $this->amount,
        ];
    }
}
