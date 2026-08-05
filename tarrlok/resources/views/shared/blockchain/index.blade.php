@extends($portal['layout'])

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/ledger.css') }}">
@endpush

@section('title', $portal['title'].' - Tarrlok')

@section('page_title', $portal['title'])
@section('page_subtitle', 'The same on-chain log, integrity alerts, and blocked attempts every stakeholder can see')

@section('content')
@if (($portal['layout'] ?? '') === 'layouts.tarrlok-admin')
    <h1 class="admin-heading">{{ $portal['title'] }}</h1>
    <p class="admin-subheading">The same on-chain log, integrity alerts, and blocked attempts every stakeholder can see.</p>
@endif

@include('shared.blockchain.ledger', [
    'traceRoute' => $portal['traceRoute'],
    'traceShowRoute' => $portal['traceShowRoute'],
])
@endsection
