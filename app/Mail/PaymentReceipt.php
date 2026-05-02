<?php

namespace App\Mail;

use App\Models\Contribution;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReceipt extends Mailable
{
    use Queueable, SerializesModels;

    public $contribution;

    public function __construct(Contribution $contribution)
    {
        $this->contribution = $contribution;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Payment Receipt - Chama Contribution',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.receipt',
        );
    }
}
