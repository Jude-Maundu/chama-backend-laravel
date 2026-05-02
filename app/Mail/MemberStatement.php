<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MemberStatement extends Mailable
{
    use Queueable, SerializesModels;

    public $member;
    public $statementPath;

    public function __construct(User $member, $statementPath)
    {
        $this->member = $member;
        $this->statementPath = $statementPath;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Monthly Statement - Chama',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.statement',
        );
    }

    public function attachments(): array
    {
        return [
            $this->statementPath,
        ];
    }
}
