<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    @hasSection('viewport')
        @yield('viewport')
    @else
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @endif
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="op-token" content="{{ $opToken ?? '' }}">
    <title>@yield('page-title', 'MES Grafica Nappa')</title>

    {{-- PWA --}}
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="MES">
    <link rel="apple-touch-icon" href="/images/icons/icon-192x192.png">

    {{-- Fonts (preload + display=swap per evitare FOIT) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500;600&display=swap"></noscript>

    {{-- MES Design Tokens --}}
    <link rel="stylesheet" href="{{ asset('css/mes-tokens.css') }}?v=1">
    <link rel="stylesheet" href="{{ asset('css/mes-print.css') }}?v=1" media="print">

    {{-- Vendor CSS (locale, no CDN latency) --}}
    <link rel="stylesheet" href="{{ asset('css/bootstrap-5.3.0.min.css') }}?v=1">
    @yield('vendor-css')

    {{-- Preload risorse critiche --}}
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" as="script">
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="https://fonts.googleapis.com">

    <style>
    /* ============================================
       MES Design System - CSS Custom Properties
       ============================================ */
    :root {
        --bg-page: #f8fafc;
        --bg-card: #ffffff;
        --bg-sidebar: #1e293b;
        --sidebar-hover: #334155;
        --sidebar-active: #2563eb;
        --text-primary: #1e293b;
        --text-secondary: #64748b;
        --border-color: #e2e8f0;
        --accent: #2563eb;
        --success: #16a34a;
        --warning: #d97706;
        --danger: #dc2626;
        --info: #0891b2;
        --external: #7c3aed;

        --sidebar-width: 220px;
        --topbar-height: 48px;
    }

    body.dark-mode {
        --bg-page: #0f172a;
        --bg-card: #1e293b;
        --bg-sidebar: #0f172a;
        --sidebar-hover: #1e293b;
        --text-primary: #f1f5f9;
        --text-secondary: #94a3b8;
        --border-color: #334155;
    }

    /* ============================================
       Base
       ============================================ */
    *, *::before, *::after { box-sizing: border-box; }

    html, body {
        margin: 0;
        padding: 0;
        height: 100%;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        background: var(--bg-page);
        color: var(--text-primary);
    }

    /* ============================================
       Sidebar
       ============================================ */
    .mes-sidebar {
        position: fixed;
        top: 0;
        left: 0;
        width: var(--sidebar-width);
        height: 100vh;
        background: var(--bg-sidebar);
        z-index: 1000;
        display: flex;
        flex-direction: column;
        overflow-y: auto;
        overflow-x: hidden;
        transition: transform 0.25s ease;
    }

    .mes-sidebar-logo {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 16px;
        border-bottom: 1px solid rgba(255,255,255,0.08);
        flex-shrink: 0;
    }

    .mes-sidebar-logo img {
        height: 28px;
        width: auto;
    }

    .mes-sidebar-logo span {
        font-size: 13px;
        font-weight: 700;
        color: #f1f5f9;
        line-height: 1.2;
    }

    .mes-sidebar-section {
        padding: 16px 16px 4px;
    }

    .mes-sidebar-section-label {
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-secondary);
        margin-bottom: 6px;
    }

    .mes-sidebar-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 9px 14px;
        margin: 1px 8px;
        border-radius: 8px;
        text-decoration: none;
        color: #cbd5e1;
        font-size: 13px;
        font-weight: 500;
        transition: background 0.18s cubic-bezier(0.4,0,0.2,1), color 0.18s, transform 0.18s;
        cursor: pointer;
        border: none;
        background: none;
        width: calc(100% - 16px);
        text-align: left;
        position: relative;
    }

    .mes-sidebar-item:hover {
        background: var(--sidebar-hover);
        color: #f8fafc;
        text-decoration: none;
        transform: translateX(2px);
    }

    .mes-sidebar-item.active {
        background: linear-gradient(90deg, rgba(59,130,246,0.18), rgba(59,130,246,0.05));
        color: #60a5fa;
        font-weight: 600;
    }
    .mes-sidebar-item.active::before {
        content: '';
        position: absolute;
        left: -8px;
        top: 6px;
        bottom: 6px;
        width: 3px;
        background: #3b82f6;
        border-radius: 0 3px 3px 0;
    }

    .mes-sidebar-item svg {
        width: 16px;
        height: 16px;
        flex-shrink: 0;
    }

    .mes-sidebar-footer {
        margin-top: auto;
        padding: 12px 16px;
        border-top: 1px solid rgba(255,255,255,0.08);
        font-size: 11px;
        color: #475569;
        flex-shrink: 0;
    }

    /* ============================================
       Topbar
       ============================================ */
    .mes-topbar {
        position: fixed;
        top: 0;
        left: var(--sidebar-width);
        right: 0;
        height: var(--topbar-height);
        background: var(--bg-card);
        border-bottom: 1px solid var(--border-color);
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 24px;
        z-index: 999;
        transition: left 0.25s ease;
        backdrop-filter: blur(8px);
    }

    .mes-topbar-title {
        font-size: 15px;
        font-weight: 600;
        color: var(--text-primary);
    }

    .mes-topbar-right {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .mes-topbar-clock {
        font-family: 'IBM Plex Mono', 'SF Mono', Consolas, monospace;
        font-size: 13px;
        font-weight: 500;
        color: var(--text-secondary);
        font-variant-numeric: tabular-nums;
        padding: 4px 10px;
        background: var(--bg-page);
        border: 1px solid var(--border-color);
        border-radius: 6px;
    }

    .mes-topbar-user {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 500;
        color: var(--text-primary);
    }

    .mes-topbar-avatar {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: var(--accent);
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 700;
    }

    .mes-topbar-logout {
        font-size: 12px;
        font-weight: 500;
        color: var(--text-secondary);
        text-decoration: none;
        padding: 6px 12px;
        border-radius: 6px;
        border: 1px solid var(--border-color);
        transition: all 0.18s cubic-bezier(0.4,0,0.2,1);
    }

    .mes-topbar-logout:hover {
        background: var(--danger);
        color: #fff;
        border-color: var(--danger);
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(239,68,68,0.2);
    }

    /* Dark mode toggle */
    .mes-darkmode-toggle {
        background: none;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        padding: 5px 8px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-secondary);
        transition: background 0.15s, color 0.15s;
    }

    .mes-darkmode-toggle:hover {
        background: var(--border-color);
        color: var(--text-primary);
    }

    .mes-darkmode-toggle svg {
        width: 16px;
        height: 16px;
    }

    /* ============================================
       Content Area
       ============================================ */
    .mes-content {
        margin-left: var(--sidebar-width);
        padding-top: var(--topbar-height);
        min-height: 100vh;
        transition: margin-left 0.25s ease;
    }

    .mes-content-inner {
        padding: 8px 16px;
    }

    /* ============================================
       Mobile Hamburger
       ============================================ */
    .mes-hamburger {
        display: none;
        background: none;
        border: none;
        cursor: pointer;
        padding: 10px;
        margin-right: 8px;
        border-radius: 8px;
        color: var(--text-primary);
        min-width: 44px;
        min-height: 44px;
        align-items: center;
        justify-content: center;
        transition: background 0.15s;
        /* iOS Safari 18+: rimuove delay double-tap zoom (300ms) e garantisce
           tap immediato. Senza, l'icona reagisce all'hover ma il click non
           parte. */
        touch-action: manipulation;
        -webkit-tap-highlight-color: rgba(0,0,0,0.1);
        position: relative;
        z-index: 1000;
    }

    .mes-hamburger:hover, .mes-hamburger:focus-visible {
        background: var(--border-color);
        outline: none;
    }

    .mes-hamburger:active {
        background: var(--mes-primary-soft, rgba(59,130,246,0.15));
    }

    .mes-hamburger svg {
        width: 24px;
        height: 24px;
    }

    .mes-sidebar-overlay {
        display: none;
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 999;
    }

    /* ============================================
       Alerts
       ============================================ */
    .mes-alert {
        border-radius: 8px;
        font-size: 13px;
        padding: 10px 16px;
        margin-bottom: 16px;
    }

    /* ============================================
       KPI Card Styles
       ============================================ */
    .kpi-card {
        display: flex;
        background: var(--bg-card);
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        overflow: hidden;
        transition: transform 0.15s, box-shadow 0.15s;
    }
    .kpi-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .kpi-border {
        width: 4px;
        flex-shrink: 0;
    }
    .kpi-body {
        padding: 16px 20px;
    }
    .kpi-label {
        display: block;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: var(--text-secondary);
    }
    .kpi-value {
        display: block;
        font-size: 28px;
        font-weight: 700;
        color: var(--text-primary);
        line-height: 1.2;
    }
    .kpi-subtitle {
        display: block;
        font-size: 12px;
        color: var(--text-secondary);
        margin-top: 2px;
    }

    /* ============================================
       Status Badge Styles
       ============================================ */
    .status-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.02em;
    }
    .status-0 { background: #e9ecef; color: #495057; }
    .status-1 { background: #dbeafe; color: #1d4ed8; }
    .status-2 { background: #fef3c7; color: #b45309; }
    .status-3 { background: #d1fae5; color: #065f46; }
    .status-4 { background: #d1d5db; color: #1f2937; }

    /* ============================================
       Progress Bar Styles
       ============================================ */
    .mes-progress {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .mes-progress-track {
        flex: 1;
        height: 6px;
        border-radius: 3px;
        background: var(--border-color);
        position: relative;
        overflow: hidden;
    }
    .mes-progress-fill {
        position: absolute;
        top: 0;
        left: 0;
        height: 100%;
        border-radius: 3px;
    }
    .mes-progress-done { background: var(--success); z-index: 2; }
    .mes-progress-active { background: var(--warning); z-index: 1; }
    .mes-progress-text {
        font-size: 11px;
        font-weight: 600;
        color: var(--text-secondary);
        min-width: 32px;
    }

    /* ============================================
       Data Table Styles
       ============================================ */
    .mes-table-container {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        background: var(--bg-card);
    }
    .mes-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }
    .mes-table thead th {
        background: var(--bg-sidebar);
        color: #fff;
        padding: 8px 6px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        border: none;
        border-bottom: 2px solid var(--accent);
    }
    .mes-table tbody td {
        padding: 4px 8px;
        border-bottom: 1px solid var(--border-color);
        color: var(--text-primary);
        line-height: 1.4;
        vertical-align: middle;
    }
    .mes-table tbody tr:hover td {
        background: rgba(37,99,235,0.04);
    }
    .mes-table tbody tr:nth-child(even) td {
        background: rgba(0,0,0,0.015);
    }
    .mes-table tbody tr:nth-child(even):hover td {
        background: rgba(37,99,235,0.04);
    }

    /* ============================================
       Row highlight classes (preserved from legacy)
       ============================================ */
    tr.scaduta td {
        background-color: #e8747a !important;
        color: #000 !important;
        font-weight: 700;
    }
    tr.warning-strong td {
        background-color: #f96f2a !important;
        color: #000 !important;
        font-weight: 700;
    }
    tr.warning-light td {
        background-color: #ffd07a !important;
        color: #000 !important;
        font-weight: 700;
    }

    /* ============================================
       Responsive (< 1024px)
       ============================================ */
    @media (max-width: 1023px) {
        .mes-sidebar {
            transform: translateX(-100%);
        }

        .mes-sidebar.open {
            transform: translateX(0);
        }

        .mes-sidebar-overlay.open {
            display: block;
        }

        .mes-topbar {
            left: 0;
        }

        .mes-content {
            margin-left: 0;
        }

        .mes-hamburger {
            display: flex;
        }
    }

    /* ============================================
       Utility classes
       ============================================ */
    .text-accent { color: var(--accent) !important; }
    .text-success { color: var(--success) !important; }
    .text-warning { color: var(--warning) !important; }
    .text-danger { color: var(--danger) !important; }
    .text-info { color: var(--info) !important; }
    .text-external { color: var(--external) !important; }
    .text-muted-mes { color: var(--text-secondary) !important; }
    .bg-card { background: var(--bg-card) !important; }
    .border-mes { border-color: var(--border-color) !important; }

    </style>

    @yield('styles')
</head>
<body>

    {{-- Memorizza dashboard PRINCIPALE di provenienza (solo /owner/dashboard, /operatore/dashboard, /spedizione/dashboard) --}}
    <script>
        (function() {
            try {
                var p = location.pathname;
                if (/^\/(owner|operatore|spedizione)\/dashboard\/?$/.test(p)) {
                    sessionStorage.setItem('mesLastDashboard', p + location.search);
                }
            } catch (e) {}
        })();
    </script>

    {{-- Loading bar per navigazione veloce --}}
    <div id="mesLoadingBar" style="position:fixed;top:0;left:0;width:0;height:3px;background:var(--accent,#2563eb);z-index:99999;transition:width 0.3s ease;"></div>
    <style>
    #mesLoadingBar.active { width: 70%; transition: width 2s ease; }
    #mesLoadingBar.done { width: 100%; transition: width 0.2s ease; opacity: 0; transition: width 0.2s ease, opacity 0.3s ease 0.2s; }
    </style>
    <script>
    // Mostra barra di caricamento su ogni click di navigazione
    document.addEventListener('click', function(e) {
        var link = e.target.closest('a[href]');
        if (!link) return;
        var href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:') || link.target === '_blank') return;
        var bar = document.getElementById('mesLoadingBar');
        if (bar) { bar.style.width = '0'; bar.className = ''; requestAnimationFrame(function() { bar.className = 'active'; }); }
    });
    window.addEventListener('pageshow', function() {
        var bar = document.getElementById('mesLoadingBar');
        if (bar) { bar.className = 'done'; setTimeout(function() { bar.style.width = '0'; bar.className = ''; }, 500); }
    });
    </script>

    {{-- Dark mode: apply class before paint --}}
    <script>
    (function(){
        if (localStorage.getItem('mes-dark-mode') === '1') {
            document.body.classList.add('dark-mode');
        }
    })();
    </script>

    {{-- Sidebar Overlay (mobile) --}}
    <div class="mes-sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    {{-- Sidebar --}}
    <nav class="mes-sidebar" id="mesSidebar">
        <div class="mes-sidebar-logo">
            <img src="{{ asset('images/logo_gn.png') }}" alt="Logo">
            <span>MES<br>Grafica Nappa</span>
        </div>

        @yield('sidebar-items')

        <div class="mes-sidebar-footer">
            v2.0 &middot; {{ now()->format('d/m/Y') }}
        </div>
    </nav>

    {{-- Topbar --}}
    <header class="mes-topbar" id="mesTopbar">
        <div style="display:flex; align-items:center;">
            <button class="mes-hamburger" id="mesHamburger" onclick="toggleSidebar()" aria-label="Menu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
            <span class="mes-topbar-title">@yield('topbar-title', 'Dashboard')</span>
        </div>
        <div class="mes-topbar-right">
            @yield('topbar-actions')

            {{-- Dark mode toggle --}}
            <button class="mes-darkmode-toggle" id="darkModeToggle" onclick="toggleDarkMode()" title="Tema scuro/chiaro">
                {{-- Sun icon (shown in dark mode) --}}
                <svg id="iconSun" style="display:none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                {{-- Moon icon (shown in light mode) --}}
                <svg id="iconMoon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
            </button>

            {{-- Clock --}}
            <span class="mes-topbar-clock" id="mesClock"></span>

            {{-- User --}}
            <div class="mes-topbar-user">
                <div class="mes-topbar-avatar">
                    {{ strtoupper(substr($operatore->nome ?? 'U', 0, 1)) }}{{ strtoupper(substr($operatore->cognome ?? '', 0, 1)) }}
                </div>
                <span>{{ $operatore->nome ?? '' }} {{ $operatore->cognome ?? '' }}</span>
            </div>

            <a href="{{ route('operatore.logout') }}" class="mes-topbar-logout">Esci</a>
        </div>
    </header>

    {{-- Content --}}
    <main class="mes-content">
        <div class="mes-content-inner">
            {{-- Flash messages --}}
            @if(session('success'))
                <div class="alert alert-success mes-alert alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger mes-alert alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @yield('content')
        </div>
    </main>

    {{-- Vendor JS (solo Bootstrap globale) --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>

    {{-- Script opzionali: caricati solo dalle pagine che li usano via @section('vendor-scripts') --}}
    @yield('vendor-scripts')

    <script>
    /* ===========================================
       CSRF + Op Token + Fetch Interceptor
       (preserved from app.blade.php)
       =========================================== */
    window.csrfToken = function() {
        return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    };

    (function() {
        var metaToken = document.querySelector('meta[name="op-token"]');
        var urlToken = new URLSearchParams(window.location.search).get('op_token');
        var token = urlToken || (metaToken ? metaToken.getAttribute('content') : '') || sessionStorage.getItem('op_token') || '';

        if (token) {
            sessionStorage.setItem('op_token', token);
        }

        window.opToken = function() {
            return sessionStorage.getItem('op_token') || '';
        };

        function appendTokenToLinks() {
            var tk = window.opToken();
            if (!tk) return;
            document.querySelectorAll('a[href]').forEach(function(a) {
                var href = a.getAttribute('href');
                if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('data:')) return;
                try {
                    var url = new URL(href, window.location.origin);
                    if (url.origin !== window.location.origin) return;
                    if (url.searchParams.has('op_token')) return;
                    url.searchParams.set('op_token', tk);
                    a.setAttribute('href', url.pathname + url.search + url.hash);
                } catch(e) {}
            });
        }

        function appendTokenToForms() {
            var tk = window.opToken();
            if (!tk) return;
            document.querySelectorAll('form').forEach(function(form) {
                if (form.querySelector('input[name="op_token"]')) return;
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'op_token';
                input.value = tk;
                form.appendChild(input);
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            appendTokenToLinks();
            appendTokenToForms();
        });

        var observer = new MutationObserver(function() {
            appendTokenToLinks();
            appendTokenToForms();
        });
        observer.observe(document.documentElement, { childList: true, subtree: true });

        var originalFetch = window.fetch;
        window.fetch = function(url, options) {
            options = options || {};
            options.headers = options.headers || {};
            if (options.headers['X-CSRF-TOKEN']) {
                options.headers['X-CSRF-TOKEN'] = csrfToken();
            }
            var tk = window.opToken();
            if (tk) {
                options.headers['X-Op-Token'] = tk;
            }
            return originalFetch.call(this, url, options).then(function(response) {
                if (response.status === 419) {
                    alert('Sessione scaduta. La pagina verra ricaricata.');
                    window.location.reload();
                    return Promise.reject('Token scaduto');
                }
                return response;
            });
        };
    })();

    /* ===========================================
       CSRF Token Refresh (evita 419 Page Expired)
       =========================================== */
    // Refresh su pageshow (frecce avanti/indietro del browser)
    window.addEventListener('pageshow', function(e) {
        if (e.persisted) {
            fetch('/csrf-refresh').then(function(r) { return r.json(); }).then(function(d) {
                if (d.token) {
                    var meta = document.querySelector('meta[name="csrf-token"]');
                    if (meta) meta.setAttribute('content', d.token);
                }
            }).catch(function() {});
        }
    });
    // Refresh ogni 30 minuti
    setInterval(function() {
        fetch('/csrf-refresh').then(function(r) { return r.json(); }).then(function(d) {
            if (d.token) {
                var meta = document.querySelector('meta[name="csrf-token"]');
                if (meta) meta.setAttribute('content', d.token);
            }
        }).catch(function() {});
    }, 30 * 60 * 1000);

    /* ===========================================
       Sidebar Toggle (mobile)
       =========================================== */
    function toggleSidebar() {
        document.getElementById('mesSidebar').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('open');
    }

    // iOS Safari 18+: onclick inline talvolta non parte se l'elemento ha
    // backdrop-filter parent. Listener esplicito su 'click' E 'touchend'
    // garantisce tap reattivo. preventDefault su touchend evita doppio fire.
    (function() {
        var h = document.getElementById('mesHamburger');
        if (!h) return;
        var fired = false;
        h.addEventListener('touchend', function(e) {
            fired = true;
            e.preventDefault();
            toggleSidebar();
            setTimeout(function() { fired = false; }, 350);
        }, { passive: false });
        h.addEventListener('click', function(e) {
            if (fired) return;
            toggleSidebar();
        });
    })();

    /* ===========================================
       Dark Mode Toggle
       =========================================== */
    function toggleDarkMode() {
        var isDark = document.body.classList.toggle('dark-mode');
        localStorage.setItem('mes-dark-mode', isDark ? '1' : '0');
        updateDarkModeIcons();
    }

    function updateDarkModeIcons() {
        var isDark = document.body.classList.contains('dark-mode');
        document.getElementById('iconSun').style.display = isDark ? 'block' : 'none';
        document.getElementById('iconMoon').style.display = isDark ? 'none' : 'block';
    }

    // Initialize icons on load
    updateDarkModeIcons();

    /* ===========================================
       Topbar Clock
       =========================================== */
    function updateClock() {
        var now = new Date();
        var h = String(now.getHours()).padStart(2, '0');
        var m = String(now.getMinutes()).padStart(2, '0');
        var s = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('mesClock').textContent = h + ':' + m + ':' + s;
    }
    updateClock();
    setInterval(updateClock, 1000);

    /* ===========================================
       Service Worker Registration (PWA)
       =========================================== */
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('/sw.js').then(function(reg) {
            console.log('SW registrato:', reg.scope);
            // Auto-subscribe push notifications dopo 3s
            setTimeout(function() { initPushNotifications(reg); }, 3000);
        }).catch(function(err) {
            console.log('SW errore:', err);
        });
    }

    function initPushNotifications(swReg) {
        if (!('PushManager' in window)) return;
        if (Notification.permission === 'denied') return;

        // Chiedi permesso se non ancora concesso
        if (Notification.permission === 'default') {
            Notification.requestPermission().then(function(perm) {
                if (perm === 'granted') subscribePush(swReg);
            });
        } else if (Notification.permission === 'granted') {
            subscribePush(swReg);
        }
    }

    function subscribePush(swReg) {
        fetch('/push/vapid-key').then(r => r.json()).then(function(data) {
            if (!data.publicKey) return;
            var vapidKey = urlBase64ToUint8Array(data.publicKey);
            swReg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: vapidKey
            }).then(function(subscription) {
                var key = subscription.getKey('p256dh');
                var auth = subscription.getKey('auth');
                fetch('/push/subscribe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken()
                    },
                    body: JSON.stringify({
                        endpoint: subscription.endpoint,
                        keys: {
                            p256dh: btoa(String.fromCharCode.apply(null, new Uint8Array(key))),
                            auth: btoa(String.fromCharCode.apply(null, new Uint8Array(auth)))
                        }
                    })
                });
            }).catch(function(err) {
                console.log('Push subscribe errore:', err);
            });
        });
    }

    function urlBase64ToUint8Array(base64String) {
        var padding = '='.repeat((4 - base64String.length % 4) % 4);
        var base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        var rawData = window.atob(base64);
        var outputArray = new Uint8Array(rawData.length);
        for (var i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }
    </script>

    @yield('scripts')

    {{-- ============================================
         CHAT WIDGET FLOTTANTE (stile Messenger)
         ============================================ --}}
    <style>
    .chat-fab {
        position: fixed;
        bottom: 24px;
        right: 24px;
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: var(--accent, #2563eb);
        color: #fff;
        border: none;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(0,0,0,0.25);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .chat-fab:hover { transform: scale(1.1); box-shadow: 0 6px 20px rgba(0,0,0,0.3); }
    .chat-fab svg { width: 26px; height: 26px; }
    .chat-fab-badge {
        position: absolute;
        top: -4px;
        right: -4px;
        background: var(--danger, #dc2626);
        color: #fff;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 11px;
        font-weight: 700;
        display: none;
        align-items: center;
        justify-content: center;
        line-height: 20px;
        text-align: center;
    }

    .chat-popup {
        position: fixed;
        bottom: 90px;
        right: 24px;
        width: 360px;
        height: 480px;
        background: var(--bg-card, #fff);
        border-radius: 16px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.2);
        z-index: 9998;
        display: none;
        flex-direction: column;
        overflow: hidden;
        border: 1px solid var(--border-color, #e2e8f0);
    }
    .chat-popup.open { display: flex; }

    .chat-popup-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        background: var(--accent, #2563eb);
        color: #fff;
    }
    .chat-popup-header h6 { margin: 0; font-weight: 600; font-size: 14px; }
    .chat-popup-close {
        background: none;
        border: none;
        color: #fff;
        font-size: 20px;
        cursor: pointer;
        padding: 0 4px;
        line-height: 1;
        opacity: 0.8;
    }
    .chat-popup-close:hover { opacity: 1; }

    .chat-popup-canali {
        display: flex;
        gap: 4px;
        padding: 8px 12px;
        background: var(--bg-page, #f8fafc);
        border-bottom: 1px solid var(--border-color, #e2e8f0);
        overflow-x: auto;
    }
    .chat-popup-canali .cp-canale {
        padding: 4px 12px;
        border-radius: 16px;
        border: 1px solid var(--border-color, #e2e8f0);
        background: var(--bg-card, #fff);
        font-size: 12px;
        cursor: pointer;
        white-space: nowrap;
        color: var(--text-primary);
    }
    .chat-popup-canali .cp-canale.active {
        background: var(--accent, #2563eb);
        color: #fff;
        border-color: var(--accent, #2563eb);
    }

    .chat-popup-msgs {
        flex: 1;
        overflow-y: auto;
        padding: 12px;
        display: flex;
        flex-direction: column;
        gap: 4px;
        background: var(--bg-page, #f8fafc);
    }
    .cp-msg {
        max-width: 80%;
        padding: 6px 10px;
        border-radius: 10px;
        font-size: 13px;
        line-height: 1.4;
        word-wrap: break-word;
    }
    .cp-msg.mio { align-self: flex-end; background: #dcf8c6; border-bottom-right-radius: 2px; }
    .cp-msg.altro { align-self: flex-start; background: var(--bg-card, #fff); border-bottom-left-radius: 2px; border: 1px solid var(--border-color, #e2e8f0); }
    .cp-msg .cp-utente { font-size: 10px; font-weight: 700; color: var(--accent, #2563eb); margin-bottom: 1px; }
    .cp-msg.mio .cp-utente { color: #075e54; }
    .cp-msg .cp-ora { font-size: 9px; color: var(--text-secondary, #999); text-align: right; }

    .chat-popup-input {
        display: flex;
        gap: 6px;
        padding: 10px 12px;
        border-top: 1px solid var(--border-color, #e2e8f0);
        background: var(--bg-card, #fff);
    }
    .chat-popup-input input {
        flex: 1;
        border-radius: 20px;
        border: 1px solid var(--border-color, #e2e8f0);
        padding: 8px 14px;
        font-size: 13px;
        outline: none;
        background: var(--bg-page, #f8fafc);
        color: var(--text-primary);
    }
    .chat-popup-input input:focus { border-color: var(--accent, #2563eb); }
    .chat-popup-input button {
        border-radius: 50%;
        width: 36px;
        height: 36px;
        border: none;
        background: var(--accent, #2563eb);
        color: #fff;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .chat-popup-input button:hover { opacity: 0.9; }

    @media (max-width: 480px) {
        .chat-popup { width: calc(100vw - 20px); right: 10px; bottom: 80px; height: 60vh; }
    }
    </style>

    {{-- FAB Button --}}
    <button class="chat-fab" id="chatFab" onclick="toggleChatPopup()" title="Chat MES">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
        <span class="chat-fab-badge" id="chatFabBadge">0</span>
    </button>

    {{-- Chat Popup --}}
    <div class="chat-popup" id="chatPopup">
        <div class="chat-popup-header">
            <h6>Chat MES</h6>
            <button class="chat-popup-close" onclick="toggleChatPopup()">&times;</button>
        </div>
        <div class="chat-popup-canali" id="cpCanali"></div>
        <div class="chat-popup-msgs" id="cpMsgs">
            <div style="text-align:center; color:var(--text-secondary); padding:20px; font-size:13px;">Caricamento...</div>
        </div>
        <div class="chat-popup-input" style="position:relative;">
            <div id="cpMentionDropdown" style="display:none; position:absolute; bottom:100%; left:12px; right:60px; max-height:200px; overflow-y:auto; background:var(--bg-card,#fff); border:1px solid var(--border-color,#e2e8f0); border-radius:8px; box-shadow:0 -4px 12px rgba(0,0,0,0.15); z-index:10;"></div>
            <input type="file" id="cpFileInput" style="display:none;" onchange="cpFileSelezionato(this)">
            <button id="cpAttachBtn" onclick="cpMostraMenuAllegato(event)" title="Allega" style="background:transparent;color:var(--text-secondary,#888);padding:6px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg>
            </button>
            <input type="text" id="cpInput" placeholder="Scrivi... (@nome per menzionare)" autocomplete="off" onkeydown="if(event.key==='Enter' && !cpMentionVisible())cpInvia()" oninput="cpCheckMention(this)">
            <button onclick="cpInvia()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </button>
        </div>
    </div>

    <script>
    (function() {
        var cpOpen = false;
        var cpCanale = 'Tutti';
        var cpUltimoId = 0;
        var cpOperatoreId = {{ $operatore->id ?? 0 }};
        var cpOperatoreNome = @json(($operatore->nome ?? '') . ' ' . ($operatore->cognome ?? ''));
        var cpCanali = ['Tutti', 'Stampa Offset', 'Stampa a Caldo', 'Fustella', 'Piegaincolla', 'Legatoria', 'Spedizione', 'Prestampa', 'Urgenze'];
        var cpPollTimer = null;
        var cpUnread = 0;

        window.toggleChatPopup = function() {
            cpOpen = !cpOpen;
            document.getElementById('chatPopup').classList.toggle('open', cpOpen);
            if (cpOpen) {
                cpUnread = 0;
                updateBadge();
                cpLoadCanali();
                cpLoadMessaggi();
                if (!cpPollTimer) cpPollTimer = setInterval(cpPoll, 2000);
                setTimeout(function() { document.getElementById('cpInput').focus(); }, 100);
            }
        };

        function cpLoadCanali() {
            var html = '';
            cpCanali.forEach(function(c) {
                html += '<span class="cp-canale ' + (c === cpCanale ? 'active' : '') + '" onclick="cpCambiaCanale(\'' + c + '\')">@' + c + '</span>';
            });
            document.getElementById('cpCanali').innerHTML = html;
        }

        window.cpCambiaCanale = function(c) {
            cpCanale = c;
            cpUltimoId = 0;
            cpLoadCanali();
            cpLoadMessaggi();
        };

        function cpLoadMessaggi() {
            var container = document.getElementById('cpMsgs');
            container.innerHTML = '<div style="text-align:center; color:var(--text-secondary); padding:20px; font-size:13px;">Caricamento...</div>';
            fetch('/chat/messaggi?canale=' + cpCanale + '&after=0')
                .then(function(r) { return r.json(); })
                .then(function(msgs) {
                    container.innerHTML = '';
                    if (msgs.length === 0) {
                        container.innerHTML = '<div style="text-align:center; color:var(--text-secondary); padding:20px; font-size:13px;">Nessun messaggio</div>';
                        return;
                    }
                    msgs.forEach(function(m) { cpAppend(m, container); });
                    container.scrollTop = container.scrollHeight;
                    if (msgs.length > 0) cpUltimoId = msgs[msgs.length - 1].id;
                })
                .catch(function() {
                    container.innerHTML = '<div style="text-align:center; color:var(--text-secondary); padding:20px; font-size:13px;">Errore caricamento</div>';
                });
        }

        function cpAppend(msg, container) {
            if (!container) container = document.getElementById('cpMsgs');
            var div = document.createElement('div');
            var isMio = msg.operatore_id === cpOperatoreId || msg.mio || msg.autore_id === cpOperatoreId;
            div.className = 'cp-msg ' + (isMio ? 'mio' : 'altro') + (msg.eliminato ? ' eliminato' : '');
            if (msg.id) div.dataset.msgId = msg.id;
            div.dataset.lettureCount = (msg.letture_count || 0);
            div.dataset.destinatariCount = (msg.destinatari_count || 0);
            var html = '';
            if (!isMio && !msg.eliminato) html += '<div class="cp-utente">' + cpEsc(msg.utente || msg.operatore_nome || '') + '</div>';
            if (msg.is_pinned) html += '<div style="font-size:10px;color:#f59e0b;margin-bottom:2px;">📌 Importante</div>';
            if (msg.eliminato) {
                html += '<div style="font-style:italic;color:var(--text-secondary,#888);">🚫 Questo messaggio è stato eliminato</div>';
            } else if (msg.audio_url) {
                var durata = msg.audio_durata_sec ? msg.audio_durata_sec + 's' : '';
                html += '<div style="display:flex;align-items:center;gap:6px;">'
                     + '<audio controls preload="metadata" style="height:32px;max-width:200px;" src="' + cpEsc(msg.audio_url) + '"></audio>'
                     + (durata ? '<span style="font-size:11px;color:#888;">' + durata + '</span>' : '')
                     + '</div>';
            } else if (msg.attachment_url) {
                var mime = (msg.attachment_mime || '').toLowerCase();
                var isImg = mime.indexOf('image/') === 0;
                if (isImg) {
                    html += '<a href="' + cpEsc(msg.attachment_url) + '" target="_blank">'
                         + '<img src="' + cpEsc(msg.attachment_url) + '" style="max-width:200px;max-height:200px;border-radius:8px;display:block;" alt="' + cpEsc(msg.attachment_name || '') + '">'
                         + '</a>';
                } else {
                    var sizeKb = msg.attachment_size ? Math.round(msg.attachment_size / 1024) + ' KB' : '';
                    html += '<a href="' + cpEsc(msg.attachment_url) + '" target="_blank" download="' + cpEsc(msg.attachment_name || 'file') + '" style="display:flex;align-items:center;gap:8px;padding:8px;background:rgba(0,0,0,0.05);border-radius:8px;text-decoration:none;color:inherit;">'
                         + '<span style="font-size:24px;">📎</span>'
                         + '<span style="display:flex;flex-direction:column;"><span style="font-weight:600;font-size:12px;">' + cpEsc(msg.attachment_name || 'file') + '</span>'
                         + '<span style="font-size:10px;color:#888;">' + sizeKb + '</span></span>'
                         + '</a>';
                }
                if (msg.messaggio && msg.messaggio !== '[Allegato]') {
                    var caption = cpEsc(msg.messaggio).replace(/@([A-Za-zÀ-ÿ\s]+?)(?=\s|$)/g, '<span style="color:var(--accent,#2563eb);font-weight:600;">@$1</span>');
                    html += '<div style="margin-top:4px;">' + caption + '</div>';
                }
            } else {
                var msgText = cpEsc(msg.messaggio);
                msgText = msgText.replace(/@([A-Za-zÀ-ÿ\s]+?)(?=\s|$)/g, '<span style="color:var(--accent,#2563eb);font-weight:600;">@$1</span>');
                html += '<div>' + msgText + '</div>';
            }
            html += '<div class="cp-ora">' + cpEsc(msg.timestamp || '');
            // Letture: ✓ grigio (inviato), ✓✓ grigio (qualcuno ha letto), ✓✓ blu (tutti letto)
            if (isMio && !msg.eliminato && msg.id) {
                var lc = typeof msg.letture_count === 'number' ? msg.letture_count : 0;
                var dc = typeof msg.destinatari_count === 'number' ? msg.destinatari_count : 0;
                var checkSymbol, checkColor;
                if (lc === 0) { checkSymbol = '✓'; checkColor = '#9ca3af'; }
                else if (dc > 0 && lc >= dc) { checkSymbol = '✓✓'; checkColor = '#2563eb'; }
                else { checkSymbol = '✓✓'; checkColor = '#9ca3af'; }
                var titolo = lc + (dc > 0 ? '/' + dc : '') + ' letture';
                html += ' <span class="cp-letture" data-msgid="' + msg.id + '"'
                     + ' style="margin-left:6px;color:' + checkColor + ';font-size:11px;cursor:pointer;font-weight:600;"'
                     + ' title="' + titolo + '">' + checkSymbol + '</span>';
            }
            if (msg.id && !msg.eliminato) {
                var canDeleteAll = isMio && (typeof msg.eta_min !== 'number' || msg.eta_min <= 5);
                html += ' <span class="cp-del-trigger" data-msgid="' + msg.id + '"'
                     + ' data-canall="' + (canDeleteAll ? '1' : '0') + '"'
                     + ' style="margin-left:8px;cursor:pointer;opacity:0.6;font-size:11px;">⋮</span>';
            }
            html += '</div>';
            div.innerHTML = html;
            container.appendChild(div);
            var trigger = div.querySelector('.cp-del-trigger');
            if (trigger) trigger.addEventListener('click', cpMostraMenuElimina);
            var letture = div.querySelector('.cp-letture');
            if (letture) letture.addEventListener('click', function(e) {
                e.stopPropagation();
                cpMostraDettaglioLetture(msg);
            });
            cpAttachLongPress(div, msg);
            // Marca come letto se non e' mio + ha id (registra visualizzazione)
            if (!isMio && msg.id && !msg.eliminato) {
                cpSegnaLetto(msg.id);
            }
        }

        // Beep per nuovi messaggi (AudioContext, no file)
        var cpAudioCtx = null;
        function cpBeep() {
            try {
                if (!cpAudioCtx) cpAudioCtx = new (window.AudioContext || window.webkitAudioContext)();
                var ctx = cpAudioCtx;
                var osc = ctx.createOscillator();
                var gain = ctx.createGain();
                osc.connect(gain); gain.connect(ctx.destination);
                osc.type = 'sine';
                osc.frequency.value = 880;
                gain.gain.setValueAtTime(0, ctx.currentTime);
                gain.gain.linearRampToValueAtTime(0.15, ctx.currentTime + 0.02);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.25);
                osc.start(ctx.currentTime);
                osc.stop(ctx.currentTime + 0.3);
            } catch (e) {}
        }

        function cpSegnaLetto(msgId) {
            if (!window._cpLetti) window._cpLetti = {};
            if (window._cpLetti[msgId]) return;
            window._cpLetti[msgId] = true;
            fetch('/chat/messaggi/' + msgId + '/visualizza', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' }
            }).catch(function() { delete window._cpLetti[msgId]; });
        }

        function cpMostraDettaglioLetture(msg) {
            var existing = document.getElementById('cpLettureModal'); if (existing) existing.remove();
            var letture = msg.letture || [];
            var modal = document.createElement('div');
            modal.id = 'cpLettureModal';
            modal.style.cssText = 'position:fixed;top:0;left:0;width:100vw;height:100vh;background:rgba(0,0,0,0.4);z-index:999999;display:flex;align-items:center;justify-content:center;';
            var html = '<div style="background:var(--surface,#fff);border-radius:12px;padding:18px;min-width:260px;max-width:340px;box-shadow:0 8px 32px rgba(0,0,0,0.2);">';
            html += '<div style="font-weight:700;margin-bottom:12px;font-size:15px;">Letto da (' + letture.length + ')</div>';
            if (letture.length === 0) {
                html += '<div style="color:#888;font-size:13px;padding:8px 0;">Nessuna lettura ancora</div>';
            } else {
                html += '<div style="max-height:300px;overflow-y:auto;">';
                letture.forEach(function(l) {
                    html += '<div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid #eee;font-size:13px;">';
                    html += '<span>' + cpEsc(l.nome) + '</span>';
                    html += '<span style="color:#888;">' + cpEsc(l.letto_at) + '</span>';
                    html += '</div>';
                });
                html += '</div>';
            }
            html += '<button style="margin-top:14px;padding:8px 16px;background:var(--accent,#2563eb);color:#fff;border:none;border-radius:6px;cursor:pointer;width:100%;font-weight:600;" onclick="document.getElementById(\'cpLettureModal\').remove()">Chiudi</button>';
            html += '</div>';
            modal.innerHTML = html;
            modal.addEventListener('click', function(e) { if (e.target === modal) modal.remove(); });
            document.body.appendChild(modal);
        }

        function cpMostraMenuElimina(e) {
            e.stopPropagation();
            var trigger = e.currentTarget;
            var msgId = trigger.dataset.msgid;
            var canAll = trigger.dataset.canall === '1';
            var existing = document.getElementById('cpDelMenu'); if (existing) existing.remove();
            var menu = document.createElement('div');
            menu.id = 'cpDelMenu';
            menu.style.cssText = 'position:fixed;background:var(--surface,#fff);border:1px solid var(--border,#ddd);border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);padding:4px 0;z-index:999999;min-width:160px;';
            var rect = trigger.getBoundingClientRect();
            // Posiziona menu SOPRA il trigger (~90px altezza menu)
            var menuH = 90;
            var top = rect.top - menuH - 4;
            if (top < 8) top = rect.bottom + 4; // se non c'e' spazio sopra, va sotto
            var left = rect.left - 130;
            if (left < 8) left = 8;
            if (left + 170 > window.innerWidth) left = window.innerWidth - 178;
            menu.style.top = top + 'px';
            menu.style.left = left + 'px';
            var html = '<div class="cp-del-opt" data-action="info" style="padding:8px 14px;cursor:pointer;font-size:13px;">ⓘ Info / Letto da</div>';
            html += '<div class="cp-del-opt" data-action="pin" style="padding:8px 14px;cursor:pointer;font-size:13px;border-top:1px solid #eee;">📌 Fissa / Rimuovi pin</div>';
            html += '<div class="cp-del-opt" data-action="del-me" style="padding:8px 14px;cursor:pointer;font-size:13px;border-top:1px solid #eee;">Elimina per me</div>';
            if (canAll) html += '<div class="cp-del-opt" data-action="del-all" style="padding:8px 14px;cursor:pointer;font-size:13px;color:#dc3545;">Elimina per tutti</div>';
            menu.innerHTML = html;
            document.body.appendChild(menu);
            menu.querySelectorAll('.cp-del-opt').forEach(function(opt) {
                opt.addEventListener('click', function() {
                    var action = opt.dataset.action;
                    if (action === 'info') {
                        cpInfoMessaggio(msgId);
                    } else if (action === 'pin') {
                        cpTogglePin(msgId);
                    } else if (action === 'del-me') {
                        cpEliminaMessaggio(msgId, 'me');
                    } else if (action === 'del-all') {
                        cpEliminaMessaggio(msgId, 'all');
                    }
                    menu.remove();
                });
            });
            setTimeout(function() {
                document.addEventListener('click', function chiudi() {
                    var m = document.getElementById('cpDelMenu'); if (m) m.remove();
                    document.removeEventListener('click', chiudi);
                }, { once: true });
            }, 50);
        }

        // ============ ALLEGATI ============
        // Menu graffetta: Documento / Foto / Scatta foto / Altro
        window.cpMostraMenuAllegato = function(e) {
            e.stopPropagation();
            var existing = document.getElementById('cpAllegMenu'); if (existing) { existing.remove(); return; }
            var btn = document.getElementById('cpAttachBtn');
            var rect = btn.getBoundingClientRect();
            var menu = document.createElement('div');
            menu.id = 'cpAllegMenu';
            menu.style.cssText = 'position:fixed;background:var(--surface,#fff);border:1px solid var(--border,#ddd);border-radius:10px;box-shadow:0 4px 16px rgba(0,0,0,0.2);padding:6px 0;z-index:999999;min-width:200px;';
            menu.style.bottom = (window.innerHeight - rect.top + 8) + 'px';
            menu.style.left = Math.max(8, rect.left - 50) + 'px';
            var opts = [
                {ic:'📄', label:'Documento', accept:'application/pdf,.doc,.docx,.xls,.xlsx,.txt', cap:''},
                {ic:'🖼️', label:'Foto / Galleria', accept:'image/*', cap:''},
                {ic:'📷', label:'Scatta foto', accept:'image/*', cap:'environment'},
                {ic:'📎', label:'Altro', accept:'*/*', cap:''},
            ];
            menu.innerHTML = opts.map(function(o, i) {
                return '<div class="cp-alleg-opt" data-i="' + i + '" style="padding:10px 16px;cursor:pointer;font-size:14px;display:flex;gap:10px;align-items:center;">'
                     + '<span style="font-size:18px;">' + o.ic + '</span>'
                     + '<span>' + o.label + '</span></div>';
            }).join('');
            document.body.appendChild(menu);
            menu.querySelectorAll('.cp-alleg-opt').forEach(function(el) {
                el.addEventListener('mouseenter', function() { el.style.background = 'rgba(0,0,0,0.05)'; });
                el.addEventListener('mouseleave', function() { el.style.background = 'transparent'; });
                el.addEventListener('click', function() {
                    var o = opts[parseInt(el.dataset.i)];
                    var input = document.getElementById('cpFileInput');
                    input.setAttribute('accept', o.accept);
                    if (o.cap) input.setAttribute('capture', o.cap);
                    else input.removeAttribute('capture');
                    menu.remove();
                    input.click();
                });
            });
            setTimeout(function() {
                document.addEventListener('click', function chiudi() {
                    var m = document.getElementById('cpAllegMenu'); if (m) m.remove();
                    document.removeEventListener('click', chiudi);
                }, { once: true });
            }, 50);
        };

        window.cpFileSelezionato = function(input) {
            var file = input.files && input.files[0];
            if (!file) return;
            if (file.size > 10 * 1024 * 1024) {
                alert('File troppo grande (max 10MB)');
                input.value = '';
                return;
            }
            // Determina canale da mention nel testo del campo input
            var testo = document.getElementById('cpInput').value.trim();
            var canaleInvio = cpCanale;
            var testoLower = testo.toLowerCase();
            cpCanali.forEach(function(c) {
                if (c.toLowerCase() === 'tutti') return;
                if (testoLower.indexOf('@' + c.toLowerCase()) !== -1) canaleInvio = c;
            });

            var form = new FormData();
            form.append('file', file);
            form.append('canale', canaleInvio);
            if (testo) form.append('messaggio', testo);

            fetch('/csrf-refresh').then(function(r){return r.json();}).then(function(d){
                if (d && d.token) document.querySelector('meta[name="csrf-token"]').setAttribute('content', d.token);
            }).catch(function(){}).finally(function() {
                fetch('/chat/allega', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                    body: form
                }).then(function(r) { return r.json(); })
                  .then(function(data) {
                      if (data && data.ok && data.id) {
                          cpUltimoId = Math.max(cpUltimoId, data.id);
                          document.getElementById('cpInput').value = '';
                          if (canaleInvio === cpCanale) {
                              var container = document.getElementById('cpMsgs');
                              cpAppend({
                                  id: data.id,
                                  messaggio: data.messaggio,
                                  attachment_url: data.attachment_url,
                                  attachment_name: data.attachment_name,
                                  attachment_mime: data.attachment_mime,
                                  utente: cpOperatoreNome,
                                  timestamp: data.timestamp,
                                  mio: true,
                                  autore_id: cpOperatoreId,
                                  eta_min: 0,
                                  letture_count: 0,
                                  destinatari_count: 0,
                                  letture: []
                              }, container);
                              container.scrollTop = container.scrollHeight;
                          } else if (window.MES && MES.toast) {
                              MES.toast('Allegato inviato a @' + canaleInvio, 'success', 2500);
                          }
                      } else if (window.MES && MES.toast) {
                          MES.toast(data && data.errore || 'Errore allegato', 'error');
                      }
                  }).catch(function() {
                      if (window.MES && MES.toast) MES.toast('Errore upload', 'error');
                  });
            });
            input.value = '';
        };

        // ============ PIN MESSAGGIO ============
        function cpTogglePin(msgId) {
            fetch('/csrf-refresh').then(function(r){return r.json();}).then(function(d){
                if (d && d.token) document.querySelector('meta[name="csrf-token"]').setAttribute('content', d.token);
            }).catch(function(){}).finally(function() {
                fetch('/chat/messaggi/' + msgId + '/pin', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' }
                }).then(function(r) { return r.json(); }).then(function(data) {
                    if (data && data.ok && window.MES && MES.toast) {
                        MES.toast(data.is_pinned ? '📌 Messaggio fissato' : 'Pin rimosso', 'success', 2000);
                    }
                });
            });
        }

        // ============ AUDIO VOCALE ============
        var cpRecorder = null;
        var cpRecorderChunks = [];
        var cpRecordingStart = 0;

        window.cpToggleRecord = function() {
            var btn = document.getElementById('cpMicBtn');
            if (cpRecorder && cpRecorder.state === 'recording') {
                cpRecorder.stop();
                btn.style.color = 'var(--accent,#2563eb)';
                btn.title = 'Vocale';
                return;
            }
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Microfono non supportato in questo browser');
                return;
            }
            navigator.mediaDevices.getUserMedia({ audio: true }).then(function(stream) {
                cpRecorderChunks = [];
                var mime = MediaRecorder.isTypeSupported('audio/webm') ? 'audio/webm' : '';
                cpRecorder = new MediaRecorder(stream, mime ? { mimeType: mime } : undefined);
                cpRecorder.ondataavailable = function(e) { if (e.data.size > 0) cpRecorderChunks.push(e.data); };
                cpRecorder.onstop = function() {
                    stream.getTracks().forEach(function(t) { t.stop(); });
                    var durata = Math.max(1, Math.round((Date.now() - cpRecordingStart) / 1000));
                    var blob = new Blob(cpRecorderChunks, { type: 'audio/webm' });
                    cpInviaAudio(blob, durata);
                };
                cpRecorder.start();
                cpRecordingStart = Date.now();
                btn.style.color = '#dc3545';
                btn.title = 'Stop registrazione';
                if (window.MES && MES.toast) MES.toast('Registrazione... tocca di nuovo per inviare', 'info', 1500);
            }).catch(function() {
                alert('Permesso microfono negato');
            });
        };

        function cpInviaAudio(blob, durata) {
            // Determina canale da mention nel placeholder testo? Default cpCanale.
            // Per vocali usiamo cpCanale corrente.
            var canaleInvio = cpCanale;
            var form = new FormData();
            form.append('audio', blob, 'vocale.webm');
            form.append('canale', canaleInvio);
            form.append('durata', durata);

            fetch('/csrf-refresh').then(function(r){return r.json();}).then(function(d){
                if (d && d.token) document.querySelector('meta[name="csrf-token"]').setAttribute('content', d.token);
            }).catch(function(){}).finally(function() {
                fetch('/chat/audio', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                    body: form
                }).then(function(r) { return r.json(); })
                  .then(function(data) {
                      if (data && data.ok && data.id) {
                          cpUltimoId = Math.max(cpUltimoId, data.id);
                          var container = document.getElementById('cpMsgs');
                          cpAppend({
                              id: data.id,
                              messaggio: '[Vocale]',
                              audio_url: data.audio_url,
                              audio_durata_sec: data.durata,
                              utente: cpOperatoreNome,
                              timestamp: data.timestamp,
                              mio: true,
                              autore_id: cpOperatoreId,
                              eta_min: 0,
                              eliminato: false,
                              letture_count: 0,
                              destinatari_count: 0,
                              letture: []
                          }, container);
                          container.scrollTop = container.scrollHeight;
                      } else if (window.MES && MES.toast) {
                          MES.toast('Errore invio vocale', 'error');
                      }
                  }).catch(function() {
                      if (window.MES && MES.toast) MES.toast('Errore invio vocale', 'error');
                  });
            });
        }

        function cpInfoMessaggio(msgId) {
            // Recupera dati messaggio dal poll piu' recente (refresha canale)
            fetch('/chat/messaggi?canale=' + cpCanale + '&after=0')
                .then(function(r) { return r.json(); })
                .then(function(msgs) {
                    var m = msgs.find(function(x) { return x.id == msgId; });
                    if (m) cpMostraDettaglioLetture(m);
                }).catch(function() {});
        }

        // Long-press handler su messaggio (touch + mouse). Apre menu come click su ⋮.
        function cpAttachLongPress(div, msg) {
            if (!msg.id || msg.eliminato) return;
            var timer = null;
            var fired = false;
            var trigger = div.querySelector('.cp-del-trigger');
            var openMenu = function(e) {
                if (!trigger) return;
                fired = true;
                // Simula click sul trigger ⋮
                var ev = new MouseEvent('click', { bubbles: true, cancelable: true });
                trigger.dispatchEvent(ev);
            };
            var start = function(e) {
                fired = false;
                timer = setTimeout(function() { openMenu(e); }, 500);
            };
            var cancel = function() {
                if (timer) { clearTimeout(timer); timer = null; }
            };
            div.addEventListener('mousedown', start);
            div.addEventListener('mouseup', cancel);
            div.addEventListener('mouseleave', cancel);
            div.addEventListener('touchstart', start, { passive: true });
            div.addEventListener('touchend', cancel);
            div.addEventListener('touchmove', cancel);
            div.addEventListener('contextmenu', function(e) {
                e.preventDefault();
                openMenu(e);
            });
        }

        function cpEliminaMessaggio(id, scope) {
            fetch('/csrf-refresh').then(function(r){return r.json();}).then(function(d){
                if (d && d.token) document.querySelector('meta[name="csrf-token"]').setAttribute('content', d.token);
            }).catch(function(){}).finally(function() {
                fetch('/chat/messaggi/' + id + '/elimina', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                    body: JSON.stringify({ scope: scope })
                }).then(function(r) { return r.json(); }).then(function(data) {
                    if (data && data.ok) {
                        var el = document.querySelector('.cp-msg[data-msg-id="' + id + '"]');
                        if (el) el.remove();
                    } else if (window.MES && MES.toast) {
                        MES.toast(data && data.errore || 'Errore eliminazione', 'error');
                    }
                }).catch(function() {});
            });
        }

        function cpEsc(t) {
            var d = document.createElement('div');
            d.textContent = t || '';
            return d.innerHTML;
        }

        window.cpInvia = function() {
            var input = document.getElementById('cpInput');
            var testo = input.value.trim();
            if (!testo) return;
            input.value = '';
            input.focus();

            // Parse mention @Canale: match longest contro lista canali noti
            // (gestisce nomi con spazi tipo "Stampa a Caldo").
            var canaleInvio = cpCanale;
            var testoLower = testo.toLowerCase();
            var bestMatch = null;
            cpCanali.forEach(function(c) {
                if (c.toLowerCase() === 'tutti') return;
                var needle = '@' + c.toLowerCase();
                if (testoLower.indexOf(needle) !== -1) {
                    if (!bestMatch || c.length > bestMatch.length) bestMatch = c;
                }
            });
            if (bestMatch) canaleInvio = bestMatch;

            // Append locale ottimistico. Marca temp_id per matching post-server.
            var tempEl = null;
            if (canaleInvio === cpCanale) {
                var container = document.getElementById('cpMsgs');
                cpAppend({
                    messaggio: testo,
                    utente: cpOperatoreNome,
                    timestamp: new Date().toLocaleTimeString('it-IT', {hour:'2-digit', minute:'2-digit'}),
                    mio: true,
                    autore_id: cpOperatoreId
                }, container);
                tempEl = container.lastElementChild;
                if (tempEl) tempEl.dataset.tempPending = '1';
                container.scrollTop = container.scrollHeight;
            } else if (window.MES && typeof MES.toast === 'function') {
                MES.toast('Inviato a @' + canaleInvio, 'success', 2500);
            }

            // Refresh CSRF token PRIMA del POST (evita 419 Sessione scaduta)
            fetch('/csrf-refresh').then(function(r) { return r.json(); }).then(function(d) {
                if (d && d.token) {
                    var meta = document.querySelector('meta[name="csrf-token"]');
                    if (meta) meta.setAttribute('content', d.token);
                }
            }).catch(function() {}).finally(function() {
                fetch('/chat/invia', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                    body: JSON.stringify({ messaggio: testo, canale: canaleInvio })
                }).then(function(r) { return r.json(); })
                  .then(function(data) {
                      if (data && data.ok) {
                          cpUltimoId = Math.max(cpUltimoId, data.id || cpUltimoId);
                          if (tempEl && data.id) {
                              // Rimuovi tempEl e ricrea con id + marker ⋮/✓ subito visibili
                              var container = document.getElementById('cpMsgs');
                              tempEl.remove();
                              cpAppend({
                                  id: data.id,
                                  messaggio: testo,
                                  utente: cpOperatoreNome,
                                  timestamp: data.timestamp || new Date().toLocaleTimeString('it-IT', {hour:'2-digit', minute:'2-digit'}),
                                  mio: true,
                                  autore_id: cpOperatoreId,
                                  eta_min: 0,
                                  eliminato: false,
                                  letture_count: 0,
                                  destinatari_count: 0,
                                  letture: []
                              }, container);
                              container.scrollTop = container.scrollHeight;
                          }
                      }
                  })
                  .catch(function(e) { console.error('Chat errore:', e); });
            });
        };

        function cpPoll() {
            // Refresh totale: ricarica ultimi 50 messaggi, aggiunge i nuovi,
            // aggiorna i tombstone (eliminato), niente duplicati.
            fetch('/chat/messaggi?canale=' + cpCanale + '&after=0')
                .then(function(r) { return r.json(); })
                .then(function(msgs) {
                    var container = document.getElementById('cpMsgs');
                    var vuota = container.querySelector('div[style*="text-align:center"]');
                    msgs.forEach(function(m) {
                        var existing = container.querySelector('.cp-msg[data-msg-id="' + m.id + '"]');
                        if (existing) {
                            // Update se cambiato: eliminato OR letture_count
                            var oldLC = parseInt(existing.dataset.lettureCount || '0');
                            var changed = (m.eliminato && !existing.classList.contains('eliminato'))
                                       || ((m.letture_count || 0) !== oldLC);
                            if (changed) {
                                existing.remove();
                                cpAppend(m, container);
                            }
                        } else {
                            if (vuota) { vuota.remove(); vuota = null; }
                            cpAppend(m, container);
                            container.scrollTop = container.scrollHeight;
                            if (!m.mio && m.autore_id !== cpOperatoreId) {
                                cpBeep();
                                if (!cpOpen) { cpUnread++; updateBadge(); }
                            }
                        }
                        if (m.id > cpUltimoId) cpUltimoId = m.id;
                    });
                })
                .catch(function() {});
        }

        function updateBadge() {
            var badge = document.getElementById('chatFabBadge');
            if (cpUnread > 0) {
                badge.textContent = cpUnread > 9 ? '9+' : cpUnread;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }

        // Polling globale per badge (anche quando popup chiuso)
        setInterval(function() {
            if (cpOpen) return; // Il poll attivo lo gestisce cpPoll
            fetch('/chat/messaggi?canale=' + cpCanale + '&after=' + cpUltimoId)
                .then(function(r) { return r.json(); })
                .then(function(msgs) {
                    msgs.forEach(function(m) {
                        if (m.id > cpUltimoId && m.operatore_id !== cpOperatoreId) {
                            cpUnread++;
                            cpUltimoId = m.id;
                        }
                    });
                    updateBadge();
                })
                .catch(function() {});
        }, 10000);

        // === MENZIONI @ ===
        var cpMentionList = [
            // Reparti
            {type: 'reparto', name: 'Stampa Offset', icon: '🏭'},
            {type: 'reparto', name: 'Stampa a Caldo', icon: '🔥'},
            {type: 'reparto', name: 'Fustella', icon: '✂️'},
            {type: 'reparto', name: 'Piegaincolla', icon: '📐'},
            {type: 'reparto', name: 'Legatoria', icon: '📚'},
            {type: 'reparto', name: 'Spedizione', icon: '🚚'},
            {type: 'reparto', name: 'Prestampa', icon: '🖥️'},
            {type: 'reparto', name: 'Plastificazione', icon: '✨'},
            {type: 'reparto', name: 'Finitura Digitale', icon: '🖨️'},
            {type: 'reparto', name: 'Tutti', icon: '📢'},
            // Operatori dal server
            @if(isset($operatori_chat))
            @foreach($operatori_chat as $op)
            {type: 'operatore', name: @json($op->nome . ' ' . $op->cognome), icon: '👤'},
            @endforeach
            @endif
        ];

        var cpMentionActive = false;
        var cpMentionQuery = '';
        var cpMentionStart = -1;
        var cpMentionSelected = 0;

        window.cpMentionVisible = function() { return cpMentionActive; };

        window.cpCheckMention = function(input) {
            var val = input.value;
            var pos = input.selectionStart;
            // Cerca l'ultima @ prima del cursore
            var lastAt = val.lastIndexOf('@', pos - 1);
            if (lastAt >= 0) {
                var afterAt = val.substring(lastAt + 1, pos);
                // Se c'è uno spazio dopo @, non è una menzione
                if (afterAt.indexOf(' ') === -1 || afterAt.length <= 20) {
                    cpMentionQuery = afterAt.toLowerCase();
                    cpMentionStart = lastAt;
                    var filtered = cpMentionList.filter(function(m) {
                        return m.name.toLowerCase().indexOf(cpMentionQuery) >= 0;
                    }).slice(0, 8);
                    if (filtered.length > 0 && cpMentionQuery.length >= 0) {
                        cpMentionActive = true;
                        cpMentionSelected = 0;
                        renderMentionDropdown(filtered);
                        return;
                    }
                }
            }
            hideMentionDropdown();
        };

        function renderMentionDropdown(items) {
            var dd = document.getElementById('cpMentionDropdown');
            dd.innerHTML = '';
            items.forEach(function(item, i) {
                var div = document.createElement('div');
                div.style.cssText = 'padding:8px 12px; cursor:pointer; font-size:13px; display:flex; align-items:center; gap:8px; border-bottom:1px solid var(--border-color,#eee);';
                if (i === cpMentionSelected) div.style.background = 'var(--accent,#2563eb)20';
                div.innerHTML = '<span>' + item.icon + '</span><span>' + cpEsc(item.name) + '</span><span style="font-size:10px;color:var(--text-secondary,#999);margin-left:auto;">' + item.type + '</span>';
                div.onclick = function() { selectMention(item); };
                dd.appendChild(div);
            });
            dd.style.display = 'block';
        }

        function hideMentionDropdown() {
            cpMentionActive = false;
            document.getElementById('cpMentionDropdown').style.display = 'none';
        }

        function selectMention(item) {
            var input = document.getElementById('cpInput');
            var val = input.value;
            input.value = val.substring(0, cpMentionStart) + '@' + item.name + ' ' + val.substring(input.selectionStart);
            input.focus();
            hideMentionDropdown();
        }

        // Keyboard navigation nel dropdown
        document.getElementById('cpInput').addEventListener('keydown', function(e) {
            if (!cpMentionActive) return;
            var dd = document.getElementById('cpMentionDropdown');
            var items = dd.children;
            if (e.key === 'ArrowDown') { e.preventDefault(); cpMentionSelected = Math.min(cpMentionSelected + 1, items.length - 1); cpCheckMention(this); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); cpMentionSelected = Math.max(cpMentionSelected - 1, 0); cpCheckMention(this); }
            else if (e.key === 'Enter' && items[cpMentionSelected]) {
                e.preventDefault();
                items[cpMentionSelected].click();
            }
            else if (e.key === 'Escape') { hideMentionDropdown(); }
        });
    })();
    </script>
    <script src="{{ asset('js/mes-ui.js') }}?v=1"></script>
    @yield('scripts')
</body>
</html>
