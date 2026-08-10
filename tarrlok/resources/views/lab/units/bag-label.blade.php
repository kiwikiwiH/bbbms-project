<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bag label — {{ $unit->unit_code }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/hospital.css') }}">
    <style>
        body { margin: 0; padding: 24px; font-family: Inter, 'Segoe UI', Roboto, Arial, sans-serif; background: #f7f9fe; color: #181c20; }
        .bag-label {
            width: 72mm;
            max-width: 100%;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #cfd3da;
            border-radius: 6px;
            padding: 10px 12px 12px;
            box-sizing: border-box;
        }
        .bag-label-brand {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 8px;
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 2px solid #a20513;
        }
        .bag-label-brand strong { font-size: 14px; color: #a20513; letter-spacing: 0.02em; }
        .bag-label-brand span { font-size: 10px; color: #555f6f; text-align: right; line-height: 1.3; }
        .bag-label-code {
            text-align: center;
            margin: 8px 0 6px;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.04em;
        }
        .bag-label-meta {
            text-align: center;
            font-size: 11px;
            color: #555f6f;
            line-height: 1.45;
            margin: 0 0 10px;
        }
        .bag-label-qr {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }
        .bag-label-qr img {
            width: 128px;
            height: 128px;
            image-rendering: pixelated;
        }
        .bag-label-hint {
            margin: 0;
            font-size: 10px;
            color: #555f6f;
            text-align: center;
        }
        .bag-label-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
            margin-top: 20px;
        }
        .bag-label-note {
            max-width: 420px;
            margin: 16px auto 0;
            text-align: center;
            font-size: 13px;
            color: #555f6f;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .bag-label { border-color: #000; border-radius: 0; }
            .bag-label-actions,
            .bag-label-note { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="bag-label" aria-label="Blood pack label for {{ $unit->unit_code }}">
        <div class="bag-label-brand">
            <strong>Tarrlok</strong>
            <span>{{ $hospital->name }}</span>
        </div>
        <div class="bag-label-code">{{ $unit->unit_code }}</div>
        <p class="bag-label-meta">
            {{ $unit->blood_group }} · {{ $unit->componentLabel() }}
            @if ($unit->expires_at)
                <br>Expires {{ $unit->expires_at->format('d/m/Y') }}
            @endif
        </p>
        <div class="bag-label-qr">
            <img src="{{ $qrDataUri }}" width="128" height="128" alt="QR code linking to {{ $trackUrl }}">
            <p class="bag-label-hint">Scan to verify / track</p>
        </div>
    </div>

    <p class="bag-label-note">
        Stick this label on the blood pack. No donor personal details are printed here.
        Phones must reach <code>{{ parse_url($trackUrl, PHP_URL_HOST) }}</code> (set <code>APP_URL</code> to a tunnel/LAN URL for demos).
    </p>

    <div class="bag-label-actions">
        <button type="button" class="hospital-btn hospital-btn-primary" onclick="window.print()">Print bag label</button>
        <a href="{{ route('lab.units.slip', $unit) }}" class="hospital-btn hospital-btn-outline">Donor slip</a>
        <a href="{{ route('lab.units.screening.show', $unit) }}" class="hospital-btn hospital-btn-outline">Back to screening</a>
    </div>
</body>
</html>
