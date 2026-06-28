<div class="hospital-card">
    <div class="hospital-card-head">
        <h2 class="hospital-card-title">Donation status</h2>
        <span @class(['hospital-screening-badge', $unit->screening_status])>
            {{ $unit->screeningStatusLabel() }}
        </span>
    </div>
    <div class="hospital-card-body">
        <p style="margin:0 0 20px;font-size:15px;color:#555f6f;">{{ $unit->donorStatusLabel() }}</p>

        <h3 class="screening-report-heading">Where your blood has been</h3>
        <ol class="trace-timeline">
            <li class="trace-timeline-item done">
                <span class="trace-timeline-icon material-symbols-outlined">water_drop</span>
                <div>
                    <strong>Collected at {{ $unit->hospital->name }}</strong>
                    <p>{{ $unit->collected_at->format('M j, Y') }}</p>
                    @include('track.partials.facility-contact', ['hospital' => $unit->hospital])
                </div>
            </li>

            @if ($unit->screening_status === 'pending')
                <li class="trace-timeline-item current">
                    <span class="trace-timeline-icon material-symbols-outlined">science</span>
                    <div>
                        <strong>Awaiting lab screening</strong>
                        <p>Your unit is being tested before it can be used.</p>
                        @include('track.partials.facility-contact', ['hospital' => $unit->hospital])
                    </div>
                </li>
            @elseif ($unit->screening_status === 'cleared')
                <li class="trace-timeline-item done">
                    <span class="trace-timeline-icon material-symbols-outlined">verified</span>
                    <div>
                        <strong>Screening cleared</strong>
                        <p>All required tests were non-reactive on {{ $unit->screened_at?->format('M j, Y') }}.</p>
                    </div>
                </li>
            @else
                <li class="trace-timeline-item failed">
                    <span class="trace-timeline-icon material-symbols-outlined">block</span>
                    <div>
                        <strong>Did not pass screening</strong>
                        <p>This unit cannot be used for transfusion. Contact the blood bank if you have questions.</p>
                        @include('track.partials.facility-contact', ['hospital' => $unit->hospital])
                    </div>
                </li>
            @endif

            @if ($unit->screening_status === 'cleared' && $unit->status === 'available' && ! $unit->isExpired())
                <li class="trace-timeline-item current">
                    <span class="trace-timeline-icon material-symbols-outlined">inventory_2</span>
                    <div>
                        <strong>In stock at {{ $unit->hospital->name }}</strong>
                        <p>Available for hospital or partner use.
                            @if ($unit->expires_at)
                                Expires {{ $unit->expires_at->format('M j, Y') }}.
                            @endif
                        </p>
                        @include('track.partials.facility-contact', ['hospital' => $unit->hospital])
                    </div>
                </li>
            @endif

            @if ($unit->isExpired())
                <li class="trace-timeline-item failed">
                    <span class="trace-timeline-icon material-symbols-outlined">event_busy</span>
                    <div>
                        <strong>Expired</strong>
                        <p>This unit passed its shelf life on {{ $unit->expires_at?->format('M j, Y') }} and was removed from inventory.</p>
                    </div>
                </li>
            @endif

            @foreach ($unit->bloodRequests as $request)
                <li class="trace-timeline-item done">
                    <span class="trace-timeline-icon material-symbols-outlined">local_shipping</span>
                    <div>
                        <strong>Supplied to partner hospital</strong>
                        <p>
                            Transferred to <strong>{{ $request->requestingHospital->name }}</strong> for hospital supply
                            on {{ $request->fulfilled_at?->format('M j, Y') ?? $request->updated_at->format('M j, Y') }}.
                        </p>
                        @include('track.partials.facility-contact', ['hospital' => $request->requestingHospital])
                    </div>
                </li>
            @endforeach
        </ol>

        @if ($unit->blockchain_register_tx || $unit->blockchain_screening_tx || $unit->blockchain_issue_tx)
            <div class="trace-blockchain-panel">
                <h3 class="screening-report-heading">Blockchain verification</h3>
                <dl class="hospital-detail-grid">
                    @if ($unit->blockchain_register_tx)
                        <div class="hospital-detail-item">
                            <dt>Registration</dt>
                            <dd><code class="trace-tx-hash">{{ $unit->blockchain_register_tx }}</code></dd>
                        </div>
                    @endif
                    @if ($unit->blockchain_screening_tx)
                        <div class="hospital-detail-item">
                            <dt>Screening</dt>
                            <dd><code class="trace-tx-hash">{{ $unit->blockchain_screening_tx }}</code></dd>
                        </div>
                    @endif
                    @if ($unit->blockchain_issue_tx)
                        <div class="hospital-detail-item">
                            <dt>Partner transfer</dt>
                            <dd><code class="trace-tx-hash">{{ $unit->blockchain_issue_tx }}</code></dd>
                        </div>
                    @endif
                </dl>
            </div>
        @endif
    </div>
</div>
