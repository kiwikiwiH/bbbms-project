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
            Reactive markers reject the unit from stock.
            <a href="{{ route('hospital.requests') }}" style="color:#a20513;">Blood Requests</a>.
        </p>
    </div>
</div>

<form class="hospital-filter-bar" method="GET" action="{{ route('hospital.inventory') }}">
    <label class="hospital-filter-label" for="inv_blood_group">Blood type</label>
    <select id="inv_blood_group" name="blood_group" class="hospital-input hospital-filter-select" onchange="this.form.submit()">
        <option value="">All groups</option>
        @foreach ($bloodGroups as $group)
            <option value="{{ $group }}" @selected($bloodGroup === $group)>{{ $group }}</option>
        @endforeach
    </select>
    <label class="hospital-filter-label" for="inv_component_type">Component</label>
    <select id="inv_component_type" name="component_type" class="hospital-input hospital-filter-select" onchange="this.form.submit()">
        <option value="">All components</option>
        @foreach ($componentTypes as $key => $label)
            <option value="{{ $key }}" @selected($componentType === $key)>{{ $label }}</option>
        @endforeach
    </select>
    <label class="hospital-filter-label" for="inv_screening">Screening</label>
    <select id="inv_screening" name="screening" class="hospital-input hospital-filter-select" onchange="this.form.submit()">
        <option value="" @selected($screening === '')>All</option>
        <option value="pending" @selected($screening === 'pending')>Pending</option>
        <option value="cleared" @selected($screening === 'cleared')>Cleared</option>
        <option value="failed" @selected($screening === 'failed')>Failed / disease reject</option>
    </select>
    @if ($bloodGroup || $componentType || $screening)
        <a href="{{ route('hospital.inventory') }}" class="hospital-btn hospital-btn-outline hospital-btn-sm">Clear filters</a>
    @endif
</form>

<div class="hospital-stats" style="margin-bottom:20px;">
    @forelse ($availableByGroup as $group => $count)
        <a href="{{ route('hospital.inventory', ['blood_group' => $group, 'screening' => 'cleared']) }}" class="hospital-stat hospital-stat-link">
            <div class="hospital-stat-label">{{ $group }} cleared</div>
            <div class="hospital-stat-value">{{ $count }}</div>
        </a>
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
                        <a href="{{ route('hospital.trace.show', $unit) }}" class="hospital-request-id">{{ $unit->unit_code }}</a>
                        <span class="hospital-blood-group">{{ $unit->blood_group }}</span>
                        <span>{{ $unit->componentLabel() }}</span>
                        <span class="hospital-expiry-badge warning">Expires {{ $unit->expires_at->format('M j, Y') }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<div class="hospital-card">
    <div class="hospital-card-head">
        <h2 class="hospital-card-title">
            @if ($bloodGroup || $screening)
                Filtered units
            @else
                All registered units
            @endif
        </h2>
    </div>
    @if ($units->flatten()->isEmpty())
        <div class="hospital-placeholder">
            <p>No blood units match these filters.</p>
        </div>
    @else
        <div class="hospital-table-wrap">
            <table class="hospital-table">
                <thead>
                    <tr>
                        <th>Unit ID</th>
                        <th>Blood group</th>
                        <th>Component</th>
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
                            <td>
                                <a href="{{ route('hospital.trace.show', $unit) }}" class="hospital-request-id">{{ $unit->unit_code }}</a>
                            </td>
                            <td><span class="hospital-blood-group">{{ $unit->blood_group }}</span></td>
                            <td>{{ $unit->componentLabel() }}</td>
                            <td>
                                <span @class(['hospital-screening-badge', $unit->screening_status])>
                                    {{ $unit->screeningStatusLabel() }}
                                </span>
                            </td>
                            <td>
                                <span @class(['hospital-req-status', $unit->stockStatusClass()])>{{ $unit->stockStatusLabel() }}</span>
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
