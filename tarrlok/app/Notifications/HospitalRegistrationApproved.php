<?php

namespace App\Notifications;

use App\Models\Hospital;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HospitalRegistrationApproved extends Notification
{
    use Queueable;

    public function __construct(
        public Hospital $hospital,
        public ?string $adminMessage = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tarrlok — '.$this->hospital->name.' registration approved')
            ->view('emails.hospital-approved', [
                'hospital' => $this->hospital,
                'contactName' => $notifiable->name,
                'adminMessage' => $this->adminMessage,
                'loginUrl' => url('/login'),
            ]);
    }
}
