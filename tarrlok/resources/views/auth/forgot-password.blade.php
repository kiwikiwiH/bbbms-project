@extends('layouts.tarrlok-guest')

@section('title', 'Forgot Password - Tarrlok')

@section('content')
<main class="login-shell">
    <div class="login-brand">
        <div class="login-brand-icon">
            <span class="material-symbols-outlined login-brand-glyph filled">bloodtype</span>
        </div>
        <h1 class="login-title">Reset password</h1>
        <p class="login-subtitle">We'll send a secure reset link to your work email</p>
    </div>

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

        <form class="login-form" method="POST" action="{{ route('password.email') }}">
            @csrf

            <div class="login-field">
                <label class="login-label" for="email">Email address</label>
                <div class="login-input-wrap">
                    <span class="material-symbols-outlined login-input-icon">mail</span>
                    <input
                        class="login-input"
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        placeholder="Enter your work email"
                        required
                        autofocus
                        autocomplete="username"
                    >
                </div>
            </div>

            <button class="login-submit" type="submit">
                <span class="material-symbols-outlined">mail</span>
                Email reset link
            </button>

            <div class="login-forgot-bottom">
                <a class="login-forgot-link" href="{{ route('login') }}">Back to sign in</a>
            </div>
        </form>
    </div>
</main>
@endsection
