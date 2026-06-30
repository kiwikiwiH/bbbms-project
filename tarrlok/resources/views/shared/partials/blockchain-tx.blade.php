@props(['hash'])

@php
    $explorer = config('blockchain.explorer_tx_url');
    $url = $explorer ? str_replace('{hash}', $hash, $explorer) : null;
@endphp

@if ($url)
    <a href="{{ $url }}" class="trace-tx-link" target="_blank" rel="noopener noreferrer" title="{{ $hash }}">
        <code class="trace-tx-hash">{{ Str::limit($hash, 18, '…') }}</code>
    </a>
@else
    <code class="trace-tx-hash" title="{{ $hash }}">{{ $hash }}</code>
@endif
