@extends('layouts.tarrlok-guest')

@section('title', 'Track Donation - Tarrlok')

@section('content')
<main class="login-shell" style="max-width:480px;">
    <div class="login-brand">
        <div class="login-brand-icon">
            <span class="material-symbols-outlined login-brand-glyph filled">favorite</span>
        </div>
        <h1 class="login-title">Track your donation</h1>
        <p class="login-subtitle">Enter the unit ID from your donation slip — you can only view that donation</p>
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

        <form class="login-form" method="POST" action="{{ route('track.lookup') }}">
            @csrf

            <div class="login-fields">
                <div class="login-field">
                    <label class="login-label" for="unit_code">Blood unit ID</label>
                    <div class="login-input-wrap">
                        <span class="material-symbols-outlined login-input-icon">qr_code_2</span>
                        <input
                            class="login-input"
                            id="unit_code"
                            name="unit_code"
                            type="text"
                            value="{{ old('unit_code') }}"
                            placeholder="e.g. UNIT-002-00001"
                            required
                            autofocus
                            autocomplete="off"
                            spellcheck="false"
                            style="text-transform:uppercase;"
                        >
                    </div>
                    <p class="hospital-field-hint" style="margin-top:8px;">
                        Lab staff give you this ID when your blood is registered. It works like a tracking number — no login required.
                    </p>
                </div>
            </div>

            <button class="login-submit" type="submit">
                <span class="material-symbols-outlined">search</span>
                Track donation
            </button>

            <div class="login-forgot-bottom">
                <a class="login-forgot-link" href="{{ route('login') }}">Hospital or lab staff sign in</a>
            </div>
        </form>
    </div>

    <div class="login-footer">
        <span class="material-symbols-outlined login-footer-icon">lock</span>
        You cannot browse other donors' units — only the ID you enter is shown.
    </div>
</main>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/hospital.css') }}">
@endpush
