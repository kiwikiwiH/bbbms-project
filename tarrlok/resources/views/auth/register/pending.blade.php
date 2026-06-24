@extends('layouts.tarrlok-register')

@section('title', 'Pending Verification - Tarrlok')

@section('content')
<main class="reg-shell">
    @include('auth.register.partials.header')

    <div class="reg-card">
        <div class="reg-card-accent"></div>

        <div class="reg-pending">
            <div class="reg-pending-icon">
                <span class="material-symbols-outlined">hourglass_top</span>
            </div>

            <h1 class="reg-heading">Application Submitted</h1>
            <p class="reg-subheading">Your facility registration is pending verification by the Tarrlok team.</p>

            <div class="reg-pending-note">
                <strong>What happens next?</strong>
                <ul style="margin: 8px 0 0; padding-left: 20px;">
                    <li>Our team will verify your HeFRA license and facility details.</li>
                    <li>You will receive an email at your work address once approved.</li>
                    <li>After approval, you can sign in and access the blood bank dashboard.</li>
                </ul>
            </div>

            <div class="reg-submit-wrap" style="margin-top: 28px;">
                <a class="reg-btn reg-btn-primary" href="{{ route('login') }}">
                    <span class="material-symbols-outlined">login</span>
                    Return to Sign In
                </a>
            </div>
        </div>
    </div>

    @include('auth.register.partials.footer')
</main>
@endsection
