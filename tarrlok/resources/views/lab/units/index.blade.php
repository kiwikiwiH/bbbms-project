@extends('layouts.tarrlok-lab')

@section('title', 'Lab Inventory - Tarrlok')

@section('page_title', 'Facility inventory')
@section('page_subtitle')
    {{ $availableCount }} cleared for issue · {{ $pendingScreening }} awaiting screening
@endsection

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
    <p class="hospital-field-hint" style="margin:0;">You have registered <strong>{{ $recordedByYou }}</strong> unit(s). Only <strong>cleared</strong> units can be issued to partner hospitals.</p>
    <a href="{{ route('lab.units.create') }}" class="hospital-btn hospital-btn-primary hospital-btn-sm">
        <span class="material-symbols-outlined">add</span>
        Register unit
    </a>
</div>

<div class="hospital-card">
    @if ($units->isEmpty())
        <div class="hospital-placeholder">
            <div class="hospital-placeholder-icon">
                <span class="material-symbols-outlined">inventory_2</span>
            </div>
            <h2>No units yet</h2>
            <p>Register a blood unit after collection, then complete the lab screening report.</p>
        </div>
    @else
        <div class="hospital-table-wrap">
            <table class="hospital-table">
                <thead>
                    <tr>
                        <th>Unit ID</th>
                        <th>Blood group</th>
                        <th>Screening</th>
                        <th>Stock status</th>
                        <th>Collected</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($units as $unit)
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
                                @if ($unit->screening_status === 'pending')
                                    <a href="{{ route('lab.units.screening.show', $unit) }}" class="hospital-btn hospital-btn-primary hospital-btn-sm">
                                        Complete screening
                                    </a>
                                @else
                                    <a href="{{ route('lab.units.screening.show', $unit) }}" class="hospital-btn hospital-btn-outline hospital-btn-sm">
                                        View report
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="hospital-pagination">{{ $units->links('vendor.pagination.admin') }}</div>
    @endif
</div>
@endsection
