@extends('layouts.tarrlok-lab')

@section('title', 'Lab Inventory - Tarrlok')

@section('page_title', 'Facility inventory')
@section('page_subtitle')
    Units at {{ $hospital->name }} — {{ $availableCount }} available
@endsection

@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px;">
    <p class="hospital-field-hint" style="margin:0;">You have registered <strong>{{ $recordedByYou }}</strong> unit(s). Hospital admin issues stock from here when partners request blood.</p>
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
            <p>Register a blood unit after processing a donation to add it to facility inventory.</p>
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
                        <th>Recorded by</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($units as $unit)
                        <tr>
                            <td><span class="hospital-request-id">{{ $unit->unit_code }}</span></td>
                            <td><span class="hospital-blood-group">{{ $unit->blood_group }}</span></td>
                            <td><span @class(['hospital-req-status', $unit->status === 'available' ? 'approved' : ($unit->status === 'issued' ? 'fulfilled' : 'pending')])>{{ ucfirst($unit->status) }}</span></td>
                            <td>{{ $unit->collected_at->format('M j, Y') }}</td>
                            <td>{{ $unit->recorder?->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="hospital-pagination">{{ $units->links('vendor.pagination.admin') }}</div>
    @endif
</div>
@endsection
