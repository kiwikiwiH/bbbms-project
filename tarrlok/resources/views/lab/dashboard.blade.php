@extends('layouts.tarrlok-lab')

@section('title', 'Lab Overview - Tarrlok')

@section('page_title', 'Lab overview')
@section('page_subtitle')
    Welcome, {{ $user->name }}
@endsection

@section('content')
<div class="hospital-card">
    <div class="hospital-card-body">
        <div class="hospital-placeholder" style="padding:32px 24px;">
            <div class="hospital-placeholder-icon">
                <span class="material-symbols-outlined">science</span>
            </div>
            <h2>Lab portal</h2>
            <p>Your account was issued by <strong>{{ $hospital->name }}</strong>. Blood inventory and unit recording features will be available here soon.</p>
        </div>
        <dl class="hospital-detail-grid" style="margin-top:8px;">
            <div class="hospital-detail-item">
                <dt>Facility</dt>
                <dd>{{ $hospital->name }}</dd>
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
