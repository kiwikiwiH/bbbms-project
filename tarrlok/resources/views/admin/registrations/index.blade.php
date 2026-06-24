@extends('layouts.tarrlok-admin')

@section('title', 'Facility Registrations - Tarrlok Admin')

@section('content')
<h1 class="admin-heading">Facility Registrations</h1>
<p class="admin-subheading">Verify HeFRA licenses and facility details before granting network access.</p>

<div class="admin-filters">
    @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All'] as $key => $label)
        <a href="{{ route('admin.registrations.index', ['status' => $key]) }}" @class(['active' => $status === $key])>{{ $label }}</a>
    @endforeach
</div>

<div class="admin-card">
    @if ($hospitals->isEmpty())
        <div class="admin-empty">No registrations found for this filter.</div>
    @else
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Facility</th>
                        <th>Administrator</th>
                        <th>License</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($hospitals as $hospital)
                        @php $contact = $hospital->primaryContact(); @endphp
                        <tr>
                            <td>
                                <strong>{{ $hospital->name }}</strong><br>
                                <span style="color:#555f6f;font-size:13px;">{{ $hospital->city }}, {{ $hospital->regionLabel() }}</span>
                            </td>
                            <td>{{ $contact?->name ?? '—' }}<br><span style="color:#555f6f;font-size:13px;">{{ $contact?->email }}</span></td>
                            <td>{{ $hospital->license_id }}</td>
                            <td><span class="admin-badge {{ $hospital->status }}">{{ $hospital->status }}</span></td>
                            <td>{{ $hospital->created_at->format('M j, Y') }}</td>
                            <td><a href="{{ route('admin.registrations.show', $hospital) }}">Open</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="admin-pagination">{{ $hospitals->links('vendor.pagination.admin') }}</div>
    @endif
</div>
@endsection
