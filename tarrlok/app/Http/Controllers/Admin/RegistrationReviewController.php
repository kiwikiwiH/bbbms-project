<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BloodRequest;
use App\Models\Hospital;
use App\Notifications\HospitalAccessRevoked;
use App\Notifications\HospitalRegistrationApproved;
use App\Notifications\HospitalRegistrationRejected;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
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

    public function approve(Request $request, Hospital $hospital): RedirectResponse
    {
        if ($hospital->status === 'approved') {
            return back()->with('status', 'This facility is already approved.');
        }

        $validated = $request->validate([
            'admin_message' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($hospital) {
            $hospital->update([
                'status' => 'approved',
                'rejection_reason' => null,
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
            ]);

            $hospital->users()->update(['status' => 'active']);
        });

        $hospital->load('users');
        $contact = $hospital->primaryContact();
        if ($contact) {
            Notification::send(
                $contact,
                new HospitalRegistrationApproved(
                    $hospital,
                    $validated['admin_message'] ?? null,
                )
            );
        }

        return redirect()
            ->route('admin.registrations.show', $hospital)
            ->with('status', 'Facility approved. An official email has been sent to the hospital administrator.');
    }

    public function reject(Request $request, Hospital $hospital): RedirectResponse
    {
        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:10', 'max:1000'],
            'admin_message' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($hospital->status !== 'pending') {
            return back()->with('status', 'Only pending registrations can be rejected. Use Revoke access for an approved facility.');
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

        $hospital->load('users');
        $contact = $hospital->primaryContact();
        if ($contact) {
            Notification::send(
                $contact,
                new HospitalRegistrationRejected(
                    $hospital,
                    $validated['admin_message'] ?? null,
                )
            );
        }

        return redirect()
            ->route('admin.registrations.show', $hospital)
            ->with('status', 'Facility registration rejected. An official email has been sent to the hospital administrator.');
    }

    public function revoke(Request $request, Hospital $hospital): RedirectResponse
    {
        if ($hospital->status !== 'approved') {
            return back()->with('status', 'Only approved facilities can have network access revoked.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'min:10', 'max:1000'],
            'admin_message' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($hospital, $validated) {
            $hospital->update([
                'status' => 'rejected',
                'rejection_reason' => $validated['rejection_reason'],
                'reviewed_at' => now(),
                'reviewed_by' => auth()->id(),
            ]);

            $hospital->users()->update(['status' => 'suspended']);

            BloodRequest::query()
                ->where(function ($query) use ($hospital) {
                    $query->where('fulfilling_hospital_id', $hospital->id)
                        ->orWhere('requesting_hospital_id', $hospital->id);
                })
                ->whereIn('status', ['pending', 'approved'])
                ->update([
                    'status' => 'rejected',
                    'rejection_reason' => 'Closed because fulfilling/requesting facility access was revoked by platform admin.',
                    'rejected_by' => auth()->id(),
                    'rejected_at' => now(),
                ]);
        });

        $hospital->load('users');
        $contact = $hospital->primaryContact();
        if ($contact) {
            Notification::send(
                $contact,
                new HospitalAccessRevoked(
                    $hospital,
                    $validated['admin_message'] ?? null,
                )
            );
        }

        return redirect()
            ->route('admin.registrations.show', $hospital)
            ->with('status', 'Network access revoked. Staff can no longer sign in; open partner requests were closed; an email was sent to the hospital administrator.');
    }
}
