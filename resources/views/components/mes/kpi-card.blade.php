@props([
    'value' => '0',
    'label' => '',
    'color' => 'accent',
    'subtitle' => null,
    'href' => null,
    'id' => null,
])

<div class="kpi-card"
     @if($id) id="{{ $id }}" @endif
     @if($href) onclick="window.location='{{ $href }}'" style="cursor:pointer" @else style="cursor:default" @endif
     {{ $attributes }}>
    <div class="kpi-border" style="background:var(--{{ $color }})"></div>
    <div class="kpi-body">
        <span class="kpi-label">{{ $label }}</span>
        <span class="kpi-value">{{ $value }}</span>
        @if($subtitle)
            <span class="kpi-subtitle">{{ $subtitle }}</span>
        @endif
    </div>
</div>
