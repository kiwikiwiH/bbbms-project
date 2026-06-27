@php
    $user = auth()->user();
    $hospital = $user->hospital;
    $route = request()->route()->getName();
@endphp
<aside class="hospital-sidebar" aria-label="Hospital navigation">
    <a href="{{ route('hospital.dashboard') }}" class="hospital-sidebar-brand">
        <span class="material-symbols-outlined hospital-sidebar-brand-icon filled">bloodtype</span>
        <span>
            <span class="hospital-sidebar-brand-text">Tarrlok</span>
            <span class="hospital-sidebar-badge">Hospital Portal</span>
        </span>
    </a>

    @if ($hospital)
        <div class="hospital-facility-chip">
            <p class="hospital-facility-name">{{ $hospital->name }}</p>
            <p class="hospital-facility-meta">{{ $hospital->city }}, {{ $hospital->regionLabel() }}</p>
        </div>
    @endif

    <nav class="hospital-sidebar-nav">
        <a href="{{ route('hospital.dashboard') }}" @class(['active' => $route === 'hospital.dashboard']) title="Overview">
            <span class="material-symbols-outlined">dashboard</span>
            <span>Overview</span>
        </a>
        <a href="{{ route('hospital.inventory') }}" @class(['active' => $route === 'hospital.inventory']) title="Blood Inventory">
            <span class="material-symbols-outlined">inventory_2</span>
            <span>Blood Inventory</span>
        </a>
        <a href="{{ route('hospital.requests') }}" @class(['active' => str_starts_with($route ?? '', 'hospital.requests')]) title="Blood Requests">
            <span class="material-symbols-outlined">bloodtype</span>
            <span>Blood Requests</span>
        </a>
        <a href="{{ route('hospital.partners') }}" @class(['active' => $route === 'hospital.partners']) title="Partner Exchange">
            <span class="material-symbols-outlined">swap_horiz</span>
            <span>Partner Exchange</span>
        </a>
        <a href="{{ route('hospital.trace') }}" @class(['active' => str_starts_with($route ?? '', 'hospital.trace')]) title="Trace Unit">
            <span class="material-symbols-outlined">timeline</span>
            <span>Trace Unit</span>
        </a>
        <a href="{{ route('hospital.facility') }}" @class(['active' => $route === 'hospital.facility']) title="Facility Profile">
            <span class="material-symbols-outlined">local_hospital</span>
            <span>Facility Profile</span>
        </a>
        <a href="{{ route('hospital.lab-staff.index') }}" @class(['active' => str_starts_with($route ?? '', 'hospital.lab-staff')]) title="Lab Staff">
            <span class="material-symbols-outlined">science</span>
            <span>Lab Staff</span>
        </a>
    </nav>

    <div class="hospital-sidebar-foot">
        <div class="hospital-sidebar-user">
            <strong>{{ $user->name }}</strong>
            {{ $user->job_title ?? 'Administrator' }}
            <a href="{{ route('profile.edit') }}" style="display:block;margin-top:6px;font-size:12px;color:#a20513;">Profile</a>
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
