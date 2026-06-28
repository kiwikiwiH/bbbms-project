<?php

namespace App\Services;

use App\Models\BloodUnit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;

class BlockchainStatusService
{
    public function __construct(
        protected BlockchainService $blockchain
    ) {}

    public function isConfigured(): bool
    {
        return $this->blockchain->isEnabled();
    }

    /**
     * @return array<string, mixed>
     */
    public function getChainStatus(): array
    {
        $fallback = [
            'ok' => false,
            'rpcUrl' => config('blockchain.rpc_url'),
            'rpcReachable' => false,
            'blockNumber' => null,
            'chainId' => null,
            'contractDeployed' => false,
            'contractAddress' => null,
            'contractOwner' => null,
            'signerAddress' => null,
            'signerBalanceEth' => null,
            'deployedAt' => null,
            'errors' => [],
        ];

        if (! config('blockchain.private_key')) {
            $fallback['errors'][] = 'BLOCKCHAIN_PRIVATE_KEY is not set in .env';

            return $fallback;
        }

        $projectRoot = config('blockchain.project_root');
        $script = config('blockchain.status_script');

        $result = Process::path($projectRoot)
            ->timeout(10)
            ->env([
                'BLOCKCHAIN_RPC_URL' => config('blockchain.rpc_url'),
                'BLOCKCHAIN_PRIVATE_KEY' => config('blockchain.private_key'),
            ])
            ->run(['node', $script]);

        $output = trim($result->output());
        $decoded = json_decode($output, true);

        if (! is_array($decoded)) {
            $fallback['errors'][] = $result->successful()
                ? 'Could not read chain status.'
                : trim($result->errorOutput() ?: 'Chain status script failed.');

            return $fallback;
        }

        return $decoded;
    }

    /**
     * @return array<string, int>
     */
    public function getAnchoringStats(): array
    {
        $total = BloodUnit::count();

        return [
            'total_units' => $total,
            'registered_on_chain' => BloodUnit::whereNotNull('blockchain_register_tx')->count(),
            'screened_on_chain' => BloodUnit::whereNotNull('blockchain_screening_tx')->count(),
            'issued_on_chain' => BloodUnit::whereNotNull('blockchain_issue_tx')->count(),
            'missing_register' => BloodUnit::whereNull('blockchain_register_tx')->count(),
            'missing_screening' => BloodUnit::where('screening_status', '!=', 'pending')
                ->whereNull('blockchain_screening_tx')
                ->count(),
        ];
    }

    public function getRecentAnchoredUnits(int $limit = 25): Collection
    {
        return BloodUnit::query()
            ->with('hospital')
            ->where(function ($query) {
                $query->whereNotNull('blockchain_register_tx')
                    ->orWhereNotNull('blockchain_screening_tx')
                    ->orWhereNotNull('blockchain_issue_tx');
            })
            ->latest('updated_at')
            ->limit($limit)
            ->get();
    }

    public function overallHealth(array $chain): string
    {
        if (! $this->isConfigured()) {
            return 'disabled';
        }

        if ($chain['ok'] ?? false) {
            return 'healthy';
        }

        if ($chain['rpcReachable'] ?? false) {
            return 'degraded';
        }

        return 'offline';
    }
}
