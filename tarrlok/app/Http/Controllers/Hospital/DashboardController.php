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

        $availableByGroup = $hospital->bloodUnits()
            ->available()
            ->selectRaw('blood_group, count(*) as total')
            ->groupBy('blood_group')
            ->pluck('total', 'blood_group');

        $screeningBreakdown = [
            'pending' => $hospital->bloodUnits()->where('screening_status', 'pending')->count(),
            'cleared' => $hospital->bloodUnits()->where('screening_status', 'cleared')->count(),
            'failed' => $hospital->bloodUnits()->where('screening_status', 'failed')->count(),
        ];

        $requestStatusCounts = [
            'pending' => $hospital->incomingBloodRequests()->where('status', 'pending')->count(),
            'approved' => $hospital->incomingBloodRequests()->where('status', 'approved')->count(),
            'fulfilled' => $hospital->incomingBloodRequests()->where('status', 'fulfilled')->count(),
            'rejected' => $hospital->incomingBloodRequests()->where('status', 'rejected')->count(),
        ];

        $incomingActionable = $hospital->incomingBloodRequests()
            ->whereIn('status', ['pending', 'approved'])
            ->get();

        $insufficientFlags = $incomingActionable
            ->filter(fn ($req) => ! $req->hasSufficientStockAt($hospital))
            ->count();

        $stockMax = max(1, (int) ($availableByGroup->max() ?: 0));
        $requestMax = max(1, max($requestStatusCounts));
        $screeningMax = max(1, max($screeningBreakdown));

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
            'availableByGroup' => $availableByGroup,
            'screeningBreakdown' => $screeningBreakdown,
            'requestStatusCounts' => $requestStatusCounts,
            'insufficientFlags' => $insufficientFlags,
            'stockMax' => $stockMax,
            'requestMax' => $requestMax,
            'screeningMax' => $screeningMax,
        ]);
    }
}
