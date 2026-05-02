<?php

namespace App\Mail;

use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MeetingMinutes extends Mailable
{
    use Queueable, SerializesModels;

    public $meeting;

    public function __construct(Meeting $meeting)
    {
        $this->meeting = $meeting;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Meeting Minutes - ' . $this->meeting->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.meeting-minutes',
        );
    }
}
