<header class="hospital-topbar">
    <div class="hospital-topbar-lead">
        <button
            type="button"
            class="hospital-menu-toggle"
            data-sidebar-toggle
            aria-label="Open navigation"
            aria-controls="hospital-sidebar"
            aria-expanded="false"
        >
            <span class="material-symbols-outlined">menu</span>
        </button>
        <div>
            <h1 class="hospital-topbar-title">@yield('page_title', 'Overview')</h1>
            @hasSection('page_subtitle')
                <p class="hospital-topbar-sub">@yield('page_subtitle')</p>
            @endif
        </div>
    </div>
    <span class="hospital-topbar-status">
        <span class="material-symbols-outlined" style="font-size:16px;">verified</span>
        Network approved
    </span>
</header>
