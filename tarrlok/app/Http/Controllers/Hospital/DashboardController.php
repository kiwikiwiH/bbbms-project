<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user()->load('hospital');
        $hospital = $user->hospital;

        return view('hospital.dashboard', [
            'user' => $user,
            'hospital' => $hospital,
            'labStaffCount' => $hospital->labStaff()->count(),
            'unitsOnHand' => $hospital->availableUnitsCount(),
            'pendingRequests' => $hospital->incomingBloodRequests()->whereIn('status', ['pending', 'approved'])->count(),
            'expiringSoon' => $hospital->bloodUnits()->where('status', 'available')->expiringSoon()->count(),
            'expiredCount' => $hospital->bloodUnits()->where('status', 'available')->expired()->count(),
        ]);
    }
}
