@extends('layouts.tarrlok-register')

@section('title', 'Account Holder - Tarrlok')

@section('content')
<main class="reg-shell">
    @include('auth.register.partials.header')

    <div class="reg-card">
        <div class="reg-card-accent"></div>

        @include('auth.register.partials.progress', ['step' => 2, 'percent' => 66, 'label' => 'Account Holder'])

        <h1 class="reg-heading">Administrator Account</h1>
        <p class="reg-subheading">Create the primary account for your facility's blood bank administrator.</p>

        @if ($errors->any())
            <div class="login-alert">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form class="reg-form" method="POST" action="{{ route('register.step2.store') }}">
            @csrf

            <div class="reg-grid reg-grid-2">
                <div class="reg-field reg-span-2">
                    <label class="reg-label" for="name">Full Name</label>
                    <div class="reg-input-wrap">
                        <span class="material-symbols-outlined">person</span>
                        <input
                            class="reg-input"
                            id="name"
                            name="name"
                            type="text"
                            value="{{ old('name', $account['name'] ?? '') }}"
                            placeholder="Dr. Kwame Mensah"
                            required
                            autofocus
                        >
                    </div>
                </div>

                <div class="reg-field reg-span-2">
                    <label class="reg-label" for="job_title">Job Role / Title</label>
                    <div class="reg-input-wrap">
                        <span class="material-symbols-outlined">work</span>
                        <input
                            class="reg-input"
                            id="job_title"
                            name="job_title"
                            type="text"
                            value="{{ old('job_title', $account['job_title'] ?? '') }}"
                            placeholder="e.g. Blood Bank Manager"
                            required
                        >
                    </div>
                </div>

                <div class="reg-field reg-span-2">
                    <label class="reg-label" for="email">Work Email</label>
                    <div class="reg-input-wrap">
                        <span class="material-symbols-outlined">alternate_email</span>
                        <input
                            class="reg-input"
                            id="email"
                            name="email"
                            type="email"
                            value="{{ old('email', $account['email'] ?? '') }}"
                            placeholder="admin@hospital.gov.gh"
                            required
                            autocomplete="username"
                        >
                    </div>
                </div>

                <div class="reg-field">
                    <label class="reg-label" for="password">Password</label>
                    <div class="reg-input-wrap">
                        <span class="material-symbols-outlined">lock</span>
                        <input
                            class="reg-input reg-input-password"
                            id="password"
                            name="password"
                            type="password"
                            placeholder="Minimum 8 characters"
                            required
                            autocomplete="new-password"
                        >
                        <button
                            type="button"
                            class="reg-toggle-password"
                            data-toggle-password
                            aria-label="Toggle password visibility"
                        >
                            <span class="material-symbols-outlined">visibility_off</span>
                        </button>
                    </div>
                </div>

                <div class="reg-field">
                    <label class="reg-label" for="password_confirmation">Confirm Password</label>
                    <div class="reg-input-wrap">
                        <span class="material-symbols-outlined">lock_reset</span>
                        <input
                            class="reg-input reg-input-password"
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            placeholder="Re-enter password"
                            required
                            autocomplete="new-password"
                        >
                        <button
                            type="button"
                            class="reg-toggle-password"
                            data-toggle-password
                            aria-label="Toggle password visibility"
                        >
                            <span class="material-symbols-outlined">visibility_off</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="reg-nav">
                <a class="reg-btn reg-btn-outline" href="{{ route('register') }}">
                    <span class="material-symbols-outlined">arrow_back</span>
                    Back
                </a>
                <button class="reg-btn reg-btn-primary" type="submit">
                    Next: Review
                    <span class="material-symbols-outlined">arrow_forward</span>
                </button>
            </div>
        </form>
    </div>

    @include('auth.register.partials.footer')
</main>
@endsection
