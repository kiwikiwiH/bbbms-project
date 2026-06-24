<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LabStaffController extends Controller
{
    public function index(): View
    {
        $hospital = auth()->user()->hospital;
        $labStaff = $hospital->labStaff()->latest()->get();

        return view('hospital.lab-staff.index', compact('hospital', 'labStaff'));
    }

    public function create(): View
    {
        return view('hospital.lab-staff.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'job_title' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        auth()->user()->hospital->users()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'job_title' => $validated['job_title'],
            'role' => 'lab',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        return redirect()
            ->route('hospital.lab-staff.index')
            ->with('status', 'Lab staff account created. They can sign in with the credentials you provided.');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        $this->ensureLabStaff($user);

        $newStatus = $user->status === 'active' ? 'suspended' : 'active';
        $user->update(['status' => $newStatus]);

        $message = $newStatus === 'active'
            ? 'Lab staff account reactivated.'
            : 'Lab staff account suspended.';

        return back()->with('status', $message);
    }

    private function ensureLabStaff(User $user): void
    {
        abort_unless(
            $user->role === 'lab' && $user->hospital_id === auth()->user()->hospital_id,
            403
        );
    }
}
