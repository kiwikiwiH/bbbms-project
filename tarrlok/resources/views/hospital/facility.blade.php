@extends('layouts.tarrlok-hospital')

@section('title', 'Facility Profile - '.$hospital->name)

@section('page_title', 'Facility Profile')
@section('page_subtitle', 'Registered facility details on the Tarrlok network')

@section('content')
<div class="hospital-card" style="margin-bottom:20px;">
    <div class="hospital-card-head">
        <h2 class="hospital-card-title">{{ $hospital->name }}</h2>
        <span class="hospital-badge {{ $hospital->status === 'approved' ? 'approved' : 'pending' }}">{{ ucfirst($hospital->status) }}</span>
    </div>
    <div class="hospital-card-body">
        <dl class="hospital-detail-grid">
            <div class="hospital-detail-item">
                <dt>Institution type</dt>
                <dd>{{ $hospital->typeLabel() }}</dd>
            </div>
            <div class="hospital-detail-item">
                <dt>HeFRA license</dt>
                <dd>{{ $hospital->license_id }}</dd>
            </div>
            <div class="hospital-detail-item">
                <dt>Network status</dt>
                <dd>{{ ucfirst($hospital->status) }}</dd>
            </div>
            <div class="hospital-detail-item">
                <dt>Primary administrator</dt>
                <dd>{{ $user->name }} ({{ $user->email }})</dd>
            </div>
        </dl>
        <p class="hospital-field-hint" style="margin:16px 0 0;">
            Name, type, and HeFRA licence are fixed after approval. Update contact details below if they change.
        </p>
    </div>
</div>

<div class="hospital-card">
    <div class="hospital-card-head">
        <h2 class="hospital-card-title">Contact details</h2>
    </div>
    <div class="hospital-card-body">
        <form class="hospital-form" method="POST" action="{{ route('hospital.facility.update') }}" style="max-width:520px;">
            @csrf
            @method('PATCH')

            <div class="hospital-field">
                <label class="hospital-label" for="city">City / district</label>
                <input class="hospital-input" id="city" name="city" type="text" value="{{ old('city', $hospital->city) }}" required>
                @error('city')<p class="hospital-field-hint" style="color:#93000a;">{{ $message }}</p>@enderror
            </div>

            <div class="hospital-field">
                <label class="hospital-label" for="region">Region</label>
                <select class="hospital-input" id="region" name="region" required>
                    @foreach (config('tarrlok.ghana_regions') as $value => $label)
                        <option value="{{ $value }}" @selected(old('region', $hospital->region) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('region')<p class="hospital-field-hint" style="color:#93000a;">{{ $message }}</p>@enderror
            </div>

            <div class="hospital-field">
                <label class="hospital-label" for="phone">Official phone</label>
                <input class="hospital-input" id="phone" name="phone" type="text" value="{{ old('phone', $hospital->phone) }}" required>
                @error('phone')<p class="hospital-field-hint" style="color:#93000a;">{{ $message }}</p>@enderror
            </div>

            <div class="hospital-field">
                <label class="hospital-label" for="email">Official email</label>
                <input class="hospital-input" id="email" name="email" type="email" value="{{ old('email', $hospital->email) }}" required>
                @error('email')<p class="hospital-field-hint" style="color:#93000a;">{{ $message }}</p>@enderror
            </div>

            <div class="hospital-form-actions">
                <button type="submit" class="hospital-btn hospital-btn-primary">
                    <span class="material-symbols-outlined">save</span>
                    Save contact details
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
