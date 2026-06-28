<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class BloodInventoryController extends Controller
{
    public function index(): View
    {
        $hospital = auth()->user()->hospital;

        $units = $hospital->bloodUnits()
            ->with(['recorder', 'screener'])
            ->latest('collected_at')
            ->get()
            ->groupBy('blood_group');

        $availableByGroup = $hospital->bloodUnits()
            ->available()
            ->selectRaw('blood_group, count(*) as total')
            ->groupBy('blood_group')
            ->pluck('total', 'blood_group');

        return view('hospital.inventory.index', [
            'hospital' => $hospital,
            'units' => $units,
            'availableByGroup' => $availableByGroup,
            'expiringSoon' => $hospital->bloodUnits()->where('status', 'available')->expiringSoon()->get(),
        ]);
    }
}
