@extends('layouts.tarrlok-guest')

@section('title', 'Confirm Password - Tarrlok')

@section('content')
<main class="login-shell">
    <div class="login-brand">
        <div class="login-brand-icon">
            <span class="material-symbols-outlined login-brand-glyph filled">lock</span>
        </div>
        <h1 class="login-title">Confirm password</h1>
        <p class="login-subtitle">This is a secure area. Please confirm your password to continue.</p>
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

        <form class="login-form" method="POST" action="{{ route('password.confirm') }}">
            @csrf

            <div class="login-field">
                <label class="login-label" for="password">Password</label>
                <div class="login-input-wrap">
                    <span class="material-symbols-outlined login-input-icon">lock</span>
                    <input
                        class="login-input"
                        id="password"
                        name="password"
                        type="password"
                        required
                        autofocus
                        autocomplete="current-password"
                    >
                </div>
            </div>

            <button class="login-submit" type="submit">
                <span class="material-symbols-outlined">check</span>
                Confirm
            </button>
        </form>
    </div>
</main>
@endsection
