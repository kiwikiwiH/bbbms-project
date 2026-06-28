<div class="donor-facility-contact">
    <a href="tel:{{ preg_replace('/\s+/', '', $hospital->phone) }}" class="donor-contact-link">
        <span class="material-symbols-outlined">call</span>
        {{ $hospital->phone }}
    </a>
    <a href="mailto:{{ $hospital->email }}" class="donor-contact-link">
        <span class="material-symbols-outlined">mail</span>
        {{ $hospital->email }}
    </a>
</div>
