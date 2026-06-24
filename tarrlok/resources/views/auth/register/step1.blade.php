@extends('layouts.tarrlok-register')

@section('title', 'Register Facility - Tarrlok')

@section('content')
@php
    $phoneLocal = old('phone_local');
    if (! $phoneLocal && ! empty($facility['phone'])) {
        $phoneLocal = preg_replace('/^\+233/', '', $facility['phone']);
    }
@endphp

<main class="reg-shell">
    @include('auth.register.partials.header')

    <div class="reg-card">
        <div class="reg-card-accent"></div>

        @include('auth.register.partials.progress', ['step' => 1, 'percent' => 25, 'label' => 'Facility Details'])

        <h1 class="reg-heading">Register Your Facility</h1>
        <p class="reg-subheading">Join Ghana's blockchain-verified blood bank network. Complete your hospital details to begin.</p>

        @if ($errors->any())
            <div class="login-alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form class="reg-form" method="POST" action="{{ route('register.step1.store') }}">
            @csrf

            <div class="reg-grid reg-grid-2">
                <div class="reg-field reg-span-2">
                    <label class="reg-label" for="name">Hospital / Facility Name</label>
                    <div class="reg-input-wrap">
                        <span class="material-symbols-outlined">local_hospital</span>
                        <input
                            class="reg-input"
                            id="name"
                            name="name"
                            type="text"
                            value="{{ old('name', $facility['name'] ?? '') }}"
                            placeholder="e.g. Korle Bu Teaching Hospital"
                            required
                            autofocus
                        >
                    </div>
                </div>

                <div class="reg-field">
                    <label class="reg-label" for="type">Institution Type</label>
                    <div class="reg-input-wrap">
                        <span class="material-symbols-outlined">domain</span>
                        <select class="reg-select" id="type" name="type" required>
                            <option value="" disabled {{ old('type', $facility['type'] ?? '') ? '' : 'selected' }}>Select type</option>
                            @foreach ($institutionTypes as $value => $label)
                                <option value="{{ $value }}" @selected(old('type', $facility['type'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="reg-field">
                    <label class="reg-label" for="region">Ghana Region</label>
                    <div class="reg-input-wrap">
                        <span class="material-symbols-outlined">map</span>
                        <select class="reg-select" id="region" name="region" required>
                            <option value="" disabled {{ old('region', $facility['region'] ?? '') ? '' : 'selected' }}>Select region</option>
                            @foreach ($regions as $value => $label)
                                <option value="{{ $value }}" @selected(old('region', $facility['region'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="reg-field">
                    <label class="reg-label" for="city">City / District</label>
                    <div class="reg-input-wrap">
                        <span class="material-symbols-outlined">location_city</span>
                        <input
                            class="reg-input"
                            id="city"
                            name="city"
                            type="text"
                            value="{{ old('city', $facility['city'] ?? '') }}"
                            placeholder="e.g. Accra"
                            required
                        >
                    </div>
                </div>

                <div class="reg-field">
                    <label class="reg-label" for="license_id">HeFRA License ID</label>
                    <div class="reg-input-wrap">
                        <span class="material-symbols-outlined">badge</span>
                        <input
                            class="reg-input"
                            id="license_id"
                            name="license_id"
                            type="text"
                            value="{{ old('license_id', $facility['license_id'] ?? '') }}"
                            placeholder="HFRA-ACC-2024"
                            required
                        >
                    </div>
                    <p class="reg-hint">Format: HFRA-XXX-1234</p>
                </div>

                <div class="reg-field">
                    <label class="reg-label" for="phone_local">Official Phone</label>
                    <div class="reg-phone-wrap">
                        <span class="reg-phone-prefix">+233</span>
                        <input
                            class="reg-input reg-input-no-icon"
                            id="phone_local"
                            name="phone_local"
                            type="tel"
                            value="{{ $phoneLocal }}"
                            placeholder="24 123 4567"
                            inputmode="numeric"
                            pattern="\d{9}"
                            maxlength="9"
                            required
                        >
                    </div>
                </div>

                <div class="reg-field reg-span-2">
                    <label class="reg-label" for="email">Official Facility Email</label>
                    <div class="reg-input-wrap">
                        <span class="material-symbols-outlined">mail</span>
                        <input
                            class="reg-input"
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email', $facility['email'] ?? '') }}"
                            placeholder="bloodbank@hospital.gov.gh"
                            required
                        >
                    </div>
                </div>
            </div>

            <div class="reg-nav">
                <a class="reg-nav-link" href="{{ route('login') }}">Already registered? Sign in</a>
                <button class="reg-btn reg-btn-primary" type="submit">
                    Next: Account Details
                    <span class="material-symbols-outlined">arrow_forward</span>
                </button>
            </div>
        </form>
    </div>

    @include('auth.register.partials.footer')
</main>
@endsection
