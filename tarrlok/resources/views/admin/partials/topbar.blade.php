<header class="admin-topbar">
    <a href="{{ route('admin.dashboard') }}" class="admin-brand">
        <span class="material-symbols-outlined admin-brand-icon filled">bloodtype</span>
        <span class="admin-brand-text">Tarrlok</span>
        <span class="admin-brand-badge">Platform Admin</span>
    </a>
    <div class="admin-topbar-actions">
        <span class="admin-user">{{ auth()->user()->name }}</span>
        <form class="admin-logout-form" method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Sign out</button>
        </form>
    </div>
</header>
