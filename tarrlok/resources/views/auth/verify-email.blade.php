@extends('layouts.tarrlok-guest')

@section('title', 'Verify Email - Tarrlok')

@section('content')
<main class="login-shell">
    <div class="login-brand">
        <div class="login-brand-icon">
            <span class="material-symbols-outlined login-brand-glyph filled">mail</span>
        </div>
        <h1 class="login-title">Verify your email</h1>
        <p class="login-subtitle">Check your inbox for the verification link we sent you.</p>
    </div>

    <div class="login-card">
        <div class="login-card-accent"></div>

        @if (session('status') === 'verification-link-sent')
            <div class="login-alert ok">A new verification link has been sent to your email address.</div>
        @endif

        <div class="login-form">
            <form method="POST" action="{{ route('verification.send') }}" style="margin-bottom:16px;">
                @csrf
                <button class="login-submit" type="submit">
                    <span class="material-symbols-outlined">send</span>
                    Resend verification email
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <div class="login-forgot-bottom">
                    <button type="submit" class="login-forgot-link" style="background:none;border:none;cursor:pointer;padding:0;">
                        Log out
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>
@endsection
