<!DOCTYPE html>
<html class="light" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Lab Portal - Tarrlok')</title>
    <link rel="stylesheet" href="{{ asset('assets/css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/hospital.css') }}">
    @stack('styles')
</head>
<body class="hospital-page">
    <div class="hospital-app">
        <div class="hospital-sidebar-backdrop" data-sidebar-backdrop hidden></div>
        @include('lab.partials.sidebar')

        <div class="hospital-main">
            @include('lab.partials.topbar')

            <main class="hospital-content">
                @if (session('status'))
                    <div class="hospital-alert ok">{{ session('status') }}</div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
    <script>
        (function () {
            const page = document.body;
            const sidebar = document.getElementById('hospital-sidebar');
            const backdrop = document.querySelector('[data-sidebar-backdrop]');
            const toggles = document.querySelectorAll('[data-sidebar-toggle]');
            if (!sidebar || !backdrop || !toggles.length) return;

            const setOpen = (open) => {
                page.classList.toggle('sidebar-open', open);
                backdrop.hidden = !open;
                toggles.forEach((btn) => {
                    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
                    btn.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
                    const icon = btn.querySelector('.material-symbols-outlined');
                    if (icon) icon.textContent = open ? 'close' : 'menu';
                });
            };

            toggles.forEach((btn) => btn.addEventListener('click', () => {
                setOpen(!page.classList.contains('sidebar-open'));
            }));
            backdrop.addEventListener('click', () => setOpen(false));
            sidebar.querySelectorAll('a').forEach((link) => {
                link.addEventListener('click', () => setOpen(false));
            });
            window.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') setOpen(false);
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
