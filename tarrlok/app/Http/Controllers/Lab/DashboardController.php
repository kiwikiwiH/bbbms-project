<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user()->load('hospital');
        $hospital = $user->hospital;

        return view('lab.dashboard', [
            'user' => $user,
            'hospital' => $hospital,
            'availableCount' => $hospital->availableUnitsCount(),
            'recordedByYou' => $hospital->bloodUnits()->where('recorded_by', $user->id)->count(),
            'issuedCount' => $hospital->bloodUnits()->where('status', 'issued')->count(),
        ]);
    }
}
