<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanApprovedNotification extends Notification
{
    use Queueable;

    public $loan;

    public function __construct(Loan $loan)
    {
        $this->loan = $loan;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Loan Application Approved')
            ->greeting('Hello ' . $notifiable->name)
            ->line('Your loan application of ' . number_format($this->loan->amount, 2) . ' has been approved.')
            ->line('Loan Type: ' . ucfirst($this->loan->loan_type))
            ->line('Interest Rate: ' . $this->loan->interest_rate . '%')
            ->line('Duration: ' . $this->loan->duration_months . ' months')
            ->action('View Loan Details', url('/loans/' . $this->loan->id))
            ->line('Funds will be sent to your M-Pesa shortly.');
    }

    public function toArray($notifiable)
    {
        return [
            'loan_id' => $this->loan->id,
            'amount' => $this->loan->amount,
            'status' => $this->loan->status,
        ];
    }
}
