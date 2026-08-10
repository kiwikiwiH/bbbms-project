@extends('layouts.tarrlok-admin')

@section('title', 'Blockchain - Tarrlok Admin')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/ledger.css') }}">
@endpush

@section('content')
@php
    $total = max(1, (int) $stats['total_units']);
    $registerPct = (int) round(($stats['registered_on_chain'] / $total) * 100);
    $screenPct = (int) round(($stats['screened_on_chain'] / $total) * 100);
    $issuePct = (int) round(($stats['issued_on_chain'] / $total) * 100);
    $rpcOk = (bool) ($chain['rpcReachable'] ?? false);
    $contractOk = (bool) ($chain['contractDeployed'] ?? false) && ! empty($chain['contractAddress']);
    $signerOk = ! empty($chain['signerAddress']);
@endphp

<h1 class="admin-heading">Blockchain audit trail</h1>
<p class="admin-subheading">Full-network chain health, unit history search with block numbers, and integrity monitoring.</p>

{{-- Health banner --}}
<div class="admin-blockchain-health admin-blockchain-health-{{ $health }}">
    <span class="material-symbols-outlined admin-blockchain-health-icon">
        @if ($health === 'healthy')
            verified
        @elseif ($health === 'degraded')
            warning
        @elseif ($health === 'disabled')
            power_off
        @else
            cloud_off
        @endif
    </span>
    <div>
        <strong>
            @if ($health === 'healthy')
                Chain connected — anchoring is operational
            @elseif ($health === 'degraded')
                Chain reachable but setup is incomplete
            @elseif ($health === 'disabled')
                Blockchain anchoring is disabled
            @else
                Cannot reach the blockchain node
            @endif
        </strong>
        <p>
            @if ($health === 'healthy')
                New lab registrations, screenings, and partner issues are written to <code>BloodBank.sol</code>.
            @elseif ($health === 'disabled')
                Set <code>BLOCKCHAIN_ENABLED=true</code> in <code>.env</code> to anchor events.
            @else
                Start the local node (<code>cd blockchain && npm run node</code>) and deploy the contract.
            @endif
        </p>
    </div>
</div>

