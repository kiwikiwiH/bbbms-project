@php
    $history = $unitHistory ?? null;
@endphp

<section class="admin-card ledger-unit-search" aria-label="Unit history search">
    <div class="admin-card-head">
        <h2 class="admin-card-title">Unit history search</h2>
    </div>
    <p class="ledger-intro" style="margin-top:0;">
        Look up any unit code to see its on-chain trail as <strong>Registered → Block N · tx 0x…</strong>
    </p>
    <form method="get" action="{{ route('admin.blockchain') }}" class="ledger-search-form">
        <label for="unit-history-q" class="sr-only">Unit code</label>
        <input
            id="unit-history-q"
            type="text"
            name="unit"
            value="{{ $unitQuery ?? '' }}"
            placeholder="e.g. UNIT-001-00001"
            autocomplete="off"
            required
        >
        <button type="submit" class="admin-btn">Search trail</button>
        @if (! empty($unitQuery))
            <a href="{{ route('admin.blockchain') }}" class="admin-btn admin-btn-outline">Clear</a>
        @endif
    </form>

    @if ($history !== null)
        @if (! ($history['found'] ?? false))
            <div class="admin-empty" style="margin-top:16px;">
                No on-chain or database record found for <code>{{ $unitQuery }}</code>.
            </div>
        @else
            <div class="ledger-unit-trail" style="margin-top:18px;">
                @if ($history['unit'])
                    <p class="ledger-trail-meta">
                        <strong>{{ $history['unit']->unit_code }}</strong>
                        · {{ $history['unit']->blood_group }}
                        · {{ $history['unit']->hospital?->name }}
                        · {{ $history['unit']->stockStatusLabel() }}
                        <a href="{{ route('admin.trace.show', $history['unit']) }}">Open full trace</a>
                    </p>
                @else
                    <p class="ledger-trail-meta">
                        <strong>{{ $unitQuery }}</strong> · on-chain only (not in MySQL)
                    </p>
                @endif

                <ol class="ledger-trail-steps">
                    @forelse ($history['events'] as $event)
                        <li>
                            <strong>{{ $event['label'] ?? $event['name'] }}</strong>
                            <span>
                                @if (! empty($event['blockNumber']))
                                    Block {{ $event['blockNumber'] }}
                                @else
                                    Block —
                                @endif
                                @if (! empty($event['txHash']))
                                    · tx <code title="{{ $event['txHash'] }}">{{ \Illuminate\Support\Str::limit($event['txHash'], 18, '…') }}</code>
                                @endif
                            </span>
                            @if (! empty($event['actorName']))
                                <em>{{ $event['actorName'] }}
                                    @if (! empty($event['timestamp']))
                                        · {{ \Illuminate\Support\Carbon::createFromTimestamp($event['timestamp'])->format('M j, Y H:i') }}
                                    @endif
                                </em>
                            @endif
                        </li>
                    @empty
                        <li class="ledger-empty">Unit exists in the app, but no chain events were returned (node offline or not yet anchored).</li>
                    @endforelse
                </ol>
            </div>
        @endif
    @endif
</section>
