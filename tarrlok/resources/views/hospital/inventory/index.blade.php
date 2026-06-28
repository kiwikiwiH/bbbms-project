@extends('layouts.tarrlok-hospital')

@section('title', 'Blood Inventory - Tarrlok')

@section('page_title', 'Blood inventory')
@section('page_subtitle')
    Cleared units at {{ $hospital->name }} — only screened units can be issued
@endsection

@section('content')
<div class="hospital-card" style="margin-bottom:20px;">
    <div class="hospital-card-body">
        <p class="hospital-flow-note" style="margin:0;">
            <span class="material-symbols-outlined">science</span>
            Units must pass lab screening (HIV, Hep B/C, Syphilis) before they count as available.
            <a href="{{ route('hospital.requests') }}" style="color:#a20513;">Blood Requests</a>.
        </p>
    </div>
</div>

<div class="hospital-stats" style="margin-bottom:20px;">
    @forelse ($availableByGroup as $group => $count)
        <div class="hospital-stat">
            <div class="hospital-stat-label">{{ $group }} cleared</div>
            <div class="hospital-stat-value">{{ $count }}</div>
        </div>
    @empty
        <div class="hospital-stat">
            <div class="hospital-stat-label">Cleared units</div>
            <div class="hospital-stat-value">0</div>
            <div class="hospital-stat-note">Lab staff register units and complete screening reports</div>
        </div>
    @endforelse
</div>

@if ($expiringSoon->isNotEmpty())
    <div class="hospital-card hospital-expiry-alert" style="margin-bottom:20px;">
        <div class="hospital-card-head">
            <h2 class="hospital-card-title">Expiring soon</h2>
        </div>
        <div class="hospital-card-body">
            <p class="hospital-field-hint" style="margin:0 0 12px;">
                These cleared units expire within {{ config('tarrlok.expiry_warning_days', 7) }} days — issue or discard before shelf life ends.
            </p>
            <ul class="hospital-expiry-list">
                @foreach ($expiringSoon as $unit)
                    <li>
                        <span class="hospital-request-id">{{ $unit->unit_code }}</span>
                        <span class="hospital-blood-group">{{ $unit->blood_group }}</span>
                        <span class="hospital-expiry-badge warning">Expires {{ $unit->expires_at->format('M j, Y') }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<div class="hospital-card">
    <div class="hospital-card-head">
        <h2 class="hospital-card-title">All registered units</h2>
    </div>
    @if ($units->flatten()->isEmpty())
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
                        <th>Screening</th>
                        <th>Status</th>
                        <th>Collected</th>
                        <th>Expires</th>
                        <th>Tested</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($units->flatten() as $unit)
                        <tr>
                            <td><span class="hospital-request-id">{{ $unit->unit_code }}</span></td>
                            <td><span class="hospital-blood-group">{{ $unit->blood_group }}</span></td>
                            <td>
                                <span @class(['hospital-screening-badge', $unit->screening_status])>
                                    {{ $unit->screeningStatusLabel() }}
                                </span>
                            </td>
                            <td>
                                <span @class(['hospital-req-status', match ($unit->status) {
                                    'available' => 'fulfilled',
                                    'issued' => 'approved',
                                    'discarded' => 'rejected',
                                    default => 'pending',
                                }])>{{ ucfirst($unit->status) }}</span>
                            </td>
                            <td>{{ $unit->collected_at->format('M j, Y') }}</td>
                            <td>
                                @if ($unit->expires_at)
                                    @if ($unit->isExpired())
                                        <span class="hospital-expiry-badge expired">Expired</span>
                                    @elseif ($unit->isExpiringSoon())
                                        <span class="hospital-expiry-badge warning">{{ $unit->expires_at->format('M j, Y') }}</span>
                                    @else
                                        {{ $unit->expires_at->format('M j, Y') }}
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $unit->screened_at?->format('M j, Y') ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
