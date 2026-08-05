@extends('layouts.tarrlok-landing')

@section('title', 'Tarrlok — Every donor is a pulse')

@section('content')
<div class="lp-shell">
    <header class="lp-nav-wrap">
        <div class="lp-nav">
            <a href="{{ route('home') }}" class="lp-brand">
                <span class="lp-brand-mark" aria-hidden="true">
                    <span class="material-symbols-outlined">ecg_heart</span>
                </span>
                Tarrlok
            </a>
            <nav class="lp-nav-links" aria-label="Landing">
                <a href="#portals">Portals</a>
                <a href="#how">How it works</a>
                <a href="#impact">Impact</a>
                <a href="{{ route('login') }}" class="lp-nav-signin">Sign in</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="lp-hero">
            <div class="lp-hero-copy">
                <p class="lp-hero-eyebrow">Blockchain-verified blood network</p>
                <h1>Every donor is a <em>pulse</em> this network keeps alive.</h1>
                <p>
                    Tarrlok connects donors, collection staff, and network admins on one ledger —
                    so a unit of blood is never more than a few clicks from the patient who needs it.
                </p>
                <div class="lp-hero-actions">
                    <a href="{{ route('track.index') }}" class="lp-btn lp-btn-primary">
                        <span class="material-symbols-outlined">water_drop</span>
                        Track a donation
                    </a>
                    <a href="#portals" class="lp-btn lp-btn-ghost">Explore portals</a>
                </div>
            </div>
            <div class="lp-hero-art" aria-hidden="true">
                <svg class="lp-pulse-line" viewBox="0 0 420 120" fill="none">
                    <path d="M0 60 H70 L86 60 L98 22 L118 98 L136 60 H190 L206 60 L218 38 L236 82 L252 60 H420" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span class="lp-hero-rule"></span>
            </div>
        </section>

        <section class="lp-stock" aria-label="Network blood group availability">
            <div class="lp-stock-inner">
                <p class="lp-stock-label">Live network stock</p>
                <div class="lp-stock-grid">
                    @foreach ($stock as $item)
                        <article @class(['lp-stock-card', 'is-low' => $item['low']])>
                            <div class="lp-stock-top">
                                <strong>{{ $item['group'] }}</strong>
                                @if ($item['low'])
                                    <span class="lp-stock-dot" title="No cleared units in network stock"></span>
                                @endif
                            </div>
                            <span class="lp-stock-count">{{ $item['count'] }} {{ \Illuminate\Support\Str::plural('unit', $item['count']) }}</span>
                            <div class="lp-stock-bar" aria-hidden="true">
                                <i style="--w: {{ $item['low'] ? 18 : max(14, $item['percent']) }}%"></i>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="lp-portals" id="portals">
            <p class="lp-kicker">Three ways in</p>
            <h2>One network, three vantage points.</h2>
            <p class="lp-lede">
                Donors, collection staff, and network admins each see exactly what their role needs —
                nothing borrowed from a generic dashboard template.
            </p>

            <div class="lp-portal-grid">
                <article class="lp-portal-card accent-red">
                    <span class="lp-portal-icon" aria-hidden="true">
                        <span class="material-symbols-outlined">water_drop</span>
                    </span>
                    <p class="lp-portal-label">Donor portal</p>
                    <h3>Give blood. Track your impact.</h3>
                    <p>Look up a unit ID from your donation slip and follow screening, stock, and partner transfer — without creating an account.</p>
                    <div class="lp-portal-actions">
                        <a href="{{ route('track.index') }}" class="lp-btn lp-btn-light">Track unit</a>
                    </div>
                </article>

                <article class="lp-portal-card accent-gold">
                    <span class="lp-portal-icon" aria-hidden="true">
                        <span class="material-symbols-outlined">assignment</span>
                    </span>
                    <p class="lp-portal-label">Staff portal</p>
                    <h3>Manage collection and inventory.</h3>
                    <p>Hospital and lab teams register units, complete screening, and issue blood to partner facilities on the Tarrlok network.</p>
                    <div class="lp-portal-actions">
                        <a href="{{ route('login') }}" class="lp-btn lp-btn-light">Sign in</a>
                        <a href="{{ route('register') }}" class="lp-text-link">Register facility <span aria-hidden="true">›</span></a>
                    </div>
                </article>

                <article class="lp-portal-card accent-slate">
                    <span class="lp-portal-icon" aria-hidden="true">
                        <span class="material-symbols-outlined">verified_user</span>
                    </span>
                    <p class="lp-portal-label">Admin portal</p>
                    <h3>Oversee the entire network.</h3>
                    <p>Approve hospital registrations, review the audit trail, and monitor on-chain anchors for every critical lifecycle event.</p>
                    <div class="lp-portal-actions">
                        <a href="{{ route('login') }}" class="lp-btn lp-btn-light">Sign in</a>
                    </div>
                </article>
            </div>
        </section>

        <section class="lp-how" id="how">
            <p class="lp-kicker">How it works</p>
            <h2>From draw to dispatch, on one ledger.</h2>
            <div class="lp-how-grid">
                <article>
                    <span>01</span>
                    <h3>Register the unit</h3>
                    <p>Lab staff record the donation, donor, blood group, and expiry. The event is anchored on-chain.</p>
                </article>
                <article>
                    <span>02</span>
                    <h3>Screen and clear</h3>
                    <p>HIV, Hep B/C, and syphilis results decide whether a unit enters inventory or is discarded.</p>
                </article>
                <article>
                    <span>03</span>
                    <h3>Issue and track</h3>
                    <p>Partner hospitals request stock. Transfers are timestamped, and donors can follow the unit ID publicly.</p>
                </article>
            </div>
        </section>

        <section class="lp-impact" id="impact">
            <div class="lp-impact-inner">
                <p class="lp-impact-kicker">
                    <span class="material-symbols-outlined">groups</span>
                    Network impact
                </p>
                <h2>Numbers a spreadsheet can’t feel, but keeps honest anyway.</h2>
                <p>Every figure below is drawn from live donation and inventory records, not a marketing estimate.</p>
                <div class="lp-impact-grid">
                    <div>
                        <strong>{{ number_format($unitsThisYear) }}</strong>
                        <span>units donated this year</span>
                    </div>
                    <div>
                        <strong>{{ number_format($hospitalsOnNetwork) }}</strong>
                        <span>hospitals on the network</span>
                    </div>
                    <div>
                        <strong>{{ number_format($availableUnits) }}</strong>
                        <span>cleared units in stock</span>
                    </div>
                    <div>
                        <strong>{{ number_format($anchoredUnits) }}</strong>
                        <span>units with on-chain anchors</span>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="lp-footer-wrap">
        <div class="lp-footer">
            <div class="lp-footer-brand">
                <a href="{{ route('home') }}" class="lp-brand">
                    <span class="lp-brand-mark" aria-hidden="true">
                        <span class="material-symbols-outlined">ecg_heart</span>
                    </span>
                    Tarrlok
                </a>
                <p>A shared ledger for donors, staff, and admins — built so no unit of blood goes untracked.</p>
            </div>
            <div>
                <h3>Portals</h3>
                <a href="{{ route('track.index') }}">Track a donation</a>
                <a href="{{ route('login') }}">Staff sign in</a>
                <a href="{{ route('login') }}">Admin sign in</a>
            </div>
            <div>
                <h3>Network</h3>
                <a href="#how">How it works</a>
                <a href="#impact">Impact</a>
                <a href="{{ route('register') }}">Register a facility</a>
            </div>
            <div>
                <h3>Support</h3>
                <a href="{{ route('track.index') }}">Donor unit lookup</a>
                <a href="{{ route('login') }}">Hospital portal</a>
                <a href="{{ route('register') }}">Join the network</a>
            </div>
        </div>
        <p class="lp-footer-note">© {{ now()->year }} Tarrlok · HeFRA-network blood bank management</p>
    </footer>
</div>
@endsection
