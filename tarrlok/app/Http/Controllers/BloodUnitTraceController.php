<?php

namespace App\Http\Controllers;

use App\Models\BloodUnit;
use App\Services\BlockchainIntegrityService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BloodUnitTraceController extends Controller
{
    public function index(Request $request, BlockchainIntegrityService $integrity): View
    {
        $query = trim((string) $request->query('q', ''));
        $unit = null;

        if ($query !== '') {
            $unit = BloodUnit::query()
                ->with([
                    'hospital',
                    'recorder',
                    'screener',
                    'bloodRequests.requestingHospital',
                    'bloodRequests.fulfillingHospital',
                ])
                ->where('unit_code', $query)
                ->first();
        }

        return view('shared.trace.index', [
            'query' => $query,
            'unit' => $unit,
            'integrity' => $unit ? $integrity->verify($unit) : null,
            'screeningTests' => config('tarrlok.screening_tests'),
            'portal' => $this->portalContext(),
        ]);
    }

    public function show(BloodUnit $bloodUnit, BlockchainIntegrityService $integrity): View
    {
        $bloodUnit->load([
            'hospital',
            'recorder',
            'screener',
            'bloodRequests.requestingHospital',
            'bloodRequests.fulfillingHospital',
        ]);

        return view('shared.trace.index', [
            'query' => $bloodUnit->unit_code,
            'unit' => $bloodUnit,
            'integrity' => $integrity->verify($bloodUnit),
            'screeningTests' => config('tarrlok.screening_tests'),
            'portal' => $this->portalContext(),
        ]);
    }

    private function portalContext(): array
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return [
                'layout' => 'layouts.tarrlok-admin',
                'dashboardRoute' => 'admin.dashboard',
                'traceRoute' => 'admin.trace',
                'traceShowRoute' => 'admin.trace.show',
            ];
        }

        if ($user->isLab()) {
            return [
                'layout' => 'layouts.tarrlok-lab',
                'dashboardRoute' => 'lab.dashboard',
                'traceRoute' => 'lab.trace',
                'traceShowRoute' => 'lab.trace.show',
            ];
        }

        return [
            'layout' => 'layouts.tarrlok-hospital',
            'dashboardRoute' => 'hospital.dashboard',
            'traceRoute' => 'hospital.trace',
            'traceShowRoute' => 'hospital.trace.show',
        ];
    }
}
