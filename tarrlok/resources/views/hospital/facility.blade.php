@extends('layouts.tarrlok-hospital')

@section('title', 'Facility Profile - '.$hospital->name)

@section('page_title', 'Facility Profile')
@section('page_subtitle', 'Registered facility details on the Tarrlok network')

@section('content')
<div class="hospital-card">
    <div class="hospital-card-head">
        <h2 class="hospital-card-title">{{ $hospital->name }}</h2>
    </div>
    <div class="hospital-card-body">
        <dl class="hospital-detail-grid">
            <div class="hospital-detail-item">
                <dt>Institution type</dt>
                <dd>{{ $hospital->typeLabel() }}</dd>
            </div>
            <div class="hospital-detail-item">
                <dt>HeFRA license</dt>
                <dd>{{ $hospital->license_id }}</dd>
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
                <dt>Network status</dt>
                <dd>{{ ucfirst($hospital->status) }}</dd>
            </div>
            <div class="hospital-detail-item">
                <dt>Primary administrator</dt>
                <dd>{{ $user->name }} ({{ $user->email }})</dd>
            </div>
        </dl>
    </div>
</div>
@endsection
