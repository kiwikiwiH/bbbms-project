@extends('layouts.tarrlok-admin')

@section('title', 'Activity log - Tarrlok Admin')

@section('content')
<h1 class="admin-heading">Activity log</h1>
<p class="admin-subheading">Sign-ins, sign-outs, failed attempts, and key actions users performed while signed in.</p>

<div class="admin-filters">
    <a href="{{ route('admin.auth-log') }}" @class(['active' => $event === ''])>All</a>
    <a href="{{ route('admin.auth-log', ['event' => 'login']) }}" @class(['active' => $event === 'login'])>Logins</a>
    <a href="{{ route('admin.auth-log', ['event' => 'logout']) }}" @class(['active' => $event === 'logout'])>Logouts</a>
    <a href="{{ route('admin.auth-log', ['event' => 'login_failed']) }}" @class(['active' => $event === 'login_failed'])>Failed</a>
    <a href="{{ route('admin.auth-log', ['event' => 'action']) }}" @class(['active' => $event === 'action'])>Actions</a>
</div>

<div class="admin-card">
    @if ($logs->isEmpty())
        <div class="admin-empty">No activity recorded yet. Have staff sign in and register/screen/issue units, then refresh this page.</div>
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
                        <th>Summary</th>
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
                                    'approved' => $log->event === 'login' || $log->event === 'action',
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
                            <td style="max-width:280px;overflow-wrap:anywhere;">{{ $log->summary ?: '—' }}</td>
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
