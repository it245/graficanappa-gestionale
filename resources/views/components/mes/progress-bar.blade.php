@props([
    'percentuale' => 0,
    'avviate' => 0,
    'totale' => 0,
    'terminate' => 0,
])

@php
$pct = (int) $percentuale;
$avv = (int) $avviate;
@endphp

<div class="mes-progress" title="{{ $terminate }}/{{ $totale }} terminate" {{ $attributes }}>
    <div class="mes-progress-track">
        @if($pct > 0)
            <div class="mes-progress-fill mes-progress-done" style="width:{{ $pct }}%"></div>
        @endif
        @if($avv > 0)
            <div class="mes-progress-fill mes-progress-active" style="width:{{ $pct + $avv }}%"></div>
        @endif
    </div>
    <span class="mes-progress-text">{{ $pct }}%</span>
</div>
