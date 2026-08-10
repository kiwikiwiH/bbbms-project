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
        $componentType = trim((string) $request->query('component_type', ''));
        $screening = trim((string) $request->query('screening', ''));
        $allowedGroups = config('tarrlok.blood_groups');
        $allowedComponents = config('tarrlok.component_types');

        if ($bloodGroup !== '' && ! in_array($bloodGroup, $allowedGroups, true)) {
            $bloodGroup = '';
        }

        if ($componentType !== '' && ! array_key_exists($componentType, $allowedComponents)) {
            $componentType = '';
        }

        if (! in_array($screening, ['', 'pending', 'cleared', 'failed'], true)) {
            $screening = '';
        }

        $unitsQuery = $hospital->bloodUnits()
            ->with(['recorder', 'screener'])
            ->when($bloodGroup !== '', fn ($q) => $q->where('blood_group', $bloodGroup))
            ->when($componentType !== '', fn ($q) => $q->where('component_type', $componentType))
            ->when($screening !== '', fn ($q) => $q->where('screening_status', $screening))
            ->latest('collected_at');

        $units = $unitsQuery->get()->groupBy('blood_group');

        $availableByGroup = $hospital->bloodUnits()
            ->available()
            ->when($componentType !== '', fn ($q) => $q->where('component_type', $componentType))
            ->selectRaw('blood_group, count(*) as total')
            ->groupBy('blood_group')
            ->pluck('total', 'blood_group');

        return view('hospital.inventory.index', [
            'hospital' => $hospital,
            'units' => $units,
            'availableByGroup' => $availableByGroup,
            'bloodGroup' => $bloodGroup,
            'componentType' => $componentType,
            'screening' => $screening,
            'bloodGroups' => $allowedGroups,
            'componentTypes' => $allowedComponents,
            'expiringSoon' => $hospital->bloodUnits()
                ->where('status', 'available')
                ->when($bloodGroup !== '', fn ($q) => $q->where('blood_group', $bloodGroup))
                ->when($componentType !== '', fn ($q) => $q->where('component_type', $componentType))
                ->expiringSoon()
                ->get(),
        ]);
    }
}
