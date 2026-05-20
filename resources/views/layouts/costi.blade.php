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

/* Sidebar dark */
.gn-shell { display: flex; min-height: 100vh; }
.gn-sidebar { width: 240px; background: #0f1729; color: #d1d5db; flex-shrink: 0; padding: 18px 0; position: sticky; top: 0; height: 100vh; overflow-y: auto; }
.gn-sidebar-brand { display: flex; align-items: center; gap: 10px; padding: 4px 18px 22px 18px; border-bottom: 1px solid #1f2937; }
.gn-sidebar-brand img { height: 32px; width: auto; }
.gn-sidebar-brand .gn-brand-text { font-size: 11px; font-weight: 700; color: #94a3b8; letter-spacing: 1.5px; }
.gn-sidebar-section { padding: 14px 0 6px 18px; font-size: 10px; color: #6b7280; text-transform: uppercase; letter-spacing: 1px; }
.gn-sidebar a { display: flex; align-items: center; gap: 12px; padding: 9px 18px; color: #cbd5e1; text-decoration: none; font-size: 13px; transition: all .15s; }
.gn-sidebar a:hover { background: #1f2937; color: #fff; }
.gn-sidebar a.active { background: #1e3a8a; color: #fff; border-left: 3px solid #3b82f6; padding-left: 15px; }
.gn-sidebar a .gn-icon { width: 16px; opacity: 0.7; font-size: 14px; }
.gn-sidebar .gn-submenu { padding-left: 30px; font-size: 12.5px; }
.gn-sidebar .gn-submenu a { padding: 6px 18px; color: #94a3b8; }
.gn-sidebar .gn-submenu a.active { background: #1d4ed8; color: #fff; border-left: 2px solid #60a5fa; padding-left: 16px; }

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
            <img src="{{ asset('images/logo_graficanappa.png') }}" alt="Grafica Nappa">
            <span class="gn-brand-text">MES</span>
        </div>

        <a href="{{ route('owner.dashboard') }}?op_token={{ request('op_token') }}"><span class="gn-icon">⬛</span> Dashboard</a>
        <a href="{{ route('owner.dashboard') }}?op_token={{ request('op_token') }}"><span class="gn-icon">📋</span> Commesse</a>
        <a href="{{ route('owner.scheduling') }}?op_token={{ request('op_token') }}"><span class="gn-icon">📅</span> Pianificazione</a>
        <a href="{{ route('owner.repartiOverview') }}?op_token={{ request('op_token') }}"><span class="gn-icon">🏭</span> Produzione</a>
        <a href="{{ route('magazzino.dashboard') }}?op_token={{ request('op_token') }}"><span class="gn-icon">📦</span> Magazzino</a>

        <div class="gn-sidebar-section">Analytics</div>
        <a href="{{ route('owner.costi.analisi.index') }}?op_token={{ request('op_token') }}" class="{{ Route::is('owner.costi.analisi*') || Route::is('owner.analisi.custom*') ? 'active' : '' }}"><span class="gn-icon">💰</span> Costi & Analisi</a>
        <div class="gn-submenu">
            <a href="{{ route('owner.costi.analisi.index') }}?op_token={{ request('op_token') }}" class="{{ Route::is('owner.costi.analisi.index') || Route::is('owner.costi.analisi.show') ? 'active' : '' }}">Analisi Commesse</a>
            <a href="{{ route('owner.analisi.custom.index') }}?op_token={{ request('op_token') }}" class="{{ Route::is('owner.analisi.custom*') ? 'active' : '' }}">Analisi Custom</a>
        </div>

        <div class="gn-sidebar-section">Sistema</div>
        <a href="{{ route('owner.reportOre') }}?op_token={{ request('op_token') }}"><span class="gn-icon">📊</span> Report Ore</a>
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
</body>
</html>
