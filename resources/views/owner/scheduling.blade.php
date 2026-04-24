@extends('layouts.mes')

@section('topbar-title', 'Scheduling Produzione')

@section('sidebar-items')
<div class="mes-sidebar-section">
    <div class="mes-sidebar-section-label">Produzione</div>
    <a href="{{ route('owner.dashboard') }}?op_token={{ request('op_token') }}" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        Dashboard
    </a>
    <a href="{{ route('owner.scheduling') }}?op_token={{ request('op_token') }}" class="mes-sidebar-item active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        Scheduling
    </a>
</div>
<div class="mes-sidebar-section">
    <div class="mes-sidebar-section-label">Analisi</div>
    <a href="{{ route('owner.reportOre') }}?op_token={{ request('op_token') }}" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        Report Ore
    </a>
    <a href="{{ route('owner.fasiTerminate') }}?op_token={{ request('op_token') }}" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        Fasi Terminate
    </a>
</div>
@endsection

@section('content')
<style>
    html, body { overflow-x:hidden; background:#12122a; }

    /* ===== HEADER ===== */
    .sched-header {
        display:flex; align-items:center; justify-content:space-between;
        padding:18px 24px 14px;
        background:linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
        color:#fff;
        margin-bottom:0;
    }
    .sched-header h2 { margin:0; font-size:28px; font-weight:700; letter-spacing:-0.5px; }
    .sched-header .subtitle { font-size:13px; color:rgba(255,255,255,0.6); margin-top:2px; }
    .header-right { display:flex; gap:12px; align-items:center; }
    .search-box {
        width:300px; font-size:14px; padding:8px 16px;
        border-radius:25px; border:1px solid rgba(255,255,255,0.25);
        background:rgba(255,255,255,0.1); color:#fff;
        outline:none; transition:all 0.3s;
    }
    .search-box::placeholder { color:rgba(255,255,255,0.45); }
    .search-box:focus { background:rgba(255,255,255,0.2); border-color:rgba(255,255,255,0.5); }

    /* ===== KPI ===== */
    .kpi-row { display:flex; gap:14px; padding:18px 24px 14px; flex-wrap:wrap; }
    .kpi-card {
        flex:1; min-width:190px; background:linear-gradient(145deg,#1e1e38,#1a1a30);
        border-radius:12px; padding:14px 60px 14px 18px; position:relative; overflow:hidden;
        box-shadow:0 1px 8px rgba(0,0,0,0.2); transition:transform 0.15s, box-shadow 0.15s;
        border:1px solid rgba(255,255,255,0.05);
    }
    .kpi-card:hover { transform:translateY(-2px); box-shadow:0 4px 14px rgba(0,0,0,0.3); }
    .kpi-card small {
        display:block; color:#9090b0; font-size:11px; font-weight:700;
        text-transform:uppercase; letter-spacing:0.8px; margin-bottom:6px;
    }
    .kpi-card h3 {
        margin:0; font-size:32px; font-weight:800; letter-spacing:-1px;
        display:flex; align-items:baseline; gap:6px; line-height:1;
    }
    .kpi-card .kpi-pct { font-size:14px; font-weight:700; opacity:0.85; }
    .kpi-card .kpi-unit { font-size:18px; font-weight:700; opacity:0.65; margin-left:1px; }
    .kpi-card .kpi-icon {
        position:absolute; top:50%; right:16px; transform:translateY(-50%);
        width:36px; height:36px;
        border-radius:9px; display:flex; align-items:center; justify-content:center;
        font-size:16px; font-weight:bold; color:#fff; opacity:0.9;
    }
    .kpi-blue h3 { color:#5a9cff; }
    .kpi-blue .kpi-icon { background:linear-gradient(135deg,#0d6efd,#6ea8fe); }
    .kpi-red h3 { color:#ff6b7a; }
    .kpi-red .kpi-icon { background:linear-gradient(135deg,#dc3545,#f1737b); }
    .kpi-orange h3 { color:#f0a04b; }
    .kpi-orange .kpi-icon { background:linear-gradient(135deg,#fd7e14,#fdb96a); }
    .kpi-purple h3 { color:#a98eda; }
    .kpi-purple .kpi-icon { background:linear-gradient(135deg,#6f42c1,#a98eda); }
    .kpi-green h3 { color:#5dd39e; }
    .kpi-green .kpi-icon { background:linear-gradient(135deg,#198754,#5dd39e); }

    /* ===== LEGENDA ===== */
    .legend {
        display:flex; gap:22px; padding:6px 24px 12px; font-size:13px;
        align-items:center; flex-wrap:wrap;
    }
    .legend-title { font-weight:700; color:#c8c8e0; }
    .legend-item { display:flex; align-items:center; gap:6px; color:#9090b0; }
    .legend-color { width:18px; height:18px; border-radius:5px; box-shadow:0 1px 3px rgba(0,0,0,0.3); }

    /* ===== TABS ===== */
    .tab-nav {
        display:flex; gap:0; margin:0 24px; border-bottom:3px solid #2a2a45;
        background:#1a1a2e; border-radius:12px 12px 0 0; padding:0 6px;
    }
    .tab-btn {
        padding:14px 30px; cursor:pointer; border:none; background:none;
        font-size:15px; font-weight:600; color:#6c6c8c;
        border-bottom:3px solid transparent; margin-bottom:-3px;
        transition:all 0.2s;
    }
    .tab-btn.active { color:#5a9cff; border-bottom-color:#0d6efd; }
    .tab-btn:hover { color:#c8c8e0; background:rgba(255,255,255,0.03); }
    .tab-content { display:none; }
    .tab-content.active { display:block; }

    /* ===== ZOOM & FILTRI ===== */
    .controls-bar {
        display:flex; flex-direction:column; align-items:stretch;
        padding:12px 24px; gap:10px;
        background:#1a1a34; border-radius:0;
        overflow:hidden; max-width:100%; box-sizing:border-box;
    }
    .controls-bar .zoom-controls { align-self:flex-start; }
    .zoom-controls { display:flex; align-items:center; gap:10px; }
    .zoom-controls button {
        width:32px; height:32px; border:1px solid rgba(255,255,255,0.08); border-radius:7px;
        background:#1e1e38; cursor:pointer; font-size:16px; font-weight:bold;
        color:#c8c8e0; transition:all 0.15s;
    }
    .zoom-controls button:hover { background:#0d6efd; color:#fff; border-color:#0d6efd; }
    .zoom-controls .zoom-val {
        font-size:12px; color:#a0a0c0; background:rgba(22,22,46,0.7);
        padding:4px 10px; border-radius:6px; font-weight:600; min-width:58px; text-align:center;
    }
    .filter-row {
        display:flex; gap:6px; align-items:center; flex-wrap:wrap;
        width:100%; min-width:0; max-width:100%;
    }
    .filter-chip {
        padding:5px 12px; border:1px solid rgba(255,255,255,0.08); border-radius:16px;
        font-size:11.5px; font-weight:600; cursor:pointer; background:rgba(30,30,56,0.5);
        transition:all 0.12s; color:#a0a0c0; white-space:nowrap;
    }
    .filter-chip:hover { background:rgba(42,42,80,0.7); color:#fff; }
    .filter-chip.active { background:#0d6efd; color:#fff; border-color:#0d6efd; box-shadow:0 2px 6px rgba(13,110,253,0.35); }
    .filter-chip:hover:not(.active) { border-color:#5a5a7c; color:#ccc; }

    /* ===== GANTT ===== */
    .gantt-wrapper {
        margin:0 24px 20px; overflow-x:auto; border:1px solid #2a2a45;
        border-radius:0 0 12px 12px; background:#1a1a34;
        box-shadow:0 4px 20px rgba(0,0,0,0.3);
        scrollbar-width:none; /* Firefox */
    }
    .gantt-wrapper::-webkit-scrollbar-horizontal { height:0; display:none; }
    .gantt-row { display:flex; border-bottom:1px solid #2a2a45; min-height:50px; }
    .gantt-row:nth-child(even) { background:#1e1e3a; }
    .gantt-row:hover { background:#252550; }
    .gantt-label {
        width:220px; min-width:220px; padding:10px 14px;
        font-size:14px; font-weight:700; color:#c8c8e0;
        border-right:2px solid #2a2a45;
        display:flex; align-items:center;
        background:#1a1a34; position:sticky; left:0; z-index:5;
    }
    .gantt-row:nth-child(even) .gantt-label { background:#1e1e3a; }
    .gantt-row:hover .gantt-label { background:#252550; }
    .gantt-label .label-sub { font-size:11px; color:#6c6c8c; font-weight:500; margin-top:2px; }
    .gantt-timeline { position:relative; flex:1; min-height:50px; }

    .gantt-bar {
        position:absolute; top:8px; height:34px; border-radius:6px;
        cursor:pointer; overflow:hidden; display:flex; align-items:center;
        padding:0 8px; font-size:12px; color:#fff; font-weight:700;
        white-space:nowrap; min-width:4px;
        transition:opacity 0.2s, transform 0.15s;
        box-shadow:0 2px 8px rgba(0,0,0,0.4);
    }
    .gantt-bar:hover { opacity:0.9; transform:scaleY(1.1); z-index:20; }
    .gantt-bar span { overflow:hidden; text-overflow:ellipsis; text-shadow:0 1px 2px rgba(0,0,0,0.5); }
    .gantt-bar.bar-highlight {
        outline:3px solid #fff; outline-offset:1px; z-index:25;
        box-shadow:0 0 20px rgba(255,255,255,0.5);
    }
    .gantt-bar.bar-dimmed { opacity:0.22; transition:opacity 0.15s; }

    /* Filtri rapidi */
    .quick-filters {
        display:flex; gap:8px; padding:12px 24px 4px;
        flex-wrap:wrap; align-items:center;
    }
    .qf-btn {
        background:rgba(30,30,56,0.6); color:#a0a0c0;
        border:1px solid rgba(255,255,255,0.06);
        padding:7px 14px; border-radius:8px; font-size:12.5px; font-weight:600;
        cursor:pointer; transition:all 0.12s;
        display:inline-flex; align-items:center; gap:6px;
    }
    .qf-btn:hover { background:rgba(42,42,80,0.8); color:#fff; border-color:rgba(255,255,255,0.1); }
    .qf-btn.active {
        background:linear-gradient(135deg,#0d6efd,#4891ff); color:#fff;
        border-color:#0d6efd; box-shadow:0 2px 8px rgba(13,110,253,0.4);
    }

    .bar-scaduta { background:linear-gradient(135deg,#dc3545,#e35d6a); }
    .bar-critica { background:linear-gradient(135deg,#e67e22,#f0a04b); }
    .bar-normale { background:linear-gradient(135deg,#0d6efd,#5a9cff); }
    .bar-avviata { background:linear-gradient(135deg,#198754,#3cc07e); }
    .bar-pronta { background:linear-gradient(135deg,#0dcaf0,#56d8f0); }

    /* Day dividers */
    .gantt-day-line { position:absolute; top:0; bottom:0; border-left:1px dashed #3a3a5c; z-index:1; }
    .gantt-night { position:absolute; top:0; bottom:0; z-index:0; background:rgba(0,0,0,0.2); }
    .gantt-night-label { position:absolute; bottom:2px; left:3px; font-size:8px; color:rgba(255,255,255,0.15); font-weight:600; }

    /* Time header */
    .gantt-time-label {
        position:absolute; top:0; font-size:11px; color:#6c6c8c;
        padding:3px 6px; border-left:1px solid #3a3a5c;
        height:100%; display:flex; align-items:flex-end; padding-bottom:6px;
    }
    .gantt-time-label.day-label {
        font-weight:700; color:#c8c8e0; font-size:13px;
        align-items:flex-start; padding-top:8px;
        border-left:2px solid #5a5a7c;
    }

    /* ===== TOOLTIP ===== */
    .gantt-tooltip {
        position:fixed; z-index:9999;
        background:linear-gradient(135deg,#1a1a2e,#16213e);
        color:#fff; padding:16px 20px; border-radius:12px;
        font-size:13px; pointer-events:none; display:none;
        max-width:380px; line-height:1.8;
        box-shadow:0 8px 30px rgba(0,0,0,0.5);
        border:1px solid rgba(255,255,255,0.1);
    }
    .gantt-tooltip .tt-title { font-size:16px; font-weight:800; color:#ffc107; margin-bottom:6px; }
    .gantt-tooltip .tt-row { display:flex; gap:6px; }
    .gantt-tooltip .tt-label { color:#8c9ab5; font-weight:500; min-width:110px; }
    .gantt-tooltip .tt-val { color:#fff; font-weight:600; }
    .gantt-tooltip .tt-divider { border-top:1px solid rgba(255,255,255,0.1); margin:6px 0; }

    /* ===== TABELLA PRIORITA (DARK) ===== */
    .prio-header {
        display:flex; justify-content:space-between; align-items:center;
        padding:18px 28px; background:#1a1a2e; margin:0 24px;
        border-radius:12px 12px 0 0; border-bottom:1px solid #2a2a45;
    }
    .prio-title { color:#fff; font-size:20px; font-weight:700; margin:0; }
    .prio-filters { display:flex; gap:10px; }
    .prio-btn {
        padding:8px 20px; border-radius:20px; border:1.5px solid #3a3a5c;
        background:transparent; color:#8c8caa; font-size:13px; font-weight:600;
        cursor:pointer; transition:all 0.2s;
    }
    .prio-btn.active { background:#0d6efd; color:#fff; border-color:#0d6efd; }
    .prio-btn:hover:not(.active) { border-color:#5a5a7c; color:#ccc; }
    .prio-btn-ritardo.active { background:#e67e22; border-color:#e67e22; }
    .prio-btn-critico.active { background:#dc3545; border-color:#dc3545; }

    .prio-table-wrap {
        max-height:72vh; overflow:auto; margin:0 24px 20px;
        border-radius:0 0 12px 12px; box-shadow:0 4px 20px rgba(0,0,0,0.3);
    }
    .prio-table { width:100%; font-size:13px; border-collapse:collapse; background:#1e1e38; }
    .prio-table th {
        background:#16162e; color:#8c8caa; padding:14px 16px; text-align:left;
        position:sticky; top:0; z-index:5; white-space:nowrap;
        font-size:11px; font-weight:700; letter-spacing:0.8px; text-transform:uppercase;
        border-bottom:2px solid #2a2a45;
    }
    .prio-table td {
        padding:16px 16px; border-bottom:1px solid #2a2a45;
        white-space:nowrap; color:#c8c8e0; font-size:13px;
    }
    .prio-table tr { transition:background 0.15s; }
    .prio-table tr:nth-child(even) { background:#1a1a34; }
    .prio-table tr:hover { background:#252550; }

    .prio-badge {
        display:inline-flex; align-items:center; justify-content:center;
        width:42px; height:42px; border-radius:10px; font-size:15px;
        font-weight:800; color:#fff;
    }
    .prio-badge-red { background:#dc3545; }
    .prio-badge-orange { background:#e67e22; }
    .prio-badge-green { background:#198754; }

    .prio-commessa { color:#7ea8ff; font-weight:700; font-size:14px; font-style:italic; }
    .prio-prodotto { color:#c8c8e0; max-width:250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .prio-cliente { color:#9090b0; font-size:12px; }

    .prio-margine { font-weight:800; font-size:14px; }
    .prio-margine-neg { color:#dc3545; }
    .prio-margine-warn { color:#e67e22; }
    .prio-margine-ok { color:#198754; }

    .prio-stato {
        display:inline-flex; align-items:center; gap:5px;
        padding:6px 14px; border-radius:20px; font-size:11px; font-weight:700;
    }
    .prio-stato-ritardo { background:rgba(230,126,34,0.15); color:#f0a04b; }
    .prio-stato-critico { background:rgba(220,53,69,0.15); color:#ff6b7a; }
    .prio-stato-ok { background:rgba(25,135,84,0.15); color:#3cc07e; }

    .badge-stato {
        padding:4px 12px; border-radius:20px; font-size:11px;
        font-weight:700; color:#fff; display:inline-block;
    }
    .commessa-link {
        color:#0d6efd; font-weight:700; text-decoration:none;
        border-bottom:2px solid transparent; transition:all 0.2s;
    }
    .commessa-link:hover { border-bottom-color:#0d6efd; }

    /* ===== COMMESSA GANTT ===== */
    .gantt-label-comm { width:250px; min-width:250px; }
    .gantt-label-comm .comm-title { font-size:13px; font-weight:800; color:#c8c8e0; }
    .gantt-label-comm .comm-sub { font-size:11px; color:#6c6c8c; font-weight:500; margin-top:2px; }

    /* ===== EMPTY STATE ===== */
    .empty-state { text-align:center; padding:60px 20px; color:#6c6c8c; }
    .empty-state .empty-icon { font-size:48px; margin-bottom:12px; }
    .empty-state p { font-size:16px; }

    /* ===== SCROLLBAR DARK ===== */
    .gantt-wrapper::-webkit-scrollbar { width:10px; height:0; }
    .prio-table-wrap::-webkit-scrollbar { height:10px; width:10px; }
    .gantt-wrapper::-webkit-scrollbar-track,
    .prio-table-wrap::-webkit-scrollbar-track { background:#16162e; }
    .gantt-wrapper::-webkit-scrollbar-thumb,
    .prio-table-wrap::-webkit-scrollbar-thumb { background:#3a3a5c; border-radius:5px; }
    .gantt-wrapper::-webkit-scrollbar-thumb:hover,
    .prio-table-wrap::-webkit-scrollbar-thumb:hover { background:#5a5a7c; }

    /* ===== SCROLLBAR ORIZZONTALE FISSA ===== */
    .gantt-hscroll {
        position:fixed; bottom:0; left:24px; right:24px;
        height:18px; overflow-x:auto; overflow-y:hidden;
        background:#16162e; border-top:1px solid #3a3a5c;
        z-index:100;
    }
    .gantt-hscroll::-webkit-scrollbar { height:12px; }
    .gantt-hscroll::-webkit-scrollbar-track { background:#16162e; }
    .gantt-hscroll::-webkit-scrollbar-thumb { background:#5a5a7c; border-radius:6px; }
    .gantt-hscroll::-webkit-scrollbar-thumb:hover { background:#7a7a9c; }
    .gantt-hscroll-inner { height:1px; }

    /* ===== SIDE PANEL ===== */
    .side-overlay {
        position:fixed; top:0; left:0; right:0; bottom:0;
        background:rgba(0,0,0,0.5); z-index:8000;
        opacity:0; pointer-events:none; transition:opacity 0.3s;
    }
    .side-overlay.open { opacity:1; pointer-events:auto; }

    .side-panel {
        position:fixed; top:0; right:0; bottom:0; width:480px; max-width:90vw;
        background:#16162e; z-index:8001; overflow-y:auto;
        transform:translateX(100%); transition:transform 0.3s ease;
        box-shadow:-4px 0 30px rgba(0,0,0,0.5);
        display:flex; flex-direction:column;
    }
    .side-panel.open { transform:translateX(0); }

    .sp-header {
        padding:20px 24px 16px; background:#1a1a2e;
        border-bottom:1px solid #2a2a45; position:sticky; top:0; z-index:1;
    }
    .sp-close {
        position:absolute; top:16px; right:18px; background:none; border:none;
        color:#6c6c8c; font-size:28px; cursor:pointer; line-height:1;
        transition:color 0.2s;
    }
    .sp-close:hover { color:#ff6b7a; }
    .sp-commessa { font-size:22px; font-weight:800; color:#5a9cff; margin:0 0 4px; }
    .sp-cliente { font-size:14px; color:#8c8caa; font-weight:500; }
    .sp-prodotto { font-size:13px; color:#c8c8e0; margin-top:6px; }

    .sp-body { padding:20px 24px; flex:1; }

    .sp-info-grid {
        display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:20px;
    }
    .sp-info-card {
        background:#1e1e38; border-radius:10px; padding:14px 16px;
        border:1px solid #2a2a45;
    }
    .sp-info-label { font-size:10px; color:#6c6c8c; text-transform:uppercase; letter-spacing:0.8px; font-weight:700; margin-bottom:4px; }
    .sp-info-val { font-size:18px; font-weight:800; }

    .sp-section-title {
        font-size:13px; font-weight:700; color:#8c8caa; text-transform:uppercase;
        letter-spacing:0.8px; margin:20px 0 12px; padding-bottom:8px;
        border-bottom:1px solid #2a2a45;
    }

    .sp-fase-card {
        background:#1e1e38; border-radius:10px; padding:14px 16px;
        margin-bottom:10px; border:1px solid #2a2a45;
        transition:border-color 0.2s;
    }
    .sp-fase-card:hover { border-color:#3a3a5c; }
    .sp-fase-name { font-size:14px; font-weight:700; color:#c8c8e0; }
    .sp-fase-reparto { font-size:11px; color:#6c6c8c; margin-top:2px; }
    .sp-fase-row { display:flex; justify-content:space-between; margin-top:8px; font-size:12px; }
    .sp-fase-detail { color:#8c8caa; }
    .sp-fase-detail strong { color:#c8c8e0; }

    .sp-stato-badge {
        display:inline-block; padding:3px 10px; border-radius:12px;
        font-size:10px; font-weight:700;
    }
    .sp-stato-0 { background:rgba(108,117,125,0.2); color:#8c8caa; }
    .sp-stato-1 { background:rgba(13,202,240,0.15); color:#56d8f0; }
    .sp-stato-2 { background:rgba(25,135,84,0.15); color:#3cc07e; }
    .sp-stato-3 { background:rgba(26,26,46,0.5); color:#6c6c8c; }
</style>

<!-- SIDE PANEL -->
<div class="side-overlay" id="sideOverlay"></div>
<div class="side-panel" id="sidePanel">
    <div class="sp-header">
        <button class="sp-close" id="spClose">&times;</button>
        <div class="sp-commessa" id="spCommessa"></div>
        <div class="sp-cliente" id="spCliente"></div>
        <div class="sp-prodotto" id="spProdotto"></div>
    </div>
    <div class="sp-body">
        <div class="sp-info-grid" id="spInfoGrid"></div>
        <div class="sp-section-title">Fasi di lavorazione</div>
        <div id="spFasiList"></div>
    </div>
</div>

<!-- HEADER -->
<div class="sched-header">
    <div>
        <h2>Scheduler Mossa 37 <button id="btnInfoAlgoritmo" title="Come funziona" style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.3);color:#fff;width:28px;height:28px;border-radius:50%;font-weight:700;cursor:pointer;vertical-align:middle;margin-left:6px;font-size:14px;">?</button></h2>
        <div class="subtitle">Pianificazione automatica · Gantt lavorazioni attive</div>
    </div>
    <div class="header-right">
        <a href="{{ route('owner.schedulingExcel') }}?op_token={{ request('op_token') }}"
           style="background:#059669;color:#fff;border:1px solid #047857;padding:8px 16px;border-radius:25px;text-decoration:none;font-size:13px;font-weight:600;display:inline-flex;align-items:center;gap:6px;"
           title="Scarica piano produzione Excel">
            ⬇ Excel Piano
        </a>
        <input type="text" id="searchGlobal" class="search-box" placeholder="Cerca commessa, cliente, fase...">
    </div>
</div>

<!-- MODAL INFO ALGORITMO -->
<div id="modalInfoAlgoritmo" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:9999;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#1e1e38;border:1px solid #2a2a45;border-radius:16px;max-width:720px;width:100%;max-height:85vh;overflow-y:auto;padding:32px;color:#fff;">
        <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:20px;">
            <div>
                <h2 style="margin:0;font-size:24px;">Come funziona Mossa 37</h2>
                <div style="font-size:13px;color:rgba(255,255,255,0.6);margin-top:4px;">Algoritmo di pianificazione produzione tipografica</div>
            </div>
            <button onclick="document.getElementById('modalInfoAlgoritmo').style.display='none'" style="background:none;border:none;color:#fff;font-size:28px;cursor:pointer;line-height:1;">×</button>
        </div>

        <div style="margin-bottom:22px;">
            <h3 style="color:#60a5fa;font-size:15px;margin:0 0 8px 0;">📊 4 criteri di priorità</h3>
            <div style="font-size:13px;line-height:1.6;color:rgba(255,255,255,0.85);">
                <div style="margin-bottom:8px;"><strong style="color:#f59e0b;">1. Urgenza consegna</strong> — giorni rimanenti alla data promessa. Commesse in ritardo / critiche (&le;3gg) salgono in priorità.</div>
                <div style="margin-bottom:8px;"><strong style="color:#f59e0b;">2. Sequenza ciclo produttivo</strong> — ogni fase ha un ordine nel flusso (stampa → plastifica → fustella → piegaincolla → spedizione). Mossa 37 rispetta le dipendenze.</div>
                <div style="margin-bottom:8px;"><strong style="color:#f59e0b;">3. Affinità batch</strong> — commesse con stesso formato/colore/cliché raggruppate per ridurre setup macchina (±5gg urgenza).</div>
                <div style="margin-bottom:8px;"><strong style="color:#f59e0b;">4. Disponibilità macchina</strong> — vincoli fisici: BOBST (1 macchina, 2 config rilievi/fustella, cambio 1h), Piegaincolla (1 macchina, 3 config PI01/02/03).</div>
            </div>
        </div>

        <div style="margin-bottom:22px;">
            <h3 style="color:#60a5fa;font-size:15px;margin:0 0 8px 0;">⏱ Dati real-time integrati</h3>
            <div style="font-size:13px;line-height:1.6;color:rgba(255,255,255,0.85);">
                • <strong>Prinect API</strong> Heidelberg XL106 → tempi effettivi avviamento ed esecuzione<br>
                • <strong>Fiery API</strong> Canon imagePRESS V900 → stato digitale, job in stampa, coda<br>
                • <strong>Onda ERP</strong> → commesse, ordini, priorità manuali<br>
                • <strong>NetTime</strong> → presenze operatori<br>
            </div>
        </div>

        <div style="margin-bottom:22px;">
            <h3 style="color:#60a5fa;font-size:15px;margin:0 0 8px 0;">🔄 Ricomposizione automatica</h3>
            <div style="font-size:13px;line-height:1.6;color:rgba(255,255,255,0.85);">
                Ogni volta che una fase termina, un operatore chiude una lavorazione o arriva una commessa urgente, Mossa 37 ricompone il piano in meno di 1 secondo. Il Gantt si aggiorna in tempo reale.
            </div>
        </div>

        <div style="background:rgba(96,165,250,0.1);border:1px solid rgba(96,165,250,0.3);border-radius:10px;padding:14px 16px;font-size:12px;color:rgba(255,255,255,0.9);line-height:1.5;">
            <strong style="color:#60a5fa;">Risultato</strong> — pianificazione ottimale di 50+ commesse in &lt;1 secondo, vs 2 ore pianificazione manuale su Excel. Margine d'errore minore, batch ottimizzati, consegne rispettate.
        </div>

        <div style="text-align:right;margin-top:22px;">
            <button onclick="document.getElementById('modalInfoAlgoritmo').style.display='none'" style="background:#3b82f6;border:none;color:#fff;padding:10px 22px;border-radius:8px;font-weight:600;cursor:pointer;">Chiudi</button>
        </div>
    </div>
</div>
<script>
document.getElementById('btnInfoAlgoritmo').addEventListener('click', () => {
    const m = document.getElementById('modalInfoAlgoritmo');
    m.style.display = 'flex';
});
document.getElementById('modalInfoAlgoritmo').addEventListener('click', (e) => {
    if (e.target.id === 'modalInfoAlgoritmo') e.target.style.display = 'none';
});
</script>

<!-- KPI -->
<div class="kpi-row" id="kpiRow"></div>

<!-- LEGENDA -->
<div class="legend">
    <span class="legend-title">Legenda:</span>
    <div class="legend-item"><div class="legend-color bar-avviata"></div> In lavorazione</div>
    <div class="legend-item"><div class="legend-color bar-pronta"></div> Pronta</div>
    <div class="legend-item"><div class="legend-color bar-scaduta"></div> Scaduta</div>
    <div class="legend-item"><div class="legend-color bar-critica"></div> Critica (&le;3gg)</div>
    <div class="legend-item"><div class="legend-color bar-normale"></div> Normale</div>
</div>

<!-- FILTRI RAPIDI -->
<div class="quick-filters">
    <button class="qf-btn active" data-qf="all">Tutte</button>
    <button class="qf-btn" data-qf="critiche">🔴 Solo critiche</button>
    <button class="qf-btn" data-qf="oggi">📅 Oggi/Domani</button>
    <button class="qf-btn" data-qf="2h">🕐 Prossime 2h</button>
</div>

<!-- TABS -->
<div class="tab-nav">
    <button class="tab-btn active" data-tab="macchina">Per Macchina</button>
    <button class="tab-btn" data-tab="commessa">Per Commessa</button>
    <button class="tab-btn" data-tab="tabella">Tabella Priorita</button>
</div>

<!-- TAB: Per Macchina -->
<div class="tab-content active" id="tab-macchina">
    <div class="controls-bar">
        <div class="zoom-controls">
            <button id="zoomOut">−</button>
            <div class="zoom-val" id="zoomLabel">8 px/h</div>
            <button id="zoomIn">+</button>
        </div>
        <div class="filter-row" id="filterReparti"></div>
    </div>
    <div class="gantt-wrapper" id="ganttMacchinaWrapper" style="max-height:72vh; overflow:auto;">
        <div id="ganttMacchina"></div>
    </div>
</div>

<!-- TAB: Per Commessa -->
<div class="tab-content" id="tab-commessa">
    <div class="controls-bar">
        <div class="zoom-controls">
            <button id="zoomOutC">−</button>
            <div class="zoom-val" id="zoomLabelC">8 px/h</div>
            <button id="zoomInC">+</button>
        </div>
    </div>
    <div class="gantt-wrapper" style="max-height:72vh; overflow:auto;">
        <div id="ganttCommessa"></div>
    </div>
</div>

<!-- TAB: Tabella -->
<div class="tab-content" id="tab-tabella">
    <div class="prio-header">
        <h2 class="prio-title">Ordini per Priorità</h2>
        <div class="prio-filters">
            <button class="prio-btn active" id="prioAll">Tutti (<span id="prioCountAll">0</span>)</button>
            <button class="prio-btn prio-btn-ritardo" id="prioRitardo">In Ritardo</button>
            <button class="prio-btn prio-btn-critico" id="prioCritico">Critici</button>
        </div>
    </div>
    <div class="prio-table-wrap">
        <table class="prio-table" id="tabellaScheduling">
            <thead>
                <tr>
                    <th>Prior.</th>
                    <th>Commessa</th>
                    <th>Prodotto</th>
                    <th>Cliente</th>
                    <th>Consegna</th>
                    <th>Ore tot.</th>
                    <th>Margine</th>
                    <th>Fasi</th>
                    <th>Fine stimata</th>
                    <th>Stato</th>
                </tr>
            </thead>
            <tbody id="tabellaBody"></tbody>
        </table>
    </div>
</div>

<!-- SCROLLBAR ORIZZONTALE FISSA -->
<div class="gantt-hscroll" id="ganttHScroll">
    <div class="gantt-hscroll-inner" id="ganttHScrollInner"></div>
</div>

<!-- TOOLTIP -->
<div class="gantt-tooltip" id="tooltip"></div>

<script>
const DATA = @json(json_decode($dataJson));
const NOW = new Date();
let pxPerHour = 8;
let filtroReparti = new Set();
let searchQuery = '';

// ===================== CONFIGURAZIONE TURNI PER REPARTO =====================
// Ogni reparto ha orari lun-ven e sabato separati. Domenica: tutti chiusi.
// stampa offset:  06:00-22:00 (2 turni 8h, 16h totali), anche sabato
// piegaincolla:   06:00-22:00 lun-sab
// stampa a caldo: 06:00-22:00 lun-ven, 06:00-13:00 sabato
// fustella:       06:00-22:00 lun-sab
// altri:          08:00-17:00 lun-sab

function getTurno(reparto) {
    const r = (reparto || '').toLowerCase();
    if (r === 'stampa offset')  return { inizio: 6,  fine: 22, sabInizio: 6, sabFine: 22 };
    if (r === 'piegaincolla')   return { inizio: 6,  fine: 22, sabInizio: 6, sabFine: 22 };
    if (r === 'stampa a caldo') return { inizio: 6,  fine: 22, sabInizio: 6, sabFine: 13 };
    if (r === 'fustella')       return { inizio: 6,  fine: 22, sabInizio: 6, sabFine: 22 };
    if (r.includes('bobst'))    return { inizio: 6,  fine: 22, sabInizio: 6, sabFine: 22 };
    return { inizio: 8, fine: 17, sabInizio: 8, sabFine: 17 };
}

function getWorkHours(reparto, date) {
    if (date.getDay() === 0) return null; // domenica chiuso
    const t = getTurno(reparto);
    if (date.getDay() === 6) return { inizio: t.sabInizio, fine: t.sabFine };
    return { inizio: t.inizio, fine: t.fine };
}

function getTurnoLabel(reparto) {
    const r = (reparto || '').toLowerCase();
    if (r === 'esterno') return 'parallelo';
    if (r === 'stampa offset') return '6-22';
    if (r === 'stampa a caldo') return '6-22, sab 6-13';
    if (r === 'piegaincolla' || r === 'fustella') return '6-22';
    if (r.includes('bobst')) return '6-22';
    return '8-17';
}

function isOffset(reparto) {
    return (reparto || '').toLowerCase() === 'stampa offset';
}

// ===================== DOMENICA + TURNI =====================

function skipToWorkTime(h, reparto) {
    let d = new Date(NOW.getTime() + h * 3600000);

    // Salta domenica
    if (d.getDay() === 0) {
        h += 24 - d.getHours() - d.getMinutes() / 60;
        d = new Date(NOW.getTime() + h * 3600000);
    }

    const wh = getWorkHours(reparto, d);
    if (!wh) return h;
    const hour = d.getHours() + d.getMinutes() / 60;

    if (hour >= wh.fine) {
        // Dopo fine turno: vai al prossimo giorno lavorativo
        h += (24 - hour);
        d = new Date(NOW.getTime() + h * 3600000);
        if (d.getDay() === 0) { h += 24; d = new Date(NOW.getTime() + h * 3600000); }
        const nextWh = getWorkHours(reparto, d);
        if (nextWh) h += nextWh.inizio;
    } else if (hour < wh.inizio) {
        h += wh.inizio - hour;
    }

    // Ricontrolla domenica
    d = new Date(NOW.getTime() + h * 3600000);
    if (d.getDay() === 0) { h += 24; }

    return h;
}

function advanceCursor(startH, ore, reparto) {
    let pos = skipToWorkTime(startH, reparto);
    let remaining = ore;

    while (remaining > 0.001) {
        const d = new Date(NOW.getTime() + pos * 3600000);
        const hour = d.getHours() + d.getMinutes() / 60;

        // Domenica: salta
        if (d.getDay() === 0) {
            pos += 24 - hour;
            continue;
        }

        const wh = getWorkHours(reparto, d);
        if (!wh) { pos += 24; continue; }

        if (hour < wh.inizio) {
            pos += wh.inizio - hour;
            continue;
        }
        if (hour >= wh.fine) {
            pos += (24 - hour);
            const nd = new Date(NOW.getTime() + pos * 3600000);
            if (nd.getDay() === 0) pos += 24;
            continue;
        }

        const oreDisponibili = wh.fine - hour;
        if (remaining <= oreDisponibili) {
            pos += remaining;
            remaining = 0;
        } else {
            remaining -= oreDisponibili;
            pos += (24 - hour);
            const nd = new Date(NOW.getTime() + pos * 3600000);
            if (nd.getDay() === 0) pos += 24;
        }
    }
    return pos;
}

// ===================== RIMUOVI DOMENICA DALLA TIMELINE =====================

function calToDisplay(calH) {
    if (calH <= 0) return 0;
    const startMs = NOW.getTime();
    const endMs = startMs + calH * 3600000;
    let sundayMs = 0;

    // Trova la prima domenica
    const d = new Date(startMs);
    const daysToSun = (7 - d.getDay()) % 7;
    const firstSun = new Date(d);
    firstSun.setHours(0, 0, 0, 0);
    firstSun.setDate(firstSun.getDate() + (d.getDay() === 0 ? 0 : daysToSun));

    let sun = new Date(firstSun);
    while (sun.getTime() < endMs) {
        const sunEnd = new Date(sun);
        sunEnd.setDate(sunEnd.getDate() + 1);
        const oStart = Math.max(startMs, sun.getTime());
        const oEnd = Math.min(endMs, sunEnd.getTime());
        if (oEnd > oStart) sundayMs += oEnd - oStart;
        sun.setDate(sun.getDate() + 7);
    }
    return calH - sundayMs / 3600000;
}

// ===================== SCHEDULING =====================

const FASI_ORDINE = @json(config('fasi_priorita'));
function faseOrdine(nome) { return FASI_ORDINE[nome] || 500; }

function isEsterno(reparto) {
    return reparto.toLowerCase() === 'esterno';
}

// Bobst = unica macchina per fustella + rilievo
function isBobst(fase) {
    const nome = (fase.fase || '').toLowerCase();
    const reparto = (fase.reparto || '').toLowerCase();
    if (reparto === 'esterno') return false;
    if (nome.startsWith('sfust')) return false;
    return nome.includes('fust') || nome.includes('rilievo');
}

// Piegaincolla: config diversa (PI01/PI02/PI03)
function isPiegaincolla(reparto) {
    return (reparto || '').toLowerCase() === 'piegaincolla';
}

// Config per changeover: quale "tipo setup" ha questa fase sulla macchina?
function getSetupConfig(fase) {
    const nome = (fase.fase || '').toUpperCase();
    // Bobst: rilievo vs fustella
    if (nome.includes('RILIEVO') || nome.includes('STAMPASECCO')) return 'rilievo';
    if (nome.includes('FUST')) return 'fustella';
    // Piegaincolla: PI01, PI02, PI03
    if (nome.startsWith('PI0')) return nome.substring(0, 4);
    return nome;
}

// Ore stimate migliori: usa Prinect > media storica > formula teorica
function getBestOre(fase) {
    // Se fase avviata, usa ore reali Prinect se disponibili
    if (fase.ore_prinect && fase.ore_prinect > 0) return fase.ore_prinect;
    // Media storica (ultimi 90gg) se disponibile
    if (fase.ore_media_storica && fase.ore_media_storica > 0) return fase.ore_media_storica;
    // Fallback: formula teorica (avviamento + qta/copieh)
    return fase.ore;
}

function schedulaPerMacchina(data) {
    // Se le fasi hanno sched_inizio/sched_fine dal PHP scheduler, usa quelli
    const hasSchedulerData = data.some(f => f.sched_inizio && f.sched_fine);
    if (hasSchedulerData) {
        return schedulaDaDB(data);
    }
    // Fallback: calcolo client-side
    return schedulaClientSide(data);
}

// ===================== SCHEDULER DA DB (Mossa 37 pre-calcolato) =====================
function schedulaDaDB(data) {
    const NOMI_MAC = {
        'XL106': 'Stampa offset', 'BOBST': 'Fustella piana', 'STEL': 'Fustella cilindrica',
        'JOH': 'Stampa a caldo', 'PLAST': 'Plastificazione', 'PIEGA': 'Piegaincolla',
        'FIN': 'Finestratura', 'INDIGO': 'Digitale', 'TAGLIO': 'Tagliacarte',
        'LEGAT': 'Legatoria', 'ZUND': 'Finitura digitale', 'SPED': 'Spedizione',
    };
    const TURNI_MAC = {
        'XL106': '6-22', 'BOBST': '6-22', 'STEL': '6-22', 'JOH': '6-22, sab 6-13',
        'PLAST': '6-22', 'PIEGA': '6-22', 'FIN': '6-22', 'INDIGO': '6-22',
        'TAGLIO': '6-22', 'LEGAT': '6-22', 'ZUND': '6-22', 'SPED': '-',
    };

    const macchine = {};
    data.forEach(f => {
        // Fasi esterne: usa sched_inizio (= disponibile_da) se presente, altrimenti oggi
        if (f.esterno) {
            const nome = 'Esterno';
            if (!macchine[nome]) macchine[nome] = { nome, reparto_id: 0, fasi: [], turni: '-' };
            const oreEff = getBestOre(f) || 1;
            let startH = 0, endH = oreEff;
            if (f.sched_inizio && f.sched_fine) {
                const inizio = new Date(f.sched_inizio);
                const fine = new Date(f.sched_fine);
                startH = (inizio - NOW) / 3600000;
                endH = (fine - NOW) / 3600000;
            }
            macchine[nome].fasi.push({ ...f, start_h: startH, end_h: endH, ore_effettive: oreEff, lane: 0 });
            return;
        }
        if (!f.sched_macchina || !f.sched_inizio || !f.sched_fine) return;
        const mid = f.sched_macchina;
        const nome = NOMI_MAC[mid] || f.reparto || mid;
        if (!macchine[nome]) macchine[nome] = { nome, reparto_id: f.reparto_id || 0, fasi: [], turni: TURNI_MAC[mid] || '6-22' };

        const inizio = new Date(f.sched_inizio);
        const fine = new Date(f.sched_fine);
        const startH = (inizio - NOW) / 3600000;
        const endH = (fine - NOW) / 3600000;

        macchine[nome].fasi.push({
            ...f,
            start_h: startH,
            end_h: endH,
            ore_effettive: (fine - inizio) / 3600000,
            lane: 0,
        });
    });

    // Fasi senza scheduler vengono ignorate (sono state schedulate dallo scheduler PHP
    // oppure sono fasi esterne/manuali che non hanno una macchina assegnata)

    // Ordina fasi per posizione scheduler e calcola ore_totali
    Object.values(macchine).forEach(m => {
        m.fasi.sort((a, b) => (a.sched_posizione || 999) - (b.sched_posizione || 999));
        m.lanes = 1;
        m.ore_totali = m.fasi.length > 0 ? Math.max(...m.fasi.map(f => f.end_h || 0)) : 0;
    });

    // Ordine macchine fisso per il cliente
    const ORDINE_MAC = ['Stampa offset','Fustella piana','Fustella cilindrica','Stampa a caldo',
                        'Plastificazione','Piegaincolla','Finestratura','Digitale','Tagliacarte',
                        'Legatoria','Finitura digitale','Spedizione','Esterno'];
    const arr = Object.values(macchine);
    arr.sort((a, b) => {
        const ia = ORDINE_MAC.indexOf(a.nome);
        const ib = ORDINE_MAC.indexOf(b.nome);
        return (ia === -1 ? 999 : ia) - (ib === -1 ? 999 : ib);
    });
    return arr;
}

// ===================== SCHEDULER CLIENT-SIDE (fallback) =====================
function schedulaClientSide(data) {
    const macchine = {};
    const BOBST_KEY = 'Fustella / Rilievo (Bobst)';
    const SPED_KEY = 'Spedizione';

    // Raggruppa fasi per macchina
    data.forEach(f => {
        const key = isBobst(f) ? BOBST_KEY : f.reparto;
        if (!macchine[key]) macchine[key] = { nome: key, reparto_id: f.reparto_id, fasi: [] };
        macchine[key].fasi.push({...f, ore_effettive: getBestOre(f)});
    });

    // Indice fasi schedulate per commessa (per dipendenze cross-reparto)
    // Chiave: "commessa", Valore: array di { fase_ordine, end_h }
    const scheduledByCommessa = {};

    function getCommessaPredecessorEnd(commessa, mioFaseOrdine) {
        const scheduled = scheduledByCommessa[commessa];
        if (!scheduled || scheduled.length === 0) return 0;
        let maxEnd = 0;
        for (const s of scheduled) {
            if (s.fase_ordine < mioFaseOrdine && s.end_h > maxEnd) {
                maxEnd = s.end_h;
            }
        }
        return maxEnd;
    }

    function registerScheduled(commessa, faseOrdine, endH) {
        if (!scheduledByCommessa[commessa]) scheduledByCommessa[commessa] = [];
        scheduledByCommessa[commessa].push({ fase_ordine: faseOrdine, end_h: endH });
    }

    // Raccolta TUTTE le fasi non-spedizione, ordinate per fase_ordine (flusso produttivo)
    // Questo garantisce che le dipendenze cross-reparto siano rispettate
    const allFasi = [];
    Object.values(macchine).forEach(m => {
        if (m.nome === SPED_KEY) return;
        m.fasi.forEach(f => {
            f._macchina = m.nome;
            allFasi.push(f);
        });
    });
    // Ordina per fase_ordine (flusso produttivo), poi per priorità
    allFasi.sort((a, b) => {
        const ordA = a.fase_ordine || 500;
        const ordB = b.fase_ordine || 500;
        if (ordA !== ordB) return ordA - ordB;
        return a.priorita - b.priorita;
    });

    // Cursori per macchina e ultima config per changeover
    const machineCursors = {};
    const machineLastConfig = {};
    Object.keys(macchine).forEach(key => {
        if (key !== SPED_KEY) {
            machineCursors[key] = skipToWorkTime(0, key);
            machineLastConfig[key] = null;
        }
    });

    // Schedula fase per fase nell'ordine del flusso produttivo
    allFasi.forEach(fase => {
        const mKey = fase._macchina;
        const macchina = macchine[mKey];

        if (isEsterno(mKey)) {
            // Esterno: parallelo (verrà gestito dopo)
            return;
        }

        let cursor = machineCursors[mKey];

        // Dipendenza cross-reparto: aspetta predecessori della stessa commessa
        const predEnd = getCommessaPredecessorEnd(fase.commessa, fase.fase_ordine || 500);
        cursor = Math.max(cursor, predEnd);

        // Changeover time per BOBST e Piegaincolla
        const config = getSetupConfig(fase);
        if ((mKey === BOBST_KEY || isPiegaincolla(mKey)) && machineLastConfig[mKey] !== null && machineLastConfig[mKey] !== config) {
            cursor += 1; // +1h cambio setup
        }
        machineLastConfig[mKey] = config;

        if (fase.stato === 2 && fase.data_inizio_reale) {
            const reale = new Date(fase.data_inizio_reale);
            const hFromNow = Math.max(0, (reale - NOW) / 3600000);
            fase.start_h = hFromNow;
            fase.end_h = advanceCursor(hFromNow, Math.max(fase.ore_effettive, 0.1), mKey);
            if (fase.end_h > cursor) machineCursors[mKey] = fase.end_h;
            else machineCursors[mKey] = Math.max(machineCursors[mKey], fase.end_h);
        } else {
            cursor = skipToWorkTime(cursor, mKey);
            fase.start_h = cursor;
            fase.end_h = advanceCursor(cursor, Math.max(fase.ore_effettive, 0.1), mKey);
            machineCursors[mKey] = fase.end_h;
        }
        fase.lane = 0;

        registerScheduled(fase.commessa, fase.fase_ordine || 500, fase.end_h);
    });

    // Gestione Esterno: parallelo con swim lanes + dipendenze cross-reparto
    Object.values(macchine).forEach(m => {
        if (m.nome === SPED_KEY || !isEsterno(m.nome)) return;
        const lanes = [];
        let maxCursor = 0;
        m.fasi.sort((a, b) => a.priorita - b.priorita);
        m.fasi.forEach(fase => {
            let start;
            if (fase.stato === 2 && fase.data_inizio_reale) {
                const reale = new Date(fase.data_inizio_reale);
                start = Math.max(0, (reale - NOW) / 3600000);
            } else {
                // Dipendenza cross-reparto
                const predEnd = getCommessaPredecessorEnd(fase.commessa, fase.fase_ordine || 500);
                start = Math.max(skipToWorkTime(0, m.nome), predEnd);
            }
            fase.start_h = start;
            fase.end_h = advanceCursor(start, Math.max(fase.ore_effettive, 0.1), m.nome);

            let laneIdx = lanes.findIndex(laneEnd => start >= laneEnd);
            if (laneIdx === -1) { laneIdx = lanes.length; lanes.push(0); }
            lanes[laneIdx] = fase.end_h;
            fase.lane = laneIdx;

            if (fase.end_h > maxCursor) maxCursor = fase.end_h;
            registerScheduled(fase.commessa, fase.fase_ordine || 500, fase.end_h);
        });
        m.lanes = lanes.length || 1;
        m.ore_totali = maxCursor;
    });

    // Calcola ore_totali e lanes per macchine non-esterno non-spedizione
    // Riordina fasi per start_h per rendering Gantt corretto
    Object.values(macchine).forEach(m => {
        if (m.nome === SPED_KEY || isEsterno(m.nome)) return;
        m.fasi.sort((a, b) => a.start_h - b.start_h);
        m.ore_totali = machineCursors[m.nome] || 0;
        m.lanes = 1;
    });

    // Spedizione (BRT): dopo tutte le altre fasi della stessa commessa
    if (macchine[SPED_KEY]) {
        const mSped = macchine[SPED_KEY];
        // Per ogni commessa, trova la fine più tarda di tutte le fasi non-spedizione
        const commessaMaxEnd = {};
        Object.values(macchine).forEach(m => {
            if (m.nome === SPED_KEY) return;
            m.fasi.forEach(f => {
                const c = f.commessa;
                if (!commessaMaxEnd[c] || f.end_h > commessaMaxEnd[c]) {
                    commessaMaxEnd[c] = f.end_h;
                }
            });
        });

        mSped.fasi.sort((a, b) => {
            const endA = commessaMaxEnd[a.commessa] || 0;
            const endB = commessaMaxEnd[b.commessa] || 0;
            return endA - endB;
        });

        let cursor = skipToWorkTime(0, mSped.nome);
        mSped.fasi.forEach(fase => {
            const minStart = commessaMaxEnd[fase.commessa] || 0;
            cursor = Math.max(cursor, minStart);
            cursor = skipToWorkTime(cursor, mSped.nome);
            fase.start_h = cursor;
            fase.end_h = advanceCursor(cursor, Math.max(fase.ore_effettive || fase.ore, 0.1), mSped.nome);
            cursor = fase.end_h;
            fase.lane = 0;
        });
        mSped.lanes = 1;
        mSped.ore_totali = cursor;
    }

    // Ordina macchine per flusso produttivo (stampa offset prima, spedizione ultima)
    const REPARTO_ORDINE = {
        'Stampa offset': 1,
        'Fustella / Rilievo (Bobst)': 2,
        'Digitale': 3,
        'Plastificazione': 4,
        'Stampa a caldo': 5,
        'Finitura digitale': 6,
        'Fustella piana': 7,
        'Tagliacarte': 8,
        'Piegaincolla': 9,
        'Finestratura': 10,
        'Legatoria': 11,
        'Allestimento': 12,
        'Esterno': 50,
        'Spedizione': 99,
    };
    return Object.values(macchine).sort((a, b) => {
        const ordA = REPARTO_ORDINE[a.nome] ?? 40;
        const ordB = REPARTO_ORDINE[b.nome] ?? 40;
        return ordA - ordB;
    });
}

// Cache dello scheduling completo (calcolato su TUTTI i dati, non filtrati)
let _fullScheduleCache = null;
function getFullSchedule() {
    if (!_fullScheduleCache) _fullScheduleCache = schedulaPerMacchina(DATA);
    return _fullScheduleCache;
}

// Filtra le macchine schedulate mantenendo i tempi reali di coda
function filterScheduledMacchine(macchine) {
    let result = macchine.map(m => ({...m, fasi: [...m.fasi]}));
    if (filtroReparti.size > 0) {
        result = result.filter(m => {
            if (filtroReparti.has(m.nome)) return true;
            // Bobst raggruppa fasi di reparti diversi (fustella piana, cilindrica, ecc.)
            return m.fasi.some(f => filtroReparti.has(f.reparto));
        }).map(m => {
            // Se la macchina è visibile grazie alle fasi interne, filtra solo quelle fasi
            if (!filtroReparti.has(m.nome)) {
                return {...m, fasi: m.fasi.filter(f => filtroReparti.has(f.reparto))};
            }
            return m;
        }).filter(m => m.fasi.length > 0);
    }
    if (searchQuery) {
        const q = searchQuery.toLowerCase();
        result = result.map(m => ({
            ...m,
            fasi: m.fasi.filter(f =>
                f.commessa.toLowerCase().includes(q) || f.cliente.toLowerCase().includes(q) ||
                f.descrizione.toLowerCase().includes(q) || f.fase.toLowerCase().includes(q) ||
                f.reparto.toLowerCase().includes(q)
            )
        })).filter(m => m.fasi.length > 0);
    }
    // Quick filter applicato anche su Per Macchina
    if (quickFilter && quickFilter !== 'all') {
        const now = new Date();
        const fine2h = new Date(now.getTime() + 2*3600*1000);
        const fine48h = new Date(now.getTime() + 48*3600*1000);
        result = result.map(m => ({
            ...m,
            fasi: m.fasi.filter(f => {
                if (quickFilter === 'critiche') return f.giorni_consegna !== null && f.giorni_consegna <= -3;
                if (quickFilter === 'inlav') return f.stato == 2;
                if (quickFilter === 'oggi') {
                    if (!f.sched_inizio) return false;
                    return new Date(f.sched_inizio) <= fine48h;
                }
                if (quickFilter === '2h') {
                    if (!f.sched_inizio) return false;
                    return new Date(f.sched_inizio) <= fine2h;
                }
                return true;
            })
        })).filter(m => m.fasi.length > 0);
    }
    return result;
}

function schedulaPerCommessa(data) {
    // Usa SEMPRE lo scheduling completo per tempi reali di coda
    const macchine = getFullSchedule();
    const commesse = {};
    macchine.forEach(m => m.fasi.forEach(f => {
        const key = f.commessa;
        if (!commesse[key]) commesse[key] = {
            commessa: key, cliente: f.cliente, descrizione: f.descrizione,
            consegna: f.consegna, giorni_consegna: f.giorni_consegna, fasi: []
        };
        commesse[key].fasi.push({...f});
    }));

    // Filtra per reparto e ricerca
    const filtered = filterData(DATA);
    const filteredCommesse = new Set(filtered.map(f => f.commessa));

    const result = Object.values(commesse).filter(c => filteredCommesse.has(c.commessa));
    result.forEach(c => {
        c.fasi.sort((a, b) => a.start_h - b.start_h);
        c.ore_totali = Math.max(...c.fasi.map(f => f.end_h), 0);
        c.priorita_min = Math.min(...c.fasi.map(f => f.priorita));
    });
    return result.sort((a, b) => a.priorita_min - b.priorita_min);
}

// ===================== HELPERS =====================

function getBarClass(fase) {
    if (fase.stato === 2) return 'bar-avviata';
    if (fase.stato === 1) return 'bar-pronta';
    if (fase.giorni_consegna !== null && fase.giorni_consegna < 0) return 'bar-scaduta';
    if (fase.giorni_consegna !== null && fase.giorni_consegna <= 3) return 'bar-critica';
    return 'bar-normale';
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleDateString('it-IT', { day:'2-digit', month:'2-digit', year:'numeric' });
}

function oreToDateStr(calH) {
    const d = new Date(NOW.getTime() + calH * 3600000);
    return d.toLocaleDateString('it-IT', { weekday:'short', day:'2-digit', month:'2-digit' }) + ' ' +
           d.toLocaleTimeString('it-IT', { hour:'2-digit', minute:'2-digit' });
}

function statoLabel(stato) {
    const map = { 0:['Caricato','#6c757d'], 1:['Pronto','#0dcaf0'], 2:['Avviato','#198754'], 3:['Terminato','#1a1a2e'], 4:['Consegnato','#333'] };
    const [label, color] = map[stato] || ['?','#999'];
    return `<span class="badge-stato" style="background:${color}">${label}</span>`;
}

let quickFilter = 'all';

function filterData(data) {
    let result = data;
    if (filtroReparti.size > 0) result = result.filter(f => filtroReparti.has(f.reparto));
    if (searchQuery) {
        const q = searchQuery.toLowerCase();
        result = result.filter(f =>
            f.commessa.toLowerCase().includes(q) || f.cliente.toLowerCase().includes(q) ||
            f.descrizione.toLowerCase().includes(q) || f.fase.toLowerCase().includes(q) ||
            f.reparto.toLowerCase().includes(q)
        );
    }
    if (quickFilter !== 'all') {
        const now = new Date();
        const inizio24h = new Date(now.getTime() + 2*3600*1000);
        const fine48h = new Date(now.getTime() + 48*3600*1000);
        result = result.filter(f => {
            if (quickFilter === 'critiche') return f.giorni_consegna !== null && f.giorni_consegna <= -3;
            if (quickFilter === 'inlav') return f.stato == 2;
            if (quickFilter === 'oggi') {
                if (!f.sched_inizio) return false;
                const si = new Date(f.sched_inizio);
                return si <= fine48h;
            }
            if (quickFilter === '2h') {
                if (!f.sched_inizio) return false;
                const si = new Date(f.sched_inizio);
                return si <= inizio24h;
            }
            return true;
        });
    }
    return result;
}

function el(tag, className, styles) {
    const e = document.createElement(tag);
    if (className) e.className = className;
    if (styles) Object.assign(e.style, styles);
    return e;
}

// ===================== DAY MARKERS (senza domenica) =====================

function renderDayHeaders(timeHeader, maxCalH) {
    // Raccogli i giorni (senza domenica)
    const totalDays = Math.ceil(maxCalH / 24) + 2;
    const days = [];
    for (let day = 0; day <= totalDays; day++) {
        const d = new Date(NOW);
        d.setHours(0, 0, 0, 0);
        d.setDate(d.getDate() + day);
        if (d.getDay() === 0) continue;
        const calH = (d.getTime() - NOW.getTime()) / 3600000;
        if (calH > maxCalH + 48) break;
        days.push({ date: new Date(d), calH });
    }

    // Ogni giorno = colonna con larghezza propria
    for (let i = 0; i < days.length; i++) {
        const startCalH = Math.max(0, days[i].calH);
        const endCalH = (i + 1 < days.length) ? Math.max(0, days[i + 1].calH) : startCalH + 24;
        const startDisp = calToDisplay(startCalH);
        const endDisp = calToDisplay(endCalH);
        const leftPx = startDisp * pxPerHour;
        const widthPx = (endDisp - startDisp) * pxPerHour;
        if (widthPx < 2) continue;

        const isToday = days[i].calH < 0;
        const dayLabel = el('div', 'gantt-time-label day-label');
        dayLabel.style.left = leftPx + 'px';
        dayLabel.style.width = widthPx + 'px';
        dayLabel.style.justifyContent = 'center';
        dayLabel.style.textAlign = 'center';
        dayLabel.style.overflow = 'hidden';
        dayLabel.style.textOverflow = 'ellipsis';
        dayLabel.style.whiteSpace = 'nowrap';
        dayLabel.style.boxSizing = 'border-box';

        if (isToday) {
            dayLabel.style.color = '#5a9cff';
            dayLabel.style.fontWeight = '800';
            dayLabel.style.background = 'rgba(13,110,253,0.12)';
            dayLabel.style.borderLeft = '2px solid #0d6efd';
        }

        let text;
        if (isToday) {
            if (widthPx >= 120) text = 'OGGI ' + days[i].date.toLocaleDateString('it-IT', { weekday:'short', day:'2-digit', month:'2-digit' });
            else if (widthPx >= 60) text = 'OGGI';
            else text = String(days[i].date.getDate());
        } else {
            text = days[i].date.toLocaleDateString('it-IT', { weekday:'short', day:'2-digit', month:'2-digit' });
        }
        dayLabel.textContent = text;
        timeHeader.appendChild(dayLabel);
    }
}

function renderDayLines(timeline, maxCalH, repartoNome) {
    const totalDays = Math.ceil(maxCalH / 24) + 2;
    for (let day = 0; day <= totalDays; day++) {
        const d = new Date(NOW);
        d.setHours(0, 0, 0, 0);
        d.setDate(d.getDate() + day);
        if (d.getDay() === 0) continue;
        const calH = (d.getTime() - NOW.getTime()) / 3600000;
        if (calH > maxCalH + 24) break;
        const displayH = calToDisplay(Math.max(0, calH));
        const line = el('div', 'gantt-day-line');
        line.style.left = (displayH * pxPerHour) + 'px';
        timeline.appendChild(line);

        // Zona notte (ore non lavorative) per-reparto
        if (repartoNome) {
            const wh = getWorkHours(repartoNome, d);
            if (wh && wh.fine < 24) {
                // Notte: da fine turno oggi a inizio turno domani
                const nextDay = new Date(d);
                nextDay.setDate(nextDay.getDate() + 1);
                const nextWh = getWorkHours(repartoNome, nextDay);
                const nextInizio = nextWh ? nextWh.inizio : getTurno(repartoNome).inizio;

                const nightStartH = calH + wh.fine;
                const nightEndH = calH + 24 + nextInizio;
                const dStart = calToDisplay(Math.max(0, nightStartH));
                const dEnd = calToDisplay(Math.max(0, nightEndH));
                if (dEnd > dStart && dStart * pxPerHour < 10000) {
                    const night = el('div', 'gantt-night');
                    night.style.left = (dStart * pxPerHour) + 'px';
                    night.style.width = ((dEnd - dStart) * pxPerHour) + 'px';
                    timeline.appendChild(night);
                }
            }
        }
    }
}

// ===================== KPI =====================

function renderKPI() {
    const macchine = filterScheduledMacchine(getFullSchedule());
    const oreTotali = Math.round(macchine.reduce((s, m) => s + m.fasi.reduce((s2, f) => s2 + (f.ore_effettive || f.ore), 0), 0));
    const commMap = {};
    macchine.forEach(m => m.fasi.forEach(f => {
        if (!commMap[f.commessa]) commMap[f.commessa] = {
            commessa: f.commessa, consegna: f.consegna,
            giorni_consegna: f.giorni_consegna, max_end_h: 0
        };
        if (f.end_h > commMap[f.commessa].max_end_h) commMap[f.commessa].max_end_h = f.end_h;
    }));

    const commesse = Object.values(commMap);
    const totale = commesse.length;

    // Calcola stato:
    // - Critiche: consegna superata di almeno 3 giorni
    // - In ritardo: fine stimata produzione > data consegna (ma non critica)
    // - In tempo: tutto il resto
    let scadute = 0, critiche = 0, inTempo = 0;
    commesse.forEach(c => {
        let margineH = null;
        if (c.consegna) {
            const consDate = new Date(c.consegna);
            consDate.setHours(0, 0, 0, 0);
            const fineStimata = new Date(NOW.getTime() + c.max_end_h * 3600000);
            margineH = (consDate - fineStimata) / 3600000;
        }
        if (c.giorni_consegna !== null && c.giorni_consegna <= -3) critiche++;
        else if (margineH !== null && margineH < 0) scadute++;
        else inTempo++;
    });

    const pct = (n) => totale > 0 ? Math.round(n / totale * 100) : 0;

    document.getElementById('kpiRow').innerHTML = `
        <div class="kpi-card kpi-blue">
            <small>Commesse attive</small>
            <h3>${totale}</h3>
            <div class="kpi-icon">C</div>
        </div>
        <div class="kpi-card kpi-green">
            <small>In tempo</small>
            <h3>${inTempo} <span class="kpi-pct" style="color:#5dd39e">${pct(inTempo)}%</span></h3>
            <div class="kpi-icon">&check;</div>
        </div>
        <div class="kpi-card kpi-red">
            <small>In ritardo</small>
            <h3>${scadute} <span class="kpi-pct" style="color:#ff6b7a">${pct(scadute)}%</span></h3>
            <div class="kpi-icon">!</div>
        </div>
        <div class="kpi-card kpi-orange">
            <small>Critiche &ge;3gg</small>
            <h3>${critiche} <span class="kpi-pct" style="color:#f0a04b">${pct(critiche)}%</span></h3>
            <div class="kpi-icon">!!</div>
        </div>
        <div class="kpi-card kpi-purple">
            <small>Ore stimate</small>
            <h3>${oreTotali}<span class="kpi-unit">h</span></h3>
            <div class="kpi-icon">H</div>
        </div>
    `;
}

// ===================== BAR SEGMENTS (split su weekend) =====================

function splitBarWeekend(startH, endH, lavoraSab) {
    // Guardia: valori non validi → ritorna barra intera
    if (!isFinite(startH) || !isFinite(endH) || endH <= startH) {
        return [{ startH: startH || 0, endH: endH || (startH || 0) + 1 }];
    }
    // Suddivide range ore in segmenti saltando sab (se !lavoraSab) e dom sempre.
    const segments = [];
    const startMs = NOW.getTime() + startH * 3600000;
    const endMs = NOW.getTime() + endH * 3600000;
    let cur = new Date(startMs);
    const end = new Date(endMs);
    let safety = 0;
    while (cur < end && safety++ < 400) {
        const dow = cur.getDay(); // 0=dom, 6=sab
        const skip = dow === 0 || (dow === 6 && !lavoraSab);
        const midnight = new Date(cur);
        midnight.setHours(24, 0, 0, 0);
        const segEnd = midnight < end ? midnight : end;
        if (!skip) {
            segments.push({
                startH: (cur.getTime() - NOW.getTime()) / 3600000,
                endH: (segEnd.getTime() - NOW.getTime()) / 3600000,
            });
        }
        cur = new Date(segEnd);
    }
    // Se nessun segmento (tutto weekend), mantiene barra intera per non scomparire
    if (segments.length === 0) segments.push({ startH, endH });
    return segments;
}

// ===================== HIGHLIGHT COMMESSA =====================

function highlightCommessa(commessa) {
    document.querySelectorAll('.gantt-bar').forEach(b => {
        if (!commessa) { b.classList.remove('bar-highlight', 'bar-dimmed'); return; }
        if (b.dataset.commessa === commessa) {
            b.classList.add('bar-highlight');
            b.classList.remove('bar-dimmed');
        } else {
            b.classList.add('bar-dimmed');
            b.classList.remove('bar-highlight');
        }
    });
}

// ===================== TOOLTIP =====================

function showTooltip(e, fase) {
    const tt = document.getElementById('tooltip');
    const statoNomi = ['Caricato','Pronto','Avviato','Terminato','Consegnato'];
    const turno = getTurnoLabel(fase.reparto);
    tt.innerHTML = `
        <div class="tt-title">${fase.commessa}</div>
        <div class="tt-row"><span class="tt-label">Cliente</span><span class="tt-val">${fase.cliente}</span></div>
        <div class="tt-row"><span class="tt-label">Descrizione</span><span class="tt-val">${fase.descrizione}</span></div>
        <div class="tt-divider"></div>
        <div class="tt-row"><span class="tt-label">Fase</span><span class="tt-val">${fase.fase}</span></div>
        <div class="tt-row"><span class="tt-label">Reparto</span><span class="tt-val">${fase.reparto}</span></div>
        <div class="tt-row"><span class="tt-label">Turno</span><span class="tt-val">${turno}</span></div>
        <div class="tt-row"><span class="tt-label">Stato</span><span class="tt-val">${statoNomi[fase.stato] || '?'}</span></div>
        <div class="tt-divider"></div>
        <div class="tt-row"><span class="tt-label">Ore stimate</span><span class="tt-val">${(fase.ore_effettive || fase.ore).toFixed(1)}h ${fase.ore_prinect ? '(Prinect)' : fase.ore_media_storica ? '(media storica)' : '(teorico)'}</span></div>
        ${fase.ore_prinect ? `<div class="tt-row"><span class="tt-label">Prinect reale</span><span class="tt-val" style="color:#5dd39e">${fase.ore_prinect}h</span></div>` : ''}
        ${fase.ore_media_storica ? `<div class="tt-row"><span class="tt-label">Media storica</span><span class="tt-val" style="color:#f0a04b">${fase.ore_media_storica}h</span></div>` : ''}
        <div class="tt-row"><span class="tt-label">Ore teoriche</span><span class="tt-val">${fase.ore}h</span></div>
        <div class="tt-row"><span class="tt-label">Priorita</span><span class="tt-val">${fase.priorita}</span></div>
        <div class="tt-row"><span class="tt-label">Consegna</span><span class="tt-val">${formatDate(fase.consegna)}${fase.giorni_consegna !== null ? ' (' + fase.giorni_consegna + 'gg)' : ''}</span></div>
        <div class="tt-divider"></div>
        <div class="tt-row"><span class="tt-label">Inizio stimato</span><span class="tt-val">${oreToDateStr(fase.start_h)}</span></div>
        <div class="tt-row"><span class="tt-label">Fine stimata</span><span class="tt-val">${oreToDateStr(fase.end_h)}</span></div>
    `;
    tt.style.display = 'block';
    moveTooltip(e);
}

function moveTooltip(e) {
    const tt = document.getElementById('tooltip');
    tt.style.left = Math.min(e.clientX + 18, window.innerWidth - 400) + 'px';
    tt.style.top = Math.min(e.clientY + 18, window.innerHeight - 300) + 'px';
}

function hideTooltip() { document.getElementById('tooltip').style.display = 'none'; }

// ===================== GANTT PER MACCHINA =====================

function renderGanttMacchina() {
    const macchine = filterScheduledMacchine(getFullSchedule());
    const container = document.getElementById('ganttMacchina');
    container.innerHTML = '';

    if (macchine.length === 0) {
        container.innerHTML = '<div class="empty-state"><div class="empty-icon">&#128203;</div><p>Nessuna fase da schedulare</p></div>';
        return;
    }

    const maxCalOre = Math.max(...macchine.map(m => m.ore_totali), 10);
    const maxDisplayOre = calToDisplay(maxCalOre);
    const totalWidth = Math.max(maxDisplayOre * pxPerHour, 800);

    // Header
    const headerRow = el('div', 'gantt-row', {
        position:'sticky', top:'0', zIndex:'10',
        background:'#16162e',
        borderBottom:'2px solid #3a3a5c', minHeight:'48px'
    });
    const headerLabel = el('div', 'gantt-label', { fontWeight:'700', fontSize:'13px', color:'#8c8caa', textTransform:'uppercase', letterSpacing:'0.8px', background:'#16162e' });
    headerLabel.textContent = 'Macchina / Reparto';
    headerRow.appendChild(headerLabel);

    const timeHeader = el('div', 'gantt-timeline', { width:totalWidth+'px', position:'relative' });
    renderDayHeaders(timeHeader, maxCalOre);
    headerRow.appendChild(timeHeader);
    container.appendChild(headerRow);

    // Separa Esterno + Spedizione dal resto (collassabili)
    const COLLAPSABLE = ['Esterno','Spedizione'];
    const macchineReali = macchine.filter(m => !COLLAPSABLE.includes(m.nome));
    const collapsables = macchine.filter(m => COLLAPSABLE.includes(m.nome));

    // Rows
    macchineReali.forEach(macchina => {
        const row = el('div', 'gantt-row');
        const label = el('div', 'gantt-label');
        const turnoLabel = macchina.turni || getTurnoLabel(macchina.nome);
        label.innerHTML = `<div>${macchina.nome}<div class="label-sub">${macchina.fasi.length} fasi &middot; ${Math.round(macchina.fasi.reduce((s,f)=>s+(f.ore_effettive||f.ore),0))}h &middot; ${turnoLabel}</div></div>`;
        row.appendChild(label);

        // Swim lanes: altezza dinamica per esterno
        const lanes = macchina.lanes || 1;
        const barH = lanes > 1 ? 22 : 34;
        const barGap = 2;
        const laneH = barH + barGap;
        const rowH = Math.max(50, lanes * laneH + 8);

        row.style.minHeight = rowH + 'px';
        const timeline = el('div', 'gantt-timeline', { width:totalWidth+'px', minHeight:rowH+'px' });
        renderDayLines(timeline, maxCalOre, macchina.nome);

        // Macchina lavora sabato? leggiamo dal label turni (es "6-22, sab 6-13")
        const lavoraSab = (macchina.turni || '').toLowerCase().includes('sab');

        macchina.fasi.forEach(fase => {
            const dispStart = calToDisplay(fase.start_h);
            const dispEnd = calToDisplay(fase.end_h);
            const bw = (dispEnd - dispStart) * pxPerHour;

            // Calcola segmenti saltando weekend per macchine senza sab
            const segments = splitBarWeekend(fase.start_h, fase.end_h, lavoraSab);
            segments.forEach((seg, segIdx) => {
                const bar = el('div', 'gantt-bar ' + getBarClass(fase));
                bar.dataset.commessa = fase.commessa;
                const ds = calToDisplay(seg.startH);
                const de = calToDisplay(seg.endH);
                bar.style.left = (ds * pxPerHour) + 'px';
                bar.style.width = Math.max((de - ds) * pxPerHour, 4) + 'px';
                bar.style.height = barH + 'px';
                bar.style.top = (4 + (fase.lane || 0) * laneH) + 'px';
                const segW = (de - ds) * pxPerHour;
                // Etichetta solo sul primo segmento (più lungo probabilmente)
                if (segIdx === 0) {
                    if (bw > 70) bar.innerHTML = `<span>${fase.commessa}</span>`;
                    else if (bw > 30) bar.innerHTML = `<span>${fase.commessa.slice(-6)}</span>`;
                }
                bar.addEventListener('mouseenter', e => { showTooltip(e, fase); highlightCommessa(fase.commessa); });
                bar.addEventListener('mouseleave', () => { hideTooltip(); highlightCommessa(null); });
                bar.addEventListener('mousemove', moveTooltip);
                bar.addEventListener('click', () => { hideTooltip(); openSidePanel(fase.commessa); });
                timeline.appendChild(bar);
            });
        });

        row.appendChild(timeline);
        container.appendChild(row);
    });

    // Sezioni collassabili (Esterno, Spedizione) — default chiuse
    collapsables.forEach(mac => {
        if (!mac || mac.fasi.length === 0) return;
        const toggleRow = el('div', 'gantt-row', { cursor:'pointer', background:'#1a1a2e', borderTop:'2px solid #3a3a5c' });
        toggleRow.dataset.expanded = '0';
        const toggleLabel = el('div', 'gantt-label', { fontWeight:'700' });
        toggleLabel.innerHTML = `<div>▶ ${mac.nome} <div class="label-sub">${mac.fasi.length} fasi · click per espandere</div></div>`;
        toggleRow.appendChild(toggleLabel);
        const toggleTimeline = el('div', 'gantt-timeline', { width:totalWidth+'px', minHeight:'40px' });
        toggleRow.appendChild(toggleTimeline);
        container.appendChild(toggleRow);

        const innerContainer = el('div', '', { display:'none' });
        mac.fasi.forEach(fase => {
            const fRow = el('div', 'gantt-row', { background:'#0f0f1a' });
            const fLabel = el('div', 'gantt-label', { paddingLeft:'24px' });
            fLabel.innerHTML = `<div>${fase.commessa}<div class="label-sub">${fase.fase} · ${fase.cliente || ''}</div></div>`;
            fRow.appendChild(fLabel);
            const fTimeline = el('div', 'gantt-timeline', { width:totalWidth+'px', minHeight:'40px', position:'relative' });
            const bar = el('div', 'gantt-bar ' + getBarClass(fase));
            const dispStart = calToDisplay(fase.start_h || 0);
            const dispEnd = calToDisplay(fase.end_h || fase.ore_effettive || 1);
            const bw = Math.max((dispEnd - dispStart) * pxPerHour, 90);
            bar.style.left = (dispStart * pxPerHour) + 'px';
            bar.style.width = bw + 'px';
            bar.style.height = '30px';
            bar.style.top = '6px';
            if (bw > 90) bar.innerHTML = `<span>${fase.commessa}</span>`;
            else bar.innerHTML = `<span>${fase.commessa.slice(-7)}</span>`;
            bar.addEventListener('mouseenter', e => showTooltip(e, fase));
            bar.addEventListener('mouseleave', hideTooltip);
            bar.addEventListener('mousemove', moveTooltip);
            bar.addEventListener('click', () => { hideTooltip(); openSidePanel(fase.commessa); });
            fTimeline.appendChild(bar);
            fRow.appendChild(fTimeline);
            innerContainer.appendChild(fRow);
        });
        container.appendChild(innerContainer);

        toggleRow.addEventListener('click', () => {
            const expanded = toggleRow.dataset.expanded === '1';
            toggleRow.dataset.expanded = expanded ? '0' : '1';
            innerContainer.style.display = expanded ? 'none' : 'block';
            toggleLabel.innerHTML = `<div>${expanded ? '▶' : '▼'} ${mac.nome} <div class="label-sub">${mac.fasi.length} fasi · click per ${expanded ? 'espandere' : 'chiudere'}</div></div>`;
        });
    });
}

// ===================== GANTT PER COMMESSA =====================

function renderGanttCommessa() {
    const filtered = filterData(DATA);
    const commesse = schedulaPerCommessa(filtered);
    const container = document.getElementById('ganttCommessa');
    container.innerHTML = '';

    if (commesse.length === 0) {
        container.innerHTML = '<div class="empty-state"><div class="empty-icon">&#128203;</div><p>Nessuna commessa da schedulare</p></div>';
        return;
    }

    const maxCalOre = Math.max(...commesse.map(c => c.ore_totali), 10);
    const maxDisplayOre = calToDisplay(maxCalOre);
    const totalWidth = Math.max(maxDisplayOre * pxPerHour, 800);

    // Header
    const headerRow = el('div', 'gantt-row', {
        position:'sticky', top:'0', zIndex:'10',
        background:'#16162e',
        borderBottom:'2px solid #3a3a5c', minHeight:'48px'
    });
    const headerLabel = el('div', 'gantt-label gantt-label-comm', { fontWeight:'700', fontSize:'13px', color:'#8c8caa', textTransform:'uppercase', letterSpacing:'0.8px', background:'#16162e' });
    headerLabel.textContent = 'Commessa';
    headerRow.appendChild(headerLabel);

    const timeHeader = el('div', 'gantt-timeline', { width:totalWidth+'px', position:'relative' });
    renderDayHeaders(timeHeader, maxCalOre);
    headerRow.appendChild(timeHeader);
    container.appendChild(headerRow);

    const colors = ['#0d6efd','#6f42c1','#20c997','#fd7e14','#e83e8c','#0dcaf0','#6610f2','#198754','#d63384','#ffc107'];

    commesse.forEach(comm => {
        const row = el('div', 'gantt-row');
        const label = el('div', 'gantt-label gantt-label-comm');
        const giorniStr = comm.giorni_consegna !== null
            ? (comm.giorni_consegna < 0
                ? `<span style="color:#dc3545;font-weight:700">${comm.giorni_consegna}gg</span>`
                : `<span style="color:#198754">${comm.giorni_consegna}gg</span>`)
            : '';
        label.innerHTML = `<div>
            <div class="comm-title">${comm.commessa}</div>
            <div class="comm-sub">${(comm.cliente||'').substring(0,25)} &middot; ${giorniStr}</div>
        </div>`;
        label.style.cursor = 'pointer';
        label.addEventListener('click', () => openSidePanel(comm.commessa));
        row.appendChild(label);

        const timeline = el('div', 'gantt-timeline', { width:totalWidth+'px' });
        renderDayLines(timeline, maxCalOre, null);

        // Deadline marker
        if (comm.consegna) {
            const hToC = (new Date(comm.consegna) - NOW) / 3600000;
            const dispC = calToDisplay(hToC);
            if (dispC > 0 && dispC <= maxDisplayOre) {
                const marker = el('div', '', {
                    position:'absolute', top:'0', bottom:'0',
                    left:(dispC * pxPerHour)+'px',
                    borderLeft:'2px dashed #dc3545', zIndex:'3'
                });
                const flag = el('div', '', {
                    position:'absolute', top:'2px', left:'4px',
                    fontSize:'10px', color:'#dc3545', fontWeight:'800'
                });
                flag.textContent = 'Consegna';
                marker.appendChild(flag);
                timeline.appendChild(marker);
            }
        }

        comm.fasi.forEach((fase, idx) => {
            const dispStart = calToDisplay(fase.start_h);
            const dispEnd = calToDisplay(fase.end_h);
            const bar = el('div', 'gantt-bar');
            bar.style.background = colors[idx % colors.length];
            bar.style.left = (dispStart * pxPerHour) + 'px';
            bar.style.width = Math.max((dispEnd - dispStart) * pxPerHour, 4) + 'px';
            const bw = (dispEnd - dispStart) * pxPerHour;
            if (bw > 55) bar.innerHTML = `<span>${fase.fase}</span>`;
            bar.addEventListener('mouseenter', e => showTooltip(e, fase));
            bar.addEventListener('mouseleave', hideTooltip);
            bar.addEventListener('mousemove', moveTooltip);
            bar.addEventListener('click', () => { hideTooltip(); openSidePanel(fase.commessa); });
            timeline.appendChild(bar);
        });

        row.appendChild(timeline);
        container.appendChild(row);
    });
}

// ===================== TABELLA =====================

let prioFilter = 'all'; // 'all', 'ritardo', 'critico'

function renderTabella() {
    const macchine = filterScheduledMacchine(getFullSchedule());

    // Raggruppa fasi per commessa
    const commMap = {};
    macchine.forEach(m => m.fasi.forEach(f => {
        if (!commMap[f.commessa]) commMap[f.commessa] = {
            commessa: f.commessa, descrizione: f.descrizione, cliente: f.cliente,
            consegna: f.consegna, giorni_consegna: f.giorni_consegna,
            fasi: [], ore_tot: 0, max_end_h: 0, min_priorita: Infinity
        };
        const c = commMap[f.commessa];
        c.fasi.push(f);
        c.ore_tot += (f.ore_effettive || f.ore);
        if (f.end_h > c.max_end_h) c.max_end_h = f.end_h;
        if (f.priorita < c.min_priorita) c.min_priorita = f.priorita;
    }));

    // Calcola margine e stato per ogni commessa
    let ordini = Object.values(commMap).map(c => {
        c.ore_tot = Math.round(c.ore_tot * 100) / 100;
        const fineStimata = new Date(NOW.getTime() + c.max_end_h * 3600000);
        c.fine_stimata = fineStimata;

        if (c.consegna) {
            const consegnaDate = new Date(c.consegna);
            consegnaDate.setHours(0, 0, 0, 0);
            c.margine_h = Math.round((consegnaDate - fineStimata) / 3600000 * 10) / 10;
        } else {
            c.margine_h = null;
        }

        // Stato: critico (consegna superata da ≥3gg), ritardo (fine stimata > consegna), in tempo
        if (c.giorni_consegna !== null && c.giorni_consegna <= -3) c.stato_label = 'critico';
        else if (c.margine_h !== null && c.margine_h < 0) c.stato_label = 'ritardo';
        else c.stato_label = 'ok';

        // Priorita display = min priorita delle fasi (arrotondato)
        c.priorita_display = Math.round(c.min_priorita);
        return c;
    });

    // Ordina per priorita (più basso = più urgente)
    ordini.sort((a, b) => a.min_priorita - b.min_priorita);

    // Filtri
    const totale = ordini.length;
    const ritardo = ordini.filter(o => o.stato_label === 'ritardo');
    const critico = ordini.filter(o => o.stato_label === 'critico');

    if (prioFilter === 'ritardo') ordini = ritardo;
    else if (prioFilter === 'critico') ordini = critico;

    document.getElementById('prioCountAll').textContent = totale;

    // Aggiorna bottoni attivi
    document.querySelectorAll('.prio-btn').forEach(b => b.classList.remove('active'));
    document.getElementById(prioFilter === 'ritardo' ? 'prioRitardo' : prioFilter === 'critico' ? 'prioCritico' : 'prioAll').classList.add('active');

    const tbody = document.getElementById('tabellaBody');
    tbody.innerHTML = '';

    ordini.forEach(c => {
        const tr = document.createElement('tr');

        // Badge priorità
        const pBadgeClass = c.stato_label === 'critico' ? 'prio-badge-red'
            : c.stato_label === 'ritardo' ? 'prio-badge-orange' : 'prio-badge-green';

        // Margine
        let margineHtml = '-';
        if (c.margine_h !== null) {
            const margClass = c.margine_h < 0 ? 'prio-margine-neg'
                : c.margine_h <= 24 ? 'prio-margine-warn' : 'prio-margine-ok';
            margineHtml = `<span class="prio-margine ${margClass}">${c.margine_h}h</span>`;
        }

        // Stato badge
        let statoHtml;
        if (c.stato_label === 'ritardo') {
            const ritH = Math.round(Math.abs(c.margine_h) * 10) / 10;
            statoHtml = `<span class="prio-stato prio-stato-ritardo">RITARDO +${ritH}h</span>`;
        } else if (c.stato_label === 'critico') {
            statoHtml = `<span class="prio-stato prio-stato-critico">CRITICO</span>`;
        } else {
            statoHtml = `<span class="prio-stato prio-stato-ok">In tempo</span>`;
        }

        // Fine stimata
        const fineStr = c.fine_stimata.toLocaleDateString('it-IT', { day:'2-digit', month:'2-digit', year:'numeric' })
            + '<br>' + c.fine_stimata.toLocaleTimeString('it-IT', { hour:'2-digit', minute:'2-digit' });

        tr.innerHTML = `
            <td><div class="prio-badge ${pBadgeClass}">${c.priorita_display}</div></td>
            <td class="prio-commessa">${c.commessa}</td>
            <td class="prio-prodotto" title="${c.descrizione}">${c.descrizione.length > 30 ? c.descrizione.substring(0, 28) + '...' : c.descrizione}</td>
            <td class="prio-cliente">${c.cliente}</td>
            <td>${formatDate(c.consegna)}</td>
            <td style="font-weight:700">${c.ore_tot}h</td>
            <td>${margineHtml}</td>
            <td style="text-align:center;font-weight:700;font-size:15px">${c.fasi.length}</td>
            <td style="font-size:12px;line-height:1.4">${fineStr}</td>
            <td>${statoHtml}</td>
        `;
        tr.style.cursor = 'pointer';
        tr.addEventListener('click', () => openSidePanel(c.commessa));
        tbody.appendChild(tr);
    });
}

// Filtri tabella priorità
document.getElementById('prioAll').onclick = () => { prioFilter = 'all'; renderTabella(); };
document.getElementById('prioRitardo').onclick = () => { prioFilter = 'ritardo'; renderTabella(); };
document.getElementById('prioCritico').onclick = () => { prioFilter = 'critico'; renderTabella(); };

// ===================== FILTERS =====================

function renderFilterChips() {
    const reparti = [...new Set(DATA.map(f => f.reparto))].sort();
    const container = document.getElementById('filterReparti');
    container.innerHTML = '';

    const allChip = document.createElement('span');
    allChip.className = 'filter-chip' + (filtroReparti.size === 0 ? ' active' : '');
    allChip.textContent = 'Tutti';
    allChip.onclick = () => { filtroReparti.clear(); renderAll(); };
    container.appendChild(allChip);

    reparti.forEach(rep => {
        const chip = document.createElement('span');
        chip.className = 'filter-chip' + (filtroReparti.has(rep) ? ' active' : '');
        chip.textContent = rep;
        chip.onclick = () => {
            if (filtroReparti.has(rep)) filtroReparti.delete(rep);
            else filtroReparti.add(rep);
            renderAll();
        };
        container.appendChild(chip);
    });
}

// ===================== TABS & CONTROLS =====================

document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('tab-' + this.dataset.tab).classList.add('active');
    });
});

document.getElementById('zoomIn').onclick = () => { pxPerHour = Math.min(50, pxPerHour + 2); updateZoom(); };
document.getElementById('zoomOut').onclick = () => { pxPerHour = Math.max(2, pxPerHour - 2); updateZoom(); };
document.getElementById('zoomInC').onclick = () => { pxPerHour = Math.min(50, pxPerHour + 2); updateZoom(); };
document.getElementById('zoomOutC').onclick = () => { pxPerHour = Math.max(2, pxPerHour - 2); updateZoom(); };

function updateZoom() {
    document.getElementById('zoomLabel').textContent = pxPerHour + ' px/h';
    document.getElementById('zoomLabelC').textContent = pxPerHour + ' px/h';
    renderAll();
}

document.getElementById('searchGlobal').addEventListener('input', function() {
    searchQuery = this.value.trim();
    renderAll();
});

// ===================== SIDE PANEL =====================

function openSidePanel(commessaId) {
    // Raccogli tutte le fasi di questa commessa dallo scheduling completo
    const macchine = getFullSchedule();
    const allFasi = [];
    macchine.forEach(m => m.fasi.forEach(f => { if (f.commessa === commessaId) allFasi.push(f); }));

    if (allFasi.length === 0) return;

    const prima = allFasi[0];
    const oreTot = Math.round(allFasi.reduce((s, f) => s + (f.ore_effettive || f.ore), 0) * 100) / 100;
    const maxEndH = Math.max(...allFasi.map(f => f.end_h));
    const fineStimata = new Date(NOW.getTime() + maxEndH * 3600000);

    let margineH = null;
    let statoLabel = 'ok';
    if (prima.consegna) {
        const consDate = new Date(prima.consegna);
        consDate.setHours(0, 0, 0, 0);
        margineH = Math.round((consDate - fineStimata) / 3600000 * 10) / 10;
    }
    if (prima.giorni_consegna !== null && prima.giorni_consegna <= -5) statoLabel = 'critico';
    else if (margineH !== null && margineH < 0) statoLabel = 'ritardo';

    // Header
    document.getElementById('spCommessa').textContent = commessaId;
    document.getElementById('spCliente').textContent = prima.cliente;
    document.getElementById('spProdotto').textContent = prima.descrizione;

    // Info grid
    const statoColors = { ok: '#3cc07e', ritardo: '#f0a04b', critico: '#ff6b7a' };
    const statoNames = { ok: 'In tempo', ritardo: 'Ritardo', critico: 'Critico' };

    document.getElementById('spInfoGrid').innerHTML = `
        <div class="sp-info-card">
            <div class="sp-info-label">Consegna</div>
            <div class="sp-info-val" style="color:#c8c8e0;font-size:15px">${formatDate(prima.consegna)}</div>
        </div>
        <div class="sp-info-card">
            <div class="sp-info-label">Giorni</div>
            <div class="sp-info-val" style="color:${prima.giorni_consegna < 0 ? '#ff6b7a' : '#3cc07e'}">${prima.giorni_consegna !== null ? prima.giorni_consegna + 'gg' : '-'}</div>
        </div>
        <div class="sp-info-card">
            <div class="sp-info-label">Ore totali</div>
            <div class="sp-info-val" style="color:#5a9cff">${oreTot}h</div>
        </div>
        <div class="sp-info-card">
            <div class="sp-info-label">Margine</div>
            <div class="sp-info-val" style="color:${margineH !== null && margineH < 0 ? '#ff6b7a' : margineH !== null && margineH <= 24 ? '#f0a04b' : '#3cc07e'}">${margineH !== null ? margineH + 'h' : '-'}</div>
        </div>
        <div class="sp-info-card">
            <div class="sp-info-label">Fine stimata</div>
            <div class="sp-info-val" style="color:#c8c8e0;font-size:13px">${fineStimata.toLocaleDateString('it-IT', {day:'2-digit',month:'2-digit',year:'numeric'})} ${fineStimata.toLocaleTimeString('it-IT', {hour:'2-digit',minute:'2-digit'})}</div>
        </div>
        <div class="sp-info-card">
            <div class="sp-info-label">Stato</div>
            <div class="sp-info-val" style="color:${statoColors[statoLabel]}">${statoNames[statoLabel]}</div>
        </div>
    `;

    // Fasi
    const statoNomi = ['Caricato', 'Pronto', 'Avviato', 'Terminato', 'Consegnato'];
    allFasi.sort((a, b) => a.start_h - b.start_h);

    document.getElementById('spFasiList').innerHTML = allFasi.map(f => `
        <div class="sp-fase-card">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <div>
                    <div class="sp-fase-name">${f.fase}</div>
                    <div class="sp-fase-reparto">${f.reparto} &middot; ${getTurnoLabel(f.reparto)}</div>
                </div>
                <span class="sp-stato-badge sp-stato-${f.stato}">${statoNomi[f.stato] || '?'}</span>
            </div>
            <div class="sp-fase-row">
                <span class="sp-fase-detail">Ore: <strong>${(f.ore_effettive || f.ore).toFixed(1)}h</strong> ${f.ore_prinect ? '<small style="color:#5dd39e">(P)</small>' : f.ore_media_storica ? '<small style="color:#f0a04b">(S)</small>' : ''}</span>
                <span class="sp-fase-detail">Inizio: <strong>${oreToDateStr(f.start_h)}</strong></span>
            </div>
            <div class="sp-fase-row">
                <span class="sp-fase-detail">Fine: <strong>${oreToDateStr(f.end_h)}</strong></span>
                <span class="sp-fase-detail">Qta: <strong>${f.qta_carta || '-'}</strong></span>
            </div>
        </div>
    `).join('');

    // Apri
    document.getElementById('sidePanel').classList.add('open');
    document.getElementById('sideOverlay').classList.add('open');
}

function closeSidePanel() {
    document.getElementById('sidePanel').classList.remove('open');
    document.getElementById('sideOverlay').classList.remove('open');
}

document.getElementById('spClose').onclick = closeSidePanel;
document.getElementById('sideOverlay').onclick = closeSidePanel;
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeSidePanel(); });

// ===================== SCROLLBAR ORIZZONTALE FISSA =====================

const hScroll = document.getElementById('ganttHScroll');
const hScrollInner = document.getElementById('ganttHScrollInner');
let activeGanttWrapper = null;
let syncing = false;

function syncHScroll() {
    // Trova il wrapper Gantt attivo (tab visibile)
    const activeTab = document.querySelector('.tab-content.active');
    if (!activeTab) { hScroll.style.display = 'none'; return; }

    const wrapper = activeTab.querySelector('.gantt-wrapper');
    if (!wrapper || wrapper.scrollWidth <= wrapper.clientWidth) {
        hScroll.style.display = 'none';
        return;
    }

    // Aggiorna la larghezza interna e la posizione della scrollbar
    hScroll.style.display = 'block';
    // Allinea la larghezza della scrollbar fissa al wrapper
    const rect = wrapper.getBoundingClientRect();
    hScroll.style.left = rect.left + 'px';
    hScroll.style.width = rect.width + 'px';
    hScrollInner.style.width = wrapper.scrollWidth + 'px';

    // Se il wrapper è cambiato, ricollega gli eventi
    if (activeGanttWrapper !== wrapper) {
        if (activeGanttWrapper) {
            activeGanttWrapper.removeEventListener('scroll', onWrapperScroll);
        }
        activeGanttWrapper = wrapper;
        activeGanttWrapper.addEventListener('scroll', onWrapperScroll);
        hScroll.scrollLeft = wrapper.scrollLeft;
    }
}

function onWrapperScroll() {
    if (syncing) return;
    syncing = true;
    hScroll.scrollLeft = activeGanttWrapper.scrollLeft;
    syncing = false;
}

hScroll.addEventListener('scroll', function() {
    if (syncing || !activeGanttWrapper) return;
    syncing = true;
    activeGanttWrapper.scrollLeft = hScroll.scrollLeft;
    syncing = false;
});

// ===================== QUICK FILTERS =====================
document.querySelectorAll('.qf-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.qf-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        quickFilter = btn.dataset.qf;
        renderAll();
    });
});

// ===================== INIT =====================

function renderAll() {
    // Salva posizione scroll orizzontale prima di ricostruire il DOM
    const wrappers = document.querySelectorAll('.gantt-wrapper');
    const savedScroll = Array.from(wrappers).map(w => w.scrollLeft);

    renderKPI();
    renderFilterChips();
    renderGanttMacchina();
    renderGanttCommessa();
    renderTabella();

    // Ripristina posizione scroll dopo il render
    requestAnimationFrame(() => {
        const newWrappers = document.querySelectorAll('.gantt-wrapper');
        newWrappers.forEach((w, i) => {
            if (savedScroll[i]) w.scrollLeft = savedScroll[i];
        });
        syncHScroll();
    });
}

// Aggiorna scrollbar al cambio tab
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        activeGanttWrapper = null;
        requestAnimationFrame(syncHScroll);
    });
});

// Aggiorna scrollbar al resize
window.addEventListener('resize', () => requestAnimationFrame(syncHScroll));

renderAll();
</script>
@endsection
