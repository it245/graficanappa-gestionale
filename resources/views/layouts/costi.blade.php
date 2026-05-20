<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'MES Grafica Nappa') · Costi & Analisi</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/costi-ui.css') }}?v={{ filemtime(public_path('css/costi-ui.css')) }}">
<style>
* { box-sizing: border-box; }
body { margin: 0; font-family: 'Inter', -apple-system, sans-serif; background: #f5f7fa; color: #1f2937; }

/* Sidebar dark (clone mes.blade.php) */
.gn-shell { display: flex; min-height: 100vh; }
.gn-sidebar { width: 240px; background: #0f1729; color: #d1d5db; flex-shrink: 0; padding: 18px 0 18px 0; position: sticky; top: 0; height: 100vh; overflow-y: auto; }
.gn-sidebar-brand { display: flex; align-items: center; gap: 12px; padding: 4px 18px 22px 18px; border-bottom: 1px solid #1f2937; }
.gn-sidebar-brand .gn-logo-box { width: 40px; height: 40px; background: #1e3a5f; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.gn-sidebar-brand .gn-logo-box img { max-width: 32px; max-height: 32px; filter: brightness(0) invert(1); }
.gn-sidebar-brand .gn-brand-text { display: flex; flex-direction: column; line-height: 1.1; }
.gn-sidebar-brand .gn-brand-text .t1 { font-size: 14px; font-weight: 700; color: #fff; }
.gn-sidebar-brand .gn-brand-text .t2 { font-size: 13px; font-weight: 600; color: #cbd5e1; }

.gn-sidebar-section { margin-top: 14px; }
.gn-sidebar-section-label {
    display: flex; align-items: center; justify-content: space-between;
    padding: 6px 18px; font-size: 10px; color: #6b7280;
    text-transform: uppercase; letter-spacing: 1.2px; font-weight: 700;
    cursor: pointer; user-select: none;
}
.gn-sidebar-section-label:hover { color: #9ca3af; }
.gn-sidebar-section-label .chev { transition: transform .15s; font-size: 9px; opacity: .7; }
.gn-sidebar-section.collapsed .chev { transform: rotate(-90deg); }
.gn-sidebar-section.collapsed .gn-sidebar-items { display: none; }

.gn-sidebar a, .gn-sidebar .gn-nav-item {
    display: flex; align-items: center; gap: 12px;
    padding: 9px 18px; color: #cbd5e1; text-decoration: none;
    font-size: 13px; transition: all .15s; border-left: 3px solid transparent;
}
.gn-sidebar a:hover { background: #1f2937; color: #fff; }
.gn-sidebar a.active { background: rgba(30, 58, 138, .35); color: #fff; border-left-color: #3b82f6; }
.gn-sidebar a svg { width: 18px; height: 18px; flex-shrink: 0; opacity: .85; }
.gn-sidebar a .gn-badge-side {
    margin-left: auto; background: #10b981; color: #fff;
    font-size: 10px; padding: 1px 7px; border-radius: 10px; font-weight: 700;
}
.gn-sidebar a .gn-badge-side.warn { background: #f59e0b; }
.gn-sidebar a .gn-badge-side.info { background: #3b82f6; }

.gn-sidebar .gn-submenu { padding-left: 28px; }
.gn-sidebar .gn-submenu a { padding: 6px 18px; font-size: 12.5px; color: #94a3b8; border-left: 2px solid transparent; }
.gn-sidebar .gn-submenu a.active { background: #1d4ed8; color: #fff; border-left-color: #60a5fa; }

.gn-main { flex: 1; min-width: 0; }
.gn-topbar { background: #fff; border-bottom: 1px solid var(--gn-border); padding: 12px 24px; display: flex; justify-content: flex-end; align-items: center; gap: 14px; }
.gn-user { display: flex; align-items: center; gap: 10px; }
.gn-avatar { width: 36px; height: 36px; border-radius: 50%; background: var(--gn-primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; }
.gn-user-name { font-size: 13px; font-weight: 600; color: var(--gn-text); line-height: 1.2; }
.gn-user-role { font-size: 11px; color: var(--gn-muted); }
</style>
@yield('head')
</head>
<body>
<div class="gn-shell">
    <aside class="gn-sidebar">
        <div class="gn-sidebar-brand">
            <div class="gn-logo-box"><img src="{{ asset('images/logo_graficanappa.png') }}" alt="GN"></div>
            <div class="gn-brand-text">
                <span class="t1">MES</span>
                <span class="t2">Grafica Nappa</span>
            </div>
        </div>

        {{-- PRODUZIONE --}}
        <div class="gn-sidebar-section" data-section="produzione">
            <div class="gn-sidebar-section-label" onclick="toggleSezione(this.parentElement)">
                <span>Produzione</span><span class="chev">▼</span>
            </div>
            <div class="gn-sidebar-items">
                <a href="{{ route('owner.dashboard') }}?op_token={{ request('op_token') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
                    Dashboard
                </a>
                <a href="{{ route('owner.repartiOverview') }}?op_token={{ request('op_token') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 20h20"/><rect x="4" y="8" width="4" height="12"/><rect x="10" y="4" width="4" height="16"/><rect x="16" y="11" width="4" height="9"/></svg>
                    Panoramica Reparti
                </a>
                <a href="{{ route('owner.scheduling') }}?op_token={{ request('op_token') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Scheduling
                </a>
                <a href="{{ route('owner.esterne') }}?op_token={{ request('op_token') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                    Lav. Esterne
                </a>
                <a href="{{ route('owner.fustelle') }}?op_token={{ request('op_token') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                    Fustelle
                </a>
                <a href="{{ route('magazzino.dashboard') }}?op_token={{ request('op_token') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                    Magazzino
                </a>
            </div>
        </div>

        {{-- ANALISI --}}
        <div class="gn-sidebar-section" data-section="analisi">
            <div class="gn-sidebar-section-label" onclick="toggleSezione(this.parentElement)">
                <span>Analisi</span><span class="chev">▼</span>
            </div>
            <div class="gn-sidebar-items">
                <a href="{{ route('owner.reportOre') }}?op_token={{ request('op_token') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Report Ore
                </a>
                <a href="{{ route('owner.costi.analisi.index') }}?op_token={{ request('op_token') }}" class="{{ Route::is('owner.costi.analisi*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                    Analisi Costi
                </a>
                <a href="{{ route('owner.analisi.custom.index') }}?op_token={{ request('op_token') }}" class="{{ Route::is('owner.analisi.custom*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>
                    Analisi Custom
                </a>
                <a href="{{ route('owner.costi.trend') }}?op_token={{ request('op_token') }}" class="{{ Route::is('owner.costi.trend*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                    Trend Mensile
                </a>
                <a href="{{ route('owner.analisi.custom.confrontaSelect') }}?op_token={{ request('op_token') }}" class="{{ Route::is('owner.analisi.custom.confronta*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 3h5v5"/><path d="M4 20L21 3"/><path d="M21 16v5h-5"/><path d="M15 15l6 6"/><path d="M4 4l5 5"/></svg>
                    Confronta Analisi
                </a>
                <a href="{{ route('owner.costi.anomalie') }}?op_token={{ request('op_token') }}" class="{{ Route::is('owner.costi.anomalie*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    Anomalie
                </a>
                <a href="{{ route('owner.fasiTerminate') }}?op_token={{ request('op_token') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    Fasi Terminate
                </a>
            </div>
        </div>

        {{-- CONFIGURAZIONE --}}
        <div class="gn-sidebar-section" data-section="config">
            <div class="gn-sidebar-section-label" onclick="toggleSezione(this.parentElement)">
                <span>Configurazione</span><span class="chev">▼</span>
            </div>
            <div class="gn-sidebar-items">
                <a href="{{ route('owner.costi.categorie.index') }}?op_token={{ request('op_token') }}" class="{{ Route::is('owner.costi.categorie*') ? 'active' : '' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                    Categorie Costi
                </a>
            </div>
        </div>
    </aside>

    <div class="gn-main">
        <div class="gn-topbar">
            <div class="gn-user">
                @php
                    $nomeUtente = session('admin_nome') ?? session('operatore_nome') ?? 'Owner';
                    $iniziali = strtoupper(substr($nomeUtente, 0, 2));
                @endphp
                <div class="gn-avatar">{{ $iniziali }}</div>
                <div>
                    <div class="gn-user-name">{{ $nomeUtente }}</div>
                    <div class="gn-user-role">Owner</div>
                </div>
            </div>
        </div>
        @yield('content')
    </div>
</div>
@yield('scripts')
<script>
function toggleSezione(el) {
    el.classList.toggle('collapsed');
    const key = 'gn-sidebar-' + (el.dataset.section || 'x');
    localStorage.setItem(key, el.classList.contains('collapsed') ? '1' : '0');
}
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.gn-sidebar-section[data-section]').forEach(s => {
        const key = 'gn-sidebar-' + s.dataset.section;
        if (localStorage.getItem(key) === '1') s.classList.add('collapsed');
    });
});
</script>
</body>
</html>
