@extends('layouts.tarrlok-admin')

@section('title', 'Admin Overview - Tarrlok')

@section('content')
<h1 class="admin-heading">Platform Administration</h1>
<p class="admin-subheading">Review and approve hospital facility registrations for the Tarrlok network.</p>

<div class="admin-stats">
    <a href="{{ route('admin.registrations.index', ['status' => 'pending']) }}" class="admin-stat pending">
        <div class="admin-stat-label">Pending Review</div>
        <div class="admin-stat-value">{{ $counts['pending'] }}</div>
    </a>
    <a href="{{ route('admin.registrations.index', ['status' => 'approved']) }}" class="admin-stat approved">
        <div class="admin-stat-label">Approved</div>
        <div class="admin-stat-value">{{ $counts['approved'] }}</div>
    </a>
    <a href="{{ route('admin.registrations.index', ['status' => 'rejected']) }}" class="admin-stat rejected">
        <div class="admin-stat-label">Rejected</div>
        <div class="admin-stat-value">{{ $counts['rejected'] }}</div>
    </a>
</div>

<div class="admin-card" style="margin-bottom:24px;">
    <div class="admin-card-head">
        <h2 class="admin-card-title">Sign-in log</h2>
        <a href="{{ route('admin.auth-log') }}" class="admin-btn admin-btn-outline">View log</a>
    </div>
    <div class="admin-meta" style="border-top:none;">
        Track who signed in and out (hospital, lab, and admin accounts), including failed attempts.
    </div>
</div>

<div class="admin-card" style="margin-bottom:24px;">
    <div class="admin-card-head">
        <h2 class="admin-card-title">Blockchain audit trail</h2>
        <a href="{{ route('admin.blockchain') }}" class="admin-btn admin-btn-outline">View chain status</a>
    </div>
    <div class="admin-meta" style="border-top:none;">
        Monitor Hardhat node health, <code>BloodBank.sol</code> deployment, and transaction hashes anchored when labs register units, complete screening, and hospitals issue blood to partners.
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-head">
        <h2 class="admin-card-title">Awaiting Approval</h2>
        <a href="{{ route('admin.registrations.index', ['status' => 'pending']) }}" class="admin-btn admin-btn-outline">View all</a>
    </div>

    @if ($pending->isEmpty())
        <div class="admin-empty">No facility registrations are waiting for review.</div>
    @else
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Facility</th>
                        <th>HeFRA License</th>
                        <th>Region</th>
                        <th>Submitted</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($pending as $hospital)
                        <tr>
                            <td>
                                <strong>{{ $hospital->name }}</strong><br>
                                <span style="color:#555f6f;font-size:13px;">{{ $hospital->typeLabel() }}</span>
                            </td>
                            <td>{{ $hospital->license_id }}</td>
                            <td>{{ $hospital->regionLabel() }}</td>
                            <td>{{ $hospital->created_at->format('M j, Y') }}</td>
                            <td><a href="{{ route('admin.registrations.show', $hospital) }}">Review</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
