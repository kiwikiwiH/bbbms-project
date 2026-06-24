<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class HospitalRegistrationController extends Controller
{
    private const SESSION_KEY = 'hospital_registration';

    public function step1(): View
    {
        $data = session(self::SESSION_KEY.'.facility', []);

        return view('auth.register.step1', [
            'facility' => $data,
            'institutionTypes' => config('tarrlok.institution_types'),
            'regions' => config('tarrlok.ghana_regions'),
        ]);
    }

    public function storeStep1(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:'.implode(',', array_keys(config('tarrlok.institution_types')))],
            'region' => ['required', 'string', 'in:'.implode(',', array_keys(config('tarrlok.ghana_regions')))],
            'city' => ['required', 'string', 'max:255'],
            'license_id' => ['required', 'string', 'max:50', 'regex:/^HFRA-[A-Z]{2,5}-\d{4,}$/i', 'unique:hospitals,license_id'],
            'phone_local' => ['required', 'string', 'regex:/^\d{9}$/'],
            'email' => ['required', 'string', 'email', 'max:255'],
        ], [
            'license_id.regex' => 'HeFRA License ID must follow the format HFRA-XXX-1234.',
            'phone_local.regex' => 'Enter a valid 9-digit Ghana mobile number (without +233).',
        ]);

        $phone = '+233'.ltrim($validated['phone_local'], '0');

        session([
            self::SESSION_KEY.'.facility' => [
                'name' => $validated['name'],
                'type' => $validated['type'],
                'region' => $validated['region'],
                'city' => $validated['city'],
                'license_id' => strtoupper($validated['license_id']),
                'phone' => $phone,
                'email' => $validated['email'],
            ],
        ]);

        return redirect()->route('register.step2');
    }

    public function step2(): View|RedirectResponse
    {
        if (! session()->has(self::SESSION_KEY.'.facility')) {
            return redirect()->route('register')->withErrors(['session' => 'Please complete facility details first.']);
        }

        return view('auth.register.step2', [
            'account' => session(self::SESSION_KEY.'.account', []),
        ]);
    }

    public function storeStep2(Request $request): RedirectResponse
    {
        if (! session()->has(self::SESSION_KEY.'.facility')) {
            return redirect()->route('register');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'job_title' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        session([
            self::SESSION_KEY.'.account' => $validated,
        ]);

        return redirect()->route('register.review');
    }

    public function review(): View|RedirectResponse
    {
        $facility = session(self::SESSION_KEY.'.facility');
        $account = session(self::SESSION_KEY.'.account');

        if (! $facility || ! $account) {
            return redirect()->route('register');
        }

        return view('auth.register.review', [
            'facility' => $facility,
            'account' => $account,
            'institutionTypes' => config('tarrlok.institution_types'),
            'regions' => config('tarrlok.ghana_regions'),
        ]);
    }

    public function submit(): RedirectResponse
    {
        $facility = session(self::SESSION_KEY.'.facility');
        $account = session(self::SESSION_KEY.'.account');

        if (! $facility || ! $account) {
            return redirect()->route('register');
        }

        DB::transaction(function () use ($facility, $account) {
            $hospital = Hospital::create([
                'name' => $facility['name'],
                'type' => $facility['type'],
                'region' => $facility['region'],
                'city' => $facility['city'],
                'license_id' => $facility['license_id'],
                'phone' => $facility['phone'],
                'email' => $facility['email'],
                'status' => 'pending',
            ]);

            User::create([
                'name' => $account['name'],
                'email' => $account['email'],
                'password' => Hash::make($account['password']),
                'hospital_id' => $hospital->id,
                'job_title' => $account['job_title'],
                'role' => 'hospital',
                'status' => 'pending',
            ]);
        });

        session()->forget(self::SESSION_KEY);

        return redirect()->route('register.pending');
    }

    public function pending(): View
    {
        return view('auth.register.pending');
    }
}
