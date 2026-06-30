@extends('layouts.tarrlok-hospital')

@section('title', 'Lab Staff - Tarrlok')

@section('page_title', 'Lab Staff')
@section('page_subtitle')
    Issue and manage lab accounts for {{ $hospital->name }}
@endsection

@section('content')
<div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
    <a href="{{ route('hospital.lab-staff.create') }}" class="hospital-btn hospital-btn-primary">
        <span class="material-symbols-outlined">person_add</span>
        Issue lab account
    </a>
</div>

<div class="hospital-card">
    @if ($labStaff->isEmpty())
        <div class="hospital-placeholder">
            <div class="hospital-placeholder-icon">
                <span class="material-symbols-outlined">science</span>
            </div>
            <h2>No lab staff yet</h2>
            <p>Create accounts for laboratory technicians who will record blood units and run tests at your facility.</p>
        </div>
    @else
        <div class="hospital-table-wrap">
            <table class="hospital-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Job title</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Issued</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($labStaff as $staff)
                        <tr>
                            <td><strong>{{ $staff->name }}</strong></td>
                            <td>{{ $staff->job_title }}</td>
                            <td>{{ $staff->email }}</td>
                            <td>
                                <span class="hospital-badge {{ $staff->status === 'active' ? 'approved' : 'rejected' }}">
                                    {{ $staff->status }}
                                </span>
                            </td>
                            <td>{{ $staff->created_at->format('M j, Y') }}</td>
                            <td>
                                <form method="POST" action="{{ route('hospital.lab-staff.toggle', $staff) }}" style="display:inline;">
                                    @csrf
                                    <button type="submit" class="hospital-btn hospital-btn-outline hospital-btn-sm">
                                        {{ $staff->status === 'active' ? 'Suspend' : 'Reactivate' }}
                                    </button>
                                </form>
                                <a href="{{ route('hospital.lab-staff.edit', $staff) }}" class="hospital-btn hospital-btn-outline hospital-btn-sm">Edit</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
