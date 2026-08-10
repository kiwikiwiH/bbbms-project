<?php

namespace App\Http\Controllers;

use App\Models\BloodUnit;
use App\Services\BlockchainIntegrityService;
use App\Services\BlockchainLedgerService;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BloodUnitTraceController extends Controller
{
    public function index(
        Request $request,
        BlockchainIntegrityService $integrity,
        BlockchainLedgerService $ledger,
        QrCodeService $qr
    ): View {
        $query = strtoupper(trim((string) $request->query('q', '')));
        $unit = null;
        $chainHistory = null;
        $trackUrl = null;
        $qrDataUri = null;

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

            if ($unit && $this->canViewUnit($unit)) {
                $chainHistory = $ledger->historyForUnit($unit->unit_code);
                $trackUrl = route('track.show', $unit, absolute: true);
                $qrDataUri = $qr->pngDataUri($trackUrl, 148);
            } elseif ($unit && ! $this->canViewUnit($unit)) {
                $unit = null;
            }
        }

        return view('shared.trace.index', [
            'query' => $query,
            'unit' => $unit,
            'integrity' => $unit ? $integrity->verify($unit) : null,
            'chainHistory' => $chainHistory,
            'trackUrl' => $trackUrl,
            'qrDataUri' => $qrDataUri,
            'screeningTests' => config('tarrlok.screening_tests'),
            'portal' => $this->portalContext(),
        ]);
    }

    public function show(
        BloodUnit $bloodUnit,
        BlockchainIntegrityService $integrity,
        BlockchainLedgerService $ledger,
        QrCodeService $qr
    ): View {
        abort_unless($this->canViewUnit($bloodUnit), 404);

        $bloodUnit->load([
            'hospital',
            'recorder',
            'screener',
            'bloodRequests.requestingHospital',
            'bloodRequests.fulfillingHospital',
        ]);

        $trackUrl = route('track.show', $bloodUnit, absolute: true);

        return view('shared.trace.index', [
            'query' => $bloodUnit->unit_code,
            'unit' => $bloodUnit,
            'integrity' => $integrity->verify($bloodUnit),
            'chainHistory' => $ledger->historyForUnit($bloodUnit->unit_code),
            'trackUrl' => $trackUrl,
            'qrDataUri' => $qr->pngDataUri($trackUrl, 148),
            'screeningTests' => config('tarrlok.screening_tests'),
            'portal' => $this->portalContext(),
        ]);
    }

    private function canViewUnit(BloodUnit $unit): bool
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return true;
        }

        if (! $user->hospital_id) {
            return false;
        }

        if ((int) $unit->hospital_id === (int) $user->hospital_id) {
            return true;
        }

        if ($user->isLab()) {
            return false;
        }

        return $unit->bloodRequests()
            ->where(function ($q) use ($user) {
                $q->where('requesting_hospital_id', $user->hospital_id)
                    ->orWhere('fulfilling_hospital_id', $user->hospital_id);
            })
            ->exists();
    }

    /**
     * @return array{layout: string, dashboardRoute: string, traceRoute: string, traceShowRoute: string}
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
