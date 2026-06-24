@props(['context' => 'login'])

<div class="access-roles-note">
    <p class="access-roles-title">
        <span class="material-symbols-outlined">info</span>
        Who can use Tarrlok?
    </p>
    <ul class="access-roles-list">
        <li>
            <strong>Hospitals &amp; blood banks</strong>
            @if ($context === 'register')
                — Register on this page. Your facility is reviewed before you can sign in.
            @else
                — <a class="login-link" href="{{ route('register') }}">Register your facility</a> (pending platform approval).
            @endif
        </li>
        <li>
            <strong>Lab staff</strong>
            — Accounts are issued by your hospital administrator after your facility is approved.
        </li>
        <li>
            <strong>Platform administrators</strong>
            — Tarrlok operations team only. No public registration.
        </li>
    </ul>
    <p class="access-roles-foot">Blood donors do not need a login — donations are recorded in person by hospital and lab staff.</p>
</div>
