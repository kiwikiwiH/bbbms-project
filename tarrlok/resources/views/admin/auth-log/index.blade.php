@extends('layouts.tarrlok-admin')

@section('title', 'Sign-in log - Tarrlok Admin')

@section('content')
<h1 class="admin-heading">Sign-in log</h1>
<p class="admin-subheading">Who signed in and out of Tarrlok — for platform administrators only.</p>

<div class="admin-filters">
    <a href="{{ route('admin.auth-log') }}" @class(['active' => $event === ''])>All</a>
    <a href="{{ route('admin.auth-log', ['event' => 'login']) }}" @class(['active' => $event === 'login'])>Logins</a>
    <a href="{{ route('admin.auth-log', ['event' => 'logout']) }}" @class(['active' => $event === 'logout'])>Logouts</a>
    <a href="{{ route('admin.auth-log', ['event' => 'login_failed']) }}" @class(['active' => $event === 'login_failed'])>Failed</a>
</div>

<div class="admin-card">
    @if ($logs->isEmpty())
        <div class="admin-empty">No sign-in activity recorded yet. Have a hospital or lab user sign in, then refresh this page.</div>
    @else
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>Event</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Hospital</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($logs as $log)
                        <tr>
                            <td>{{ $log->created_at?->format('M j, Y H:i:s') }}</td>
                            <td>
                                <span @class([
                                    'admin-badge',
                                    'approved' => $log->event === 'login',
                                    'pending' => $log->event === 'logout',
                                    'rejected' => $log->event === 'login_failed',
                                ])>{{ $log->eventLabel() }}</span>
                            </td>
                            <td>
                                <strong>{{ $log->actor_name ?? '—' }}</strong><br>
                                <span style="color:#555f6f;font-size:13px;">{{ $log->email ?? '—' }}</span>
                            </td>
                            <td>{{ $log->roleLabel() }}</td>
                            <td>{{ $log->hospital?->name ?? '—' }}</td>
                            <td><code style="font-size:12px;">{{ $log->ip_address ?? '—' }}</code></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="admin-pagination">{{ $logs->links('vendor.pagination.admin') }}</div>
    @endif
</div>
@endsection
