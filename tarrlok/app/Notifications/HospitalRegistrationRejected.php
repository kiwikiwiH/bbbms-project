<?php

namespace App\Notifications;

use App\Models\Hospital;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HospitalRegistrationRejected extends Notification
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
            ->subject('Tarrlok — registration decision for '.$this->hospital->name)
            ->view('emails.hospital-rejected', [
                'hospital' => $this->hospital,
                'contactName' => $notifiable->name,
                'rejectionReason' => $this->hospital->rejection_reason,
                'adminMessage' => $this->adminMessage,
            ]);
    }
}
