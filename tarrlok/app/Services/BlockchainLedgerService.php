<?php

namespace App\Services;

use App\Models\BlockchainTamperAttempt;
use App\Models\BloodUnit;
use App\Models\Hospital;
use App\Models\User;
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
     *     ledgerError: ?string,
     *     visibility: string,
     *     scopeLabel: string,
     *     totalEvents: int,
     *     unitHistory: ?array,
     *     unitQuery: string
     * }
     */
    public function snapshot(?User $user = null, string $unitQuery = ''): array
    {
        $user ??= auth()->user();
        $visibility = $this->visibilityFor($user);
        $unitQuery = strtoupper(trim($unitQuery));

        $unitsQuery = BloodUnit::query()
            ->with(['hospital', 'recorder', 'screener'])
            ->orderByDesc('updated_at');

        if ($visibility !== 'full' && $user?->hospital_id) {
            $relevantCodes = $this->relevantUnitCodesFor($user);
            $unitsQuery->where(function ($q) use ($user, $relevantCodes) {
                $q->where('hospital_id', $user->hospital_id);
                if ($relevantCodes->isNotEmpty()) {
                    $q->orWhereIn('unit_code', $relevantCodes->all());
                }
            });
        }

        $units = $unitsQuery->limit(200)->get();
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
            $event['blockNumber'] = isset($event['blockNumber']) ? (int) $event['blockNumber'] : null;

            return $event;
        }, $ledger['events'] ?? []);

        $totalEvents = count($events);

        if ($visibility !== 'full') {
            $allowedCodes = $units->pluck('unit_code')
                ->merge($this->relevantUnitCodesFor($user))
                ->unique()
                ->filter()
                ->values();

            $hospitalId = (int) $user->hospital_id;

            $events = array_values(array_filter($events, function (array $event) use ($allowedCodes, $hospitalId) {
                if (! empty($event['unitCode']) && $allowedCodes->contains($event['unitCode'])) {
                    return true;
                }

                foreach (['hospitalId', 'fromHospitalId', 'toHospitalId'] as $key) {
                    if (isset($event[$key]) && (int) $event[$key] === $hospitalId) {
                        return true;
                    }
                }

                return false;
            }));
        }

        $ledgerForIntegrity = $ledger;
        $ledgerForIntegrity['events'] = $events;

        $attemptsQuery = BlockchainTamperAttempt::query()
            ->with(['user', 'hospital'])
            ->latest()
            ->limit(50);

        if ($visibility !== 'full' && $user?->hospital_id) {
            $attemptsQuery->where(function ($q) use ($user) {
                $q->where('hospital_id', $user->hospital_id)
                    ->orWhere('user_id', $user->id);
            });
        }

        $unitHistory = null;
        if ($unitQuery !== '' && $visibility === 'full') {
            $unitHistory = $this->historyForUnit($unitQuery, $events, $ledger);
        }

        return [
            'events' => $events,
            'alerts' => $this->integrity->alerts($units, $ledgerForIntegrity),
            'attempts' => $attemptsQuery->get(),
            'ledgerOk' => (bool) ($ledger['ok'] ?? false),
            'ledgerError' => $ledger['error'] ?? null,
            'visibility' => $visibility,
            'scopeLabel' => match ($visibility) {
                'full' => 'Full network audit trail',
                'lab' => 'Facility units your laboratory handles',
                default => 'Units your hospital requested, holds, or issued',
            },
            'totalEvents' => $totalEvents,
            'unitHistory' => $unitHistory,
            'unitQuery' => $unitQuery,
            'showTechnical' => $visibility === 'full',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $knownEvents
     * @param  array<string, mixed>  $ledger
     * @return array{unit: ?BloodUnit, events: list<array<string, mixed>>, onChain: ?array, found: bool}
     */
    public function historyForUnit(string $unitCode, array $knownEvents = [], array $ledger = []): array
    {
        $unitCode = strtoupper(trim($unitCode));
        $unit = BloodUnit::query()
            ->with(['hospital', 'recorder', 'screener'])
            ->where('unit_code', $unitCode)
            ->first();

        if ($knownEvents === []) {
            $ledger = $this->blockchain->fetchLedger([$unitCode]);
            $hospitals = Hospital::query()->pluck('name', 'id');
            $knownEvents = array_map(function (array $event) use ($hospitals) {
                $event['hospitalName'] = isset($event['hospitalId'])
                    ? ($hospitals[$event['hospitalId']] ?? null)
                    : null;
                $event['fromHospitalName'] = isset($event['fromHospitalId'])
                    ? ($hospitals[$event['fromHospitalId']] ?? null)
                    : null;
                $event['toHospitalName'] = isset($event['toHospitalId'])
                    ? ($hospitals[$event['toHospitalId']] ?? null)
                    : null;
                $event['blockNumber'] = isset($event['blockNumber']) ? (int) $event['blockNumber'] : null;

                return $event;
            }, $ledger['events'] ?? []);
        }

        $events = array_values(array_filter(
            $knownEvents,
            fn (array $event) => strtoupper((string) ($event['unitCode'] ?? '')) === $unitCode
        ));

        // Oldest first for lifecycle demo
        usort($events, function (array $a, array $b) {
            $blockA = (int) ($a['blockNumber'] ?? 0);
            $blockB = (int) ($b['blockNumber'] ?? 0);
            if ($blockA !== $blockB) {
                return $blockA <=> $blockB;
            }

            return ((int) ($a['timestamp'] ?? 0)) <=> ((int) ($b['timestamp'] ?? 0));
        });

        $onChain = $ledger['units'][$unitCode]
            ?? $this->blockchain->getUnit($unitCode);

        return [
            'unit' => $unit,
            'events' => $events,
            'onChain' => $onChain,
            'found' => $unit !== null || $events !== [] || (($onChain['exists'] ?? false) === true),
        ];
    }

    private function visibilityFor(?User $user): string
    {
        if (! $user) {
            return 'hospital';
        }

        if ($user->isAdmin()) {
            return 'full';
        }

        if ($user->isLab()) {
            return 'lab';
        }

        return 'hospital';
    }

    /**
     * @return Collection<int, string>
     */
    private function relevantUnitCodesFor(User $user): Collection
    {
        if (! $user->hospital_id) {
            return collect();
        }

        $hospitalId = $user->hospital_id;

        $owned = BloodUnit::query()
            ->where('hospital_id', $hospitalId)
            ->pluck('unit_code');

        if ($user->isLab()) {
            return $owned;
        }

        $viaRequests = BloodUnit::query()
            ->whereHas('bloodRequests', function ($q) use ($hospitalId) {
                $q->where('requesting_hospital_id', $hospitalId)
                    ->orWhere('fulfilling_hospital_id', $hospitalId);
            })
            ->pluck('unit_code');

        return $owned->merge($viaRequests)->unique()->values();
    }
}
