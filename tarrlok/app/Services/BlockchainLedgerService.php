<?php

namespace App\Services;

use App\Models\BlockchainTamperAttempt;
use App\Models\BloodUnit;
use App\Models\Hospital;
use Illuminate\Support\Collection;

class BlockchainLedgerService
{
    public function __construct(
        protected BlockchainService $blockchain,
        protected BlockchainIntegrityService $integrity
    ) {}

    /**
     * @return array{
     *     events: list<array<string, mixed>>,
     *     alerts: list<array<string, mixed>>,
     *     attempts: Collection<int, BlockchainTamperAttempt>,
     *     ledgerOk: bool,
     *     ledgerError: ?string
     * }
     */
    public function snapshot(): array
    {
        $units = BloodUnit::query()
            ->with(['hospital', 'recorder', 'screener'])
            ->orderByDesc('updated_at')
            ->limit(200)
            ->get();

        $ledger = $this->blockchain->fetchLedger($units->pluck('unit_code')->all());
        $hospitals = Hospital::query()->pluck('name', 'id');

        $events = array_map(function (array $event) use ($hospitals) {
            $event['hospitalName'] = isset($event['hospitalId'])
                ? ($hospitals[$event['hospitalId']] ?? null)
                : null;
            $event['fromHospitalName'] = isset($event['fromHospitalId'])
                ? ($hospitals[$event['fromHospitalId']] ?? null)
                : null;
            $event['toHospitalName'] = isset($event['toHospitalId'])
                ? ($hospitals[$event['toHospitalId']] ?? null)
                : null;

            return $event;
        }, $ledger['events'] ?? []);

        return [
            'events' => $events,
            'alerts' => $this->integrity->alerts($units, $ledger),
            'attempts' => BlockchainTamperAttempt::query()
                ->with(['user', 'hospital'])
                ->latest()
                ->limit(50)
                ->get(),
            'ledgerOk' => (bool) ($ledger['ok'] ?? false),
            'ledgerError' => $ledger['error'] ?? null,
        ];
    }
}
