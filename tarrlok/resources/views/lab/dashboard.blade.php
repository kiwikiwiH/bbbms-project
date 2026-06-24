@extends('layouts.tarrlok-lab')

@section('title', 'Lab Overview - Tarrlok')

@section('page_title', 'Lab overview')
@section('page_subtitle')
    Welcome, {{ $user->name }}
@endsection

@section('content')
<div class="hospital-stats" style="margin-bottom:24px;">
    <div class="hospital-stat">
        <div class="hospital-stat-label">Available at facility</div>
        <div class="hospital-stat-value">{{ $availableCount }}</div>
        <div class="hospital-stat-note">Ready for hospital requests</div>
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
</div>

<div class="hospital-card" style="margin-bottom:20px;">
    <div class="hospital-card-body">
        <p class="hospital-flow-note" style="margin:0;">
            <span class="material-symbols-outlined">science</span>
            <strong>Your role:</strong> After a donor gives blood and it passes testing, you <strong>register each unit</strong> here. That adds stock to <strong>{{ $hospital->name }}</strong> inventory so the hospital administrator can fulfill partner requests.
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
