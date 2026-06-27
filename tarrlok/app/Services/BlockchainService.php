<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

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

        if (! $result->successful()) {
            Log::warning('Blockchain anchor process failed', [
                'action' => $action,
                'output' => $output,
                'error' => $result->errorOutput(),
            ]);

            return null;
        }

        $response = json_decode($output, true);

        if (! is_array($response) || empty($response['ok'])) {
            Log::warning('Blockchain anchor returned error', [
                'action' => $action,
                'response' => $response,
            ]);

            return null;
        }

        return $response['txHash'] ?? null;
    }

    public function registerUnit(string $unitCode, int $hospitalId, string $bloodGroup): ?string
    {
        return $this->anchor('registerUnit', [
            'unitCode' => $unitCode,
            'hospitalId' => $hospitalId,
            'bloodGroup' => $bloodGroup,
        ]);
    }

    public function recordScreening(string $unitCode, string $status): ?string
    {
        return $this->anchor('recordScreening', [
            'unitCode' => $unitCode,
            'status' => $status,
        ]);
    }

    public function recordIssue(
        string $unitCode,
        int $fromHospitalId,
        int $toHospitalId,
        string $requestCode
    ): ?string {
        return $this->anchor('recordIssue', [
            'unitCode' => $unitCode,
            'fromHospitalId' => $fromHospitalId,
            'toHospitalId' => $toHospitalId,
            'requestCode' => $requestCode,
        ]);
    }
}
