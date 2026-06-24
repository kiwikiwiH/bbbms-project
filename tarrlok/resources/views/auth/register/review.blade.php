@extends('layouts.tarrlok-register')

@section('title', 'Review & Submit - Tarrlok')

@section('content')
<main class="reg-shell">
    @include('auth.register.partials.header')

    <div class="reg-card">
        <div class="reg-card-accent"></div>

        @include('auth.register.partials.progress', ['step' => 3, 'percent' => 100, 'label' => 'Review & Submit'])

        <h1 class="reg-heading">Review Your Application</h1>
        <p class="reg-subheading">Confirm your facility and administrator details before submitting for verification.</p>

        <div class="reg-review-grid">
            <section class="reg-summary">
                <div class="reg-summary-head">
                    <h2 class="reg-summary-title">
                        <span class="material-symbols-outlined">local_hospital</span>
                        Hospital Details
                    </h2>
                    <a class="reg-summary-edit" href="{{ route('register') }}">Edit</a>
                </div>
                <dl class="reg-summary-list">
                    <div>
                        <dt>Facility Name</dt>
                        <dd>{{ $facility['name'] }}</dd>
                    </div>
                    <div>
                        <dt>Institution Type</dt>
                        <dd>{{ $institutionTypes[$facility['type']] ?? $facility['type'] }}</dd>
                    </div>
                    <div>
                        <dt>Region</dt>
                        <dd>{{ $regions[$facility['region']] ?? $facility['region'] }}</dd>
                    </div>
                    <div>
                        <dt>City / District</dt>
                        <dd>{{ $facility['city'] }}</dd>
                    </div>
                    <div>
                        <dt>HeFRA License</dt>
                        <dd>{{ $facility['license_id'] }}</dd>
                    </div>
                    <div>
                        <dt>Official Phone</dt>
                        <dd>{{ $facility['phone'] }}</dd>
                    </div>
                    <div>
                        <dt>Official Email</dt>
                        <dd>{{ $facility['email'] }}</dd>
                    </div>
                </dl>
            </section>

            <section class="reg-summary">
                <div class="reg-summary-head">
                    <h2 class="reg-summary-title">
                        <span class="material-symbols-outlined">admin_panel_settings</span>
                        Administrator Account
                    </h2>
                    <a class="reg-summary-edit" href="{{ route('register.step2') }}">Edit</a>
                </div>
                <dl class="reg-summary-list">
                    <div>
                        <dt>Full Name</dt>
                        <dd>{{ $account['name'] }}</dd>
                    </div>
                    <div>
                        <dt>Job Role</dt>
                        <dd>{{ $account['job_title'] }}</dd>
                    </div>
                    <div>
                        <dt>Work Email</dt>
                        <dd>{{ $account['email'] }}</dd>
                    </div>
                    <div>
                        <dt>Password</dt>
                        <dd>&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</dd>
                    </div>
                </dl>
            </section>
        </div>

        <form class="reg-submit-wrap" method="POST" action="{{ route('register.submit') }}">
            @csrf
            <button class="reg-btn reg-btn-primary" type="submit">
                <span class="material-symbols-outlined">send</span>
                Request Access
            </button>
        </form>
    </div>

    @include('auth.register.partials.footer')
</main>
@endsection
