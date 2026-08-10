<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\BlockchainLedgerService;
use App\Services\BlockchainStatusService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlockchainController extends Controller
{
    public function __invoke(Request $request, BlockchainStatusService $status, BlockchainLedgerService $ledger): View
    {
        $chain = $status->getChainStatus();
        $snapshot = $ledger->snapshot(auth()->user(), (string) $request->query('unit', ''));

        return view('admin.blockchain.index', [
            'configured' => $status->isConfigured(),
            'chain' => $chain,
            'health' => $status->overallHealth($chain),
            'stats' => $status->getAnchoringStats(),
            'recentUnits' => $status->getRecentAnchoredUnits(),
            ...$snapshot,
            'traceRoute' => 'admin.trace',
            'traceShowRoute' => 'admin.trace.show',
        ]);
    }
}
