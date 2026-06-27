@extends('layouts.tarrlok-lab')

@section('title', ($readOnly ? 'Lab Report' : 'Complete Screening').' - Tarrlok')

@section('page_title', $readOnly ? 'Lab screening report' : 'Complete lab screening')
@section('page_subtitle')
    {{ $unit->unit_code }} · {{ $unit->blood_group }} · Collected {{ $unit->collected_at->format('M j, Y') }}
@endsection

@section('content')
<div class="hospital-card" style="max-width:640px;">
    <div class="hospital-card-head">
        <h2 class="hospital-card-title">{{ $readOnly ? 'Screening report' : 'Lab report' }}</h2>
    </div>
    <div class="hospital-card-body">
        <dl class="hospital-detail-grid screening-unit-summary">
            <div class="hospital-detail-item">
                <dt>Unit ID</dt>
                <dd><span class="hospital-request-id">{{ $unit->unit_code }}</span></dd>
            </div>
            <div class="hospital-detail-item">
                <dt>Blood group</dt>
                <dd><span class="hospital-blood-group">{{ $unit->blood_group }}</span></dd>
            </div>
            <div class="hospital-detail-item">
                <dt>Recorded by</dt>
                <dd>{{ $unit->recorder?->name ?? '—' }}</dd>
            </div>
            <div class="hospital-detail-item">
                <dt>Screening status</dt>
                <dd>
                    <span @class(['hospital-screening-badge', $unit->screening_status])>
                        {{ $unit->screeningStatusLabel() }}
                    </span>
                </dd>
            </div>
        </dl>

        @if ($errors->any())
            <div class="hospital-alert" style="background:#ffdad6;border:1px solid #e4beba;color:#93000a;margin-bottom:20px;">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @if ($readOnly)
            <div class="screening-report-block">
                <h3 class="screening-report-heading">Serology results</h3>
                <ul class="screening-test-list">
                    @foreach ($screeningTests as $field => $label)
                        <li @class(['screening-test-item', 'passed' => $unit->{$field}])>
                            <span class="material-symbols-outlined">{{ $unit->{$field} ? 'check_circle' : 'cancel' }}</span>
                            <span>{{ $label }}</span>
                            <strong>{{ $unit->{$field} ? 'Non-reactive' : 'Reactive / not cleared' }}</strong>
                        </li>
                    @endforeach
                </ul>

                <dl class="hospital-detail-grid" style="margin-top:20px;">
                    <div class="hospital-detail-item">
                        <dt>Test date</dt>
                        <dd>{{ $unit->screened_at?->format('M j, Y') ?? '—' }}</dd>
                    </div>
                    <div class="hospital-detail-item">
                        <dt>Tested by</dt>
                        <dd>{{ $unit->screener?->name ?? '—' }}</dd>
                    </div>
                </dl>

                @if ($unit->screening_notes)
                    <div class="screening-notes-box">
                        <strong>Notes</strong>
                        <p>{{ $unit->screening_notes }}</p>
                    </div>
                @endif
            </div>

            <div class="hospital-form-actions">
                <a href="{{ route('lab.units.index') }}" class="hospital-btn hospital-btn-primary">Back to inventory</a>
            </div>
        @else
            <form class="hospital-form" method="POST" action="{{ route('lab.units.screening.update', $unit) }}">
                @csrf

                <p class="hospital-field-hint" style="margin-bottom:16px;">
                    Mark each test <strong>non-reactive</strong> before clearing the unit for hospital inventory and partner requests.
                </p>

                <fieldset class="hospital-field screening-tests-field">
                    <legend class="hospital-label">Screening tests</legend>
                    <div class="screening-test-checks">
                        @foreach ($screeningTests as $field => $label)
                            <label class="screening-test-check">
                                <input
                                    type="checkbox"
                                    name="{{ $field }}"
                                    value="1"
                                    @checked(old($field, $unit->{$field}))
                                >
                                <span class="screening-test-check-box">
                                    <span class="material-symbols-outlined">science</span>
                                    <span>{{ $label }}</span>
                                    <small>Non-reactive</small>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>

                <div class="hospital-field hospital-date-field" style="margin-top:0;padding-top:20px;">
                    <label class="hospital-label" for="screened_at">Test date</label>
                    <div class="hospital-date-row">
                        <div class="hospital-input-wrap">
                            <span class="material-symbols-outlined hospital-input-icon" aria-hidden="true">calendar_today</span>
                            <input
                                class="hospital-input hospital-date-input"
                                id="screened_at"
                                name="screened_at"
                                type="date"
                                value="{{ old('screened_at', now()->toDateString()) }}"
                                max="{{ now()->toDateString() }}"
                                required
                            >
                        </div>
                        <button type="button" class="hospital-date-today" id="screened_at_today">
                            <span class="material-symbols-outlined">today</span>
                            Today
                        </button>
                    </div>
                </div>

                <div class="hospital-field">
                    <label class="hospital-label" for="screening_notes">Notes (optional)</label>
                    <textarea class="hospital-input hospital-textarea" id="screening_notes" name="screening_notes" rows="3" placeholder="Any lab observations...">{{ old('screening_notes') }}</textarea>
                </div>

                <div class="hospital-form-actions screening-form-actions">
                    <a href="{{ route('lab.units.index') }}" class="hospital-btn hospital-btn-outline">Save for later</a>
                    <button type="submit" name="action" value="failed" class="hospital-btn hospital-btn-outline screening-fail-btn">
                        <span class="material-symbols-outlined">block</span>
                        Mark failed
                    </button>
                    <button type="submit" name="action" value="cleared" class="hospital-btn hospital-btn-primary">
                        <span class="material-symbols-outlined">verified</span>
                        Clear for inventory
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection

@unless ($readOnly)
@push('scripts')
<script>
    document.getElementById('screened_at_today')?.addEventListener('click', function () {
        const input = document.getElementById('screened_at');
        if (!input) return;
        input.value = new Date().toISOString().slice(0, 10);
    });
</script>
@endpush
@endunless
