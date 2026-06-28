<?php

namespace App\Notifications;

use App\Models\BloodUnit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DonationStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public BloodUnit $unit,
        public string $event
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $unit = $this->unit->loadMissing('hospital');
        $label = match ($this->event) {
            'screening_cleared' => 'cleared lab screening and is available for hospital use',
            'screening_failed' => 'did not pass lab screening',
            'issued' => 'was transferred to a partner hospital for supply',
            'expired' => 'has expired and was removed from inventory',
            default => 'was updated',
        };

        return (new MailMessage)
            ->subject('Your blood donation '.$unit->unit_code.' — Tarrlok')
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your donation **'.$unit->unit_code.'** ('.$unit->blood_group.') '.$label.'.')
            ->line('Current facility: '.$unit->hospital->name)
            ->action('View donation', url('/track/'.$unit->unit_code))
            ->line('Thank you for donating through the Tarrlok network.');
    }
}
