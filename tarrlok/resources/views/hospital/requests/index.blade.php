@extends('layouts.tarrlok-hospital')

@section('title', 'Blood Requests - Tarrlok')

@section('page_title', 'Active blood requests')
@section('page_subtitle')
    Review and fulfill blood supply requests from partner hospitals — issued from your inventory
@endsection

@section('content')
<div class="hospital-requests-toolbar">
    <form class="hospital-search-form" method="GET" action="{{ route('hospital.requests') }}">
        <span class="material-symbols-outlined">search</span>
        <input
            type="search"
            name="q"
            value="{{ $search }}"
            placeholder="Search request ID or hospital..."
            class="hospital-search-input"
        >
    </form>
    <div class="hospital-inventory-pill">
        <span class="material-symbols-outlined">inventory_2</span>
        {{ $inventoryNote }} units available in stock
        <a href="{{ route('hospital.inventory') }}">View inventory</a>
    </div>
</div>

@if ($errors->has('stock'))
    <div class="hospital-alert" style="background:#ffdad6;border:1px solid #e4beba;color:#93000a;margin-bottom:16px;">
        {{ $errors->first('stock') }}
    </div>
@endif

<div class="hospital-card">
    @if ($requests->isEmpty())
        <div class="hospital-placeholder">
            <div class="hospital-placeholder-icon">
                <span class="material-symbols-outlined">bloodtype</span>
            </div>
            <h2>No active requests</h2>
            <p>Partner hospitals will appear here when they request blood from your facility.</p>
        </div>
    @else
        <div class="hospital-table-wrap">
            <table class="hospital-table hospital-requests-table">
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Hospital name</th>
                        <th>Blood group</th>
                        <th>Quantity</th>
                        <th>Urgency</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($requests as $req)
                        <tr>
                            <td><span class="hospital-request-id">{{ $req->request_code }}</span></td>
                            <td>{{ $req->requestingHospital->name }}</td>
                            <td><span class="hospital-blood-group">{{ $req->blood_group }}</span></td>
                            <td>{{ $req->quantity }} {{ str('unit')->plural($req->quantity) }}</td>
                            <td>
                                <span @class(['hospital-urgency', 'emergency' => $req->urgency === 'emergency', 'routine' => $req->urgency === 'routine'])>
                                    @if ($req->urgency === 'emergency')
                                        <span class="material-symbols-outlined" style="font-size:14px;">emergency</span>
                                    @endif
                                    {{ ucfirst($req->urgency) }}
                                </span>
                            </td>
                            <td>
                                <span @class(['hospital-req-status', $req->status])>{{ ucfirst($req->status) }}</span>
                            </td>
                            <td>{{ $req->created_at->format('M j, g:i A') }}</td>
                            <td>
                                <div class="hospital-request-actions">
                                    @if ($req->status === 'pending')
                                        <form method="POST" action="{{ route('hospital.requests.approve', $req) }}">
                                            @csrf
                                            <button type="submit" class="hospital-btn hospital-btn-outline hospital-btn-sm">Approve</button>
                                        </form>
                                        <form method="POST" action="{{ route('hospital.requests.reject', $req) }}">
                                            @csrf
                                            <button type="submit" class="hospital-btn hospital-btn-outline hospital-btn-sm">Reject</button>
                                        </form>
                                    @endif
                                    @if (in_array($req->status, ['pending', 'approved'], true))
                                        <form method="POST" action="{{ route('hospital.requests.issue', $req) }}">
                                            @csrf
                                            <button type="submit" class="hospital-btn hospital-btn-primary hospital-btn-sm">Issue unit</button>
                                        </form>
                                    @endif
                                    @if ($req->status === 'fulfilled')
                                        <span class="hospital-muted">Completed</span>
                                    @endif
                                    @if ($req->status === 'rejected')
                                        <span class="hospital-muted">Rejected</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

<p class="hospital-flow-note">
    <span class="material-symbols-outlined">info</span>
    Blood is collected from donors at your facility, recorded by <strong>lab staff</strong> into inventory, then <strong>issued</strong> here when partner hospitals request units.
</p>
@endsection
