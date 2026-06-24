@extends('layouts.tarrlok-hospital')

@section('title', 'Issue Lab Account - Tarrlok')

@section('page_title', 'Issue lab account')
@section('page_subtitle', 'Create a sign-in for laboratory staff at your facility')

@section('content')
<div class="hospital-card" style="max-width:640px;">
    <div class="hospital-card-head">
        <h2 class="hospital-card-title">New lab staff member</h2>
    </div>
    <div class="hospital-card-body">
        @if ($errors->any())
            <div class="hospital-alert" style="background:#ffdad6;border:1px solid #e4beba;color:#93000a;margin-bottom:20px;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form class="hospital-form" method="POST" action="{{ route('hospital.lab-staff.store') }}">
            @csrf

            <div class="hospital-field">
                <label class="hospital-label" for="name">Full name</label>
                <input class="hospital-input" id="name" name="name" type="text" value="{{ old('name') }}" required autofocus>
            </div>

            <div class="hospital-field">
                <label class="hospital-label" for="job_title">Job title</label>
                <input class="hospital-input" id="job_title" name="job_title" type="text" value="{{ old('job_title') }}" placeholder="e.g. Laboratory Technician" required>
            </div>

            <div class="hospital-field">
                <label class="hospital-label" for="email">Work email</label>
                <input class="hospital-input" id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="off">
            </div>

            <div class="hospital-field">
                <label class="hospital-label" for="password">Temporary password</label>
                <input class="hospital-input" id="password" name="password" type="password" required autocomplete="new-password">
                <p class="hospital-field-hint">Minimum 8 characters. Share this securely with the lab staff member.</p>
            </div>

            <div class="hospital-field">
                <label class="hospital-label" for="password_confirmation">Confirm password</label>
                <input class="hospital-input" id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
            </div>

            <div class="hospital-form-actions">
                <a href="{{ route('hospital.lab-staff.index') }}" class="hospital-btn hospital-btn-outline">Cancel</a>
                <button type="submit" class="hospital-btn hospital-btn-primary">
                    <span class="material-symbols-outlined">check</span>
                    Create account
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
