<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $pending = Hospital::query()
            ->with('users')
            ->where('status', 'pending')
            ->latest()
            ->get();

        $counts = [
            'pending' => Hospital::where('status', 'pending')->count(),
            'approved' => Hospital::where('status', 'approved')->count(),
            'rejected' => Hospital::where('status', 'rejected')->count(),
        ];

        return view('admin.dashboard', compact('pending', 'counts'));
    }
}
