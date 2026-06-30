<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Donation slip — {{ $unit->unit_code }}</title>
    <link rel="stylesheet" href="{{ asset('assets/css/fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/icons.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/hospital.css') }}">
    <style>
        body { margin: 0; padding: 24px; font-family: Inter, 'Segoe UI', Roboto, Arial, sans-serif; background: #f7f9fe; color: #181c20; }
        .donation-slip { max-width: 420px; margin: 0 auto; background: #fff; border: 1px solid #e0e2e7; border-radius: 8px; overflow: hidden; }
        .donation-slip-head { background: #a20513; color: #fff; padding: 20px; text-align: center; }
        .donation-slip-head h1 { margin: 0 0 4px; font-size: 22px; }
        .donation-slip-head p { margin: 0; font-size: 13px; opacity: 0.9; }
        .donation-slip-body { padding: 24px; }
        .donation-slip-id { text-align: center; margin: 0 0 20px; padding: 16px; border: 2px dashed #e0e2e7; border-radius: 8px; }
        .donation-slip-id dt { font-size: 11px; text-transform: uppercase; letter-spacing: 0.06em; color: #555f6f; }
        .donation-slip-id dd { margin: 8px 0 0; font-size: 24px; font-weight: 700; letter-spacing: 0.04em; }
        .donation-slip-meta { margin: 0; padding: 0; list-style: none; font-size: 14px; line-height: 1.7; }
        .donation-slip-meta li { padding: 8px 0; border-bottom: 1px solid #e6e8ed; }
        .donation-slip-meta li:last-child { border-bottom: none; }
        .donation-slip-track { margin-top: 20px; padding-top: 16px; border-top: 1px solid #e6e8ed; font-size: 13px; color: #555f6f; word-break: break-all; }
        .donation-slip-actions { display: flex; gap: 12px; justify-content: center; margin-top: 24px; }
        @media print {
            body { background: #fff; padding: 0; }
            .donation-slip { border: none; box-shadow: none; max-width: 100%; }
            .donation-slip-actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="donation-slip">
        <div class="donation-slip-head">
            <h1>Tarrlok</h1>
            <p>Blood donation tracking slip</p>
        </div>
        <div class="donation-slip-body">
            <dl class="donation-slip-id">
                <dt>Your unit ID</dt>
                <dd>{{ $unit->unit_code }}</dd>
            </dl>
            <ul class="donation-slip-meta">
                <li><strong>Facility:</strong> {{ $hospital->name }}</li>
                <li><strong>Blood group:</strong> {{ $unit->blood_group }}</li>
                <li><strong>Collected:</strong> {{ $unit->collected_at->format('M j, Y') }}</li>
                @if ($unit->donor)
                    <li><strong>Donor:</strong> {{ $unit->donor->name }}</li>
                @endif
            </ul>
            <div class="donation-slip-track">
                <strong>Track your donation:</strong><br>
                {{ $trackUrl }}
            </div>
        </div>
    </div>
    <div class="donation-slip-actions">
        <button type="button" class="hospital-btn hospital-btn-primary" onclick="window.print()">Print slip</button>
        <a href="{{ route('lab.units.screening.show', $unit) }}" class="hospital-btn hospital-btn-outline">Back to screening</a>
    </div>
</body>
</html>
