<?php

namespace App\Mail;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoanApprovalMail extends Mailable
{
    use Queueable, SerializesModels;

    public $loan;

    public function __construct(Loan $loan)
    {
        $this->loan = $loan;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Loan Application Approved',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.loan-approved',
        );
    }
}
