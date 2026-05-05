@props([
    'icon' => 'inbox',
    'title' => 'Nessun risultato',
    'subtitle' => null,
    'compact' => false,
])

@php
    // SVG icon library inline (no external font)
    $icons = [
        'inbox'   => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11L2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"/>',
        'search'  => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
        'truck'   => '<rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
        'check'   => '<polyline points="22 11.08 22 12 12 12 12 2 12.92 2"/><polyline points="22 4 12 14.01 9 11.01"/>',
        'alert'   => '<path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        'box'     => '<path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>',
    ];
    $svgInner = $icons[$icon] ?? $icons['inbox'];
    $size = $compact ? '36' : '56';
    $padding = $compact ? '20px 16px' : '40px 20px';
    $titleSize = $compact ? '14px' : '16px';
@endphp

<div class="mes-empty-state" style="display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:{{ $padding }};color:var(--mes-text-secondary,#6b7280);font-family:inherit;">
    <svg width="{{ $size }}" height="{{ $size }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="opacity:.4;margin-bottom:12px;color:var(--mes-text-tertiary,#9ca3af);">
        {!! $svgInner !!}
    </svg>
    <div style="font-size:{{ $titleSize }};font-weight:600;color:var(--mes-text-primary,#374151);margin-bottom:4px;">{{ $title }}</div>
    @if($subtitle)
        <div style="font-size:12px;color:var(--mes-text-secondary,#6b7280);max-width:340px;line-height:1.5;">{{ $subtitle }}</div>
    @endif
    @isset($action)
        <div style="margin-top:16px;">{{ $action }}</div>
    @endisset
</div>
