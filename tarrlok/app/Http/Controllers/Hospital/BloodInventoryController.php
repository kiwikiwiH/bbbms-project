<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use App\Services\ExpiryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BloodInventoryController extends Controller
{
    public function index(Request $request, ExpiryService $expiry): View
    {
        $expiry->discardExpiredUnits();

        $hospital = auth()->user()->hospital;
        $bloodGroup = trim((string) $request->query('blood_group', ''));
        $screening = trim((string) $request->query('screening', ''));
        $allowedGroups = config('tarrlok.blood_groups');

        if ($bloodGroup !== '' && ! in_array($bloodGroup, $allowedGroups, true)) {
            $bloodGroup = '';
        }

        if (! in_array($screening, ['', 'pending', 'cleared', 'failed'], true)) {
            $screening = '';
        }

        $unitsQuery = $hospital->bloodUnits()
            ->with(['recorder', 'screener'])
            ->when($bloodGroup !== '', fn ($q) => $q->where('blood_group', $bloodGroup))
            ->when($screening !== '', fn ($q) => $q->where('screening_status', $screening))
            ->latest('collected_at');

        $units = $unitsQuery->get()->groupBy('blood_group');

        $availableByGroup = $hospital->bloodUnits()
            ->available()
            ->selectRaw('blood_group, count(*) as total')
            ->groupBy('blood_group')
            ->pluck('total', 'blood_group');

        return view('hospital.inventory.index', [
            'hospital' => $hospital,
            'units' => $units,
            'availableByGroup' => $availableByGroup,
            'bloodGroup' => $bloodGroup,
            'screening' => $screening,
            'bloodGroups' => $allowedGroups,
            'expiringSoon' => $hospital->bloodUnits()
                ->where('status', 'available')
                ->when($bloodGroup !== '', fn ($q) => $q->where('blood_group', $bloodGroup))
                ->expiringSoon()
                ->get(),
        ]);
    }
}
