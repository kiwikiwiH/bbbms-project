@extends($portal['layout'])

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/hospital.css') }}">
@endpush

@section('title', 'Trace Unit - Tarrlok')

@section('page_title', 'Trace blood unit')
@section('page_subtitle')
    Look up a unit ID to see collection, screening, and partner issue history
@endsection

@section('content')
<div class="hospital-card trace-search-card">
    <div class="hospital-card-body">
        <form class="hospital-search-form trace-search-form" method="GET" action="{{ route($portal['traceRoute']) }}">
            <span class="material-symbols-outlined">search</span>
            <input
                type="search"
                name="q"
                value="{{ $query }}"
                placeholder="Enter unit ID, e.g. UNIT-001-00001"
                class="hospital-search-input"
                required
            >
            <button type="submit" class="hospital-btn hospital-btn-primary hospital-btn-sm">Trace</button>
        </form>
    </div>
</div>

@if ($query !== '' && ! $unit)
    <div class="hospital-placeholder" style="margin-top:20px;">
        <div class="hospital-placeholder-icon">
            <span class="material-symbols-outlined">search_off</span>
        </div>
        <h2>Unit not found</h2>
        <p>No blood unit matches <strong>{{ $query }}</strong> on the Tarrlok network.</p>
    </div>
@elseif ($unit)
    <div class="hospital-card" style="margin-top:20px;">
        <div class="hospital-card-head">
            <h2 class="hospital-card-title">{{ $unit->unit_code }}</h2>
            <span @class(['hospital-screening-badge', $unit->screening_status])>
                {{ $unit->screeningStatusLabel() }}
            </span>
        </div>
        <div class="hospital-card-body">
            <dl class="hospital-detail-grid trace-unit-summary">
                <div class="hospital-detail-item">
                    <dt>Blood group</dt>
                    <dd><span class="hospital-blood-group">{{ $unit->blood_group }}</span></dd>
                </div>
                <div class="hospital-detail-item">
                    <dt>Facility</dt>
                    <dd>{{ $unit->hospital->name }}</dd>
                </div>
                <div class="hospital-detail-item">
                    <dt>Stock status</dt>
                    <dd>{{ ucfirst($unit->status) }}</dd>
                </div>
                <div class="hospital-detail-item">
                    <dt>Collected</dt>
                    <dd>{{ $unit->collected_at->format('M j, Y') }}</dd>
                </div>
            </dl>

            <h3 class="screening-report-heading">Lifecycle timeline</h3>
            <ol class="trace-timeline">
                <li class="trace-timeline-item done">
                    <span class="trace-timeline-icon material-symbols-outlined">water_drop</span>
                    <div>
                        <strong>Collected & registered</strong>
                        <p>{{ $unit->collected_at->format('M j, Y g:i A') }} · {{ $unit->recorder?->name ?? 'Lab staff' }} · {{ $unit->hospital->name }}</p>
                    </div>
                </li>

                @if ($unit->screening_status === 'pending')
                    <li class="trace-timeline-item current">
                        <span class="trace-timeline-icon material-symbols-outlined">science</span>
                        <div>
                            <strong>Lab screening pending</strong>
                            <p>Unit is in quarantine until serology tests are completed.</p>
                        </div>
                    </li>
                @elseif ($unit->screening_status === 'cleared')
                    <li class="trace-timeline-item done">
                        <span class="trace-timeline-icon material-symbols-outlined">verified</span>
                        <div>
                            <strong>Screening cleared</strong>
                            <p>
                                {{ $unit->screened_at?->format('M j, Y') ?? '—' }}
                                · {{ $unit->screener?->name ?? 'Lab staff' }}
                                · HIV, Hep B/C, Syphilis non-reactive
                            </p>
                        </div>
                    </li>
                @else
                    <li class="trace-timeline-item failed">
                        <span class="trace-timeline-icon material-symbols-outlined">block</span>
                        <div>
                            <strong>Screening failed — discarded</strong>
                            <p>
                                {{ $unit->screened_at?->format('M j, Y') ?? '—' }}
                                · {{ $unit->screener?->name ?? 'Lab staff' }}
                                @if ($unit->screening_notes)
                                    · {{ $unit->screening_notes }}
                                @endif
                            </p>
                        </div>
                    </li>
                @endif

                @if ($unit->screening_status === 'cleared' && $unit->status === 'available')
                    <li class="trace-timeline-item current">
                        <span class="trace-timeline-icon material-symbols-outlined">inventory_2</span>
                        <div>
                            <strong>Available in inventory</strong>
                            <p>Cleared for partner requests at {{ $unit->hospital->name }}.</p>
                        </div>
                    </li>
                @endif

                @forelse ($unit->bloodRequests as $request)
                    <li class="trace-timeline-item done">
                        <span class="trace-timeline-icon material-symbols-outlined">local_shipping</span>
                        <div>
                            <strong>Transferred to partner</strong>
                            <p>
                                Request {{ $request->request_code }}
                                · {{ $request->requestingHospital->name }}
                                · {{ $request->fulfilled_at?->format('M j, Y g:i A') ?? $request->updated_at->format('M j, Y g:i A') }}
                            </p>
                        </div>
                    </li>
                @empty
                    @if ($unit->status === 'issued')
                        <li class="trace-timeline-item done">
                            <span class="trace-timeline-icon material-symbols-outlined">local_shipping</span>
                            <div>
                                <strong>Issued</strong>
                                <p>Unit marked issued from {{ $unit->hospital->name }} inventory.</p>
                            </div>
                        </li>
                    @endif
                @endforelse
            </ol>

            @if ($unit->screening_status !== 'pending')
                <div class="trace-screening-panel">
                    <h3 class="screening-report-heading">Lab screening report</h3>
                    <ul class="screening-test-list">
                        @foreach ($screeningTests as $field => $label)
                            <li @class(['screening-test-item', 'passed' => $unit->{$field}])>
                                <span class="material-symbols-outlined">{{ $unit->{$field} ? 'check_circle' : 'cancel' }}</span>
                                <span>{{ $label }}</span>
                                <strong>{{ $unit->{$field} ? 'Non-reactive' : 'Reactive / not cleared' }}</strong>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($unit->blockchain_register_tx || $unit->blockchain_screening_tx || $unit->blockchain_issue_tx)
                <div class="trace-blockchain-panel">
                    <h3 class="screening-report-heading">Blockchain audit trail</h3>
                    <dl class="hospital-detail-grid">
                        @if ($unit->blockchain_register_tx)
                            <div class="hospital-detail-item">
                                <dt>Registration tx</dt>
                                <dd>@include('shared.partials.blockchain-tx', ['hash' => $unit->blockchain_register_tx])</dd>
                            </div>
                        @endif
                        @if ($unit->blockchain_screening_tx)
                            <div class="hospital-detail-item">
                                <dt>Screening tx</dt>
                                <dd>@include('shared.partials.blockchain-tx', ['hash' => $unit->blockchain_screening_tx])</dd>
                            </div>
                        @endif
                        @if ($unit->blockchain_issue_tx)
                            <div class="hospital-detail-item">
                                <dt>Partner issue tx</dt>
                                <dd>@include('shared.partials.blockchain-tx', ['hash' => $unit->blockchain_issue_tx])</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            @else
                <p class="hospital-flow-note" style="margin-top:20px;">
                    <span class="material-symbols-outlined">link_off</span>
                    No blockchain transactions yet. Enable <code>BLOCKCHAIN_ENABLED=true</code> and run the local chain to anchor events.
                </p>
            @endif
        </div>
    </div>
@else
    <div class="hospital-placeholder" style="margin-top:20px;">
        <div class="hospital-placeholder-icon">
            <span class="material-symbols-outlined">timeline</span>
        </div>
        <h2>Search by unit ID</h2>
        <p>Enter a code like <strong>UNIT-001-00001</strong> from lab inventory or a fulfilled partner request.</p>
    </div>
@endif
@endsection
