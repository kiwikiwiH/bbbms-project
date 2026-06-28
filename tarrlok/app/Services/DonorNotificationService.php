<?php

namespace App\Services;

use App\Models\BloodUnit;
use App\Notifications\DonationStatusChanged;
use Illuminate\Support\Facades\Notification;

class DonorNotificationService
{
    public function notifyStatusChange(BloodUnit $unit, string $event): void
    {
        $unit->loadMissing(['donor', 'hospital']);

        $donor = $unit->donor;

        if (! $donor || ! $donor->tracking_consent || ! $donor->email) {
            return;
        }

        Notification::route('mail', [$donor->email => $donor->name])
            ->notify(new DonationStatusChanged($unit, $event));
    }
}
