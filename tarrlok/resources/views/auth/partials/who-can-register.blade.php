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
            <strong>Blood donors</strong>
            — No login needed. Use the <a class="login-link" href="{{ route('track.index') }}">unit ID</a> on your donation slip to track that donation only (no access to other donors' units).
        </li>
        <li>
            <strong>Platform administrators</strong>
            — Tarrlok operations team only. No public registration.
        </li>
    </ul>
    <p class="access-roles-foot">Donations are recorded by lab staff. Donors track a single unit with the ID printed at registration.</p>
</div>
