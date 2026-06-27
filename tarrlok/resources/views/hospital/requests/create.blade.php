@extends('layouts.tarrlok-hospital')

@section('title', 'Request Blood - Tarrlok')

@section('page_title', 'Request blood from partner')
@section('page_subtitle')
    Send a blood supply request to another hospital on the Tarrlok network
@endsection

@section('content')
<div class="hospital-card" style="max-width:640px;">
    <div class="hospital-card-head">
        <h2 class="hospital-card-title">New request</h2>
    </div>
    <div class="hospital-card-body">
        @if ($errors->any())
            <div class="hospital-alert" style="background:#ffdad6;border:1px solid #e4beba;color:#93000a;margin-bottom:20px;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form class="hospital-form" method="POST" action="{{ route('hospital.requests.store') }}">
            @csrf

            <div class="hospital-field">
                <label class="hospital-label" for="fulfilling_hospital_id">Partner hospital</label>
                <select class="hospital-input" id="fulfilling_hospital_id" name="fulfilling_hospital_id" required>
                    <option value="" disabled {{ old('fulfilling_hospital_id', $selectedPartner?->id) ? '' : 'selected' }}>Select partner hospital</option>
                    @foreach ($partners as $partner)
                        <option
                            value="{{ $partner->id }}"
                            @selected((string) old('fulfilling_hospital_id', $selectedPartner?->id) === (string) $partner->id)
                        >
                            {{ $partner->name }} — {{ $partner->city }}
                        </option>
                    @endforeach
                </select>
                <p class="hospital-field-hint">
                    <a href="{{ route('hospital.partners') }}">Browse all partners</a>
                </p>
            </div>

            <fieldset class="hospital-field blood-group-field">
                <legend class="hospital-label">Blood group</legend>
                <p class="hospital-field-hint">Type required from the partner facility.</p>
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

            <div class="hospital-field hospital-date-field" style="margin-top:0;padding-top:20px;">
                <label class="hospital-label" for="quantity">Quantity</label>
                <p class="hospital-field-hint">Number of units needed (1–50).</p>
                <input
                    class="hospital-input"
                    id="quantity"
                    name="quantity"
                    type="number"
                    min="1"
                    max="50"
                    value="{{ old('quantity', 1) }}"
                    required
                >
            </div>

            <fieldset class="hospital-field urgency-field">
                <legend class="hospital-label">Urgency</legend>
                <div class="urgency-options">
                    <label class="urgency-option">
                        <input type="radio" name="urgency" value="routine" @checked(old('urgency', 'routine') === 'routine') required>
                        <span class="urgency-btn">
                            <span class="material-symbols-outlined">schedule</span>
                            Routine
                        </span>
                    </label>
                    <label class="urgency-option">
                        <input type="radio" name="urgency" value="emergency" @checked(old('urgency') === 'emergency')>
                        <span class="urgency-btn urgency-btn-emergency">
                            <span class="material-symbols-outlined">emergency</span>
                            Emergency
                        </span>
                    </label>
                </div>
            </fieldset>

            <div class="hospital-form-actions">
                <a href="{{ route('hospital.partners') }}" class="hospital-btn hospital-btn-outline">Cancel</a>
                <button type="submit" class="hospital-btn hospital-btn-primary">
                    <span class="material-symbols-outlined">send</span>
                    Send request
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
