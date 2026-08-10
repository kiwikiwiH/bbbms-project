<?php

namespace App\Http\Controllers;

use App\Services\BlockchainLedgerService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlockchainLedgerController extends Controller
{
    public function __invoke(Request $request, BlockchainLedgerService $ledger): View
    {
        $snapshot = $ledger->snapshot(auth()->user(), (string) $request->query('unit', ''));

        return view('shared.blockchain.index', [
            ...$snapshot,
            'portal' => $this->portalContext($snapshot['visibility']),
        ]);
    }

    /**
     * @return array{layout: string, dashboardRoute: string, traceRoute: string, traceShowRoute: string, title: string, subtitle: string}
     */
    private function portalContext(string $visibility): array
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return [
                'layout' => 'layouts.tarrlok-admin',
                'dashboardRoute' => 'admin.dashboard',
                'traceRoute' => 'admin.trace',
                'traceShowRoute' => 'admin.trace.show',
                'title' => 'Shared ledger',
                'subtitle' => 'Full network audit trail with block numbers and integrity checks',
            ];
        }

        if ($user->isLab()) {
            return [
                'layout' => 'layouts.tarrlok-lab',
                'dashboardRoute' => 'lab.dashboard',
                'traceRoute' => 'lab.trace',
                'traceShowRoute' => 'lab.trace.show',
                'title' => 'Facility ledger',
                'subtitle' => 'On-chain history for units your laboratory handles — not the full network dump',
            ];
        }

        return [
            'layout' => 'layouts.tarrlok-hospital',
            'dashboardRoute' => 'hospital.dashboard',
            'traceRoute' => 'hospital.trace',
            'traceShowRoute' => 'hospital.trace.show',
            'title' => 'Unit audit trail',
            'subtitle' => 'Status history for units your hospital requested, holds, or issued',
        ];
    }
}
