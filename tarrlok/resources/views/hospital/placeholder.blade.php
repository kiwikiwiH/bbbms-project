@extends('layouts.tarrlok-hospital')

@section('title', $title.' - Tarrlok')

@section('page_title', $title)

@section('content')
<div class="hospital-card">
    <div class="hospital-placeholder">
        <div class="hospital-placeholder-icon">
            <span class="material-symbols-outlined">construction</span>
        </div>
        <h2>{{ $title }}</h2>
        <p>This section is under development. It will be part of your hospital blood bank workflow on Tarrlok.</p>
    </div>
</div>
@endsection
