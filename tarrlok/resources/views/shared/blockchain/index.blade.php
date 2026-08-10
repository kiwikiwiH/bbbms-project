@extends($portal['layout'])

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/ledger.css') }}">
@endpush

@section('title', $portal['title'].' - Tarrlok')

@section('page_title', $portal['title'])
@section('page_subtitle', $portal['subtitle'] ?? 'Role-scoped audit trail for units you work with')

@section('content')
@if (($portal['layout'] ?? '') === 'layouts.tarrlok-admin')
    <h1 class="admin-heading">{{ $portal['title'] }}</h1>
    <p class="admin-subheading">{{ $portal['subtitle'] }}</p>
@endif

@include('shared.blockchain.ledger', [
    'traceRoute' => $portal['traceRoute'],
    'traceShowRoute' => $portal['traceShowRoute'],
])
@endsection
