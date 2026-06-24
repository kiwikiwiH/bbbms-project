@extends('layouts.tarrlok-hospital')

@section('title', 'Overview - '.$hospital->name)

@section('page_title', 'Overview')
@section('page_subtitle', 'Welcome back, '.$user->name)

@section('content')
<div class="hospital-stats">
    <div class="hospital-stat">
        <div class="hospital-stat-label">Units on hand</div>
        <div class="hospital-stat-value">—</div>
        <div class="hospital-stat-note">Inventory module coming soon</div>
    </div>
    <div class="hospital-stat">
        <div class="hospital-stat-label">Pending requests</div>
        <div class="hospital-stat-value">—</div>
        <div class="hospital-stat-note">Request blood from partner facilities</div>
    </div>
    <div class="hospital-stat">
        <div class="hospital-stat-label">Lab staff accounts</div>
        <div class="hospital-stat-value">{{ $labStaffCount }}</div>
        <div class="hospital-stat-note"><a href="{{ route('hospital.lab-staff.index') }}" style="color:#a20513;">Manage lab staff</a></div>
    </div>
    <div class="hospital-stat">
        <div class="hospital-stat-label">HeFRA license</div>
        <div class="hospital-stat-value" style="font-size:16px;">{{ $hospital->license_id }}</div>
        <div class="hospital-stat-note">Verified by Tarrlok</div>
    </div>
</div>

<div class="hospital-card">
    <div class="hospital-card-head">
        <h2 class="hospital-card-title">Your facility</h2>
    </div>
    <div class="hospital-card-body">
        <dl class="hospital-detail-grid">
            <div class="hospital-detail-item">
                <dt>Facility name</dt>
                <dd>{{ $hospital->name }}</dd>
            </div>
            <div class="hospital-detail-item">
                <dt>Institution type</dt>
                <dd>{{ $hospital->typeLabel() }}</dd>
            </div>
            <div class="hospital-detail-item">
                <dt>Region</dt>
                <dd>{{ $hospital->regionLabel() }}</dd>
            </div>
            <div class="hospital-detail-item">
                <dt>City / district</dt>
                <dd>{{ $hospital->city }}</dd>
            </div>
            <div class="hospital-detail-item">
                <dt>Official phone</dt>
                <dd>{{ $hospital->phone }}</dd>
            </div>
            <div class="hospital-detail-item">
                <dt>Official email</dt>
                <dd>{{ $hospital->email }}</dd>
            </div>
            <div class="hospital-detail-item">
                <dt>Your role</dt>
                <dd>{{ $user->job_title }}</dd>
            </div>
            <div class="hospital-detail-item">
                <dt>Work email</dt>
                <dd>{{ $user->email }}</dd>
            </div>
        </dl>
    </div>
</div>
@endsection
