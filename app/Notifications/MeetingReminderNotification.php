<?php

namespace App\Notifications;

use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MeetingReminderNotification extends Notification
{
    use Queueable;

    public $meeting;

    public function __construct(Meeting $meeting)
    {
        $this->meeting = $meeting;
    }

    public function via($notifiable)
    {
        return ['mail', 'database', 'sms'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Meeting Reminder: ' . $this->meeting->title)
            ->greeting('Hello ' . $notifiable->name)
            ->line('This is a reminder for the upcoming meeting.')
            ->line('Title: ' . $this->meeting->title)
            ->line('Date: ' . $this->meeting->meeting_date->format('F j, Y g:i A'))
            ->line('Venue: ' . $this->meeting->venue)
            ->when($this->meeting->virtual_link, function ($mail) {
                return $mail->line('Virtual Link: ' . $this->meeting->virtual_link);
            })
            ->action('View Meeting Details', url('/meetings/' . $this->meeting->id))
            ->line('Please confirm your attendance.');
    }

    public function toArray($notifiable)
    {
        return [
            'meeting_id' => $this->meeting->id,
            'title' => $this->meeting->title,
            'meeting_date' => $this->meeting->meeting_date->toDateTimeString(),
            'venue' => $this->meeting->venue,
        ];
    }

    public function toSms($notifiable)
    {
        return "CHAMA REMINDER: {$this->meeting->title} on {$this->meeting->meeting_date->format('M d, Y H:i')} at {$this->meeting->venue}";
    }
}
