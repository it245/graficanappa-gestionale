@extends('layouts.app')

@section('content')
<div class="container-fluid px-3">
<style>
    /* Fiery dashboard — usa SOLO token globali da public/css/mes-tokens.css */
    body { background: var(--mes-bg-page) !important; color: var(--mes-text-primary); font-family: var(--mes-font-sans); }

    /* Header */
    .dash-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 20px 0 24px; border-bottom: 1px solid var(--mes-border); margin-bottom: 24px;
    }
    .dash-header .brand { display: flex; align-items: center; gap: 14px; }
    .dash-header .brand-icon {
        width: 42px; height: 42px; background: var(--mes-primary); border-radius: var(--mes-radius-md);
        display: flex; align-items: center; justify-content: center;
        font-size: 18px; font-weight: 800; color: #fff;
        box-shadow: var(--mes-shadow-md);
    }
    .dash-header .brand-name { font-size: 18px; font-weight: 700; color: var(--mes-text-primary); }
    .dash-header .brand-sub { font-size: 12px; color: var(--mes-text-secondary); }
    .dash-header .nav-links { display: flex; gap: 6px; }
    .dash-header .nav-links a {
        color: var(--mes-text-secondary); text-decoration: none; font-size: 12px; font-weight: 500;
        padding: 6px 14px; border: 1px solid var(--mes-border); border-radius: var(--mes-radius-sm);
        transition: all var(--mes-duration-base) var(--mes-ease-standard);
    }
    .dash-header .nav-links a:hover { border-color: var(--mes-primary); color: var(--mes-primary); }
    .dash-header .nav-links a.active { background: var(--mes-primary); color: #fff; border-color: var(--mes-primary); }

    /* Cards */
    .card-d {
        background: var(--mes-bg-card); border: 1px solid var(--mes-border); border-radius: var(--mes-radius-lg);
        padding: 20px; margin-bottom: 16px; box-shadow: var(--mes-shadow-md);
        transition: transform var(--mes-duration-base) var(--mes-ease-standard),
                    box-shadow var(--mes-duration-base) var(--mes-ease-standard),
                    border-color var(--mes-duration-base) var(--mes-ease-standard);
    }
    .card-d:hover { border-color: var(--mes-border-strong); box-shadow: var(--mes-shadow-lg); transform: translateY(-1px); }
    .card-label {
        font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 1.2px;
        color: var(--mes-text-secondary); margin-bottom: 12px;
    }

    /* Status badge */
    .status-badge {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 8px 20px; border-radius: var(--mes-radius-full); font-size: 13px; font-weight: 700;
    }
    .sb-stampa { background: var(--mes-success-soft); color: var(--mes-success-hover); border: 1px solid rgba(16,185,129,0.25); }
    .sb-idle { background: rgba(107,114,128,0.12); color: var(--mes-text-tertiary); border: 1px solid rgba(107,114,128,0.2); }
    .sb-errore { background: var(--mes-danger-soft); color: var(--mes-danger-hover); border: 1px solid rgba(239,68,68,0.25); }
    .sb-offline { background: var(--mes-danger-soft); color: var(--mes-danger-hover); border: 1px solid rgba(239,68,68,0.25); }
    .status-dot { width: 8px; height: 8px; border-radius: var(--mes-radius-full); }
    .dot-stampa { background: var(--mes-success); box-shadow: 0 0 8px var(--mes-success); animation: pulse 2s infinite; }
    .dot-idle { background: #6b7280; }
    .dot-errore { background: var(--mes-danger); box-shadow: 0 0 8px var(--mes-danger); animation: pulse 1s infinite; }
    .dot-offline { background: var(--mes-danger); }
    @keyframes pulse { 0%,100%{opacity:1}50%{opacity:0.3} }

    /* Warning */
    .warning-strip {
        background: var(--mes-warning-soft); color: var(--mes-warning-hover); border: 1px solid rgba(245,158,11,0.25);
        border-radius: var(--mes-radius-md); padding: 8px 14px; font-size: 12px; font-weight: 500; margin-top: 12px;
    }

    /* Progress */
    .prog-bar-wrap { margin-top: 16px; }
    .prog-bar { height: 28px; background: var(--mes-bg-hover); border-radius: 14px; overflow: hidden; position: relative; border: 1px solid var(--mes-border); }
    .prog-fill {
        height: 100%; border-radius: 14px;
        background: linear-gradient(90deg, var(--mes-primary), var(--mes-success));
        transition: width 0.8s ease; position: relative; overflow: hidden;
    }
    .prog-fill::after {
        content:''; position:absolute; top:0; left:-100%; width:200%; height:100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
        animation: shimmer 3s infinite;
    }
    @keyframes shimmer { 0%{transform:translateX(-50%)}100%{transform:translateX(50%)} }
    .prog-text {
        position:absolute; top:0; left:0; right:0; bottom:0;
        display:flex; align-items:center; justify-content:center;
        font-size:12px; font-weight:700; color:#fff; text-shadow:0 1px 2px rgba(0,0,0,0.3);
        font-family: var(--mes-font-mono); font-variant-numeric: tabular-nums;
    }
    .prog-stats { display:flex; justify-content:space-between; margin-top:8px; font-size:12px; color:var(--mes-text-secondary); }
    .prog-stats strong { color: var(--mes-text-primary); font-family: var(--mes-font-mono); font-variant-numeric: tabular-nums; }

    /* KPI row — premium */
    .kpi-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 16px; }
    .kpi {
        background: var(--mes-bg-hover); border: 1px solid var(--mes-border); border-radius: var(--mes-radius-lg);
        padding: 14px 10px; text-align: center;
        box-shadow: var(--mes-shadow-sm);
        transition: transform var(--mes-duration-base) var(--mes-ease-standard),
                    box-shadow var(--mes-duration-base) var(--mes-ease-standard),
                    border-color var(--mes-duration-base) var(--mes-ease-standard);
    }
    .kpi:hover { transform: translateY(-2px); box-shadow: var(--mes-shadow-md); border-color: var(--mes-border-strong); }
    .kpi-val {
        font-size: 22px; font-weight: 700; color: var(--mes-text-primary);
        font-family: var(--mes-font-mono); font-variant-numeric: tabular-nums;
        letter-spacing: -0.5px;
    }
    .kpi-label { font-size: 9px; font-weight: 600; text-transform: uppercase; color: var(--mes-text-secondary); letter-spacing: 0.8px; margin-top: 4px; }

    /* Toner bars */
    .toner-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); gap: 10px; }
    .toner-item { text-align: center; }
    .toner-label { font-size: 10px; font-weight: 600; color: var(--mes-text-secondary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 6px; }
    .toner-bar-v { width: 32px; height: 80px; background: var(--mes-bg-hover); border: 1px solid var(--mes-border); border-radius: 16px; margin: 0 auto; position: relative; overflow: hidden; }
    .toner-fill-v {
        position: absolute; bottom: 0; left: 0; right: 0; border-radius: 16px;
        transition: height 0.6s ease;
    }
    .toner-pct {
        font-size: 14px; font-weight: 700; margin-top: 6px;
        font-family: var(--mes-font-mono); font-variant-numeric: tabular-nums;
    }
    .toner-warn { animation: blink 1.5s infinite; }
    @keyframes blink { 0%,100%{opacity:1}50%{opacity:0.5} }

    /* Trays */
    .tray-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px; }
    .tray-item {
        background: var(--mes-bg-hover); border-radius: var(--mes-radius-md); padding: 10px 12px; text-align: center;
        border: 1px solid var(--mes-border);
    }
    .tray-item.low { border-color: rgba(239,68,68,0.4); background: rgba(239,68,68,0.06); }
    .tray-name { font-size: 10px; font-weight: 600; color: var(--mes-text-secondary); text-transform: uppercase; letter-spacing: 0.5px; }
    .tray-bar-h { height: 6px; background: var(--mes-border); border-radius: 3px; margin: 8px 0; overflow: hidden; }
    .tray-fill-h { height: 100%; border-radius: 3px; background: var(--mes-primary); transition: width 0.5s; }
    .tray-info { font-size: 12px; font-weight: 600; color: var(--mes-text-primary); font-family: var(--mes-font-mono); font-variant-numeric: tabular-nums; }
    .tray-type { font-size: 9px; color: var(--mes-text-tertiary); margin-top: 2px; }

    /* Finisher */
    .fin-row { display: flex; gap: 12px; }
    .fin-item {
        background: var(--mes-bg-hover); border-radius: var(--mes-radius-md); padding: 12px 20px; text-align: center;
        flex: 1; border: 1px solid var(--mes-border);
    }
    .fin-item.low { border-color: rgba(245,158,11,0.4); }
    .fin-name { font-size: 10px; font-weight: 600; color: var(--mes-text-secondary); text-transform: uppercase; letter-spacing: 0.5px; }
    .fin-pct { font-size: 20px; font-weight: 700; color: var(--mes-text-primary); margin-top: 4px; font-family: var(--mes-font-mono); font-variant-numeric: tabular-nums; }

    /* Alert strip */
    .alert-strip {
        border-radius: var(--mes-radius-md); padding: 10px 16px; font-size: 12px; font-weight: 500; margin-bottom: 16px;
    }
    .alert-strip.warn { background: var(--mes-warning-soft); color: var(--mes-warning-hover); border: 1px solid rgba(245,158,11,0.25); }
    .alert-strip.ok { background: var(--mes-success-soft); color: var(--mes-success-hover); border: 1px solid rgba(16,185,129,0.2); }

    /* Info fields */
    .info-lbl { font-size: 10px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--mes-text-secondary); }
    .info-val { font-size: 15px; font-weight: 700; color: var(--mes-text-primary); margin-top: 2px; }
    .info-val-sm { font-size: 13px; font-weight: 600; color: var(--mes-text-primary); }
    .info-val-dim { font-size: 12px; color: var(--mes-text-secondary); margin-top: 2px; }

    /* RIP chip */
    .rip-chip {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 11px; font-weight: 600; padding: 4px 12px; border-radius: var(--mes-radius-full);
    }
    .rip-active { background: var(--mes-primary-soft); color: var(--mes-primary); border: 1px solid rgba(59,130,246,0.25); }
    .rip-idle { background: var(--mes-bg-hover); color: var(--mes-text-secondary); border: 1px solid var(--mes-border); }

    /* Print doc */
    .print-doc { font-size: 16px; font-weight: 700; color: var(--mes-text-primary); word-break: break-all; line-height: 1.4; }
    .commessa-chip {
        display: inline-block; background: var(--mes-primary-soft); color: var(--mes-primary);
        font-size: 12px; font-weight: 600; padding: 3px 10px; border-radius: var(--mes-radius-sm);
        font-family: var(--mes-font-mono);
    }
    .no-job { color: var(--mes-text-tertiary); font-size: 14px; padding: 20px 0; }

    /* Fase pills — pill radius full, color-coded */
    .fasi-wrap { display: flex; gap: 4px; flex-wrap: wrap; }
    .fpill {
        display: inline-block; font-size: 10px; font-weight: 600;
        padding: 2px 8px; border-radius: var(--mes-radius-full); white-space: nowrap;
    }
    .fp-s0 { background: var(--mes-bg-hover); color: var(--mes-text-secondary); border: 1px solid var(--mes-border); }
    .fp-s1 { background: var(--mes-primary-soft); color: var(--mes-primary); }
    .fp-s2 { background: var(--mes-warning-soft); color: var(--mes-warning-hover); }
    .fp-s3 { background: var(--mes-success-soft); color: var(--mes-success-hover); }
    .fp-ext { background: rgba(139,92,246,0.12); color: var(--mes-external); }

    /* Queue cards */
    .q-cards { display: flex; flex-direction: column; gap: 10px; }
    .q-card {
        background: var(--mes-bg-hover); border: 1px solid var(--mes-border); border-radius: var(--mes-radius-md);
        padding: 14px 18px; display: grid;
        grid-template-columns: 1fr 180px 100px 90px;
        gap: 10px 16px; align-items: start;
        transition: border-color var(--mes-duration-base) var(--mes-ease-standard),
                    box-shadow var(--mes-duration-base) var(--mes-ease-standard);
    }
    .q-card:hover { border-color: var(--mes-primary); box-shadow: var(--mes-shadow-sm); }
    .q-title { font-size: 12px; font-weight: 600; color: var(--mes-text-primary); word-break: break-all; margin-bottom: 4px; }
    .q-desc { font-size: 11px; color: var(--mes-text-secondary); margin-bottom: 6px; line-height: 1.4; }
    .q-notes { font-size: 10px; color: var(--mes-warning-hover); background: var(--mes-warning-soft); border-radius: var(--mes-radius-sm); padding: 3px 8px; display: inline-block; margin-bottom: 6px; }
    .q-carta-lbl { font-size: 9px; font-weight: 600; color: var(--mes-text-tertiary); text-transform: uppercase; letter-spacing: 0.5px; }
    .q-carta-val { font-size: 12px; font-weight: 500; color: var(--mes-text-primary); margin-top: 2px; }
    .q-carta-sub { font-size: 10px; color: var(--mes-text-tertiary); }
    .q-qta { text-align: right; }
    .q-num { font-size: 16px; font-weight: 700; color: var(--mes-text-primary); font-family: var(--mes-font-mono); font-variant-numeric: tabular-nums; }
    .q-sub { font-size: 10px; color: var(--mes-text-tertiary); }
    .q-meta { text-align: right; }
    .q-date { font-size: 11px; color: var(--mes-text-secondary); font-weight: 500; font-family: var(--mes-font-mono); }
    .q-copies { font-size: 10px; color: var(--mes-text-tertiary); margin-top: 2px; font-family: var(--mes-font-mono); }

    /* Completed table — thead gradient */
    .c-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .c-table thead th {
        font-size: 9px; text-transform: uppercase; letter-spacing: 1px;
        color: #e5e7eb; font-weight: 600; padding: 10px;
        background: linear-gradient(180deg, #1f2937 0%, #111827 100%);
        text-align: left; white-space: nowrap;
    }
    .c-table thead th:first-child { border-top-left-radius: var(--mes-radius-md); }
    .c-table thead th:last-child { border-top-right-radius: var(--mes-radius-md); }
    .c-table tbody td {
        font-size: 12px; color: var(--mes-text-secondary); padding: 8px 10px;
        border-bottom: 1px solid var(--mes-border); vertical-align: middle;
    }
    .c-table tbody tr:hover { background: var(--mes-primary-soft); }
    .c-title { font-weight: 500; color: var(--mes-text-primary); max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .mini-bar { width: 50px; height: 4px; background: var(--mes-bg-hover); border: 1px solid var(--mes-border); border-radius: 2px; overflow: hidden; display: inline-block; vertical-align: middle; }
    .mini-bar .fill { height: 100%; background: var(--mes-success); border-radius: 2px; }
    .copies-sm { font-size: 10px; color: var(--mes-text-tertiary); margin-left: 4px; font-family: var(--mes-font-mono); font-variant-numeric: tabular-nums; }

    /* Section header */
    .sec-hdr {
        font-size: 13px; font-weight: 700; color: var(--mes-text-primary); margin-bottom: 14px;
        display: flex; align-items: center; gap: 8px;
    }
    .sec-badge {
        background: var(--mes-bg-hover); color: var(--mes-text-secondary); border: 1px solid var(--mes-border);
        font-size: 10px; font-weight: 600; padding: 2px 8px; border-radius: var(--mes-radius-full);
        font-family: var(--mes-font-mono);
    }

    .offline-box { text-align: center; padding: 60px 20px; }
    .offline-box h3 { color: var(--mes-danger); font-weight: 700; }
    .offline-box p { color: var(--mes-text-secondary); font-size: 14px; }

    .timestamp-sm { font-size: 10px; color: var(--mes-text-tertiary); font-family: var(--mes-font-mono); }

    /* Dark mode tweaks: completed table thead resta scuro (gradient già dark), assicura leggibilità extra */
    body.dark-mode .c-table tbody tr:hover { background: rgba(59,130,246,0.12); }
    body.dark-mode .commessa-chip { background: rgba(59,130,246,0.18); }
    body.dark-mode .prog-bar { background: #0f172a; }
    body.dark-mode .toner-bar-v { background: #0f172a; }
    body.dark-mode .tray-bar-h { background: #334155; }

    @media (max-width: 992px) {
        .q-card { grid-template-columns: 1fr; }
        .q-qta, .q-meta { text-align: left; }
    }
</style>

<div class="dash-header">
    <div class="brand">
        <div class="brand-icon">V9</div>
        <div>
            <div class="brand-name">Canon imagePRESS V900</div>
            <div class="brand-sub">Fiery P400 &middot; MES Dashboard</div>
        </div>
    </div>
    <div class="nav-links">
        <a href="{{ route('mes.fiery') }}" class="active">Dashboard</a>
        <a href="{{ route('mes.fiery.contatori') }}">Contatori</a>
        <a href="{{ route('mes.prinect') }}">Prinect XL106</a>
        <a href="{{ route('owner.dashboard') }}">Owner</a>
    </div>
</div>

{{-- Alert SNMP --}}
@if(!empty($snmp['alert']))
<div class="alert-strip warn" id="snmp-alert">&#9888; {{ $snmp['alert'] }}</div>
@else
<div class="alert-strip ok" id="snmp-alert">&#10003; Nessun avviso attivo sulla macchina</div>
@endif

@if($status)

{{-- === ROW 1: KPI + Toner + Vassoi affiancati === --}}
<div class="row" style="margin-bottom:16px;">
    {{-- KPI --}}
    <div class="col-lg-4">
        <div class="card-d" style="height:100%;">
            <div class="card-label">Statistiche</div>
            <div class="kpi-row" id="kpi-row" style="margin-bottom:0;">
                <div class="kpi"><div class="kpi-val" style="color:var(--mes-success);" id="stat-completed">{{ count($jobData['completed']) }}</div><div class="kpi-label">Completati</div></div>
                <div class="kpi"><div class="kpi-val" style="color:var(--mes-warning);" id="stat-queue">{{ count($jobData['queue']) }}</div><div class="kpi-label">In coda</div></div>
                <div class="kpi"><div class="kpi-val" id="stat-total">{{ $jobData['total'] }}</div><div class="kpi-label">Totale</div></div>
                @if(!empty($snmp) && !isset($snmp['errore']))
                <div class="kpi"><div class="kpi-val" id="kpi-totale">{{ number_format($snmp['totale_1'] ?? 0, 0, ',', '.') }}</div><div class="kpi-label">Click totali</div></div>
                <div class="kpi"><div class="kpi-val" style="color:var(--mes-primary);" id="kpi-colore">{{ number_format(($snmp['colore_grande'] ?? 0) + ($snmp['colore_piccolo'] ?? 0), 0, ',', '.') }}</div><div class="kpi-label">Colore</div></div>
                <div class="kpi"><div class="kpi-val" id="kpi-bn">{{ number_format(($snmp['nero_grande'] ?? 0) + ($snmp['nero_piccolo'] ?? 0), 0, ',', '.') }}</div><div class="kpi-label">B/N</div></div>
                @endif
            </div>
        </div>
    </div>
    {{-- Toner --}}
    <div class="col-lg-4">
        <div class="card-d" id="toner-card" style="height:100%;">
            <div class="card-label">Livelli Toner</div>
            @if(!empty($snmp['toner']))
            <div class="toner-row" id="toner-container">
                @foreach($snmp['toner'] as $t)
                @php
                    $tc = match($t['nome']) { 'Nero'=>'#374151','Cyan'=>'#06b6d4','Magenta'=>'#ec4899','Yellow'=>'#eab308','Waste Toner'=>'#78716c', default=>'#6b7280' };
                    $pct = $t['livello'];
                    $warn = $pct >= 0 && $pct <= 15;
                @endphp
                <div class="toner-item">
                    <div class="toner-label">{{ $t['nome'] }}</div>
                    <div class="toner-bar-v">
                        <div class="toner-fill-v {{ $warn ? 'toner-warn' : '' }}" style="height:{{ max($pct, 0) }}%; background:{{ $tc }};"></div>
                    </div>
                    <div class="toner-pct {{ $warn ? 'toner-warn' : '' }}" style="color:{{ $tc }}">{{ $pct >= 0 ? $pct.'%' : '?' }}</div>
                </div>
                @endforeach
            </div>
            @else
            <div style="color:var(--mes-text-tertiary);font-size:13px;">SNMP non disponibile</div>
            @endif
        </div>
    </div>
    {{-- Vassoi + Finisher --}}
    <div class="col-lg-4">
        <div class="card-d" id="supplies-card" style="height:100%;">
            @if(!empty($snmp['vassoi']))
            <div class="card-label">Vassoi Carta</div>
            <div class="tray-row" id="tray-container">
                @foreach($snmp['vassoi'] as $v)
                @php
                    $pct = $v['percentuale'];
                    $low = $pct !== null && $pct >= 0 && $pct <= 20;
                @endphp
                <div class="tray-item {{ $low ? 'low' : '' }}">
                    <div class="tray-name">{{ $v['nome'] ?: 'Vassoio '.($loop->index+1) }}</div>
                    <div class="tray-bar-h"><div class="tray-fill-h" style="width:{{ $pct !== null && $pct >= 0 ? $pct : 0 }}%; {{ $low ? 'background:var(--mes-danger);' : '' }}"></div></div>
                    <div class="tray-info">
                        @if($pct === -1) Presente
                        @elseif($pct !== null) {{ $pct }}%
                        @else -
                        @endif
                    </div>
                    @if($v['tipo'])<div class="tray-type">{{ $v['tipo'] }}</div>@endif
                </div>
                @endforeach
            </div>
            @endif

            @if(!empty($snmp['punti']))
            <div class="card-label" style="margin-top:14px;">Finisher</div>
            <div class="fin-row" id="fin-container">
                @foreach($snmp['punti'] as $p)
                @php $low = $p['livello'] >= 0 && $p['livello'] <= 20; @endphp
                <div class="fin-item {{ $low ? 'low' : '' }}">
                    <div class="fin-name">{{ $p['nome'] }}</div>
                    <div class="fin-pct" style="{{ $low ? 'color:var(--mes-danger);' : '' }}">{{ $p['livello'] >= 0 ? $p['livello'].'%' : '?' }}</div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>

{{-- === ROW 2: Stato + Job in stampa | Operatore === --}}
<div class="row">
    <div class="col-lg-8">
        {{-- Stato macchina --}}
        <div class="card-d">
            <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
                <div id="stato-container">
                    <span class="status-badge sb-{{ $status['stato'] }}">
                        <span class="status-dot dot-{{ $status['stato'] }}"></span>
                        {{ ucfirst($status['stato']) }}
                    </span>
                </div>
                <div id="rip-container">
                    @if(!$status['rip']['idle'] && $status['rip']['documento'])
                    <span class="rip-chip rip-active">&#9654; RIP: {{ $status['rip']['documento'] }}</span>
                    @else
                    <span class="rip-chip rip-idle">RIP idle</span>
                    @endif
                </div>
                <div class="timestamp-sm" id="ultimo-aggiornamento">{{ $status['ultimo_aggiornamento'] }}</div>
            </div>
            @if($status['avviso'])
            <div class="warning-strip" id="avviso-box">{{ $status['avviso'] }}</div>
            @endif
        </div>

        {{-- Job in stampa --}}
        <div class="card-d" id="print-card">
            <div class="card-label">Job in stampa</div>
            <div id="stampa-container">
            @if($status['stampa']['documento'])
                <div class="print-doc" id="print-doc">{{ $status['stampa']['documento'] }}</div>
                @if(!empty($status['commessa']))
                <div class="mt-2" id="commessa-inline">
                    <span class="commessa-chip">{{ $status['commessa']['commessa'] }}</span>
                    <span style="color:var(--mes-text-secondary);margin-left:8px;font-size:13px;">{{ $status['commessa']['cliente'] }}</span>
                </div>
                @else
                <div class="mt-2" id="commessa-inline"></div>
                @endif

                <div class="prog-bar-wrap">
                    <div class="prog-bar">
                        <div class="prog-fill" id="progress-fill" style="width:{{ $status['stampa']['progresso'] }}%"></div>
                        <div class="prog-text" id="progress-text">{{ $status['stampa']['progresso'] }}%</div>
                    </div>
                    <div class="prog-stats">
                        <span id="copies-info">Copie: <strong>{{ $status['stampa']['copie_fatte'] }}</strong> / {{ $status['stampa']['copie_totali'] }}</span>
                        <span id="pages-info">Pagine: {{ $status['stampa']['pagine'] }}</span>
                        <span>Utente: {{ $status['stampa']['utente'] }}</span>
                    </div>
                </div>
                @if(!empty($jobData['commessa_sheets']) && $jobData['commessa_sheets']['fogli_totali'] > 0)
                <div id="commessa-sheets-info" style="margin-top:10px; padding:8px 14px; background:#dbeafe; border:1px solid #93c5fd; border-radius:8px; font-size:12px; color:var(--mes-text-secondary);">
                    Fogli totali commessa: <strong style="color:var(--mes-primary);">{{ $jobData['commessa_sheets']['fogli_totali'] }}</strong>
                    <span style="margin-left:8px;">{{ $jobData['commessa_sheets']['copie_totali'] }} copie</span>
                    <span style="margin-left:8px; color:var(--mes-text-tertiary);">{{ $jobData['commessa_sheets']['run_count'] }} run</span>
                </div>
                @endif
            @else
                <div class="no-job" id="no-print">Nessun job in stampa</div>
            @endif
            </div>
        </div>
    </div>

    {{-- Operatore --}}
    <div class="col-lg-4">
        <div class="card-d">
            <div class="card-label">Operatore assegnato</div>
            <div style="font-size:18px;font-weight:800;color:var(--mes-text-primary);" id="operatore-nome">{{ config('fiery.operatore') }}</div>
            <div id="commessa-detail" class="mt-3">
                @if(!empty($status['commessa']))
                <div class="mb-2"><div class="info-lbl">Commessa</div><div class="info-val">{{ $status['commessa']['commessa'] }}</div></div>
                <div class="mb-2"><div class="info-lbl">Cliente</div><div class="info-val-sm">{{ $status['commessa']['cliente'] }}</div></div>
                <div class="mb-2"><div class="info-lbl">Descrizione</div><div class="info-val-dim">{{ \Illuminate\Support\Str::limit($status['commessa']['descrizione'] ?? '', 80) }}</div></div>
                @endif
            </div>
            @php $printMes = $jobData['printing']['mes'] ?? null; @endphp
            @if($printMes)
            <div class="mt-3" style="border-top:1px solid var(--mes-border); padding-top:12px;">
                @if($printMes['carta'])
                <div class="mb-2"><div class="info-lbl">Carta</div><div class="info-val-sm">{{ $printMes['carta'] }}</div>
                @if($printMes['cod_carta'])<div class="info-val-dim">{{ $printMes['cod_carta'] }}</div>@endif</div>
                @endif
                <div class="mb-2"><div class="info-lbl">Quantita</div>
                    <div class="info-val-sm">{{ number_format($printMes['qta_richiesta'] ?? 0, 0, ',', '.') }} pz
                    @if($printMes['qta_carta']) <span style="color:var(--mes-text-secondary);font-size:11px;"> / {{ number_format($printMes['qta_carta'], 0, ',', '.') }} fg</span>@endif</div>
                </div>
                @if($printMes['data_prevista'])<div class="mb-2"><div class="info-lbl">Consegna prevista</div><div class="info-val-sm">{{ $printMes['data_prevista'] }}</div></div>@endif
                @if($printMes['responsabile'])<div class="mb-2"><div class="info-lbl">Responsabile</div><div class="info-val-sm">{{ $printMes['responsabile'] }}</div></div>@endif
                @if($printMes['note_prestampa'])<div class="mb-2"><div class="info-lbl">Note prestampa</div><div class="info-val-dim">{{ $printMes['note_prestampa'] }}</div></div>@endif
                @if(!empty($printMes['fasi']))
                <div class="mb-2"><div class="info-lbl">Fasi</div><div class="fasi-wrap">
                    @foreach($printMes['fasi'] as $f)<span class="fpill {{ $f['esterno'] ? 'fp-ext' : 'fp-s'.$f['stato'] }}">{{ $f['fase'] }}</span>@endforeach
                </div></div>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ===== CODA DI STAMPA ===== --}}
@if(!empty($jobData['queue']))
<div class="card-d">
    <div class="sec-hdr">Coda di stampa <span class="sec-badge">{{ count($jobData['queue']) }}</span></div>
    <div class="q-cards" id="queue-container">
        @foreach($jobData['queue'] as $job)
        <div class="q-card">
            <div>
                <div class="q-title">{{ $job['title'] }}</div>
                @if($job['mes'])
                <div style="margin-bottom:6px;">
                    <span class="commessa-chip">{{ $job['mes']['commessa'] }}</span>
                    <span style="font-size:10px;color:var(--mes-text-tertiary);margin-left:6px;">{{ $job['mes']['cliente'] }}</span>
                </div>
                <div class="q-desc">{{ \Illuminate\Support\Str::limit($job['mes']['descrizione'] ?? '', 100) }}</div>
                @if(!empty($job['mes']['note_prestampa']))
                <div class="q-notes">{{ $job['mes']['note_prestampa'] }}</div>
                @endif
                @if(!empty($job['mes']['fasi']))
                <div class="fasi-wrap">
                    @foreach($job['mes']['fasi'] as $f)
                    <span class="fpill {{ $f['esterno'] ? 'fp-ext' : 'fp-s'.$f['stato'] }}">{{ $f['fase'] }}</span>
                    @endforeach
                </div>
                @endif
                @endif
            </div>
            <div>
                @if($job['mes'] && $job['mes']['carta'])
                <div class="q-carta-lbl">Carta</div>
                <div class="q-carta-val">{{ $job['mes']['carta'] }}</div>
                <div class="q-carta-sub">{{ $job['mes']['cod_carta'] ?? '' }}</div>
                @endif
            </div>
            <div class="q-qta">
                @if($job['mes'])
                <div class="q-num">{{ number_format($job['mes']['qta_richiesta'] ?? 0, 0, ',', '.') }}</div>
                <div class="q-sub">pezzi</div>
                @if($job['mes']['qta_carta'])<div class="q-sub">{{ number_format($job['mes']['qta_carta'], 0, ',', '.') }} fg</div>@endif
                @endif
            </div>
            <div class="q-meta">
                <div class="q-date">{{ $job['mes']['data_prevista'] ?? '-' }}</div>
                <div class="q-copies">{{ $job['num_copies'] ?: '-' }} copie</div>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- ===== COMPLETATI ===== --}}
@if(!empty($jobData['completed']))
<div class="card-d">
    <div class="sec-hdr">Completati di recente <span class="sec-badge">{{ count($jobData['completed']) }}</span></div>
    <div style="overflow-x:auto;">
    <table class="c-table">
        <thead>
            <tr>
                <th>Job</th>
                <th>Commessa</th>
                <th>Carta</th>
                <th>Copie</th>
                <th>Fogli</th>
                <th>B/V</th>
                <th>Data</th>
            </tr>
        </thead>
        <tbody id="completed-body">
            @foreach($jobData['completed'] as $job)
            @php $pct = $job['num_copies'] > 0 ? round(($job['copies_printed'] / $job['num_copies']) * 100) : 100; @endphp
            <tr>
                <td class="c-title">{{ $job['title'] }}</td>
                <td>
                    @if($job['mes'])
                    <span class="commessa-chip">{{ $job['mes']['commessa'] }}</span>
                    <span style="font-size:10px;color:var(--mes-text-tertiary);margin-left:4px;">{{ $job['mes']['cliente'] }}</span>
                    @endif
                </td>
                <td style="font-size:11px;">{{ $job['mes']['carta'] ?? '' }}</td>
                <td>
                    <div class="mini-bar"><div class="fill" style="width:{{ $pct }}%"></div></div>
                    <span class="copies-sm">{{ $job['copies_printed'] }}/{{ $job['num_copies'] }}</span>
                </td>
                <td style="font-weight:600;color:var(--mes-text-primary);">{{ $job['total_sheets'] }}</td>
                <td>{{ $job['duplex'] ? 'Si' : '-' }}</td>
                <td style="font-size:11px;color:var(--mes-text-tertiary);white-space:nowrap;">{{ $job['date'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    </div>
</div>
@endif

@else
<div class="card-d">
    <div class="offline-box">
        <div style="font-size:48px;margin-bottom:16px;">&#9888;</div>
        <h3>Fiery P400 non raggiungibile</h3>
        <p>Verificare che la stampante sia accesa e raggiungibile su <strong>{{ config('fiery.host') }}</strong></p>
    </div>
</div>
@endif
</div>

<script>
var tonerColors = {'Nero':'#374151','Cyan':'#06b6d4','Magenta':'#ec4899','Yellow':'#eab308','Waste Toner':'#78716c'};

function fasiHtml(fasi) {
    if (!fasi || !fasi.length) return '';
    var h = '<div class="fasi-wrap">';
    fasi.forEach(function(f) { h += '<span class="fpill ' + (f.esterno ? 'fp-ext' : 'fp-s' + f.stato) + '">' + f.fase + '</span>'; });
    return h + '</div>';
}

function fmt(n) { return Number(n || 0).toLocaleString('it-IT'); }

setInterval(function() {
    fetch('{{ route("mes.fiery.status") }}')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (!data.online) {
                document.getElementById('stato-container').innerHTML = '<span class="status-badge sb-offline"><span class="status-dot dot-offline"></span>Offline</span>';
                return;
            }

            // Stato
            document.getElementById('stato-container').innerHTML = '<span class="status-badge sb-' + data.stato + '"><span class="status-dot dot-' + data.stato + '"></span>' + data.stato.charAt(0).toUpperCase() + data.stato.slice(1) + '</span>';

            // Avviso
            var avvBox = document.getElementById('avviso-box');
            if (data.avviso) {
                if (!avvBox) { avvBox = document.createElement('div'); avvBox.id = 'avviso-box'; avvBox.className = 'warning-strip'; document.getElementById('stato-container').closest('.card-d').appendChild(avvBox); }
                avvBox.textContent = data.avviso; avvBox.style.display = '';
            } else if (avvBox) { avvBox.style.display = 'none'; }

            // Timestamp
            var ts = document.getElementById('ultimo-aggiornamento');
            if (ts && data.ultimo_aggiornamento) ts.textContent = data.ultimo_aggiornamento;

            // RIP
            var rc = document.getElementById('rip-container');
            if (data.rip && !data.rip.idle && data.rip.documento) { rc.innerHTML = '<span class="rip-chip rip-active">&#9654; RIP: ' + data.rip.documento + '</span>'; }
            else { rc.innerHTML = '<span class="rip-chip rip-idle">RIP idle</span>'; }

            // Job in stampa
            var sc = document.getElementById('stampa-container');
            if (data.stampa && data.stampa.documento) {
                var commHtml = '';
                if (data.commessa) {
                    commHtml = '<div class="mt-2"><span class="commessa-chip">' + data.commessa.commessa + '</span><span style="color:var(--mes-text-secondary);margin-left:8px;font-size:13px;">' + data.commessa.cliente + '</span></div>';
                }
                sc.innerHTML = '<div class="print-doc">' + data.stampa.documento + '</div>' + commHtml +
                    '<div class="prog-bar-wrap"><div class="prog-bar"><div class="prog-fill" style="width:' + data.stampa.progresso + '%"></div><div class="prog-text">' + data.stampa.progresso + '%</div></div>' +
                    '<div class="prog-stats"><span>Copie: <strong>' + data.stampa.copie_fatte + '</strong> / ' + data.stampa.copie_totali + '</span><span>Pagine: ' + data.stampa.pagine + '</span><span>Utente: ' + (data.stampa.utente || '') + '</span></div></div>';
                if (data.jobs && data.jobs.commessa_sheets && data.jobs.commessa_sheets.fogli_totali > 0) {
                    var cs = data.jobs.commessa_sheets;
                    sc.innerHTML += '<div style="margin-top:10px;padding:8px 14px;background:#dbeafe;border:1px solid #93c5fd;border-radius:8px;font-size:12px;color:var(--mes-text-secondary);">Fogli totali: <strong style="color:var(--mes-primary);">' + cs.fogli_totali + '</strong> <span style="margin-left:8px;">' + cs.copie_totali + ' copie</span> <span style="margin-left:8px;color:var(--mes-text-tertiary);">' + cs.run_count + ' run</span></div>';
                }
            } else {
                sc.innerHTML = '<div class="no-job">Nessun job in stampa</div>';
            }

            // Sidebar commessa
            var cd = document.getElementById('commessa-detail');
            if (cd && data.commessa) {
                cd.innerHTML = '<div class="mb-2"><div class="info-lbl">Commessa</div><div class="info-val">' + data.commessa.commessa + '</div></div><div class="mb-2"><div class="info-lbl">Cliente</div><div class="info-val-sm">' + data.commessa.cliente + '</div></div>';
            } else if (cd) { cd.innerHTML = ''; }

            // Stats
            if (data.jobs) {
                var s1 = document.getElementById('stat-completed'), s2 = document.getElementById('stat-queue'), s3 = document.getElementById('stat-total');
                if (s1) s1.textContent = data.jobs.completed.length;
                if (s2) s2.textContent = data.jobs.queue.length;
                if (s3) s3.textContent = data.jobs.total;

                // Queue cards
                var qc = document.getElementById('queue-container');
                if (qc) {
                    var qh = '';
                    data.jobs.queue.forEach(function(j) {
                        var m = j.mes;
                        qh += '<div class="q-card"><div><div class="q-title">' + j.title + '</div>';
                        if (m) {
                            qh += '<div style="margin-bottom:6px;"><span class="commessa-chip">' + m.commessa + '</span><span style="font-size:10px;color:var(--mes-text-tertiary);margin-left:6px;">' + m.cliente + '</span></div>';
                            qh += '<div class="q-desc">' + (m.descrizione || '').substring(0, 100) + '</div>';
                            if (m.note_prestampa) qh += '<div class="q-notes">' + m.note_prestampa + '</div>';
                            qh += fasiHtml(m.fasi);
                        }
                        qh += '</div><div>';
                        if (m && m.carta) qh += '<div class="q-carta-lbl">Carta</div><div class="q-carta-val">' + m.carta + '</div><div class="q-carta-sub">' + (m.cod_carta || '') + '</div>';
                        qh += '</div><div class="q-qta">';
                        if (m) { qh += '<div class="q-num">' + fmt(m.qta_richiesta) + '</div><div class="q-sub">pezzi</div>'; if (m.qta_carta) qh += '<div class="q-sub">' + fmt(m.qta_carta) + ' fg</div>'; }
                        qh += '</div><div class="q-meta"><div class="q-date">' + (m ? m.data_prevista || '-' : '-') + '</div><div class="q-copies">' + (j.num_copies || '-') + ' copie</div></div></div>';
                    });
                    qc.innerHTML = qh;
                }

                // Completed table
                var cb = document.getElementById('completed-body');
                if (cb) {
                    var ch = '';
                    data.jobs.completed.forEach(function(j) {
                        var pct = j.num_copies > 0 ? Math.round((j.copies_printed / j.num_copies) * 100) : 100;
                        var mesHtml = '', cartaHtml = '';
                        if (j.mes) {
                            mesHtml = '<span class="commessa-chip">' + j.mes.commessa + '</span><span style="font-size:10px;color:var(--mes-text-tertiary);margin-left:4px;">' + j.mes.cliente + '</span>';
                            cartaHtml = j.mes.carta || '';
                        }
                        ch += '<tr><td class="c-title">' + j.title + '</td><td>' + mesHtml + '</td><td style="font-size:11px;">' + cartaHtml + '</td><td><div class="mini-bar"><div class="fill" style="width:' + pct + '%"></div></div><span class="copies-sm">' + j.copies_printed + '/' + j.num_copies + '</span></td><td style="font-weight:600;color:var(--mes-text-primary);">' + j.total_sheets + '</td><td>' + (j.duplex ? 'Si' : '-') + '</td><td style="font-size:11px;color:var(--mes-text-tertiary);white-space:nowrap;">' + j.date + '</td></tr>';
                    });
                    cb.innerHTML = ch;
                }
            }

            // SNMP data refresh
            if (data.snmp && !data.snmp.errore) {
                // KPI counters
                var kTot = document.getElementById('kpi-totale');
                var kCol = document.getElementById('kpi-colore');
                var kBn = document.getElementById('kpi-bn');
                if (kTot) kTot.textContent = fmt(data.snmp.totale_1);
                if (kCol) kCol.textContent = fmt((data.snmp.colore_grande||0) + (data.snmp.colore_piccolo||0));
                if (kBn) kBn.textContent = fmt((data.snmp.nero_grande||0) + (data.snmp.nero_piccolo||0));

                // Toner
                var tc = document.getElementById('toner-container');
                if (tc && data.snmp.toner) {
                    var th = '';
                    data.snmp.toner.forEach(function(t) {
                        var c = tonerColors[t.nome] || '#6b7280';
                        var w = t.livello >= 0 && t.livello <= 15;
                        th += '<div class="toner-item"><div class="toner-label">' + t.nome + '</div>' +
                            '<div class="toner-bar-v"><div class="toner-fill-v ' + (w ? 'toner-warn' : '') + '" style="height:' + Math.max(t.livello,0) + '%;background:' + c + ';"></div></div>' +
                            '<div class="toner-pct ' + (w ? 'toner-warn' : '') + '" style="color:' + c + '">' + (t.livello >= 0 ? t.livello + '%' : '?') + '</div></div>';
                    });
                    tc.innerHTML = th;
                }

                // Trays
                var tr = document.getElementById('tray-container');
                if (tr && data.snmp.vassoi) {
                    var trh = '';
                    data.snmp.vassoi.forEach(function(v, i) {
                        var p = v.percentuale;
                        var low = p !== null && p >= 0 && p <= 20;
                        var info = '-';
                        if (p === -1) info = 'Presente';
                        else if (p !== null) info = p + '%';
                        trh += '<div class="tray-item ' + (low ? 'low' : '') + '">' +
                            '<div class="tray-name">' + (v.nome || 'Vassoio '+(i+1)) + '</div>' +
                            '<div class="tray-bar-h"><div class="tray-fill-h" style="width:' + (p !== null && p >= 0 ? p : 0) + '%;' + (low ? 'background:var(--mes-danger);' : '') + '"></div></div>' +
                            '<div class="tray-info">' + info + '</div>' +
                            (v.tipo ? '<div class="tray-type">' + v.tipo + '</div>' : '') + '</div>';
                    });
                    tr.innerHTML = trh;
                }

                // Finisher
                var fc = document.getElementById('fin-container');
                if (fc && data.snmp.punti) {
                    var fh = '';
                    data.snmp.punti.forEach(function(p) {
                        var low = p.livello >= 0 && p.livello <= 20;
                        fh += '<div class="fin-item ' + (low ? 'low' : '') + '"><div class="fin-name">' + p.nome + '</div>' +
                            '<div class="fin-pct" style="' + (low ? 'color:var(--mes-danger);' : '') + '">' + (p.livello >= 0 ? p.livello + '%' : '?') + '</div></div>';
                    });
                    fc.innerHTML = fh;
                }

                // Alert
                var ab = document.getElementById('snmp-alert');
                if (ab) {
                    if (data.snmp.alert) { ab.className = 'alert-strip warn'; ab.innerHTML = '&#9888; ' + data.snmp.alert; }
                    else { ab.className = 'alert-strip ok'; ab.innerHTML = '&#10003; Nessun avviso attivo sulla macchina'; }
                }
            }
        })
        .catch(function() {});
}, 30000);
</script>
@endsection
