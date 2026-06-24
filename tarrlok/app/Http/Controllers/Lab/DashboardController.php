<?php

namespace App\Http\Controllers\Lab;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user()->load('hospital');

        return view('lab.dashboard', [
            'user' => $user,
            'hospital' => $user->hospital,
        ]);
    }
}
