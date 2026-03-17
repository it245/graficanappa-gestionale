<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>MES Grafica Nappa — Owner Dashboard (Nuova UI)</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --sidebar-w: 220px;
    --sidebar-bg: #1e293b;
    --sidebar-hover: #334155;
    --sidebar-active: #2563eb;
    --topbar-h: 48px;
    --accent: #2563eb;
    --success: #16a34a;
    --warning: #d97706;
    --danger: #dc2626;
    --info: #0891b2;
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Inter', -apple-system, sans-serif; background: #f1f5f9; }

/* SIDEBAR */
.proto-sidebar {
    position: fixed; top: 0; left: 0; bottom: 0;
    width: var(--sidebar-w); background: var(--sidebar-bg); color: #fff;
    display: flex; flex-direction: column; z-index: 200;
    overflow-y: auto;
}
.proto-sidebar .logo {
    padding: 16px 18px; font-size: 15px; font-weight: 700;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    display: flex; align-items: center; gap: 8px;
}
.proto-sidebar .logo .dot { width: 8px; height: 8px; border-radius: 50%; background: var(--success); }
.proto-sidebar .nav-section { padding: 14px 0 2px 18px; font-size: 9px; text-transform: uppercase; letter-spacing: 1.5px; color: #475569; font-weight: 600; }
.proto-sidebar a {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 18px; color: #94a3b8; text-decoration: none;
    font-size: 12px; font-weight: 500; border-left: 3px solid transparent;
}
.proto-sidebar a:hover { background: var(--sidebar-hover); color: #fff; }
.proto-sidebar a.active { background: rgba(37,99,235,0.15); color: #fff; border-left-color: var(--sidebar-active); }
.proto-sidebar a svg { width: 16px; height: 16px; flex-shrink: 0; }
.proto-sidebar .footer { padding: 12px 18px; font-size: 10px; color: #475569; border-top: 1px solid rgba(255,255,255,0.08); margin-top: auto; }

/* TOPBAR */
.proto-topbar {
    position: fixed; top: 0; left: var(--sidebar-w); right: 0; height: var(--topbar-h);
    background: #fff; border-bottom: 1px solid #e2e8f0; z-index: 150;
    display: flex; align-items: center; justify-content: space-between; padding: 0 20px;
}
.proto-topbar .title { font-size: 14px; font-weight: 600; }
.proto-topbar .right { display: flex; align-items: center; gap: 14px; font-size: 12px; color: #64748b; }

/* KPI */
.kpi-strip { display: flex; gap: 12px; padding: 12px 16px; background: #f1f5f9; }
.kpi-card {
    flex: 1; min-width: 150px; background: #fff; border-radius: 10px; padding: 12px 14px;
    border: 1px solid #e2e8f0; border-left: 4px solid var(--accent);
}
.kpi-card.green { border-left-color: var(--success); }
.kpi-card.amber { border-left-color: var(--warning); }
.kpi-card.cyan { border-left-color: var(--info); }
.kpi-label { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; }
.kpi-value { font-size: 22px; font-weight: 700; }
.kpi-sub { font-size: 10px; color: #94a3b8; }

/* MAIN */
.proto-main {
    margin-left: var(--sidebar-w);
    margin-top: var(--topbar-h);
}
.proto-main iframe {
    width: 100%; border: none;
    height: calc(100vh - var(--topbar-h) - 76px);
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<div class="proto-sidebar">
    <div class="logo"><div class="dot"></div> MES Grafica Nappa</div>

    <div class="nav-section">Principale</div>
    <a href="{{ route('owner.dashboard') }}?proto=1" class="active">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
        Dashboard
    </a>
    <a href="{{ route('owner.fasiTerminate') }}" target="dashframe">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><path d="m9 12 2 2 4-4"/></svg>
        Fasi Terminate
    </a>

    <div class="nav-section">Produzione</div>
    <a href="{{ route('owner.scheduling') }}" target="dashframe">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
        Scheduling
    </a>
    <a href="{{ route('owner.reportOre') }}" target="dashframe">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
        Report Ore
    </a>
    <a href="{{ route('owner.esterne') }}" target="dashframe">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 12a9 9 0 0 1-9 9m9-9a9 9 0 0 0-9-9m9 9H3m9 9a9 9 0 0 1-9-9m9 9c1.66 0 3-4.03 3-9s-1.34-9-3-9"/></svg>
        Lav. Esterne
    </a>

    <div class="nav-section">Macchine</div>
    <a href="{{ route('mes.prinect') }}" target="dashframe">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
        Prinect XL106
    </a>
    <a href="{{ route('mes.fiery') }}" target="dashframe">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M8 12h8M12 8v8"/></svg>
        Fiery V900
    </a>

    <div class="nav-section">Logistica</div>
    <a href="#" target="dashframe">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
        Spedizioni BRT
    </a>
    <a href="{{ route('owner.presenze') }}" target="dashframe">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        Presenze
    </a>

    <div class="footer">
        MES v3.0 &middot; {{ now()->format('d/m/Y') }}<br>
        <a href="{{ route('owner.dashboard') }}" style="color:#64748b; font-size:10px;">Vista classica</a>
    </div>
</div>

<!-- TOPBAR -->
<div class="proto-topbar">
    <div class="title">Dashboard Owner</div>
    <div class="right">
        <span>{{ now()->format('H:i') }}</span>
        <div style="width:28px; height:28px; border-radius:50%; background:var(--accent); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:12px;">GN</div>
    </div>
</div>

<!-- MAIN -->
<div class="proto-main">
    <!-- KPI STRIP -->
    <div class="kpi-strip">
        <div class="kpi-card green">
            <div class="kpi-label">Completate Oggi</div>
            <div class="kpi-value">{{ $fasiCompletateOggi }}</div>
            <div class="kpi-sub">fasi terminate</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">In Lavorazione</div>
            <div class="kpi-value">{{ $fasiAttive }}</div>
            <div class="kpi-sub">fasi avviate</div>
        </div>
        <div class="kpi-card amber">
            <div class="kpi-label">Ore Lavorate</div>
            <div class="kpi-value">{{ $oreLavorateOggi }}h</div>
            <div class="kpi-sub">oggi</div>
        </div>
        <div class="kpi-card cyan">
            <div class="kpi-label">Consegnate</div>
            <div class="kpi-value">{{ $commesseSpediteOggi }}</div>
            <div class="kpi-sub">spedizioni oggi</div>
        </div>
    </div>

    <!-- IFRAME con la dashboard attuale -->
    <iframe src="{{ route('owner.dashboard') }}" name="dashframe"></iframe>
</div>

<script>
// Highlight sidebar link attivo basato su iframe URL
document.querySelectorAll('.proto-sidebar a[target="dashframe"]').forEach(function(a) {
    a.addEventListener('click', function() {
        document.querySelectorAll('.proto-sidebar a').forEach(function(el) { el.classList.remove('active'); });
        this.classList.add('active');
    });
});
</script>
</body>
</html>
