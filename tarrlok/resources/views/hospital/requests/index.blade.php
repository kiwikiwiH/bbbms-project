@extends('layouts.tarrlok-hospital')

@section('title', 'Blood Requests - Tarrlok')

@section('page_title', 'Blood requests')
@section('page_subtitle')
    @if ($view === 'outgoing')
        Requests your facility has sent to partner hospitals
    @else
        Incoming requests — stock is checked before approve so you do not accept transfers you cannot fulfil
    @endif
@endsection

@section('content')
<div class="hospital-request-tabs">
    <a href="{{ route('hospital.requests', array_filter(['q' => $search ?: null, 'blood_group' => $bloodGroup ?: null, 'component_type' => $componentType ?: null])) }}" @class(['hospital-request-tab', 'active' => $view === 'incoming'])>
        Incoming
        @if ($incomingPending > 0)
            <span class="hospital-tab-badge">{{ $incomingPending }}</span>
        @endif
    </a>
    <a href="{{ route('hospital.requests', array_filter(['view' => 'outgoing', 'q' => $search ?: null, 'blood_group' => $bloodGroup ?: null, 'component_type' => $componentType ?: null])) }}" @class(['hospital-request-tab', 'active' => $view === 'outgoing'])>
        Outgoing
        @if ($outgoingPending > 0)
            <span class="hospital-tab-badge">{{ $outgoingPending }}</span>
        @endif
    </a>
</div>

