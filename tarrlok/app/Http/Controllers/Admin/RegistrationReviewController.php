<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hospital;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RegistrationReviewController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'pending');

        if (! in_array($status, ['pending', 'approved', 'rejected', 'all'], true)) {
            $status = 'pending';
        }

        $query = Hospital::query()
            ->with(['users', 'reviewer'])
            ->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $hospitals = $query->paginate(15)->withQueryString();

        return view('admin.registrations.index', compact('hospitals', 'status'));
    }

    public function show(Hospital $hospital): View
    {
        $hospital->load(['users', 'reviewer']);

        return view('admin.registrations.show', compact('hospital'));
    }

    public function approve(Hospital $hospital): RedirectResponse
    {
        if ($hospital->status === 'approved') {
            return back()->with('status', 'This facility is already approved.');
        }

        DB::transaction(function () use ($hospital) {
            $hospital->update([
                'status' => 'approved',
                'rejection_reason' => null,
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
            ]);

            $hospital->users()->update(['status' => 'active']);
        });

        return redirect()
            ->route('admin.registrations.show', $hospital)
            ->with('status', 'Facility approved. The administrator can now sign in.');
    }

    public function reject(Request $request, Hospital $hospital): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);

        if ($hospital->status === 'rejected') {
            return back()->with('status', 'This facility is already rejected.');
        }

        DB::transaction(function () use ($hospital, $validated) {
            $hospital->update([
                'status' => 'rejected',
                'rejection_reason' => $validated['rejection_reason'],
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
            ]);

            $hospital->users()->update(['status' => 'pending']);
        });

        return redirect()
            ->route('admin.registrations.show', $hospital)
            ->with('status', 'Facility registration rejected.');
    }
}
