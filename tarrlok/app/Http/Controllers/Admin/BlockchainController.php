<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BlockchainStatusService;
use Illuminate\View\View;

class BlockchainController extends Controller
{
    public function __invoke(BlockchainStatusService $status): View
    {
        $chain = $status->getChainStatus();

        return view('admin.blockchain.index', [
            'configured' => $status->isConfigured(),
            'chain' => $chain,
            'health' => $status->overallHealth($chain),
            'stats' => $status->getAnchoringStats(),
            'recentUnits' => $status->getRecentAnchoredUnits(),
        ]);
    }
}
