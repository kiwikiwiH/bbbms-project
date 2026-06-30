<?php

namespace App\Notifications;

use App\Models\Hospital;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HospitalRegistrationRejected extends Notification
{
    use Queueable;

    public function __construct(public Hospital $hospital) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tarrlok — registration update for '.$this->hospital->name)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your facility registration for **'.$this->hospital->name.'** was not approved at this time.')
            ->line('Reason: '.$this->hospital->rejection_reason)
            ->line('Contact Tarrlok support if you believe this was an error.');
    }
}
