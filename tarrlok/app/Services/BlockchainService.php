<?php

namespace App\Services;

use App\Models\BlockchainTamperAttempt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Throwable;

class BlockchainService
{
    public function isEnabled(): bool
    {
        return (bool) config('blockchain.enabled') && filled(config('blockchain.private_key'));
    }

    public function anchor(string $action, array $data): ?string
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $projectRoot = config('blockchain.project_root');
        $script = config('blockchain.anchor_script');
        $payload = json_encode(array_merge(['action' => $action], $data));

        $result = Process::path($projectRoot)
            ->env([
                'BLOCKCHAIN_RPC_URL' => config('blockchain.rpc_url'),
                'BLOCKCHAIN_PRIVATE_KEY' => config('blockchain.private_key'),
            ])
            ->run(['node', $script, $payload]);

        $output = trim($result->output());
        $response = json_decode($output, true);
        $reason = $this->failureReason($result->successful(), $output, $result->errorOutput(), $response);

        if ($reason !== null) {
            Log::warning('Blockchain anchor returned error', [
                'action' => $action,
                'response' => $response,
                'output' => $output,
                'error' => $result->errorOutput(),
            ]);

            $this->recordFailedAttempt($action, $data, $reason);

            return null;
        }

        return $response['txHash'] ?? null;
    }

    /**
     * @param  list<string>  $unitCodes
     * @return array{ok: bool, events: list<array<string, mixed>>, units: array<string, array<string, mixed>>, error: ?string}
     */
    public function fetchLedger(array $unitCodes = []): array
    {
        return $this->runReadScript([
            'action' => 'ledger',
            'unitCodes' => array_values($unitCodes),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getUnit(string $unitCode): ?array
    {
        $result = $this->runReadScript([
            'action' => 'getUnit',
            'unitCode' => $unitCode,
        ]);

        if (! ($result['ok'] ?? false)) {
            return null;
        }

        return $result['unit'] ?? $result['units'][$unitCode] ?? null;
    }

    public function registerUnit(
        string $unitCode,
        int $hospitalId,
        string $bloodGroup,
        int $expiresAt,
        int $actorId,
        string $actorName
    ): ?string {
        return $this->anchor('registerUnit', [
            'unitCode' => $unitCode,
            'hospitalId' => $hospitalId,
            'bloodGroup' => $bloodGroup,
            'expiresAt' => $expiresAt,
            'actorId' => $actorId,
            'actorName' => $actorName,
        ]);
    }

    public function recordScreening(
        string $unitCode,
        string $status,
        int $actorId,
        string $actorName
    ): ?string {
        return $this->anchor('recordScreening', [
            'unitCode' => $unitCode,
            'status' => $status,
            'actorId' => $actorId,
            'actorName' => $actorName,
        ]);
    }

    public function recordIssue(
        string $unitCode,
        int $fromHospitalId,
        int $toHospitalId,
        string $requestCode,
        int $actorId,
        string $actorName
    ): ?string {
        return $this->anchor('recordIssue', [
            'unitCode' => $unitCode,
            'fromHospitalId' => $fromHospitalId,
            'toHospitalId' => $toHospitalId,
            'requestCode' => $requestCode,
            'actorId' => $actorId,
            'actorName' => $actorName,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, events: list<array<string, mixed>>, units: array<string, array<string, mixed>>, unit?: array<string, mixed>, error: ?string}
     */
    protected function runReadScript(array $payload): array
    {
        $fallback = [
            'ok' => false,
            'events' => [],
            'units' => [],
            'error' => null,
        ];

        if (! filled(config('blockchain.private_key'))) {
            $fallback['error'] = 'BLOCKCHAIN_PRIVATE_KEY is not set in .env';

            return $fallback;
        }

        $projectRoot = config('blockchain.project_root');
        $script = config('blockchain.ledger_script');

        $result = Process::path($projectRoot)
            ->timeout(20)
            ->env([
                'BLOCKCHAIN_RPC_URL' => config('blockchain.rpc_url'),
                'BLOCKCHAIN_PRIVATE_KEY' => config('blockchain.private_key'),
            ])
            ->run(['node', $script, json_encode($payload)]);

        $decoded = json_decode(trim($result->output()), true);

        if (! is_array($decoded)) {
            $fallback['error'] = $result->successful()
                ? 'Could not read the shared ledger.'
                : trim($result->errorOutput() ?: 'Ledger read script failed.');

            return $fallback;
        }

        return [
            'ok' => (bool) ($decoded['ok'] ?? false),
            'events' => is_array($decoded['events'] ?? null) ? $decoded['events'] : [],
            'units' => is_array($decoded['units'] ?? null) ? $decoded['units'] : [],
            'unit' => is_array($decoded['unit'] ?? null) ? $decoded['unit'] : null,
            'error' => $decoded['error'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>|null  $response
     */
    protected function failureReason(bool $successful, string $output, string $errorOutput, ?array $response): ?string
    {
        if (is_array($response) && ! empty($response['ok'])) {
            return null;
        }

        if (is_array($response) && filled($response['error'] ?? null)) {
            return (string) $response['error'];
        }

        if (! $successful) {
            return trim($errorOutput ?: $output) ?: 'Blockchain anchor process failed.';
        }

        return 'Blockchain anchor returned an unexpected response.';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function recordFailedAttempt(string $action, array $data, string $reason): void
    {
        $user = auth()->user();

        try {
            BlockchainTamperAttempt::create([
                'user_id' => $user?->id ?? ($data['actorId'] ?? null),
                'actor_name' => $user?->name ?? ($data['actorName'] ?? 'Unknown actor'),
                'role' => $user?->role,
                'hospital_id' => $user?->hospital_id,
                'action' => $action,
                'unit_code' => $data['unitCode'] ?? null,
                'reason' => mb_substr($reason, 0, 500),
            ]);
        } catch (Throwable $e) {
            Log::warning('Could not persist blockchain tamper attempt', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
