@props([
    'type' => 'info',
    'title' => null,
    'dismissible' => false,
    'autoclose' => null,
    'icon' => true,
])

@php
    $cfg = match($type) {
        'success' => ['bg' => '#ecfdf5', 'border' => 'var(--mes-success)', 'fg' => '#065f46', 'icon' => '✓'],
        'warning' => ['bg' => '#fffbeb', 'border' => 'var(--mes-warning)', 'fg' => '#92400e', 'icon' => '⚠'],
        'danger'  => ['bg' => '#fef2f2', 'border' => 'var(--mes-danger)', 'fg' => '#991b1b', 'icon' => '✕'],
        'info'    => ['bg' => '#eff6ff', 'border' => 'var(--mes-primary)', 'fg' => '#1e40af', 'icon' => 'ℹ'],
        default   => ['bg' => '#f3f4f6', 'border' => 'var(--mes-border-strong)', 'fg' => '#1f2937', 'icon' => 'ℹ'],
    };
    $id = 'mes-alert-' . uniqid();
@endphp

<div id="{{ $id }}" class="mes-alert"
     style="display:flex;align-items:flex-start;gap:10px;background:{{ $cfg['bg'] }};border-left:3px solid {{ $cfg['border'] }};color:{{ $cfg['fg'] }};padding:12px 14px;border-radius:var(--mes-radius-md);font-size:13px;line-height:1.5;margin-bottom:12px;font-family:inherit;"
     {{ $attributes }}>
    @if($icon)
        <span aria-hidden="true" style="flex-shrink:0;font-weight:700;font-size:14px;line-height:1.4;">{{ $cfg['icon'] }}</span>
    @endif
    <div style="flex:1;">
        @if($title)
            <strong style="display:block;margin-bottom:2px;font-weight:600;">{{ $title }}</strong>
        @endif
        {{ $slot }}
    </div>
    @if($dismissible)
        <button type="button" aria-label="Chiudi"
                onclick="document.getElementById('{{ $id }}').style.display='none';"
                style="background:none;border:none;cursor:pointer;color:inherit;opacity:.6;padding:0 4px;font-size:16px;line-height:1;">×</button>
    @endif
</div>

@if($autoclose)
    <script>
        setTimeout(function(){
            var el = document.getElementById('{{ $id }}');
            if (el) {
                el.style.transition = 'opacity .4s, transform .4s';
                el.style.opacity = '0';
                el.style.transform = 'translateY(-8px)';
                setTimeout(function(){ el.style.display='none'; }, 400);
            }
        }, {{ (int) $autoclose }});
    </script>
@endif
