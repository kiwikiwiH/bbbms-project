@extends('layouts.tarrlok-hospital')

@section('title', 'Partner Exchange - Tarrlok')

@section('page_title', 'Partner exchange')
@section('page_subtitle')
    Approved hospitals on the Tarrlok network — request blood from a partner
@endsection

@section('content')
<div class="hospital-requests-toolbar">
    <form class="hospital-search-form" method="GET" action="{{ route('hospital.partners') }}">
        <span class="material-symbols-outlined">search</span>
        <input
            type="search"
            name="q"
            value="{{ $search }}"
            placeholder="Search partner name or city..."
            class="hospital-search-input"
        >
    </form>
    <a href="{{ route('hospital.requests.create') }}" class="hospital-btn hospital-btn-primary">
        <span class="material-symbols-outlined">add</span>
        New blood request
    </a>
</div>

<div class="hospital-card">
    @if ($partners->isEmpty())
        <div class="hospital-placeholder">
            <div class="hospital-placeholder-icon">
                <span class="material-symbols-outlined">swap_horiz</span>
            </div>
            <h2>No partners yet</h2>
            <p>When other HeFRA-licensed hospitals register and are approved by Tarrlok, they will appear here for blood exchange.</p>
        </div>
    @else
        <div class="hospital-partner-grid">
            @foreach ($partners as $partner)
                <article class="hospital-partner-card">
                    <div class="hospital-partner-card-head">
                        <span class="material-symbols-outlined hospital-partner-icon">local_hospital</span>
                        <div>
                            <h3 class="hospital-partner-name">{{ $partner->name }}</h3>
                            <p class="hospital-partner-meta">{{ $partner->city }}, {{ $partner->regionLabel() }}</p>
                        </div>
                    </div>
                    <dl class="hospital-partner-details">
                        <div>
                            <dt>Type</dt>
                            <dd>{{ $partner->typeLabel() }}</dd>
                        </div>
                        <div>
                            <dt>HeFRA</dt>
                            <dd>{{ $partner->license_id }}</dd>
                        </div>
                    </dl>
                    <a href="{{ route('hospital.requests.create', ['partner' => $partner->id]) }}" class="hospital-btn hospital-btn-primary hospital-btn-block">
                        <span class="material-symbols-outlined">bloodtype</span>
                        Request blood
                    </a>
                </article>
            @endforeach
        </div>
    @endif
</div>

<p class="hospital-flow-note">
    <span class="material-symbols-outlined">info</span>
    Requests you send appear under <strong>Blood Requests → Outgoing</strong>. The partner hospital approves and issues units from their inventory.
</p>
@endsection
