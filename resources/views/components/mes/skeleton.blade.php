@props([
    'width' => '100%',
    'height' => '14px',
    'rounded' => 'sm',
    'lines' => 1,
])

@php
    $radius = match($rounded) {
        'md' => 'var(--mes-radius-md)',
        'lg' => 'var(--mes-radius-lg)',
        'full' => 'var(--mes-radius-full)',
        default => 'var(--mes-radius-sm)',
    };
@endphp

@if((int) $lines <= 1)
    <span class="mes-skeleton"
          style="display:block;width:{{ $width }};height:{{ $height }};border-radius:{{ $radius }};background:linear-gradient(90deg,#e5e7eb 0%,#f3f4f6 50%,#e5e7eb 100%);background-size:200% 100%;animation:mes-skeleton-shimmer 1.5s ease-in-out infinite;"
          {{ $attributes }}></span>
@else
    <div {{ $attributes }}>
        @for($i = 0; $i < (int) $lines; $i++)
            <span class="mes-skeleton"
                  style="display:block;width:{{ $i === ((int) $lines - 1) ? '70%' : $width }};height:{{ $height }};border-radius:{{ $radius }};background:linear-gradient(90deg,#e5e7eb 0%,#f3f4f6 50%,#e5e7eb 100%);background-size:200% 100%;animation:mes-skeleton-shimmer 1.5s ease-in-out infinite;margin-bottom:8px;"></span>
        @endfor
    </div>
@endif

@once
    <style>
        @keyframes mes-skeleton-shimmer {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        body.dark-mode .mes-skeleton {
            background: linear-gradient(90deg, #334155 0%, #475569 50%, #334155 100%) !important;
            background-size: 200% 100% !important;
        }
    </style>
@endonce
