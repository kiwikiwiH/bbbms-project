<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PlaceholderController extends Controller
{
    public function facility(): View
    {
        $user = auth()->user()->load('hospital');

        return view('hospital.facility', [
            'user' => $user,
            'hospital' => $user->hospital,
        ]);
    }
}
