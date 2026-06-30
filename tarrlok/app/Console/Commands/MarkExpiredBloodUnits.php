<?php

namespace App\Console\Commands;

use App\Services\ExpiryService;
use Illuminate\Console\Command;

class MarkExpiredBloodUnits extends Command
{
    protected $signature = 'blood:mark-expired';

    protected $description = 'Discard available blood units that have passed their expiry date';

    public function handle(ExpiryService $expiry): int
    {
        $count = $expiry->discardExpiredUnits();

        $this->info('Marked '.$count.' unit(s) as expired/discarded.');

        return self::SUCCESS;
    }
}
