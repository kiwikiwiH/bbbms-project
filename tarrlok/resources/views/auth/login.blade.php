@extends('layouts.tarrlok-guest')

@section('title', 'Login - Tarrlok')

@section('content')
<main class="login-shell">
    {{-- Branding Header --}}
    <div class="login-brand">
        <div class="login-brand-icon">
            <span class="material-symbols-outlined login-brand-glyph filled">bloodtype</span>
        </div>
        <h1 class="login-title">Tarrlok</h1>
        <p class="login-subtitle">Blockchain-Verified Blood Traceability</p>
    </div>

    {{-- Login Card --}}
    <div class="login-card">
        <div class="login-card-accent"></div>

        @if (session('status'))
            <div class="login-alert ok">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="login-alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form class="login-form" method="POST" action="{{ route('login') }}">
            @csrf

            <div class="login-fields">
                <div class="login-field">
                    <label class="login-label" for="email">Email Address</label>
                    <div class="login-input-wrap">
                        <span class="material-symbols-outlined login-input-icon">mail</span>
                        <input
                            class="login-input"
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            placeholder="Enter clinical ID or email"
                            required
                            autofocus
                            autocomplete="username"
                        >
                    </div>
                </div>

                <div class="login-field">
                    <label class="login-label" for="password">Secure Password</label>
                    <div class="login-input-wrap">
                        <span class="material-symbols-outlined login-input-icon">lock</span>
                        <input
                            class="login-input login-input-password"
                            id="password"
                            name="password"
                            type="password"
                            placeholder="••••••••"
                            required
                            autocomplete="current-password"
                        >
                        <button
                            type="button"
                            class="login-toggle-password"
                            data-toggle-password
                            aria-label="Toggle password visibility"
                        >
                            <span class="material-symbols-outlined login-input-icon">visibility_off</span>
                        </button>
                    </div>
                </div>
            </div>

            <button class="login-submit" type="submit">
                <span class="material-symbols-outlined">login</span>
                Sign In
            </button>

            <div class="login-remember">
                <input
                    id="remember_me"
                    name="remember"
                    type="checkbox"
                    {{ old('remember') ? 'checked' : '' }}
                >
                <label for="remember_me">Remember terminal session</label>
            </div>

            @if (Route::has('password.request'))
                <div class="login-forgot-bottom">
                    <a class="login-forgot-link" href="{{ route('password.request') }}">Forgot your password?</a>
                </div>
            @endif
        </form>

        <div class="login-register">
            <p>
                Unregistered clinical node?
                <a class="login-link" href="{{ route('register') }}">Request Access</a>
            </p>
        </div>
    </div>

    {{-- Footer Text --}}
    <div class="login-footer">
        <span class="material-symbols-outlined login-footer-icon">verified_user</span>
        System access monitored via distributed ledger.
    </div>
</main>
@endsection
