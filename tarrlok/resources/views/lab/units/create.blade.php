@extends('layouts.tarrlok-lab')

@section('title', 'Register Blood Unit - Tarrlok')

@section('page_title', 'Register blood unit')
@section('page_subtitle')
    Link a donor and add a unit for {{ auth()->user()->hospital->name }}
@endsection

@section('content')
<div class="hospital-card" style="max-width:640px;">
    <div class="hospital-card-head">
        <h2 class="hospital-card-title">New donation</h2>
    </div>
    <div class="hospital-card-body">
        @if ($errors->any())
            <div class="hospital-alert" style="background:#ffdad6;border:1px solid #e4beba;color:#93000a;margin-bottom:20px;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form class="hospital-form" method="POST" action="{{ route('lab.units.store') }}" id="register_unit_form">
            @csrf

            <fieldset class="hospital-field donor-field-section">
                <legend class="hospital-label">Donor</legend>
                <p class="hospital-field-hint">Search by phone — existing donors are linked automatically.</p>

                <div class="hospital-field">
                    <label class="hospital-label" for="donor_phone">Donor phone</label>
                    <div class="hospital-date-row">
                        <div class="hospital-input-wrap" style="flex:1;">
                            <span class="material-symbols-outlined hospital-input-icon">call</span>
                            <input class="hospital-input" id="donor_phone" name="donor_phone" type="tel" value="{{ old('donor_phone') }}" placeholder="244123456" required>
                        </div>
                        <button type="button" class="hospital-date-today" id="donor_lookup_btn">Look up</button>
                    </div>
                    <p id="donor_lookup_status" class="hospital-field-hint" style="margin-top:8px;"></p>
                </div>

                <div class="hospital-field">
                    <label class="hospital-label" for="donor_name">Donor full name</label>
                    <input class="hospital-input" id="donor_name" name="donor_name" type="text" value="{{ old('donor_name') }}" required>
                </div>

                <div class="hospital-field">
                    <label class="hospital-label" for="donor_email">Donor email (optional)</label>
                    <input class="hospital-input" id="donor_email" name="donor_email" type="email" value="{{ old('donor_email') }}">
                </div>
            </fieldset>

            <fieldset class="hospital-field blood-group-field">
                <legend class="hospital-label">Blood group</legend>
                <p class="hospital-field-hint">Verified blood type for this unit.</p>
                <div class="blood-group-grid" role="radiogroup" aria-label="Blood group">
                    @foreach ($bloodGroups as $group)
                        @php $isRhNeg = str_ends_with($group, '-'); @endphp
                        <label class="blood-group-option">
                            <input type="radio" name="blood_group" value="{{ $group }}" @checked(old('blood_group') === $group) @if ($loop->first) required @endif>
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
                <p class="hospital-field-hint">Shelf life: {{ $shelfLifeDays }} days from collection (expiry set automatically).</p>
                <div class="hospital-date-row">
                    <div class="hospital-input-wrap">
                        <span class="material-symbols-outlined hospital-input-icon" aria-hidden="true">calendar_today</span>
                        <input class="hospital-input hospital-date-input" id="collected_at" name="collected_at" type="date" value="{{ old('collected_at', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
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
@endsection

@push('scripts')
<script>
    document.getElementById('collected_at_today')?.addEventListener('click', function () {
        const input = document.getElementById('collected_at');
        if (input) input.value = new Date().toISOString().slice(0, 10);
    });

    document.getElementById('donor_lookup_btn')?.addEventListener('click', async function () {
        const phone = document.getElementById('donor_phone')?.value;
        const status = document.getElementById('donor_lookup_status');
        if (!phone || !status) return;

        status.textContent = 'Looking up…';

        try {
            const res = await fetch(`{{ route('lab.donors.lookup') }}?phone=${encodeURIComponent(phone)}`, {
                headers: { 'Accept': 'application/json' },
            });
            const data = await res.json();

            if (!data.found) {
                status.textContent = 'New donor — enter their name below.';
                return;
            }

            document.getElementById('donor_name').value = data.donor.name;
            if (data.donor.email) {
                document.getElementById('donor_email').value = data.donor.email;
            }
            if (data.donor.blood_group) {
                const radio = document.querySelector(`input[name="blood_group"][value="${data.donor.blood_group}"]`);
                if (radio) radio.checked = true;
            }

            status.textContent = `Found ${data.donor.donor_code}` +
                (data.donor.eligible ? ' — eligible to donate.' : ` — next eligible ${data.donor.next_eligible || 'later'}.`);
        } catch (e) {
            status.textContent = 'Lookup failed. You can still register manually.';
        }
    });
</script>
@endpush
