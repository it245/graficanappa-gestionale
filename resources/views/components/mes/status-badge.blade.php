@props([
    'stato' => 0,
])

@php
$labels = [
    0 => 'Caricato',
    1 => 'Pronto',
    2 => 'Avviato',
    3 => 'Terminato',
    4 => 'Consegnato',
];
$statoInt = (int) $stato;
$labelText = $labels[$statoInt] ?? 'Sconosciuto';
@endphp

<span class="status-badge status-{{ $statoInt }}" {{ $attributes }}>{{ $labelText }}</span>