<div class="hospital-requests-toolbar">
    <form class="hospital-search-form hospital-filter-form" method="GET" action="{{ route('hospital.requests') }}">
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
        <label class="hospital-filter-label" for="blood_group">Blood type</label>
        <select id="blood_group" name="blood_group" class="hospital-input hospital-filter-select" onchange="this.form.submit()">
            <option value="">All groups</option>
            @foreach ($bloodGroups as $group)
                <option value="{{ $group }}" @selected($bloodGroup === $group)>{{ $group }}</option>
            @endforeach
        </select>
        <label class="hospital-filter-label" for="component_type">Component</label>
        <select id="component_type" name="component_type" class="hospital-input hospital-filter-select" onchange="this.form.submit()">
            <option value="">All components</option>
            @foreach ($componentTypes as $key => $label)
                <option value="{{ $key }}" @selected($componentType === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="hospital-btn hospital-btn-outline hospital-btn-sm">Apply</button>
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
    <div class="hospital-alert hospital-alert-danger">
        {{ $errors->first('stock') }}
    </div>
@endif

@if ($view === 'incoming' && $insufficientIncoming > 0)
    <div class="hospital-alert hospital-alert-warn">
        <span class="material-symbols-outlined">flag</span>
        <div>
            <strong>{{ $insufficientIncoming }} incoming request{{ $insufficientIncoming === 1 ? '' : 's' }} flagged</strong>
            — not enough cleared stock for the blood type requested. Reject them, or wait for lab screening before approving.
        </div>
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
                        <th>Component</th>
                        <th>Quantity</th>
                        @if ($view === 'incoming')
                            <th>Your stock</th>
                        @endif
                        <th>Urgency</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>{{ $view === 'incoming' ? 'Actions' : 'Notes' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($requests as $req)
                        @php
                            $flagged = $view === 'incoming' && $req->isActionable() && ! $req->stock_sufficient;
                        @endphp
                        <tr @class(['hospital-row-flagged' => $flagged])>
                            <td><span class="hospital-request-id">{{ $req->request_code }}</span></td>
                            <td>
                                @if ($view === 'outgoing')
                                    {{ $req->fulfillingHospital->name }}
                                @else
                                    {{ $req->requestingHospital->name }}
                                @endif
                            </td>
                            <td><span class="hospital-blood-group">{{ $req->blood_group }}</span></td>
                            <td>{{ $req->componentLabel() }}</td>
                            <td>{{ $req->quantity }} {{ str('unit')->plural($req->quantity) }}</td>
                            @if ($view === 'incoming')
                                <td>
                                    @if ($req->stock_sufficient)
                                        <span class="hospital-stock-ok" title="{{ $req->stock_on_hand }} cleared on hand">
                                            {{ $req->stock_available }} free
                                            <small>({{ $req->stock_on_hand }} on hand)</small>
                                        </span>
                                    @else
                                        <span class="hospital-stock-flag" title="Need {{ $req->quantity }}, only {{ $req->stock_available }} free ({{ $req->stock_on_hand }} on hand)">
                                            <span class="material-symbols-outlined">flag</span>
                                            {{ $req->stock_available }} free / {{ $req->quantity }}
                                            <small>short {{ $req->stock_shortfall }}</small>
                                        </span>
                                    @endif
                                </td>
                            @endif
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
                                @if ($flagged)
                                    <div class="hospital-stock-flag-note">Insufficient stock</div>
                                @endif
                            </td>
                            <td>{{ $req->created_at->format('M j, g:i A') }}</td>
                            <td>
                                @if ($view === 'incoming')
                                    <div class="hospital-request-actions">
                                        @if ($req->status === 'pending')
                                            @if ($req->stock_sufficient)
                                                <form method="POST" action="{{ route('hospital.requests.approve', $req) }}">
                                                    @csrf
                                                    <button type="submit" class="hospital-btn hospital-btn-outline hospital-btn-sm">Approve</button>
                                                </form>
                                            @else
                                                <button type="button" class="hospital-btn hospital-btn-outline hospital-btn-sm" disabled title="Not enough cleared {{ $req->blood_group }} stock">
                                                    Approve blocked
                                                </button>
                                            @endif
                                            <form method="POST" action="{{ route('hospital.requests.reject', $req) }}" class="hospital-reject-form">
                                                @csrf
                                                <input
                                                    type="text"
                                                    name="rejection_reason"
                                                    class="hospital-input hospital-reject-input"
                                                    placeholder="{{ $req->stock_sufficient ? 'Reason (optional)' : 'e.g. Insufficient O+ stock' }}"
                                                    maxlength="500"
                                                >
                                                <button type="submit" class="hospital-btn hospital-btn-outline hospital-btn-sm screening-fail-btn">Reject</button>
                                            </form>
                                        @endif
                                        @if ($req->status === 'approved')
                                            <form method="POST" action="{{ route('hospital.requests.reverse', $req) }}" class="hospital-reject-form" onsubmit="return confirm('Reverse this approval back to pending?');">
                                                @csrf
                                                <input
                                                    type="text"
                                                    name="reverse_reason"
                                                    class="hospital-input hospital-reject-input"
                                                    placeholder="Reason to reverse (optional)"
                                                    maxlength="500"
                                                >
                                                <button type="submit" class="hospital-btn hospital-btn-outline hospital-btn-sm">Reverse</button>
                                            </form>
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
                                        @if (in_array($req->status, ['pending', 'approved'], true) && $req->stock_sufficient)
                                            <form method="POST" action="{{ route('hospital.requests.issue', $req) }}">
                                                @csrf
                                                <button type="submit" class="hospital-btn hospital-btn-primary hospital-btn-sm">
                                                    Issue {{ $req->quantity }} {{ str('unit')->plural($req->quantity) }}
                                                </button>
                                            </form>
                                        @elseif (in_array($req->status, ['pending', 'approved'], true))
                                            <button type="button" class="hospital-btn hospital-btn-primary hospital-btn-sm" disabled title="Stock short — reject or wait for lab">
                                                Issue blocked
                                            </button>
                                        @endif
                                        @if ($req->status === 'fulfilled')
                                            <span class="hospital-muted">Completed</span>
                                        @endif
                                        @if ($req->status === 'rejected')
                                            <span class="hospital-muted" title="{{ $req->rejection_reason }}">Rejected</span>
                                        @endif
                                    </div>
                                    <details class="hospital-audit-tray">
                                        <summary>Audit log</summary>
                                        <ul>
                                            @foreach ($req->auditTrail() as $event)
                                                <li>
                                                    <strong>{{ $event['label'] }}</strong>
                                                    <span>{{ $event['detail'] }}</span>
                                                    @if ($event['at'])
                                                        <time>{{ $event['at']->format('M j, Y g:i A') }}</time>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </details>
                                @else
                                    @if ($req->status === 'rejected' && $req->rejection_reason)
                                        <span class="hospital-muted" title="{{ $req->rejection_reason }}">{{ Str::limit($req->rejection_reason, 40) }}</span>
                                    @elseif ($req->status === 'fulfilled')
                                        <span class="hospital-muted">Received {{ $req->fulfilled_at?->format('M j') }}</span>
                                    @elseif ($req->status === 'approved')
                                        <div class="hospital-request-actions">
                                            <span class="hospital-muted">Awaiting issue</span>
                                            <form method="POST" action="{{ route('hospital.requests.cancel', $req) }}" onsubmit="return confirm('Cancel this approved request?');">
                                                @csrf
                                                <button type="submit" class="hospital-btn hospital-btn-outline hospital-btn-sm">Cancel request</button>
                                            </form>
                                        </div>
                                    @elseif ($req->status === 'pending')
                                        <form method="POST" action="{{ route('hospital.requests.cancel', $req) }}" onsubmit="return confirm('Cancel this blood request?');">
                                            @csrf
                                            <button type="submit" class="hospital-btn hospital-btn-outline hospital-btn-sm">Cancel request</button>
                                        </form>
                                    @else
                                        <span class="hospital-muted">Awaiting partner</span>
                                    @endif
                                    <details class="hospital-audit-tray">
                                        <summary>Audit log</summary>
                                        <ul>
                                            @foreach ($req->auditTrail() as $event)
                                                <li>
                                                    <strong>{{ $event['label'] }}</strong>
                                                    <span>{{ $event['detail'] }}</span>
                                                    @if ($event['at'])
                                                        <time>{{ $event['at']->format('M j, Y g:i A') }}</time>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </details>
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
        Approve is blocked when cleared stock for that blood type is below the quantity requested.
        Failed screening (HIV, Hep B/C, syphilis) already keeps units out of issuable stock.
        Use <strong>Reverse</strong> to undo an approval before issue.
    </p>
@endif
@endsection
