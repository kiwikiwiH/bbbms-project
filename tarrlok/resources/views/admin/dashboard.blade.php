@extends('layouts.tarrlok-admin')

@section('title', 'Admin Overview - Tarrlok')

@section('content')
<h1 class="admin-heading">Platform Administration</h1>
<p class="admin-subheading">Review and approve hospital facility registrations for the Tarrlok network.</p>

<div class="admin-stats">
    <div class="admin-stat pending">
        <div class="admin-stat-label">Pending Review</div>
        <div class="admin-stat-value">{{ $counts['pending'] }}</div>
    </div>
    <div class="admin-stat approved">
        <div class="admin-stat-label">Approved</div>
        <div class="admin-stat-value">{{ $counts['approved'] }}</div>
    </div>
    <div class="admin-stat rejected">
        <div class="admin-stat-label">Rejected</div>
        <div class="admin-stat-value">{{ $counts['rejected'] }}</div>
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
