@extends('layouts.tarrlok-guest')

@section('title', 'Reset Password - Tarrlok')

@section('content')
<main class="login-shell">
    <div class="login-brand">
        <div class="login-brand-icon">
            <span class="material-symbols-outlined login-brand-glyph filled">lock_reset</span>
        </div>
        <h1 class="login-title">Choose new password</h1>
        <p class="login-subtitle">Set a new secure password for your Tarrlok account</p>
    </div>

    <div class="login-card">
        <div class="login-card-accent"></div>

        @if ($errors->any())
            <div class="login-alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form class="login-form" method="POST" action="{{ route('password.store') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div class="login-fields">
                <div class="login-field">
                    <label class="login-label" for="email">Email address</label>
                    <div class="login-input-wrap">
                        <span class="material-symbols-outlined login-input-icon">mail</span>
                        <input
                            class="login-input"
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email', $request->email) }}"
                            required
                            autofocus
                            autocomplete="username"
                        >
                    </div>
                </div>

                <div class="login-field">
                    <label class="login-label" for="password">New password</label>
                    <div class="login-input-wrap">
                        <span class="material-symbols-outlined login-input-icon">lock</span>
                        <input
                            class="login-input"
                            id="password"
                            name="password"
                            type="password"
                            required
                            autocomplete="new-password"
                        >
                    </div>
                </div>

                <div class="login-field">
                    <label class="login-label" for="password_confirmation">Confirm password</label>
                    <div class="login-input-wrap">
                        <span class="material-symbols-outlined login-input-icon">lock</span>
                        <input
                            class="login-input"
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            required
                            autocomplete="new-password"
                        >
                    </div>
                </div>
            </div>

            <button class="login-submit" type="submit">
                <span class="material-symbols-outlined">verified_user</span>
                Reset password
            </button>
        </form>
    </div>
</main>
@endsection
