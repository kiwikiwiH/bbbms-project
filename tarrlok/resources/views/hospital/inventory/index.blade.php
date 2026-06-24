@extends('layouts.tarrlok-hospital')

@section('title', 'Blood Inventory - Tarrlok')

@section('page_title', 'Blood inventory')
@section('page_subtitle')
    Units available at {{ $hospital->name }} — sourced from on-site donations
@endsection

@section('content')
<div class="hospital-card" style="margin-bottom:20px;">
    <div class="hospital-card-body">
        <p class="hospital-flow-note" style="margin:0;">
            <span class="material-symbols-outlined">science</span>
            <a href="{{ route('hospital.requests') }}" style="color:#a20513;">Blood Requests</a>.
        </p>
    </div>
</div>

<div class="hospital-stats" style="margin-bottom:20px;">
    @forelse ($availableByGroup as $group => $count)
        <div class="hospital-stat">
            <div class="hospital-stat-label">{{ $group }} available</div>
            <div class="hospital-stat-value">{{ $count }}</div>
        </div>
    @empty
        <div class="hospital-stat">
            <div class="hospital-stat-label">Available units</div>
            <div class="hospital-stat-value">0</div>
            <div class="hospital-stat-note">Lab staff will add units when donations are processed</div>
        </div>
    @endforelse
</div>

<div class="hospital-card">
    <div class="hospital-card-head">
        <h2 class="hospital-card-title">All registered units</h2>
    </div>
    @if ($units->isEmpty())
        <div class="hospital-placeholder">
            <p>No blood units in inventory yet.</p>
        </div>
    @else
        <div class="hospital-table-wrap">
            <table class="hospital-table">
                <thead>
                    <tr>
                        <th>Unit ID</th>
                        <th>Blood group</th>
                        <th>Status</th>
                        <th>Collected</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($units->flatten() as $unit)
                        <tr>
                            <td><span class="hospital-request-id">{{ $unit->unit_code }}</span></td>
                            <td><span class="hospital-blood-group">{{ $unit->blood_group }}</span></td>
                            <td><span @class(['hospital-req-status', $unit->status === 'available' ? 'approved' : 'pending'])>{{ ucfirst($unit->status) }}</span></td>
                            <td>{{ $unit->collected_at->format('M j, Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
