@extends('layouts.tarrlok-lab')

@section('title', 'Register Blood Unit - Tarrlok')

@section('page_title', 'Register blood unit')
@section('page_subtitle')
    Add a tested donation to {{ auth()->user()->hospital->name }} inventory
@endsection

@section('content')
<div class="hospital-card" style="max-width:580px;">
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

            <fieldset class="hospital-field blood-group-field">
                <legend class="hospital-label">Blood group</legend>
                <p class="hospital-field-hint">Select the verified blood type for this unit.</p>
                <div class="blood-group-grid" role="radiogroup" aria-label="Blood group">
                    @foreach ($bloodGroups as $group)
                        @php
                            $isRhNeg = str_ends_with($group, '-');
                        @endphp
                        <label class="blood-group-option">
                            <input
                                type="radio"
                                name="blood_group"
                                value="{{ $group }}"
                                @checked(old('blood_group') === $group)
                                @if ($loop->first) required @endif
                            >
                            <span @class(['blood-group-btn', 'blood-group-btn-neg' => $isRhNeg])>
                                <span class="material-symbols-outlined blood-group-icon filled">bloodtype</span>
                                <span class="blood-group-label">{{ $group }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <div class="hospital-field hospital-date-field">
                <label class="hospital-label" for="collected_at">Collection date</label>
                <p class="hospital-field-hint">When the unit was collected and cleared for storage.</p>
                <div class="hospital-date-row">
                    <div class="hospital-input-wrap">
                        <span class="material-symbols-outlined hospital-input-icon" aria-hidden="true">calendar_today</span>
                        <input
                            class="hospital-input hospital-date-input"
                            id="collected_at"
                            name="collected_at"
                            type="date"
                            value="{{ old('collected_at', now()->toDateString()) }}"
                            max="{{ now()->toDateString() }}"
                            required
                        >
                    </div>
                    <button type="button" class="hospital-date-today" id="collected_at_today">
                        <span class="material-symbols-outlined">today</span>
                        Today
                    </button>
                </div>
            </div>

            <div class="hospital-form-actions">
                <a href="{{ route('lab.dashboard') }}" class="hospital-btn hospital-btn-outline">Cancel</a>
                <button type="submit" class="hospital-btn hospital-btn-primary">
                    <span class="material-symbols-outlined">check</span>
                    Register &amp; screen
                </button>
            </div>
        </form>
    </div>
</div>

<p class="hospital-flow-note">
    <span class="material-symbols-outlined">info</span>
    After registration you will complete a <strong>lab screening report</strong>. Only cleared units appear in hospital inventory.
</p>

@push('scripts')
<script>
    document.getElementById('collected_at_today')?.addEventListener('click', function () {
        const input = document.getElementById('collected_at');
        if (!input) return;
        input.value = new Date().toISOString().slice(0, 10);
    });
</script>
@endpush
@endsection
