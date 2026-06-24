@php
    $user = auth()->user();
    $hospital = $user->hospital;
    $route = request()->route()->getName();
@endphp
<aside class="hospital-sidebar" aria-label="Lab navigation">
    <a href="{{ route('lab.dashboard') }}" class="hospital-sidebar-brand">
        <span class="material-symbols-outlined hospital-sidebar-brand-icon filled">bloodtype</span>
        <span>
            <span class="hospital-sidebar-brand-text">Tarrlok</span>
            <span class="hospital-sidebar-badge">Lab Portal</span>
        </span>
    </a>

    @if ($hospital)
        <div class="hospital-facility-chip">
            <p class="hospital-facility-name">{{ $hospital->name }}</p>
            <p class="hospital-facility-meta">{{ $user->job_title }}</p>
        </div>
    @endif

    <nav class="hospital-sidebar-nav">
        <a href="{{ route('lab.dashboard') }}" @class(['active' => $route === 'lab.dashboard']) title="Overview">
            <span class="material-symbols-outlined">dashboard</span>
            <span>Overview</span>
        </a>
        <a href="{{ route('lab.units.create') }}" @class(['active' => $route === 'lab.units.create']) title="Register unit">
            <span class="material-symbols-outlined">add_circle</span>
            <span>Register unit</span>
        </a>
        <a href="{{ route('lab.units.index') }}" @class(['active' => $route === 'lab.units.index']) title="Inventory">
            <span class="material-symbols-outlined">inventory_2</span>
            <span>Inventory</span>
        </a>
    </nav>

    <div class="hospital-sidebar-foot">
        <div class="hospital-sidebar-user">
            <strong>{{ $user->name }}</strong>
            Lab staff
        </div>
        <form class="hospital-logout-form" method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">
                <span class="material-symbols-outlined">logout</span>
                <span>Sign out</span>
            </button>
        </form>
    </div>
</aside>
