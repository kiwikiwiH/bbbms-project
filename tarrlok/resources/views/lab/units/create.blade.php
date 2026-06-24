@extends('layouts.tarrlok-lab')

@section('title', 'Register Blood Unit - Tarrlok')

@section('page_title', 'Register blood unit')
@section('page_subtitle')
    Add a tested donation to {{ auth()->user()->hospital->name }} inventory
@endsection

@section('content')
<div class="hospital-card" style="max-width:520px;">
    <div class="hospital-card-head">
        <h2 class="hospital-card-title">New unit</h2>
    </div>
    <div class="hospital-card-body">
        @if ($errors->any())
            <div class="hospital-alert" style="background:#ffdad6;border:1px solid #e4beba;color:#93000a;margin-bottom:20px;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form class="hospital-form" method="POST" action="{{ route('lab.units.store') }}">
            @csrf

            <div class="hospital-field">
                <label class="hospital-label" for="blood_group">Blood group</label>
                <select class="hospital-input" id="blood_group" name="blood_group" required>
                    <option value="" disabled {{ old('blood_group') ? '' : 'selected' }}>Select group</option>
                    @foreach ($bloodGroups as $group)
                        <option value="{{ $group }}" @selected(old('blood_group') === $group)>{{ $group }}</option>
                    @endforeach
                </select>
            </div>

            <div class="hospital-field">
                <label class="hospital-label" for="collected_at">Collection date</label>
                <input
                    class="hospital-input"
                    id="collected_at"
                    name="collected_at"
                    type="date"
                    value="{{ old('collected_at', now()->toDateString()) }}"
                    max="{{ now()->toDateString() }}"
                    required
                >
                <p class="hospital-field-hint">Date the unit was collected and cleared for storage.</p>
            </div>

            <div class="hospital-form-actions">
                <a href="{{ route('lab.dashboard') }}" class="hospital-btn hospital-btn-outline">Cancel</a>
                <button type="submit" class="hospital-btn hospital-btn-primary">
                    <span class="material-symbols-outlined">check</span>
                    Register unit
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
