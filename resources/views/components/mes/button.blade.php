@props([
    'variant' => 'primary',
    'size' => 'md',
    'icon' => null,
    'loading' => false,
    'disabled' => false,
    'type' => 'button',
    'href' => null,
])

@php
    $sizeStyle = match($size) {
        'sm' => 'padding:6px 12px;font-size:12px;',
        'lg' => 'padding:12px 24px;font-size:15px;',
        default => 'padding:9px 18px;font-size:13px;',
    };
    $variantStyle = match($variant) {
        'primary'   => 'background:var(--mes-primary);color:#fff;box-shadow:0 2px 4px rgba(59,130,246,.15);',
        'secondary' => 'background:var(--mes-bg-card);color:var(--mes-text-primary);border:1px solid var(--mes-border);',
        'success'   => 'background:var(--mes-success);color:#fff;box-shadow:0 2px 4px rgba(16,185,129,.15);',
        'danger'    => 'background:var(--mes-danger);color:#fff;box-shadow:0 2px 4px rgba(239,68,68,.15);',
        'warning'   => 'background:var(--mes-warning);color:#fff;box-shadow:0 2px 4px rgba(245,158,11,.15);',
        'ghost'     => 'background:transparent;color:var(--mes-text-primary);',
        default     => 'background:var(--mes-primary);color:#fff;',
    };
    $baseStyle = 'display:inline-flex;align-items:center;gap:8px;justify-content:center;border:none;border-radius:var(--mes-radius-md);font-weight:600;letter-spacing:.2px;cursor:pointer;transition:all var(--mes-duration-base) var(--mes-ease-standard);font-family:inherit;text-decoration:none;';
    $finalStyle = $baseStyle . $sizeStyle . $variantStyle;
    $isDisabled = $disabled || $loading;
@endphp

@if($href)
    <a href="{{ $href }}"
       class="mes-btn mes-btn-{{ $variant }} {{ $isDisabled ? 'is-disabled' : '' }}"
       style="{{ $finalStyle }}{{ $isDisabled ? 'opacity:.6;pointer-events:none;' : '' }}"
       {{ $attributes }}>
        @if($loading)
            <span class="mes-btn-spinner" style="width:14px;height:14px;border:2px solid currentColor;border-top-color:transparent;border-radius:50%;animation:mes-spin .6s linear infinite;"></span>
        @elseif($icon)
            {!! $icon !!}
        @endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}"
            @disabled($isDisabled)
            class="mes-btn mes-btn-{{ $variant }}"
            style="{{ $finalStyle }}{{ $isDisabled ? 'opacity:.6;cursor:wait;' : '' }}"
            {{ $attributes }}>
        @if($loading)
            <span class="mes-btn-spinner" style="width:14px;height:14px;border:2px solid currentColor;border-top-color:transparent;border-radius:50%;animation:mes-spin .6s linear infinite;"></span>
        @elseif($icon)
            {!! $icon !!}
        @endif
        {{ $slot }}
    </button>
@endif

@once
    <style>
        @keyframes mes-spin { to { transform: rotate(360deg); } }
        .mes-btn:hover:not(:disabled):not(.is-disabled) { transform: translateY(-1px); filter: brightness(1.05); }
        .mes-btn:active:not(:disabled):not(.is-disabled) { transform: translateY(0); }
        .mes-btn:focus-visible { outline: 2px solid var(--mes-primary); outline-offset: 2px; }
    </style>
@endonce
