<header class="hospital-topbar">
    <div>
        <h1 class="hospital-topbar-title">@yield('page_title', 'Lab overview')</h1>
        @hasSection('page_subtitle')
            <p class="hospital-topbar-sub">@yield('page_subtitle')</p>
        @endif
    </div>
    <span class="hospital-topbar-status">
        <span class="material-symbols-outlined" style="font-size:16px;">science</span>
        Lab staff
    </span>
</header>
