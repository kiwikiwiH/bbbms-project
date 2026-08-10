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
        @php
            $contactName = $contact?->name ?? 'Hospital Administrator';
            $defaultApproveMessage = "Your HeFRA licence and facility details have been verified. You may now sign in and begin using the Tarrlok hospital portal for inventory, lab operations, and partner blood exchange.";
            $defaultRejectReason = "The HeFRA licence ID provided could not be verified against our records.";
        @endphp

        <div class="admin-letter-grid">
            <section class="admin-letter">
                <div class="admin-letter-head approve">
                    <span class="material-symbols-outlined">mark_email_read</span>
                    <div>
                        <strong>Approval email template</strong>
                        <p>Fixed letter text is sent automatically. Edit only the highlighted note if needed.</p>
                    </div>
                </div>
                <div class="admin-letter-body">
                    <p class="admin-letter-fixed">Dear {{ $contactName }},</p>
                    <p class="admin-letter-fixed">
                        We write with reference to the registration of
                        <strong>{{ $hospital->name }}</strong>
                        (HeFRA Licence: <strong>{{ $hospital->license_id }}</strong>)
                        on the Tarrlok blood bank network.
                    </p>
                    <p class="admin-letter-fixed">
                        We are pleased to inform you that your facility registration has been <strong>approved</strong>.
                    </p>

                    <form method="POST" action="{{ route('admin.registrations.approve', $hospital) }}">
                        @csrf
                        <label class="admin-letter-label" for="approve_admin_message">Admin note (editable)</label>
                        <textarea
                            id="approve_admin_message"
                            name="admin_message"
                            class="admin-letter-input"
                            rows="4"
                        >{{ old('admin_message', $defaultApproveMessage) }}</textarea>
                        @error('admin_message')
                            <p class="admin-letter-error">{{ $message }}</p>
                        @enderror

                        <p class="admin-letter-fixed">
                            Your hospital administrator account is now active. A sign-in button will be included in the email.
                        </p>
                        <p class="admin-letter-signoff">Yours faithfully,<br><strong>Tarrlok Platform Administration</strong></p>

                        <button type="submit" class="admin-btn admin-btn-approve">
                            <span class="material-symbols-outlined">check_circle</span>
                            Approve &amp; send email
                        </button>
                    </form>
                </div>
            </section>

            <section class="admin-letter">
                <div class="admin-letter-head reject">
                    <span class="material-symbols-outlined">mail</span>
                    <div>
                        <strong>Rejection email template</strong>
                        <p>Fill in the reason. Optionally add extra notes. Everything else stays official and fixed.</p>
                    </div>
                </div>
                <div class="admin-letter-body">
                    <p class="admin-letter-fixed">Dear {{ $contactName }},</p>
                    <p class="admin-letter-fixed">
                        We write with reference to the registration of
                        <strong>{{ $hospital->name }}</strong>
                        (HeFRA Licence: <strong>{{ $hospital->license_id }}</strong>).
                    </p>
                    <p class="admin-letter-fixed">
                        After review, we regret to inform you that your facility registration has <strong>not been approved</strong> at this time.
                    </p>

                    <form method="POST" action="{{ route('admin.registrations.reject', $hospital) }}">
                        @csrf
                        <label class="admin-letter-label" for="rejection_reason">Reason for decision (required)</label>
                        <textarea
                            id="rejection_reason"
                            name="rejection_reason"
                            class="admin-letter-input admin-letter-input-reason"
                            rows="3"
                            required
                            placeholder="e.g. Invalid or unverifiable HeFRA licence, duplicate facility, incomplete details..."
                        >{{ old('rejection_reason', $defaultRejectReason) }}</textarea>
                        @error('rejection_reason')
                            <p class="admin-letter-error">{{ $message }}</p>
                        @enderror

                        <label class="admin-letter-label" for="reject_admin_message">Additional notes (optional)</label>
                        <textarea
                            id="reject_admin_message"
                            name="admin_message"
                            class="admin-letter-input"
                            rows="3"
                            placeholder="Optional guidance, e.g. documents to re-submit, who to contact..."
                        >{{ old('admin_message') }}</textarea>
                        @error('admin_message')
                            <p class="admin-letter-error">{{ $message }}</p>
                        @enderror

                        <p class="admin-letter-fixed">
                            If you believe this decision was made in error, or can supply corrected documentation, contact Tarrlok support for reconsideration.
                        </p>
                        <p class="admin-letter-signoff">Yours faithfully,<br><strong>Tarrlok Platform Administration</strong></p>

                        <button type="submit" class="admin-btn admin-btn-reject">
                            <span class="material-symbols-outlined">cancel</span>
                            Reject &amp; send email
                        </button>
                    </form>
                </div>
            </section>
        </div>
    @elseif ($hospital->status === 'approved')
        @php
            $contactName = $contact?->name ?? 'Hospital Administrator';
            $defaultRevokeReason = 'Network access has been revoked by Tarrlok platform administration following a compliance or operational review.';
        @endphp

        <div class="admin-actions" style="margin-bottom:16px;">
            <p style="margin:0;font-size:14px;color:#166534;">This facility is approved. The administrator can sign in and appear on partner exchange.</p>
        </div>

        <section class="admin-letter">
            <div class="admin-letter-head reject">
                <span class="material-symbols-outlined">person_off</span>
                <div>
                    <strong>Revoke network access</strong>
                    <p>Removes the facility from the live network. Staff cannot sign in. Historical units and audit records are kept.</p>
                </div>
            </div>
            <div class="admin-letter-body">
                <p class="admin-letter-fixed">Dear {{ $contactName }},</p>
                <p class="admin-letter-fixed">
                    Platform administration has decided to <strong>revoke network access</strong> for
                    <strong>{{ $hospital->name }}</strong>
                    (HeFRA Licence: <strong>{{ $hospital->license_id }}</strong>).
                </p>

                <form method="POST" action="{{ route('admin.registrations.revoke', $hospital) }}" onsubmit="return confirm('Revoke network access for {{ $hospital->name }}? Staff will be signed out of hospital and lab portals.');">
                    @csrf

                    <label class="admin-letter-label" for="revoke_reason">Reason for revocation (required)</label>
                    <textarea
                        class="admin-letter-input admin-letter-input-reason"
                        id="revoke_reason"
                        name="rejection_reason"
                        rows="4"
                        required
                        minlength="10"
                        maxlength="1000"
                    >{{ old('rejection_reason', $defaultRevokeReason) }}</textarea>
                    @error('rejection_reason')
                        <p class="admin-letter-error">{{ $message }}</p>
                    @enderror

                    <label class="admin-letter-label" for="revoke_admin_message">Additional notes (optional)</label>
                    <textarea
                        class="admin-letter-input"
                        id="revoke_admin_message"
                        name="admin_message"
                        rows="3"
                        maxlength="2000"
                    >{{ old('admin_message') }}</textarea>
                    @error('admin_message')
                        <p class="admin-letter-error">{{ $message }}</p>
                    @enderror

                    <p class="admin-letter-fixed">
                        Open partner blood requests involving this facility will be closed. Inventory history remains for audit.
                    </p>
                    <p class="admin-letter-signoff">Yours faithfully,<br><strong>Tarrlok Platform Administration</strong></p>

                    <button type="submit" class="admin-btn admin-btn-reject">
                        <span class="material-symbols-outlined">person_off</span>
                        Revoke access &amp; send email
                    </button>
                </form>
            </div>
        </section>
    @endif
</div>
@endsection
