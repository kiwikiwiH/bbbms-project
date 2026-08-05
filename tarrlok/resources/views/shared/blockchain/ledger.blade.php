@php
    $traceRoute = $traceRoute ?? 'hospital.trace';
    $traceShowRoute = $traceShowRoute ?? 'hospital.trace.show';
@endphp

<section class="ledger-shell">
    <p class="ledger-intro">
        Every hospital, lab, and admin portal reads this shared ledger. MySQL can still be edited;
        the chain cannot. A mismatch means the operational record no longer matches what was anchored.
    </p>

    @if (! empty($ledgerError) && ! $ledgerOk)
        <div class="ledger-banner">
            <span class="material-symbols-outlined">cloud_off</span>
            <div>
                <strong>Chain read unavailable</strong>
                <p>{{ $ledgerError }} Blocked attempts below still come from this application.</p>
            </div>
        </div>
    @elseif ($ledgerOk)
        <div class="ledger-banner is-ok">
            <span class="material-symbols-outlined">verified</span>
            <div>
                <strong>Shared ledger is live</strong>
                <p>{{ count($events) }} on-chain event{{ count($events) === 1 ? '' : 's' }} visible to every stakeholder portal.</p>
            </div>
        </div>
    @endif

    <div class="ledger-grid">
        <article class="ledger-panel">
            <header>
                <h2>Network activity</h2>
                <span>{{ count($events) }}</span>
            </header>
            @forelse ($events as $event)
                <div class="ledger-row">
                    <div class="ledger-row-top">
                        <strong>{{ $event['label'] ?? $event['name'] }}</strong>
                        <time>{{ ! empty($event['timestamp']) ? \Illuminate\Support\Carbon::createFromTimestamp($event['timestamp'])->format('M j, Y H:i') : '—' }}</time>
                    </div>
                    <p>
                        @if (! empty($event['unitCode']))
                            <a href="{{ route($traceRoute, ['q' => $event['unitCode']]) }}"><code>{{ $event['unitCode'] }}</code></a>
                        @else
                            <code>—</code>
                        @endif
                        @if (! empty($event['actorName']))
                            · {{ $event['actorName'] }}
                        @endif
                        @if (! empty($event['hospitalName']))
                            · {{ $event['hospitalName'] }}
                        @elseif (! empty($event['fromHospitalName']) || ! empty($event['toHospitalName']))
                            · {{ $event['fromHospitalName'] ?? '—' }} → {{ $event['toHospitalName'] ?? '—' }}
                        @endif
                    </p>
                    @if (! empty($event['txHash']))
                        <code class="ledger-hash" title="{{ $event['txHash'] }}">{{ \Illuminate\Support\Str::limit($event['txHash'], 22, '…') }}</code>
                    @endif
                </div>
            @empty
                <p class="ledger-empty">No on-chain events yet. Register or screen a unit while the local chain is running.</p>
            @endforelse
        </article>

        <article class="ledger-panel">
            <header>
                <h2>Integrity alerts</h2>
                <span>{{ count($alerts) }}</span>
            </header>
            @forelse ($alerts as $alert)
                <div class="ledger-row is-alert">
                    <div class="ledger-row-top">
                        @if (! empty($alert['unit_code']))
                            <a href="{{ route($traceRoute, ['q' => $alert['unit_code']]) }}"><strong>{{ $alert['unit_code'] }}</strong></a>
                        @else
                            <strong>Unknown unit</strong>
                        @endif
                        <span class="ledger-badge tampered">Tampered</span>
                    </div>
                    <ul>
                        @foreach ($alert['mismatches'] as $mismatch)
                            <li>{{ $mismatch }}</li>
                        @endforeach
                    </ul>
                    @if (! empty($alert['note']))
                        <p class="ledger-note">{{ $alert['note'] }}</p>
                    @endif
                </div>
            @empty
                <p class="ledger-empty">No DB-vs-chain mismatches on the units checked.</p>
            @endforelse
        </article>

        <article class="ledger-panel">
            <header>
                <h2>Blocked attempts</h2>
                <span>{{ $attempts->count() }}</span>
            </header>
            @forelse ($attempts as $attempt)
                <div class="ledger-row is-blocked">
                    <div class="ledger-row-top">
                        <strong>{{ $attempt->actor_name }}</strong>
                        <time>{{ $attempt->created_at?->format('M j, Y H:i') }}</time>
                    </div>
                    <p>
                        {{ $attempt->roleLabel() }}
                        @if ($attempt->hospital)
                            · {{ $attempt->hospital->name }}
                        @endif
                        · {{ $attempt->actionLabel() }}
                        @if ($attempt->unit_code)
                            · <code>{{ $attempt->unit_code }}</code>
                        @endif
                    </p>
                    <p class="ledger-note">{{ $attempt->reason }}</p>
                </div>
            @empty
                <p class="ledger-empty">No blocked chain writes yet. Invalid transitions (double screen, expired issue, etc.) appear here.</p>
            @endforelse
        </article>
    </div>
</section>
