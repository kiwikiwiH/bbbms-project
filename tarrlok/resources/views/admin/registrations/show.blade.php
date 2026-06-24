@extends('layouts.tarrlok-admin')

@section('title', $hospital->name.' - Tarrlok Admin')

@section('content')
@php $contact = $hospital->primaryContact(); @endphp

<div style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;margin-bottom:8px;">
    <div>
        <h1 class="admin-heading" style="margin-bottom:4px;">{{ $hospital->name }}</h1>
        <p class="admin-subheading" style="margin:0;">
            Submitted {{ $hospital->created_at->format('F j, Y \a\t g:i A') }}
            · <span class="admin-badge {{ $hospital->status }}">{{ $hospital->status }}</span>
        </p>
    </div>
    <a href="{{ route('admin.registrations.index', ['status' => $hospital->status]) }}" class="admin-btn admin-btn-outline">Back to list</a>
</div>

<div class="admin-card">
    <div class="admin-detail-grid">
        <section class="admin-detail-section">
            <h3>Facility Details</h3>
            <dl class="admin-detail-list">
                <div>
                    <dt>Institution Type</dt>
                    <dd>{{ $hospital->typeLabel() }}</dd>
                </div>
                <div>
                    <dt>Region</dt>
                    <dd>{{ $hospital->regionLabel() }}</dd>
                </div>
                <div>
                    <dt>City / District</dt>
                    <dd>{{ $hospital->city }}</dd>
                </div>
                <div>
                    <dt>HeFRA License ID</dt>
                    <dd>{{ $hospital->license_id }}</dd>
                </div>
                <div>
                    <dt>Official Phone</dt>
                    <dd>{{ $hospital->phone }}</dd>
                </div>
                <div>
                    <dt>Official Email</dt>
                    <dd>{{ $hospital->email }}</dd>
                </div>
            </dl>
        </section>

        <section class="admin-detail-section">
            <h3>Administrator Account</h3>
            @if ($contact)
                <dl class="admin-detail-list">
                    <div>
                        <dt>Full Name</dt>
                        <dd>{{ $contact->name }}</dd>
                    </div>
                    <div>
                        <dt>Job Title</dt>
                        <dd>{{ $contact->job_title }}</dd>
                    </div>
                    <div>
                        <dt>Work Email</dt>
                        <dd>{{ $contact->email }}</dd>
                    </div>
                    <div>
                        <dt>Account Status</dt>
                        <dd>{{ $contact->status }}</dd>
                    </div>
                </dl>
            @else
                <p style="color:#555f6f;font-size:14px;">No administrator account linked.</p>
            @endif
        </section>
    </div>

    @if ($hospital->status === 'rejected' && $hospital->rejection_reason)
        <div class="admin-meta">
            <strong>Rejection reason:</strong> {{ $hospital->rejection_reason }}
        </div>
    @endif

    @if ($hospital->reviewed_at)
        <div class="admin-meta">
            Reviewed {{ $hospital->reviewed_at->format('M j, Y g:i A') }}
            @if ($hospital->reviewer)
                by {{ $hospital->reviewer->name }}
            @endif
        </div>
    @endif

    @if ($hospital->status === 'pending')
        <div class="admin-actions">
            <form method="POST" action="{{ route('admin.registrations.approve', $hospital) }}">
                @csrf
                <button type="submit" class="admin-btn admin-btn-approve">
                    <span class="material-symbols-outlined">check_circle</span>
                    Approve Facility
                </button>
            </form>

            <div class="admin-reject-form">
                <form method="POST" action="{{ route('admin.registrations.reject', $hospital) }}">
                    @csrf
                    <label for="rejection_reason" style="display:block;font-size:13px;font-weight:600;margin-bottom:8px;color:#5b403d;">Rejection reason (required if rejecting)</label>
                    <textarea
                        id="rejection_reason"
                        name="rejection_reason"
                        placeholder="Explain why this registration cannot be approved (e.g. invalid HeFRA license, duplicate facility)..."
                        required
                    >{{ old('rejection_reason') }}</textarea>
                    @error('rejection_reason')
                        <p style="color:#93000a;font-size:13px;margin:8px 0 0;">{{ $message }}</p>
                    @enderror
                    <button type="submit" class="admin-btn admin-btn-reject" style="margin-top:12px;">
                        <span class="material-symbols-outlined">cancel</span>
                        Reject Registration
                    </button>
                </form>
            </div>
        </div>
    @elseif ($hospital->status === 'approved')
        <div class="admin-actions">
            <p style="margin:0;font-size:14px;color:#166534;">This facility is approved. The administrator can sign in.</p>
        </div>
    @endif
</div>
@endsection
