@props([
    'size' => 'md',
    'color' => 'currentColor',
    'label' => null,
])

@php
    $sizePx = match($size) {
        'sm' => 14,
        'lg' => 32,
        'xl' => 48,
        default => 20,
    };
    $border = max(2, (int) round($sizePx / 8));
@endphp

<span class="mes-spinner-wrap" style="display:inline-flex;align-items:center;gap:8px;font-family:inherit;" {{ $attributes }}>
    <span class="mes-spinner"
          style="display:inline-block;width:{{ $sizePx }}px;height:{{ $sizePx }}px;border:{{ $border }}px solid {{ $color }};border-top-color:transparent;border-radius:50%;animation:mes-spinner-spin .7s linear infinite;"></span>
    @if($label)<span style="font-size:13px;color:var(--mes-text-secondary);">{{ $label }}</span>@endif
</span>

@once
    <style>
        @keyframes mes-spinner-spin { to { transform: rotate(360deg); } }
    </style>
@endonce
