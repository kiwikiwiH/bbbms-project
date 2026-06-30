<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use App\Services\ExpiryService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(ExpiryService $expiry): View
    {
        $expiry->discardExpiredUnits();

        $user = auth()->user()->load('hospital');
        $hospital = $user->hospital;

        return view('hospital.dashboard', [
            'user' => $user,
            'hospital' => $hospital,
            'labStaffCount' => $hospital->labStaff()->count(),
            'unitsOnHand' => $hospital->availableUnitsCount(),
            'pendingRequests' => $hospital->incomingBloodRequests()->whereIn('status', ['pending', 'approved'])->count(),
            'expiringSoon' => $hospital->bloodUnits()->where('status', 'available')->expiringSoon()->count(),
            'expiredCount' => $hospital->bloodUnits()
                ->where('status', 'discarded')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now())
                ->count(),
        ]);
    }
}
