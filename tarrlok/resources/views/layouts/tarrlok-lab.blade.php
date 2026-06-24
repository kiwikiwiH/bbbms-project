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
    @stack('scripts')
</body>
</html>
