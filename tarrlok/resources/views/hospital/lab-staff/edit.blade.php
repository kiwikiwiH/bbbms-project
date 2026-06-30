@extends('layouts.tarrlok-hospital')

@section('title', 'Edit Lab Staff - Tarrlok')

@section('page_title', 'Edit lab staff')
@section('page_subtitle')
    Update account for {{ $staff->name }}
@endsection

@section('content')
<div class="hospital-card" style="max-width:520px;">
    <div class="hospital-card-body">
        @if ($errors->any())
            <div class="hospital-alert" style="background:#ffdad6;border:1px solid #e4beba;color:#93000a;margin-bottom:20px;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form class="hospital-form" method="POST" action="{{ route('hospital.lab-staff.update', $staff) }}">
            @csrf
            @method('PATCH')

            <div class="hospital-field">
                <label class="hospital-label" for="name">Full name</label>
                <input class="hospital-input" id="name" name="name" type="text" value="{{ old('name', $staff->name) }}" required>
            </div>

            <div class="hospital-field">
                <label class="hospital-label" for="job_title">Job title</label>
                <input class="hospital-input" id="job_title" name="job_title" type="text" value="{{ old('job_title', $staff->job_title) }}" required>
            </div>

            <div class="hospital-field">
                <label class="hospital-label" for="email">Email</label>
                <input class="hospital-input" id="email" name="email" type="email" value="{{ old('email', $staff->email) }}" required>
            </div>

            <div class="hospital-field">
                <label class="hospital-label" for="password">New password (optional)</label>
                <input class="hospital-input" id="password" name="password" type="password">
            </div>

            <div class="hospital-field">
                <label class="hospital-label" for="password_confirmation">Confirm new password</label>
                <input class="hospital-input" id="password_confirmation" name="password_confirmation" type="password">
            </div>

            <div class="hospital-form-actions">
                <a href="{{ route('hospital.lab-staff.index') }}" class="hospital-btn hospital-btn-outline">Cancel</a>
                <button type="submit" class="hospital-btn hospital-btn-primary">Save changes</button>
            </div>
        </form>
    </div>
</div>
@endsection
