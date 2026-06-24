@props(['step' => 1, 'percent' => 25, 'label' => 'Facility Details'])

<div class="reg-progress-wrap">
    <div class="reg-progress-meta">
        <span class="reg-progress-step">Step {{ $step }} of 3</span>
        <span class="reg-progress-label">{{ $label }}</span>
    </div>
    <div class="reg-progress-track" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
        <div class="reg-progress-fill" style="width: {{ $percent }}%"></div>
    </div>
</div>
