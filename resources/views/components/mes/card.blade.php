@props([
    'title' => null,
    'subtitle' => null,
    'variant' => 'default',
    'padding' => 'md',
    'hover' => false,
    'href' => null,
])

@php
    $accent = match($variant) {
        'success' => 'border-left:4px solid var(--mes-success);',
        'warning' => 'border-left:4px solid var(--mes-warning);',
        'danger'  => 'border-left:4px solid var(--mes-danger);',
        'info'    => 'border-left:4px solid var(--mes-info);',
        'primary' => 'border-left:4px solid var(--mes-primary);',
        default   => '',
    };
    $padStyle = match($padding) {
        'sm' => 'padding:12px;',
        'lg' => 'padding:32px;',
        'none' => 'padding:0;',
        default => 'padding:20px;',
    };
    $cardStyle = 'background:var(--mes-bg-card);border:1px solid var(--mes-border);border-radius:var(--mes-radius-lg);box-shadow:var(--mes-shadow-md);transition:box-shadow var(--mes-duration-base) var(--mes-ease-standard),transform var(--mes-duration-base);' . $accent;
@endphp

@if($href)
    <a href="{{ $href }}" class="mes-card mes-card-clickable" style="{{ $cardStyle }}display:block;text-decoration:none;color:inherit;" {{ $attributes }}>
@else
    <div class="mes-card {{ $hover ? 'mes-card-hover' : '' }}" style="{{ $cardStyle }}" {{ $attributes }}>
@endif

    @if($title)
        <div style="padding:16px 20px;border-bottom:1px solid var(--mes-border);">
            <h3 style="margin:0;font-size:14px;font-weight:600;color:var(--mes-text-primary);font-family:inherit;">{{ $title }}</h3>
            @if($subtitle)
                <p style="margin:2px 0 0;font-size:12px;color:var(--mes-text-secondary);">{{ $subtitle }}</p>
            @endif
        </div>
    @endif

    <div style="{{ $padStyle }}">
        {{ $slot }}
    </div>

    @isset($footer)
        <div style="padding:12px 20px;border-top:1px solid var(--mes-border);background:var(--mes-bg-hover);font-size:12px;color:var(--mes-text-secondary);">
            {{ $footer }}
        </div>
    @endisset

@if($href)
    </a>
@else
    </div>
@endif

@once
    <style>
        .mes-card-clickable:hover, .mes-card-hover:hover {
            box-shadow: var(--mes-shadow-lg);
            transform: translateY(-2px);
        }
    </style>
@endonce
