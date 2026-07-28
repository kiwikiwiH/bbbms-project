@extends('emails.layout')

@section('title', 'Registration update — Tarrlok')
@section('eyebrow', 'Registration Decision · Not Approved')

@section('content')
    <p style="margin:0 0 16px;">Dear {{ $contactName }},</p>

    <p style="margin:0 0 16px;">
        We write with reference to the registration of
        <strong>{{ $hospital->name }}</strong>
        (HeFRA Licence: <strong>{{ $hospital->license_id }}</strong>)
        on the Tarrlok blood bank network.
    </p>

    <p style="margin:0 0 16px;">
        After review, we regret to inform you that your facility registration has
        <strong style="color:#93000a;">not been approved</strong> at this time.
    </p>

    <p style="margin:0 0 8px;font-weight:700;color:#111827;">Reason for decision</p>
    <div style="margin:0 0 16px;padding:14px 16px;background:#fff5f5;border-left:4px solid #a20513;color:#7f1d1d;">
        {!! nl2br(e($rejectionReason)) !!}
    </div>

    @if ($adminMessage)
        <p style="margin:0 0 8px;font-weight:700;color:#111827;">Additional notes</p>
        <div style="margin:0 0 16px;padding:14px 16px;background:#f9fafb;border-left:4px solid #9ca3af;color:#374151;">
            {!! nl2br(e($adminMessage)) !!}
        </div>
    @endif

    <p style="margin:0 0 16px;">
        If you believe this decision was made in error, or if you can supply corrected documentation, please contact Tarrlok support with your licence details for reconsideration.
    </p>

    <p style="margin:0;font-size:13px;color:#6b7280;">
        Facility: {{ $hospital->name }} · {{ $hospital->city }}, {{ $hospital->regionLabel() }}
    </p>
@endsection
