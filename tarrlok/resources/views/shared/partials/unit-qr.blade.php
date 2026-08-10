@php
    $qrSize = $qrSize ?? 128;
    $qrCaption = $qrCaption ?? 'Scan to open this unit’s track page';
    $trackUrl = $trackUrl ?? null;
    $qrDataUri = $qrDataUri ?? null;
@endphp

@if ($qrDataUri)
    <div class="unit-qr-panel">
        <img
            class="unit-qr-image"
            src="{{ $qrDataUri }}"
            width="{{ $qrSize }}"
            height="{{ $qrSize }}"
            alt="QR code for {{ $unit->unit_code ?? 'blood unit' }}"
        >
        <p class="unit-qr-caption">{{ $qrCaption }}</p>
        @if ($trackUrl)
            <p class="unit-qr-url"><code>{{ $trackUrl }}</code></p>
        @endif
        @isset($printBagLabelRoute)
            <a href="{{ $printBagLabelRoute }}" class="hospital-btn hospital-btn-outline hospital-btn-sm" target="_blank" rel="noopener">
                <span class="material-symbols-outlined">qr_code_2</span>
                Print bag label
            </a>
        @endisset
    </div>
@endif
