@extends('layouts.tarrlok-guest')

@section('title', $unit->unit_code.' - Tarrlok')

@section('content')
<main class="login-shell track-show-shell" style="max-width:720px;">
    <div class="login-brand" style="margin-bottom:20px;">
        <div class="login-brand-icon">
            <span class="material-symbols-outlined login-brand-glyph filled">bloodtype</span>
        </div>
        <h1 class="login-title" style="font-size:1.5rem;">{{ $unit->unit_code }}</h1>
        <p class="login-subtitle">{{ $unit->blood_group }} · Collected {{ $unit->collected_at->format('M j, Y') }}</p>
    </div>

    @include('track.partials.unit-status', ['unit' => $unit])

    <div style="margin-top:16px;display:flex;gap:12px;flex-wrap:wrap;justify-content:center;">
        <a href="{{ route('track.index') }}" class="hospital-btn hospital-btn-outline">Track another unit</a>
        <a href="{{ route('login') }}" class="hospital-btn hospital-btn-outline">Staff sign in</a>
    </div>
</main>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/hospital.css') }}">
<style>
    .track-show-shell .hospital-card { text-align: left; }
    .track-show-shell .hospital-btn { display: inline-flex; align-items: center; gap: 6px; text-decoration: none; }
</style>
@endpush
