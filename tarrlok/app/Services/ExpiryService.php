<?php

namespace App\Services;

use App\Models\BloodUnit;

class ExpiryService
{
    public function __construct(
        protected DonorNotificationService $notifications
    ) {}

    public function discardExpiredUnits(): int
    {
        $units = BloodUnit::query()
            ->where('status', 'available')
            ->expired()
            ->get();

        foreach ($units as $unit) {
            $unit->update(['status' => 'discarded']);
            $this->notifications->notifyStatusChange($unit, 'expired');
        }

        return $units->count();
    }
}