{{-- Visual network map --}}
<section @class(['bc-map', $health === 'healthy' ? 'is-live' : '']) aria-label="Blockchain network status">
    <div class="bc-map-title">
        <span class="material-symbols-outlined">hub</span>
        Network map
        @if ($health === 'healthy')
            <span class="bc-live-pill">
                <i></i> Live traffic
            </span>
        @endif
    </div>

    @if ($rpcOk)
        <div class="bc-block-ticker" aria-hidden="true">
            <div class="bc-block-ticker-track">
                @for ($i = 0; $i < 8; $i++)
                    <span>Block {{ number_format(max(0, ($chain['blockNumber'] ?? 0) - (7 - $i))) }}</span>
                @endfor
                @for ($i = 0; $i < 8; $i++)
                    <span>Block {{ number_format(max(0, ($chain['blockNumber'] ?? 0) - (7 - $i))) }}</span>
                @endfor
            </div>
        </div>
    @endif

    <div class="bc-pipeline">
        <article @class(['bc-node', $configured ? 'is-on' : 'is-off']) style="--d:0">
            <div class="bc-node-orb">
                <span class="material-symbols-outlined">settings</span>
            </div>
            <h3>Laravel</h3>
            <p>{{ $configured ? 'Anchoring enabled' : 'Disabled in .env' }}</p>
        </article>

        <div @class(['bc-link', $rpcOk ? 'is-on' : 'is-off']) aria-hidden="true">
            <div class="bc-link-track"></div>
            <i class="bc-packet"></i>
            <i class="bc-packet bc-packet-delay"></i>
        </div>

        <article @class(['bc-node', $rpcOk ? 'is-on' : 'is-off']) style="--d:1">
            <div class="bc-node-orb">
                <span class="material-symbols-outlined">dns</span>
                @if ($rpcOk)
                    <i class="bc-pulse"></i>
                @endif
            </div>
            <h3>Hardhat node</h3>
            <p>
                @if ($rpcOk)
                    Block {{ number_format($chain['blockNumber'] ?? 0) }}
                    @if ($chain['chainId']) · chain {{ $chain['chainId'] }} @endif
                @else
                    Offline / unreachable
                @endif
            </p>
        </article>

        <div @class(['bc-link', $contractOk ? 'is-on' : 'is-off']) aria-hidden="true">
            <div class="bc-link-track"></div>
            <i class="bc-packet"></i>
            <i class="bc-packet bc-packet-delay"></i>
        </div>

        <article @class(['bc-node', $contractOk ? 'is-on' : 'is-off']) style="--d:2">
            <div class="bc-node-orb">
                <span class="material-symbols-outlined">token</span>
            </div>
            <h3>BloodBank.sol</h3>
            <p>
                @if ($contractOk)
                    Deployed
                @else
                    Not deployed
                @endif
            </p>
        </article>

        <div @class(['bc-link', $signerOk && $health === 'healthy' ? 'is-on' : 'is-off']) aria-hidden="true">
            <div class="bc-link-track"></div>
            <i class="bc-packet"></i>
            <i class="bc-packet bc-packet-delay"></i>
        </div>

        <article @class(['bc-node', $signerOk ? 'is-on' : 'is-off']) style="--d:3">
            <div class="bc-node-orb">
                <span class="material-symbols-outlined">account_balance_wallet</span>
            </div>
            <h3>Signer wallet</h3>
            <p>
                @if ($signerOk)
                    {{ number_format((float) ($chain['signerBalanceEth'] ?? 0), 2) }} ETH
                @else
                    Key missing
                @endif
            </p>
        </article>
    </div>

    @if (($chain['contractAddress'] ?? null) || ($chain['signerAddress'] ?? null))
        <div class="bc-address-row">
            @if (! empty($chain['contractAddress']))
                <div class="bc-address">
                    <span>Contract</span>
                    <code title="{{ $chain['contractAddress'] }}">{{ $chain['contractAddress'] }}</code>
                </div>
            @endif
            @if (! empty($chain['signerAddress']))
                <div class="bc-address">
                    <span>Signer</span>
                    <code title="{{ $chain['signerAddress'] }}">{{ $chain['signerAddress'] }}</code>
                </div>
            @endif
        </div>
    @endif

    @if (! empty($chain['errors']))
        <div class="bc-errors">
            <strong>Issues</strong>
            <ul>
                @foreach ($chain['errors'] as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</section>

{{-- Visual lifecycle flow --}}
<section class="bc-flow-card" aria-label="Anchoring lifecycle">
    <div class="bc-map-title">
        <span class="material-symbols-outlined">timeline</span>
        What gets written on-chain
    </div>
    <div class="bc-flow-steps">
        <div class="bc-flow-step" style="--d:0">
            <div class="bc-flow-icon">
                <span class="material-symbols-outlined">science</span>
            </div>
            <div class="bc-flow-copy">
                <strong>1. Register unit</strong>
                <p>Lab staff + expiry stored on-chain</p>
                <code>UnitRegistered</code>
            </div>
        </div>
        <div class="bc-flow-arrow" aria-hidden="true">
            <span class="material-symbols-outlined">arrow_forward</span>
        </div>
        <div class="bc-flow-step" style="--d:1">
            <div class="bc-flow-icon">
                <span class="material-symbols-outlined">biotech</span>
            </div>
            <div class="bc-flow-copy">
                <strong>2. Screening</strong>
                <p>Actor recorded; one-time status only</p>
                <code>UnitScreened</code>
            </div>
        </div>
        <div class="bc-flow-arrow" aria-hidden="true">
            <span class="material-symbols-outlined">arrow_forward</span>
        </div>
        <div class="bc-flow-step" style="--d:2">
            <div class="bc-flow-icon">
                <span class="material-symbols-outlined">swap_horiz</span>
            </div>
            <div class="bc-flow-copy">
                <strong>3. Partner issue</strong>
                <p>Blocks expired / uncleared units</p>
                <code>UnitIssued</code>
            </div>
        </div>
    </div>
</section>

{{-- Coverage meters --}}
<section class="bc-coverage" aria-label="Anchoring coverage">
    <div class="bc-coverage-card" style="--d:0">
        <div class="bc-coverage-head">
            <span>Registrations anchored</span>
            <strong>{{ $stats['registered_on_chain'] }}/{{ $stats['total_units'] }}</strong>
        </div>
        <div class="bc-meter"><i style="--w: {{ $registerPct }}%"></i></div>
        <small>{{ $registerPct }}% of units in the database</small>
    </div>
    <div class="bc-coverage-card" style="--d:1">
        <div class="bc-coverage-head">
            <span>Screenings anchored</span>
            <strong>{{ $stats['screened_on_chain'] }}/{{ $stats['total_units'] }}</strong>
        </div>
        <div class="bc-meter"><i style="--w: {{ $screenPct }}%"></i></div>
        <small>{{ $screenPct }}% coverage</small>
    </div>
    <div class="bc-coverage-card" style="--d:2">
        <div class="bc-coverage-head">
            <span>Transfers anchored</span>
            <strong>{{ $stats['issued_on_chain'] }}/{{ $stats['total_units'] }}</strong>
        </div>
        <div class="bc-meter bc-meter-issue"><i style="--w: {{ $issuePct }}%"></i></div>
        <small>{{ $issuePct }}% coverage</small>
    </div>
</section>

{{-- Recent units as visual chains --}}
<section class="admin-card bc-recent">
    <div class="admin-card-head">
        <h2 class="admin-card-title">Recent anchored units</h2>
        <a href="{{ route('track.index') }}" class="admin-btn admin-btn-outline" style="padding:8px 12px;font-size:13px;">Public track</a>
    </div>

    @if ($recentUnits->isEmpty())
        <div class="admin-empty">
            No blockchain transactions recorded yet.
            @if (! $configured)
                Enable blockchain in <code>.env</code> and run the local chain to start anchoring.
            @endif
        </div>
    @else
        <div class="bc-unit-list">
            @foreach ($recentUnits as $unit)
                <article class="bc-unit-card" style="--d: {{ $loop->index }}">
                    <div class="bc-unit-top">
                        <div>
                            <strong>{{ $unit->unit_code }}</strong>
                            <span class="bc-unit-meta">{{ $unit->blood_group }} · {{ $unit->hospital->name }}</span>
                        </div>
                        <a href="{{ route('admin.trace.show', $unit) }}">Trace unit</a>
                    </div>

                    <div class="bc-unit-chain" aria-label="On-chain stages for {{ $unit->unit_code }}">
                        <div @class(['bc-stage', $unit->blockchain_register_tx ? 'done' : 'missing'])>
                            <span class="bc-stage-dot"></span>
                            <div>
                                <strong>Registered</strong>
                                @if ($unit->blockchain_register_tx)
                                    <code title="{{ $unit->blockchain_register_tx }}">{{ Str::limit($unit->blockchain_register_tx, 18, '…') }}</code>
                                @else
                                    <em>Not anchored</em>
                                @endif
                            </div>
                        </div>
                        <div @class(['bc-stage-line', $unit->blockchain_register_tx && $unit->blockchain_screening_tx ? 'is-active' : '']) aria-hidden="true"></div>
                        <div @class(['bc-stage', $unit->blockchain_screening_tx ? 'done' : 'missing'])>
                            <span class="bc-stage-dot"></span>
                            <div>
                                <strong>Screened</strong>
                                @if ($unit->blockchain_screening_tx)
                                    <code title="{{ $unit->blockchain_screening_tx }}">{{ Str::limit($unit->blockchain_screening_tx, 18, '…') }}</code>
                                @else
                                    <em>Not anchored</em>
                                @endif
                            </div>
                        </div>
                        <div @class(['bc-stage-line', $unit->blockchain_screening_tx && $unit->blockchain_issue_tx ? 'is-active' : '']) aria-hidden="true"></div>
                        <div @class(['bc-stage', $unit->blockchain_issue_tx ? 'done' : 'missing'])>
                            <span class="bc-stage-dot"></span>
                            <div>
                                <strong>Issued</strong>
                                @if ($unit->blockchain_issue_tx)
                                    <code title="{{ $unit->blockchain_issue_tx }}">{{ Str::limit($unit->blockchain_issue_tx, 18, '…') }}</code>
                                @else
                                    <em>Not anchored</em>
                                @endif
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        @if ($stats['missing_register'] > 0 || $stats['missing_screening'] > 0)
            <div class="admin-meta">
                <strong>Gap check:</strong>
                {{ $stats['missing_register'] }} unit(s) without registration tx;
                {{ $stats['missing_screening'] }} screened unit(s) without screening tx
                (usually means the chain was offline when those actions ran).
            </div>
        @endif
    @endif
</section>

@include('shared.blockchain.unit-history')

@include('shared.blockchain.ledger')
@endsection
