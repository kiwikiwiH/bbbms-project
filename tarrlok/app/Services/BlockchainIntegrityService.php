<?php

namespace App\Services;

use App\Models\BloodUnit;
use Illuminate\Support\Collection;

class BlockchainIntegrityService
{
    public function __construct(
        protected BlockchainService $blockchain
    ) {}

    /**
     * @return array{status: string, label: string, mismatches: list<string>, lastEditor: ?string, note: ?string, onChain: ?array<string, mixed>}
     */
    public function verify(BloodUnit $unit): array
    {
        $anchored = filled($unit->blockchain_register_tx)
            || filled($unit->blockchain_screening_tx)
            || filled($unit->blockchain_issue_tx);

        if (! filled(config('blockchain.private_key'))) {
            return $this->result($anchored ? 'unreachable' : 'not_anchored', $unit, [], null);
        }

        $onChain = $this->blockchain->getUnit($unit->unit_code);

        if ($onChain === null) {
            return $this->result($anchored ? 'unreachable' : 'not_anchored', $unit, [], null);
        }

        return $this->compare($unit, $onChain, $anchored);
    }

    /**
     * @param  Collection<int, BloodUnit>  $units
     * @param  array{ok: bool, events: list<array<string, mixed>>, units: array<string, array<string, mixed>>, error: ?string}  $ledger
     * @return list<array<string, mixed>>
     */
    public function alerts(Collection $units, array $ledger): array
    {
        if (! ($ledger['ok'] ?? false)) {
            return [];
        }

        $alerts = [];
        $onChainUnits = $ledger['units'] ?? [];
        $eventCodes = collect($ledger['events'] ?? [])
            ->pluck('unitCode')
            ->filter()
            ->unique()
            ->values();

        foreach ($units as $unit) {
            $anchored = filled($unit->blockchain_register_tx)
                || filled($unit->blockchain_screening_tx)
                || filled($unit->blockchain_issue_tx);

            $onChain = $onChainUnits[$unit->unit_code] ?? null;

            if ($onChain === null && ! $anchored && ! $eventCodes->contains($unit->unit_code)) {
                continue;
            }

            $comparison = $this->compare($unit, $onChain, $anchored);

            if ($comparison['status'] !== 'tampered') {
                continue;
            }

            $alerts[] = [
                'unit_code' => $unit->unit_code,
                'blood_group' => $unit->blood_group,
                'hospital' => $unit->hospital?->name,
                'mismatches' => $comparison['mismatches'],
                'lastEditor' => $comparison['lastEditor'],
                'note' => $comparison['note'],
            ];
        }

        foreach ($eventCodes as $unitCode) {
            if ($units->firstWhere('unit_code', $unitCode)) {
                continue;
            }

            $alerts[] = [
                'unit_code' => $unitCode,
                'blood_group' => null,
                'hospital' => null,
                'mismatches' => ['On-chain unit is missing from the operational database.'],
                'lastEditor' => null,
                'note' => 'The shared ledger has this unit, but MySQL does not.',
            ];
        }

        return $alerts;
    }

    /**
     * @param  array<string, mixed>|null  $onChain
     * @return array{status: string, label: string, mismatches: list<string>, lastEditor: ?string, note: ?string, onChain: ?array<string, mixed>}
     */
    public function compare(BloodUnit $unit, ?array $onChain, bool $anchored): array
    {
        if (! $anchored && ($onChain === null || empty($onChain['exists']))) {
            return $this->result('not_anchored', $unit, [], $onChain);
        }

        if ($anchored && ($onChain === null || empty($onChain['exists']))) {
            return $this->result('tampered', $unit, [
                'MySQL has a transaction hash, but this unit is not on chain.',
            ], $onChain);
        }

        $mismatches = [];

        if (($onChain['bloodGroup'] ?? '') !== $unit->blood_group) {
            $mismatches[] = 'Blood group: DB '.$unit->blood_group.' vs chain '.($onChain['bloodGroup'] ?: '—');
        }

        if ((int) ($onChain['hospitalId'] ?? 0) !== (int) $unit->hospital_id) {
            $mismatches[] = 'Hospital id: DB '.$unit->hospital_id.' vs chain '.($onChain['hospitalId'] ?? '—');
        }

        $dbScreening = $unit->screening_status ?: 'pending';
        $chainScreening = $onChain['screeningLabel'] ?? 'none';
        $expectedChainScreening = match ($dbScreening) {
            'cleared' => 'cleared',
            'failed' => 'failed',
            default => 'pending',
        };

        if ($chainScreening !== $expectedChainScreening && ! ($chainScreening === 'none' && $expectedChainScreening === 'pending' && ! $anchored)) {
            $mismatches[] = 'Screening: DB '.$dbScreening.' vs chain '.$chainScreening;
        }

        if ($unit->expires_at) {
            $dbExpiry = $unit->expires_at->getTimestamp();
            $chainExpiry = (int) ($onChain['expiresAt'] ?? 0);

            if ($chainExpiry > 0 && $dbExpiry !== $chainExpiry) {
                $mismatches[] = 'Expiry timestamp differs between MySQL and chain.';
            }
        }

        if ($mismatches === []) {
            return $this->result('match', $unit, [], $onChain);
        }

        return $this->result('tampered', $unit, $mismatches, $onChain);
    }

    /**
     * @param  list<string>  $mismatches
     * @param  array<string, mixed>|null  $onChain
     * @return array{status: string, label: string, mismatches: list<string>, lastEditor: ?string, note: ?string, onChain: ?array<string, mixed>}
     */
    protected function result(string $status, BloodUnit $unit, array $mismatches, ?array $onChain): array
    {
        $lastEditor = $unit->screener?->name ?? $unit->recorder?->name;

        return [
            'status' => $status,
            'label' => match ($status) {
                'match' => 'Match',
                'tampered' => 'Tampered',
                'unreachable' => 'Chain unreachable',
                default => 'Not anchored',
            },
            'mismatches' => $mismatches,
            'lastEditor' => $lastEditor,
            'note' => $status === 'tampered'
                ? 'Last editor of the operational record'
                    .($lastEditor ? ': '.$lastEditor : '')
                    .' — not cryptographic proof of a direct SQL edit.'
                : null,
            'onChain' => $onChain,
        ];
    }
}
