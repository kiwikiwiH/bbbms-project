@extends('layouts.tarrlok-guest')

@section('title', 'Profile - Tarrlok')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/hospital.css') }}">
<style>
    .profile-shell { max-width: 560px; }
    .profile-card { margin-bottom: 16px; }
    .profile-card .login-form { gap: 0; }
    .profile-section-title {
        margin: 0 0 4px;
        font-size: 16px;
        font-weight: 700;
        color: #181c20;
    }
    .profile-section-hint {
        margin: 0 0 16px;
        font-size: 13px;
        color: #555f6f;
    }
</style>
@endpush

@section('content')
<main class="login-shell profile-shell">
    <div class="login-brand">
        <div class="login-brand-icon">
            <span class="material-symbols-outlined login-brand-glyph filled">person</span>
        </div>
        <h1 class="login-title">Your profile</h1>
        <p class="login-subtitle">{{ $user->name }} · {{ ucfirst($user->role) }}</p>
    </div>

    @if (session('status') === 'profile-updated')
        <div class="login-alert ok" style="margin-bottom:16px;">Profile updated.</div>
    @endif

    <div class="login-card profile-card">
        <div class="login-card-accent"></div>
        <div style="padding:20px;">
            <h2 class="profile-section-title">Profile information</h2>
            <p class="profile-section-hint">Update your name and work email.</p>

            <form class="hospital-form login-form" method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('patch')

                <div class="hospital-field">
                    <label class="hospital-label" for="name">Name</label>
                    <input class="hospital-input" id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required>
                    @error('name')<p class="hospital-field-hint" style="color:#93000a;">{{ $message }}</p>@enderror
                </div>

                <div class="hospital-field">
                    <label class="hospital-label" for="email">Email</label>
                    <input class="hospital-input" id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required>
                    @error('email')<p class="hospital-field-hint" style="color:#93000a;">{{ $message }}</p>@enderror
                </div>

                <button class="login-submit" type="submit" style="margin-top:8px;">
                    <span class="material-symbols-outlined">save</span>
                    Save changes
                </button>
            </form>
        </div>
    </div>

    <div class="login-card profile-card">
        <div class="login-card-accent"></div>
        <div style="padding:20px;">
            <h2 class="profile-section-title">Update password</h2>
            <p class="profile-section-hint">Use a strong password you don't use elsewhere.</p>

            <form class="hospital-form login-form" method="POST" action="{{ route('password.update') }}">
                @csrf
                @method('put')

                <div class="hospital-field">
                    <label class="hospital-label" for="current_password">Current password</label>
                    <input class="hospital-input" id="current_password" name="current_password" type="password" required autocomplete="current-password">
                    @error('current_password', 'updatePassword')<p class="hospital-field-hint" style="color:#93000a;">{{ $message }}</p>@enderror
                </div>

                <div class="hospital-field">
                    <label class="hospital-label" for="password">New password</label>
                    <input class="hospital-input" id="password" name="password" type="password" required autocomplete="new-password">
                    @error('password', 'updatePassword')<p class="hospital-field-hint" style="color:#93000a;">{{ $message }}</p>@enderror
                </div>

                <div class="hospital-field">
                    <label class="hospital-label" for="password_confirmation">Confirm password</label>
                    <input class="hospital-input" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
                </div>

                <button class="login-submit" type="submit" style="margin-top:8px;">
                    <span class="material-symbols-outlined">lock</span>
                    Update password
                </button>
            </form>
        </div>
    </div>

    <div class="login-forgot-bottom" style="text-align:center;margin-top:8px;">
        @php
            $backRoute = match (true) {
                $user->isAdmin() => route('admin.dashboard'),
                $user->isLab() => route('lab.dashboard'),
                default => route('hospital.dashboard'),
            };
        @endphp
        <a class="login-forgot-link" href="{{ $backRoute }}">Back to dashboard</a>
    </div>
</main>
@endsection
