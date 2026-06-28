@extends('layouts.tarrlok-lab')

@section('title', 'Lab Overview - Tarrlok')

@section('page_title', 'Lab overview')
@section('page_subtitle')
    Welcome, {{ $user->name }}
@endsection

@section('content')
<div class="hospital-stats" style="margin-bottom:24px;">
    <div class="hospital-stat">
        <div class="hospital-stat-label">Cleared for issue</div>
        <div class="hospital-stat-value">{{ $availableCount }}</div>
        <div class="hospital-stat-note">Passed lab screening</div>
    </div>
    <div class="hospital-stat">
        <div class="hospital-stat-label">Awaiting screening</div>
        <div class="hospital-stat-value">{{ $pendingScreening }}</div>
        <div class="hospital-stat-note">
            @if ($pendingScreening > 0)
                <a href="{{ route('lab.units.index') }}" style="color:#a20513;">Complete reports</a>
            @else
                All units screened
            @endif
        </div>
    </div>
    <div class="hospital-stat">
        <div class="hospital-stat-label">Recorded by you</div>
        <div class="hospital-stat-value">{{ $recordedByYou }}</div>
        <div class="hospital-stat-note"><a href="{{ route('lab.units.index') }}" style="color:#a20513;">View units</a></div>
    </div>
    <div class="hospital-stat">
        <div class="hospital-stat-label">Issued to partners</div>
        <div class="hospital-stat-value">{{ $issuedCount }}</div>
        <div class="hospital-stat-note">Fulfilled by hospital admin</div>
    </div>
    @if ($expiringSoon > 0 || $expiredCount > 0)
        <div class="hospital-stat hospital-stat-warning">
            <div class="hospital-stat-label">Expiry alerts</div>
            <div class="hospital-stat-value">{{ $expiringSoon + $expiredCount }}</div>
            <div class="hospital-stat-note">
                @if ($expiringSoon > 0)
                    {{ $expiringSoon }} expiring soon
                @endif
                @if ($expiredCount > 0)
                    · {{ $expiredCount }} expired
                @endif
            </div>
        </div>
    @endif
</div>

@if ($expiringSoon > 0 || $expiredCount > 0)
    <div class="hospital-card hospital-expiry-alert" style="margin-bottom:20px;">
        <div class="hospital-card-body">
            <p class="hospital-flow-note" style="margin:0;">
                <span class="material-symbols-outlined">event_busy</span>
                <strong>Shelf-life check:</strong>
                @if ($expiringSoon > 0)
                    {{ $expiringSoon }} unit{{ $expiringSoon === 1 ? '' : 's' }} expire within {{ config('tarrlok.expiry_warning_days', 7) }} days.
                @endif
                @if ($expiredCount > 0)
                    {{ $expiredCount }} unit{{ $expiredCount === 1 ? ' is' : 's are' }} past expiry.
                @endif
                <a href="{{ route('lab.units.index') }}" style="color:#a20513;">View units</a>
            </p>
        </div>
    </div>
@endif

<div class="hospital-card" style="margin-bottom:20px;">
    <div class="hospital-card-body">
        <p class="hospital-flow-note" style="margin:0;">
            <span class="material-symbols-outlined">science</span>
            <strong>Your role:</strong> Register each collected unit, complete the <strong>lab screening report</strong> (HIV, Hep B/C, Syphilis), then cleared units enter <strong>{{ $hospital->name }}</strong> inventory for partner requests.
        </p>
    </div>
</div>

<div style="display:flex;gap:12px;flex-wrap:wrap;">
    <a href="{{ route('lab.units.create') }}" class="hospital-btn hospital-btn-primary">
        <span class="material-symbols-outlined">add</span>
        Register blood unit
    </a>
    <a href="{{ route('lab.units.index') }}" class="hospital-btn hospital-btn-outline">
        <span class="material-symbols-outlined">inventory_2</span>
        View inventory
    </a>
</div>
@endsection
