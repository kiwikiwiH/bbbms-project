<!DOCTYPE html>
<html class="light" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin - Tarrlok')</title>
    <link rel="stylesheet" href="{{ asset('assets/css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/admin.css') }}">
    @stack('styles')
</head>
<body class="admin-page">
    <div class="admin-shell">
        @include('admin.partials.topbar')
        @include('admin.partials.nav')

        @if (session('status'))
            <div class="admin-alert ok">{{ session('status') }}</div>
        @endif

        @yield('content')
    </div>
    @stack('scripts')
</body>
</html>
