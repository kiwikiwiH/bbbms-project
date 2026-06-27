@extends('layouts.tarrlok-hospital')

@section('title', 'Blood Requests - Tarrlok')

@section('page_title', 'Blood requests')
@section('page_subtitle')
    @if ($view === 'outgoing')
        Requests your facility has sent to partner hospitals
    @else
        Incoming requests from partners — approve, reject, or issue from your inventory
    @endif
@endsection

@section('content')
<div class="hospital-request-tabs">
    <a href="{{ route('hospital.requests', array_filter(['q' => $search ?: null])) }}" @class(['hospital-request-tab', 'active' => $view === 'incoming'])>
        Incoming
        @if ($incomingPending > 0)
            <span class="hospital-tab-badge">{{ $incomingPending }}</span>
        @endif
    </a>
    <a href="{{ route('hospital.requests', array_filter(['view' => 'outgoing', 'q' => $search ?: null])) }}" @class(['hospital-request-tab', 'active' => $view === 'outgoing'])>
        Outgoing
        @if ($outgoingPending > 0)
            <span class="hospital-tab-badge">{{ $outgoingPending }}</span>
        @endif
    </a>
</div>

<div class="hospital-requests-toolbar">
    <form class="hospital-search-form" method="GET" action="{{ route('hospital.requests') }}">
        @if ($view === 'outgoing')
            <input type="hidden" name="view" value="outgoing">
        @endif
        <span class="material-symbols-outlined">search</span>
        <input
            type="search"
            name="q"
            value="{{ $search }}"
            placeholder="Search request ID or hospital..."
            class="hospital-search-input"
        >
    </form>
    @if ($view === 'incoming')
        <div class="hospital-inventory-pill">
            <span class="material-symbols-outlined">inventory_2</span>
            {{ $inventoryNote }} units available in stock
            <a href="{{ route('hospital.inventory') }}">View inventory</a>
        </div>
    @else
        <a href="{{ route('hospital.requests.create') }}" class="hospital-btn hospital-btn-primary">
            <span class="material-symbols-outlined">add</span>
            New request
        </a>
    @endif
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
            @if ($view === 'outgoing')
                <h2>No outgoing requests</h2>
                <p>Request blood from a partner hospital on the network.</p>
                <a href="{{ route('hospital.requests.create') }}" class="hospital-btn hospital-btn-primary" style="margin-top:16px;">
                    <span class="material-symbols-outlined">add</span>
                    New blood request
                </a>
            @else
                <h2>No incoming requests</h2>
                <p>Partner hospitals will appear here when they request blood from your facility.</p>
            @endif
        </div>
    @else
        <div class="hospital-table-wrap">
            <table class="hospital-table hospital-requests-table">
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>{{ $view === 'outgoing' ? 'Partner (from)' : 'Hospital (requesting)' }}</th>
                        <th>Blood group</th>
                        <th>Quantity</th>
                        <th>Urgency</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>{{ $view === 'incoming' ? 'Actions' : 'Notes' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($requests as $req)
                        <tr>
                            <td><span class="hospital-request-id">{{ $req->request_code }}</span></td>
                            <td>
                                @if ($view === 'outgoing')
                                    {{ $req->fulfillingHospital->name }}
                                @else
                                    {{ $req->requestingHospital->name }}
                                @endif
                            </td>
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
                                @if ($view === 'incoming')
                                    <div class="hospital-request-actions">
                                        @if ($req->status === 'pending')
                                            <form method="POST" action="{{ route('hospital.requests.approve', $req) }}">
                                                @csrf
                                                <button type="submit" class="hospital-btn hospital-btn-outline hospital-btn-sm">Approve</button>
                                            </form>
                                            <form method="POST" action="{{ route('hospital.requests.reject', $req) }}" class="hospital-reject-form">
                                                @csrf
                                                <input
                                                    type="text"
                                                    name="rejection_reason"
                                                    class="hospital-input hospital-reject-input"
                                                    placeholder="Reason (optional)"
                                                    maxlength="500"
                                                >
                                                <button type="submit" class="hospital-btn hospital-btn-outline hospital-btn-sm screening-fail-btn">Reject</button>
                                            </form>
                                        @endif
                                        @if ($req->status === 'approved')
                                            <form method="POST" action="{{ route('hospital.requests.reject', $req) }}" class="hospital-reject-form">
                                                @csrf
                                                <input
                                                    type="text"
                                                    name="rejection_reason"
                                                    class="hospital-input hospital-reject-input"
                                                    placeholder="Reason for rejection"
                                                    maxlength="500"
                                                >
                                                <button type="submit" class="hospital-btn hospital-btn-outline hospital-btn-sm screening-fail-btn">Reject</button>
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
                                            <span class="hospital-muted" title="{{ $req->rejection_reason }}">Rejected</span>
                                        @endif
                                    </div>
                                @else
                                    @if ($req->status === 'rejected' && $req->rejection_reason)
                                        <span class="hospital-muted" title="{{ $req->rejection_reason }}">{{ Str::limit($req->rejection_reason, 40) }}</span>
                                    @elseif ($req->status === 'fulfilled')
                                        <span class="hospital-muted">Received {{ $req->fulfilled_at?->format('M j') }}</span>
                                    @elseif ($req->status === 'approved')
                                        <span class="hospital-muted">Awaiting issue</span>
                                    @else
                                        <span class="hospital-muted">Awaiting partner</span>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

@if ($view === 'incoming')
    <p class="hospital-flow-note">
        <span class="material-symbols-outlined">info</span>
        Blood is recorded by <strong>lab staff</strong> into inventory, then <strong>issued</strong> here — cleared units transfer to the requesting hospital.
    </p>
@endif
@endsection
