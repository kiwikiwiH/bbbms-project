<?php

namespace App\Notifications;

use App\Models\Hospital;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HospitalRegistrationApproved extends Notification
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
            ->subject('Tarrlok — '.$this->hospital->name.' approved')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your facility **'.$this->hospital->name.'** has been approved on the Tarrlok network.')
            ->line('You can now sign in with your hospital administrator account.')
            ->action('Sign in', url('/login'))
            ->line('Thank you for joining Tarrlok.');
    }
}
