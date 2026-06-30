<?php

namespace App\Http\Controllers\Lab;

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

        return view('lab.dashboard', [
            'user' => $user,
            'hospital' => $hospital,
            'availableCount' => $hospital->availableUnitsCount(),
            'pendingScreening' => $hospital->bloodUnits()->pendingScreening()->count(),
            'recordedByYou' => $hospital->bloodUnits()->where('recorded_by', $user->id)->count(),
            'issuedCount' => $hospital->partnerUnitsIssuedCount(),
            'expiringSoon' => $hospital->bloodUnits()->where('status', 'available')->expiringSoon()->count(),
            'expiredCount' => $hospital->bloodUnits()->where('status', 'discarded')->whereNotNull('expires_at')->where('expires_at', '<=', now())->count(),
        ]);
    }
}
