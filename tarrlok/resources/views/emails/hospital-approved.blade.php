@extends('emails.layout')

@section('title', 'Facility approved — Tarrlok')
@section('eyebrow', 'Registration Decision · Approved')

@section('content')
    <p style="margin:0 0 16px;">Dear {{ $contactName }},</p>

    <p style="margin:0 0 16px;">
        We write with reference to the registration of
        <strong>{{ $hospital->name }}</strong>
        (HeFRA Licence: <strong>{{ $hospital->license_id }}</strong>)
        on the Tarrlok blood bank network.
    </p>

    <p style="margin:0 0 16px;">
        We are pleased to inform you that your facility registration has been
        <strong style="color:#166534;">approved</strong>.
    </p>

    @if ($adminMessage)
        <div style="margin:0 0 16px;padding:14px 16px;background:#f0fdf4;border-left:4px solid #166534;color:#14532d;">
            {!! nl2br(e($adminMessage)) !!}
        </div>
    @endif

    <p style="margin:0 0 16px;">
        Your hospital administrator account is now active. You may sign in and begin managing inventory, lab staff, and partner blood requests.
    </p>

    <p style="margin:24px 0;">
        <a href="{{ $loginUrl }}" style="display:inline-block;background:#a20513;color:#ffffff;text-decoration:none;padding:12px 20px;border-radius:4px;font-weight:700;font-family:Arial,Helvetica,sans-serif;">
            Sign in to Tarrlok
        </a>
    </p>

    <p style="margin:0;font-size:13px;color:#6b7280;">
        Facility: {{ $hospital->name }} · {{ $hospital->city }}, {{ $hospital->regionLabel() }}
    </p>
@endsection
