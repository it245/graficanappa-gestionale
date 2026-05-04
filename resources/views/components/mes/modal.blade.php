@props([
    'id' => 'mes-modal-' . uniqid(),
    'title' => null,
    'size' => 'md',
    'closeOnBackdrop' => true,
])

@php
    $widthMap = [
        'sm' => '380px',
        'md' => '520px',
        'lg' => '720px',
        'xl' => '920px',
        'full' => '95vw',
    ];
    $width = $widthMap[$size] ?? $widthMap['md'];
@endphp

<div id="{{ $id }}" class="mes-modal" style="display:none;position:fixed;inset:0;z-index:var(--mes-z-modal);align-items:center;justify-content:center;padding:20px;" role="dialog" aria-modal="true" aria-labelledby="{{ $id }}-title">
    <div class="mes-modal-backdrop"
         style="position:absolute;inset:0;background:rgba(15,23,42,.55);backdrop-filter:blur(2px);"
         @if($closeOnBackdrop) onclick="document.getElementById('{{ $id }}').style.display='none';" @endif></div>

    <div class="mes-modal-card" style="position:relative;background:var(--mes-bg-card);border-radius:var(--mes-radius-xl);box-shadow:var(--mes-shadow-xl);width:100%;max-width:{{ $width }};max-height:90vh;overflow:auto;animation:mes-modal-in var(--mes-duration-slow) var(--mes-ease-emphasis);">
        @if($title)
            <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid var(--mes-border);">
                <h2 id="{{ $id }}-title" style="margin:0;font-size:16px;font-weight:600;color:var(--mes-text-primary);font-family:inherit;">{{ $title }}</h2>
                <button type="button" aria-label="Chiudi" onclick="document.getElementById('{{ $id }}').style.display='none';" style="background:none;border:none;cursor:pointer;color:var(--mes-text-secondary);padding:4px;line-height:1;font-size:20px;">×</button>
            </div>
        @endif

        <div style="padding:20px 24px;">
            {{ $slot }}
        </div>

        @isset($footer)
            <div style="display:flex;justify-content:flex-end;gap:8px;padding:16px 24px;border-top:1px solid var(--mes-border);background:var(--mes-bg-hover);">
                {{ $footer }}
            </div>
        @endisset
    </div>
</div>

@once
    <style>
        @keyframes mes-modal-in {
            from { opacity: 0; transform: translateY(8px) scale(.98); }
            to   { opacity: 1; transform: translateY(0) scale(1); }
        }
        .mes-modal[style*="display: flex"], .mes-modal[style*="display:flex"] { display: flex !important; }
    </style>
    <script>
        window.MESModal = window.MESModal || {
            open: function(id) { var m = document.getElementById(id); if (m) m.style.display = 'flex'; },
            close: function(id) { var m = document.getElementById(id); if (m) m.style.display = 'none'; }
        };
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.mes-modal').forEach(function(m) { if (m.style.display === 'flex') m.style.display = 'none'; });
            }
        });
    </script>
@endonce
