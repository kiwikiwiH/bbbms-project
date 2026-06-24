<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class PlaceholderController extends Controller
{
    public function inventory(): View
    {
        return $this->page('inventory', 'Blood Inventory');
    }

    public function requests(): View
    {
        return $this->page('requests', 'Blood Requests');
    }

    public function partners(): View
    {
        return $this->page('partners', 'Partner Exchange');
    }

    public function facility(): View
    {
        $user = auth()->user()->load('hospital');

        return view('hospital.facility', [
            'user' => $user,
            'hospital' => $user->hospital,
        ]);
    }

    private function page(string $section, string $title): View
    {
        return view('hospital.placeholder', compact('section', 'title'));
    }
}
