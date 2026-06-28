<?php

namespace App\Console\Commands;

use App\Models\BloodUnit;
use App\Services\DonorNotificationService;
use Illuminate\Console\Command;

class MarkExpiredBloodUnits extends Command
{
    protected $signature = 'blood:mark-expired';

    protected $description = 'Discard available blood units that have passed their expiry date';

    public function handle(DonorNotificationService $notifications): int
    {
        $units = BloodUnit::query()
            ->where('status', 'available')
            ->expired()
            ->get();

        foreach ($units as $unit) {
            $unit->update(['status' => 'discarded']);
            $notifications->notifyStatusChange($unit, 'expired');
        }

        $this->info('Marked '.$units->count().' unit(s) as expired/discarded.');

        return self::SUCCESS;
    }
}
