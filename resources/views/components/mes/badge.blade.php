@props([
    'variant' => 'default',
    'size' => 'md',
    'stato' => null,
])

@php
    if ($stato !== null) {
        $variant = match((string) $stato) {
            '0' => 'neutral',
            '1' => 'info',
            '2' => 'warning',
            '3' => 'success',
            '4' => 'success',
            '5' => 'external',
            default => 'default',
        };
        $label = match((string) $stato) {
            '0' => 'Caricato',
            '1' => 'Pronto',
            '2' => 'Avviato',
            '3' => 'Terminato',
            '4' => 'Consegnato',
            '5' => 'EXT',
            default => (string) $stato,
        };
    }

    $colorMap = [
        'primary'  => ['bg' => 'var(--mes-primary-soft)', 'fg' => 'var(--mes-primary-active)'],
        'success'  => ['bg' => 'var(--mes-success-soft)', 'fg' => 'var(--mes-success-hover)'],
        'warning'  => ['bg' => 'var(--mes-warning-soft)', 'fg' => 'var(--mes-warning-hover)'],
        'danger'   => ['bg' => 'var(--mes-danger-soft)',  'fg' => 'var(--mes-danger-hover)'],
        'info'     => ['bg' => 'var(--mes-info-soft)',    'fg' => 'var(--mes-info-hover)'],
        'external' => ['bg' => 'rgba(139,92,246,.12)',    'fg' => 'var(--mes-external-hover)'],
        'neutral'  => ['bg' => '#f3f4f6',                 'fg' => '#374151'],
        'default'  => ['bg' => '#e5e7eb',                 'fg' => '#1f2937'],
    ];
    $c = $colorMap[$variant] ?? $colorMap['default'];

    $sizeStyle = match($size) {
        'sm' => 'padding:2px 8px;font-size:10px;',
        'lg' => 'padding:6px 14px;font-size:13px;',
        default => 'padding:3px 10px;font-size:11px;',
    };
@endphp

<span class="mes-badge"
      style="display:inline-flex;align-items:center;gap:4px;background:{{ $c['bg'] }};color:{{ $c['fg'] }};border-radius:var(--mes-radius-full);font-weight:600;letter-spacing:.3px;line-height:1.4;text-transform:uppercase;{{ $sizeStyle }}"
      {{ $attributes }}>
    {{ $label ?? $slot }}
</span>
