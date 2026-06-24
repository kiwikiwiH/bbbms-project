@php
    $route = request()->route()->getName();
@endphp
<nav class="admin-nav" aria-label="Admin navigation">
    <a href="{{ route('admin.dashboard') }}" @class(['active' => $route === 'admin.dashboard'])>
        <span class="material-symbols-outlined">dashboard</span>
        Overview
    </a>
    <a href="{{ route('admin.registrations.index', ['status' => 'pending']) }}" @class(['active' => str_starts_with($route ?? '', 'admin.registrations')])>
        <span class="material-symbols-outlined">clinical_notes</span>
        Facility Registrations
    </a>
</nav>
