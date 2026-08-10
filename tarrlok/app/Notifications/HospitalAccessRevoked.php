<?php

namespace App\Notifications;

use App\Models\Hospital;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HospitalAccessRevoked extends Notification
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
            ->subject('Tarrlok — network access revoked for '.$this->hospital->name)
            ->view('emails.hospital-revoked', [
                'hospital' => $this->hospital,
                'contactName' => $notifiable->name,
                'revocationReason' => $this->hospital->rejection_reason,
                'adminMessage' => $this->adminMessage,
            ]);
    }
}
