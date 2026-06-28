@extends('layouts.tarrlok-admin')

@section('title', 'Blockchain - Tarrlok Admin')

@section('content')
<h1 class="admin-heading">Blockchain audit trail</h1>
<p class="admin-subheading">Live chain health and on-chain anchors for blood unit lifecycle events.</p>

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

<div class="admin-stats">
    <div class="admin-stat">
        <div class="admin-stat-label">Units registered on-chain</div>
        <div class="admin-stat-value" style="font-size:28px;">{{ $stats['registered_on_chain'] }}</div>
        <div class="admin-meta" style="padding:8px 0 0;border:none;background:transparent;">
            of {{ $stats['total_units'] }} total in database
        </div>
    </div>
    <div class="admin-stat approved">
        <div class="admin-stat-label">Screenings anchored</div>
        <div class="admin-stat-value" style="font-size:28px;">{{ $stats['screened_on_chain'] }}</div>
    </div>
    <div class="admin-stat pending">
        <div class="admin-stat-label">Partner transfers anchored</div>
        <div class="admin-stat-value" style="font-size:28px;">{{ $stats['issued_on_chain'] }}</div>
    </div>
    @if ($chain['blockNumber'] !== null)
        <div class="admin-stat">
            <div class="admin-stat-label">Current block</div>
            <div class="admin-stat-value" style="font-size:28px;">{{ number_format($chain['blockNumber']) }}</div>
        </div>
    @endif
</div>

<div class="admin-card" style="margin-bottom:24px;">
    <div class="admin-card-head">
        <h2 class="admin-card-title">Infrastructure</h2>
        <span @class(['admin-badge', match ($health) {
            'healthy' => 'approved',
            'degraded' => 'pending',
            'disabled' => 'rejected',
            default => 'pending',
        }])>{{ str_replace('_', ' ', $health) }}</span>
    </div>
    <div class="admin-detail-grid">
        <div class="admin-detail-section">
            <h3>Laravel configuration</h3>
            <dl class="admin-detail-list">
                <div>
                    <dt>BLOCKCHAIN_ENABLED</dt>
                    <dd>{{ $configured ? 'true' : 'false' }}</dd>
                </div>
                <div>
                    <dt>RPC URL</dt>
                    <dd><code>{{ $chain['rpcUrl'] }}</code></dd>
                </div>
                <div>
                    <dt>Signer configured</dt>
                    <dd>{{ $chain['signerAddress'] ? 'Yes' : 'No' }}</dd>
                </div>
            </dl>
        </div>
        <div class="admin-detail-section">
            <h3>On-chain contract</h3>
            <dl class="admin-detail-list">
                <div>
                    <dt>RPC reachable</dt>
                    <dd>{{ ($chain['rpcReachable'] ?? false) ? 'Yes' : 'No' }}</dd>
                </div>
                <div>
                    <dt>Chain ID</dt>
                    <dd>{{ $chain['chainId'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt>BloodBank contract</dt>
                    <dd>
                        @if ($chain['contractAddress'])
                            <code class="admin-tx-hash">{{ $chain['contractAddress'] }}</code>
                        @else
                            Not deployed
                        @endif
                    </dd>
                </div>
                <div>
                    <dt>Contract owner</dt>
                    <dd>
                        @if ($chain['contractOwner'])
                            <code class="admin-tx-hash">{{ $chain['contractOwner'] }}</code>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                @if ($chain['signerAddress'])
                    <div>
                        <dt>Signer wallet</dt>
                        <dd><code class="admin-tx-hash">{{ $chain['signerAddress'] }}</code></dd>
                    </div>
                    <div>
                        <dt>Signer balance</dt>
                        <dd>{{ $chain['signerBalanceEth'] }} ETH</dd>
                    </div>
                @endif
                @if ($chain['deployedAt'])
                    <div>
                        <dt>Deployed at</dt>
                        <dd>{{ \Illuminate\Support\Carbon::parse($chain['deployedAt'])->format('M j, Y g:i A') }}</dd>
                    </div>
                @endif
            </dl>
        </div>
    </div>
    @if (! empty($chain['errors']))
        <div class="admin-meta">
            <strong>Issues detected:</strong>
            <ul class="admin-blockchain-errors">
                @foreach ($chain['errors'] as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>

<div class="admin-card" style="margin-bottom:24px;">
    <div class="admin-card-head">
        <h2 class="admin-card-title">How anchoring works</h2>
    </div>
    <div class="admin-meta" style="border-top:none;">
        <ol class="admin-blockchain-flow">
            <li><strong>Lab registers unit</strong> → <code>UnitRegistered</code> event → saved as <code>blockchain_register_tx</code></li>
            <li><strong>Lab completes screening</strong> → <code>UnitScreened</code> event → <code>blockchain_screening_tx</code></li>
            <li><strong>Hospital issues to partner</strong> → <code>UnitIssued</code> event → <code>blockchain_issue_tx</code></li>
        </ol>
        <p style="margin:12px 0 0;">
            Donors see hashes on <a href="{{ route('track.index') }}">public unit tracking</a>.
            Hospital/lab staff see the same on trace pages.
        </p>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-head">
        <h2 class="admin-card-title">Recent anchored units</h2>
    </div>
    @if ($recentUnits->isEmpty())
        <div class="admin-empty">
            No blockchain transactions recorded yet.
            @if (! $configured)
                Enable blockchain in <code>.env</code> and run the local chain to start anchoring.
            @endif
        </div>
    @else
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Unit</th>
                        <th>Facility</th>
                        <th>Registration</th>
                        <th>Screening</th>
                        <th>Issue</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentUnits as $unit)
                        <tr>
                            <td><strong>{{ $unit->unit_code }}</strong><br><span style="font-size:12px;color:#555f6f;">{{ $unit->blood_group }}</span></td>
                            <td>{{ $unit->hospital->name }}</td>
                            <td>
                                @if ($unit->blockchain_register_tx)
                                    <code class="admin-tx-hash" title="{{ $unit->blockchain_register_tx }}">{{ Str::limit($unit->blockchain_register_tx, 14, '…') }}</code>
                                @else
                                    <span class="admin-tx-missing">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($unit->blockchain_screening_tx)
                                    <code class="admin-tx-hash" title="{{ $unit->blockchain_screening_tx }}">{{ Str::limit($unit->blockchain_screening_tx, 14, '…') }}</code>
                                @else
                                    <span class="admin-tx-missing">—</span>
                                @endif
                            </td>
                            <td>
                                @if ($unit->blockchain_issue_tx)
                                    <code class="admin-tx-hash" title="{{ $unit->blockchain_issue_tx }}">{{ Str::limit($unit->blockchain_issue_tx, 14, '…') }}</code>
                                @else
                                    <span class="admin-tx-missing">—</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('track.show', $unit) }}" target="_blank" rel="noopener">Track</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($stats['missing_register'] > 0 || $stats['missing_screening'] > 0)
            <div class="admin-meta">
                <strong>Gap check:</strong>
                {{ $stats['missing_register'] }} unit(s) without registration tx;
                {{ $stats['missing_screening'] }} screened unit(s) without screening tx
                (usually means chain was offline when those actions ran).
            </div>
        @endif
    @endif
</div>
@endsection
