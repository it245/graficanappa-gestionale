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

    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    {{-- Vendor CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
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
        padding: 10px 16px;
        border-radius: 6px;
        text-decoration: none;
        color: #cbd5e1;
        font-size: 13px;
        font-weight: 500;
        transition: background 0.15s, color 0.15s;
        cursor: pointer;
        border: none;
        background: none;
        width: 100%;
        text-align: left;
    }

    .mes-sidebar-item:hover {
        background: var(--sidebar-hover);
        color: #f1f5f9;
        text-decoration: none;
    }

    .mes-sidebar-item.active {
        background: rgba(37, 99, 235, 0.15);
        color: #2563eb;
        border-left: 3px solid #2563eb;
        padding-left: 13px;
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
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 24px;
        z-index: 999;
        transition: left 0.25s ease;
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
        font-size: 12px;
        font-weight: 500;
        color: var(--text-secondary);
        font-variant-numeric: tabular-nums;
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
        color: var(--text-secondary);
        text-decoration: none;
        padding: 4px 10px;
        border-radius: 4px;
        border: 1px solid var(--border-color);
        transition: background 0.15s, color 0.15s;
    }

    .mes-topbar-logout:hover {
        background: var(--danger);
        color: #fff;
        border-color: var(--danger);
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
        padding: 8px;
        margin-right: 8px;
        border-radius: 4px;
        color: var(--text-primary);
    }

    .mes-hamburger:hover {
        background: var(--border-color);
    }

    .mes-hamburger svg {
        width: 20px;
        height: 20px;
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

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
        <div class="chat-popup-input">
            <input type="text" id="cpInput" placeholder="Scrivi..." autocomplete="off" onkeydown="if(event.key==='Enter')cpInvia()">
            <button onclick="cpInvia()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </button>
        </div>
    </div>

    <script>
    (function() {
        var cpOpen = false;
        var cpCanale = 'generale';
        var cpUltimoId = 0;
        var cpOperatoreId = {{ $operatore->id ?? 0 }};
        var cpOperatoreNome = @json(($operatore->nome ?? '') . ' ' . ($operatore->cognome ?? ''));
        var cpCanali = ['generale', 'produzione', 'spedizione', 'urgenze'];
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
                if (!cpPollTimer) cpPollTimer = setInterval(cpPoll, 10000);
                setTimeout(function() { document.getElementById('cpInput').focus(); }, 100);
            }
        };

        function cpLoadCanali() {
            var html = '';
            cpCanali.forEach(function(c) {
                html += '<span class="cp-canale ' + (c === cpCanale ? 'active' : '') + '" onclick="cpCambiaCanale(\'' + c + '\')">#' + c + '</span>';
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
            var isMio = msg.operatore_id === cpOperatoreId || msg.mio;
            div.className = 'cp-msg ' + (isMio ? 'mio' : 'altro');
            var html = '';
            if (!isMio) html += '<div class="cp-utente">' + cpEsc(msg.utente || msg.operatore_nome || '') + '</div>';
            html += '<div>' + cpEsc(msg.messaggio) + '</div>';
            html += '<div class="cp-ora">' + cpEsc(msg.timestamp || '') + '</div>';
            div.innerHTML = html;
            container.appendChild(div);
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

            cpAppend({
                messaggio: testo,
                utente: cpOperatoreNome,
                timestamp: new Date().toLocaleTimeString('it-IT', {hour:'2-digit', minute:'2-digit'}),
                mio: true
            });
            var container = document.getElementById('cpMsgs');
            container.scrollTop = container.scrollHeight;

            fetch('/chat/invia', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                body: JSON.stringify({ messaggio: testo, canale: cpCanale })
            }).then(function(r) { return r.json(); })
              .then(function(data) { if (data.ok) cpUltimoId = Math.max(cpUltimoId, data.id || cpUltimoId); })
              .catch(function(e) { console.error('Chat errore:', e); });
        };

        function cpPoll() {
            fetch('/chat/messaggi?canale=' + cpCanale + '&after=' + cpUltimoId)
                .then(function(r) { return r.json(); })
                .then(function(msgs) {
                    var container = document.getElementById('cpMsgs');
                    var vuota = container.querySelector('div[style*="text-align:center"]');
                    msgs.forEach(function(m) {
                        if (m.id > cpUltimoId) {
                            if (!m.mio && m.operatore_id !== cpOperatoreId) {
                                if (vuota) { vuota.remove(); vuota = null; }
                                cpAppend(m, container);
                                container.scrollTop = container.scrollHeight;
                                if (!cpOpen) { cpUnread++; updateBadge(); }
                            }
                            cpUltimoId = m.id;
                        }
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
    })();
    </script>
    @yield('scripts')
</body>
</html>
