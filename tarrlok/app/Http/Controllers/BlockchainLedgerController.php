<?php

namespace App\Http\Controllers;

use App\Services\BlockchainLedgerService;
use Illuminate\View\View;

class BlockchainLedgerController extends Controller
{
    public function __invoke(BlockchainLedgerService $ledger): View
    {
        return view('shared.blockchain.index', [
            ...$ledger->snapshot(),
            'portal' => $this->portalContext(),
        ]);
    }

    /**
     * @return array{layout: string, dashboardRoute: string, traceRoute: string, traceShowRoute: string, title: string}
     */
    private function portalContext(): array
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return [
                'layout' => 'layouts.tarrlok-admin',
                'dashboardRoute' => 'admin.dashboard',
                'traceRoute' => 'admin.trace',
                'traceShowRoute' => 'admin.trace.show',
                'title' => 'Shared ledger',
            ];
        }

        if ($user->isLab()) {
            return [
                'layout' => 'layouts.tarrlok-lab',
                'dashboardRoute' => 'lab.dashboard',
                'traceRoute' => 'lab.trace',
                'traceShowRoute' => 'lab.trace.show',
                'title' => 'Network ledger',
            ];
        }

        return [
            'layout' => 'layouts.tarrlok-hospital',
            'dashboardRoute' => 'hospital.dashboard',
            'traceRoute' => 'hospital.trace',
            'traceShowRoute' => 'hospital.trace.show',
            'title' => 'Network ledger',
        ];
    }
}
