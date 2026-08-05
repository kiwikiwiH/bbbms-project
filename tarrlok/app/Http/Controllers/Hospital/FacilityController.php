<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FacilityController extends Controller
{
    public function show(): View
    {
        $user = auth()->user()->load('hospital');

        return view('hospital.facility', [
            'user' => $user,
            'hospital' => $user->hospital,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $hospital = auth()->user()->hospital;

        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'region' => ['required', 'string', Rule::in(array_keys(config('tarrlok.ghana_regions', [])))],
        ]);

        $hospital->update($validated);

        return back()->with('status', 'Facility contact details updated.');
    }
}
