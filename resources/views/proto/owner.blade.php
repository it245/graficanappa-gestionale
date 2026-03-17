<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MES Grafica Nappa — Owner Dashboard (Proto)</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
    --bg: #f1f5f9;
    --sidebar: #1e293b;
    --sidebar-hover: #334155;
    --sidebar-active: #2563eb;
    --card: #ffffff;
    --border: #e2e8f0;
    --text: #1e293b;
    --text-secondary: #64748b;
    --accent: #2563eb;
    --success: #16a34a;
    --warning: #d97706;
    --danger: #dc2626;
    --info: #0891b2;
    --purple: #7c3aed;
}

* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Inter', -apple-system, sans-serif; background: var(--bg); color: var(--text); }

/* === SIDEBAR === */
.sidebar {
    position: fixed; top: 0; left: 0; bottom: 0;
    width: 240px; background: var(--sidebar); color: #fff;
    display: flex; flex-direction: column; z-index: 100;
    transition: width 0.2s;
}
.sidebar-logo {
    padding: 20px; font-size: 18px; font-weight: 700;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    display: flex; align-items: center; gap: 10px;
}
.sidebar-logo .dot { width: 10px; height: 10px; border-radius: 50%; background: var(--success); }
.sidebar-nav { flex: 1; padding: 12px 0; overflow-y: auto; }
.sidebar-nav a {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 20px; color: #94a3b8; text-decoration: none;
    font-size: 13px; font-weight: 500; border-left: 3px solid transparent;
    transition: all 0.15s;
}
.sidebar-nav a:hover { background: var(--sidebar-hover); color: #fff; }
.sidebar-nav a.active { background: rgba(37,99,235,0.15); color: #fff; border-left-color: var(--sidebar-active); }
.sidebar-nav .section-label {
    padding: 16px 20px 6px; font-size: 10px; text-transform: uppercase;
    letter-spacing: 1.5px; color: #475569; font-weight: 600;
}
.sidebar-nav svg { width: 18px; height: 18px; flex-shrink: 0; }
.sidebar-footer {
    padding: 16px 20px; border-top: 1px solid rgba(255,255,255,0.1);
    font-size: 11px; color: #475569;
}

/* === MAIN === */
.main { margin-left: 240px; min-height: 100vh; }
.topbar {
    background: var(--card); border-bottom: 1px solid var(--border);
    padding: 12px 24px; display: flex; justify-content: space-between; align-items: center;
    position: sticky; top: 0; z-index: 50;
}
.topbar-title { font-size: 16px; font-weight: 600; }
.topbar-right { display: flex; align-items: center; gap: 16px; }
.topbar-badge {
    background: var(--danger); color: #fff; font-size: 10px; font-weight: 700;
    padding: 2px 6px; border-radius: 10px; position: relative; top: -8px; left: -8px;
}

.content { padding: 24px; }

/* === KPI CARDS === */
.kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
.kpi-card {
    background: var(--card); border-radius: 12px; padding: 20px;
    border: 1px solid var(--border); position: relative; overflow: hidden;
}
.kpi-card::before {
    content: ''; position: absolute; top: 0; left: 0; bottom: 0;
    width: 4px; border-radius: 12px 0 0 12px;
}
.kpi-card.blue::before { background: var(--accent); }
.kpi-card.green::before { background: var(--success); }
.kpi-card.amber::before { background: var(--warning); }
.kpi-card.red::before { background: var(--danger); }
.kpi-card.cyan::before { background: var(--info); }
.kpi-card.purple::before { background: var(--purple); }
.kpi-label { font-size: 12px; color: var(--text-secondary); font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
.kpi-value { font-size: 28px; font-weight: 700; margin: 4px 0; }
.kpi-sub { font-size: 12px; color: var(--text-secondary); }

/* === REPARTI GRID === */
.reparti-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; margin-bottom: 24px; }
.reparto-card {
    background: var(--card); border: 1px solid var(--border); border-radius: 10px;
    padding: 16px; cursor: pointer; transition: box-shadow 0.2s;
}
.reparto-card:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
.reparto-nome { font-size: 13px; font-weight: 600; margin-bottom: 8px; }
.reparto-stats { display: flex; gap: 12px; font-size: 12px; color: var(--text-secondary); }
.reparto-stats .num { font-weight: 700; color: var(--text); }
.reparto-bar { height: 4px; background: #e2e8f0; border-radius: 2px; margin-top: 10px; overflow: hidden; }
.reparto-bar-fill { height: 100%; border-radius: 2px; transition: width 0.5s; }

/* === TABLE === */
.data-table-wrap {
    background: var(--card); border: 1px solid var(--border); border-radius: 12px;
    overflow: hidden;
}
.data-table-header {
    padding: 16px 20px; border-bottom: 1px solid var(--border);
    display: flex; justify-content: space-between; align-items: center;
}
.data-table-header h3 { font-size: 15px; font-weight: 600; }
.data-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.data-table thead th {
    padding: 10px 12px; text-align: left; font-weight: 600; font-size: 11px;
    text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-secondary);
    background: #f8fafc; border-bottom: 1px solid var(--border);
}
.data-table tbody td {
    padding: 10px 12px; border-bottom: 1px solid #f1f5f9;
}
.data-table tbody tr:hover td { background: #f8fafc; }
.data-table tbody tr:last-child td { border-bottom: none; }

/* Status badges */
.badge-status {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600;
}
.badge-caricato { background: #f1f5f9; color: #64748b; }
.badge-pronto { background: #dbeafe; color: #1d4ed8; }
.badge-avviato { background: #fef3c7; color: #92400e; }
.badge-terminato { background: #dcfce7; color: #166534; }

/* Progress */
.progress-mini {
    height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden; min-width: 80px;
}
.progress-mini-fill { height: 100%; border-radius: 3px; }

/* Stepper */
.phase-stepper { display: flex; align-items: center; gap: 2px; }
.phase-dot {
    width: 8px; height: 8px; border-radius: 50%;
}
.phase-dot.done { background: var(--success); }
.phase-dot.active { background: var(--warning); }
.phase-dot.pending { background: #e2e8f0; }

/* Dark mode toggle */
.dark-toggle {
    background: none; border: 1px solid var(--border); border-radius: 8px;
    padding: 6px 10px; cursor: pointer; font-size: 14px;
}

/* === DARK MODE === */
body.dark {
    --bg: #0f172a;
    --sidebar: #0c1222;
    --sidebar-hover: #1e293b;
    --card: #1e293b;
    --border: #334155;
    --text: #f1f5f9;
    --text-secondary: #94a3b8;
}
body.dark .topbar { background: #1e293b; }
body.dark .data-table thead th { background: #0f172a; }
body.dark .data-table tbody tr:hover td { background: rgba(255,255,255,0.03); }
body.dark .data-table tbody td { border-bottom-color: #334155; }
body.dark .badge-caricato { background: #334155; color: #94a3b8; }
body.dark .badge-pronto { background: rgba(37,99,235,0.2); color: #60a5fa; }
body.dark .badge-avviato { background: rgba(217,119,6,0.2); color: #fbbf24; }
body.dark .badge-terminato { background: rgba(22,163,74,0.2); color: #4ade80; }
body.dark .reparto-bar { background: #334155; }
body.dark .progress-mini { background: #334155; }
</style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="sidebar-logo">
        <div class="dot"></div>
        MES Grafica Nappa
    </div>
    <nav class="sidebar-nav">
        <div class="section-label">Principale</div>
        <a href="#" class="active">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
            Dashboard
        </a>
        <a href="{{ route('owner.fasiTerminate') }}">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><path d="M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2"/><path d="m9 12 2 2 4-4"/></svg>
            Fasi Terminate
        </a>

        <div class="section-label">Produzione</div>
        <a href="{{ route('owner.scheduling') }}">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            Scheduling
        </a>
        <a href="{{ route('owner.reportOre') }}">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
            Report Ore
        </a>
        <a href="{{ route('owner.esterne') }}">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 12a9 9 0 0 1-9 9m9-9a9 9 0 0 0-9-9m9 9H3m9 9a9 9 0 0 1-9-9m9 9c1.66 0 3-4.03 3-9s-1.34-9-3-9m0 18c-1.66 0-3-4.03-3-9s1.34-9 3-9"/></svg>
            Lav. Esterne
        </a>

        <div class="section-label">Macchine</div>
        <a href="{{ route('mes.prinect') }}">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
            Prinect XL106
        </a>
        <a href="{{ route('mes.fiery') }}">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6.5 6.5 17.5 17.5M6.5 17.5 17.5 6.5"/><circle cx="12" cy="12" r="10"/></svg>
            Fiery V900
        </a>

        <div class="section-label">Logistica</div>
        <a href="#">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
            Spedizioni BRT
        </a>
        <a href="{{ route('owner.presenze') }}">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Presenze
        </a>
    </nav>
    <div class="sidebar-footer">
        MES v3.0 Proto &middot; {{ now()->format('d/m/Y') }}
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main">
    <!-- TOPBAR -->
    <div class="topbar">
        <div class="topbar-title">Dashboard Owner</div>
        <div class="topbar-right">
            <button class="dark-toggle" onclick="document.body.classList.toggle('dark')">
                <span id="themeIcon">&#9789;</span>
            </button>
            <span style="font-size:13px; color:var(--text-secondary);">{{ now()->format('H:i') }}</span>
            <div style="width:32px; height:32px; border-radius:50%; background:var(--accent); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:13px;">GN</div>
        </div>
    </div>

    <div class="content">
        <!-- KPI CARDS -->
        <div class="kpi-grid">
            <div class="kpi-card green">
                <div class="kpi-label">Fasi Completate Oggi</div>
                <div class="kpi-value">{{ $fasiOggi }}</div>
                <div class="kpi-sub">terminate oggi</div>
            </div>
            <div class="kpi-card blue">
                <div class="kpi-label">In Lavorazione</div>
                <div class="kpi-value">{{ $fasiAvviate }}</div>
                <div class="kpi-sub">fasi avviate (stato 2)</div>
            </div>
            <div class="kpi-card amber">
                <div class="kpi-label">Ore Lavorate Oggi</div>
                <div class="kpi-value">{{ number_format($oreLavOggi, 1) }}h</div>
                <div class="kpi-sub">da Prinect + operatori</div>
            </div>
            <div class="kpi-card cyan">
                <div class="kpi-label">Spedizioni Oggi</div>
                <div class="kpi-value">{{ $spedizioniOggi }}</div>
                <div class="kpi-sub">consegnate BRT</div>
            </div>
        </div>

        <!-- REPARTI OVERVIEW -->
        <div style="margin-bottom:20px;">
            <h3 style="font-size:15px; font-weight:600; margin-bottom:12px;">Panoramica Reparti</h3>
            <div class="reparti-grid">
                @foreach($repartiOverview as $rep)
                @php
                    $pct = $rep['totale'] > 0 ? round($rep['attive'] / $rep['totale'] * 100) : 0;
                    $barColor = $rep['attive'] > 0 ? 'var(--accent)' : '#e2e8f0';
                @endphp
                <div class="reparto-card">
                    <div class="reparto-nome">{{ ucfirst($rep['nome']) }}</div>
                    <div class="reparto-stats">
                        <span><span class="num">{{ $rep['attive'] }}</span> attive</span>
                        <span><span class="num">{{ $rep['pronte'] }}</span> pronte</span>
                        <span><span class="num">{{ $rep['totale'] }}</span> tot</span>
                    </div>
                    <div class="reparto-bar">
                        <div class="reparto-bar-fill" style="width:{{ $pct }}%; background:{{ $barColor }};"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- COMMESSE URGENTI -->
        <div class="data-table-wrap">
            <div class="data-table-header">
                <h3>Commesse Prioritarie</h3>
                <span style="font-size:12px; color:var(--text-secondary);">{{ $commesseUrgenti->count() }} commesse</span>
            </div>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Commessa</th>
                            <th>Cliente</th>
                            <th>Descrizione</th>
                            <th>Fase Attuale</th>
                            <th>Reparto</th>
                            <th>Stato</th>
                            <th>Consegna</th>
                            <th>Fasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($commesseUrgenti as $fase)
                        @php
                            $ordine = $fase->ordine;
                            $consegna = $ordine->data_prevista_consegna ? \Carbon\Carbon::parse($ordine->data_prevista_consegna) : null;
                            $scaduta = $consegna && $consegna->lt(\Carbon\Carbon::today());
                            $statoBadge = ['badge-caricato','badge-pronto','badge-avviato','badge-terminato'];
                            $statoLabel = ['Caricato','Pronto','Avviato','Terminato'];

                            // Calcola fasi della commessa
                            $tutteFasi = $fasi->filter(fn($f) => ($f->ordine->commessa ?? '') === ($ordine->commessa ?? ''));
                            $totFasi = $tutteFasi->count();
                            $termFasi = $tutteFasi->where('stato', '>=', 3)->count();
                            $avvFasi = $tutteFasi->where('stato', 2)->count();
                            $pctFasi = $totFasi > 0 ? round($termFasi / $totFasi * 100) : 0;
                        @endphp
                        <tr>
                            <td><a href="{{ route('owner.dettaglioCommessa', $ordine->commessa) }}" style="color:var(--accent); font-weight:600; text-decoration:none;">{{ $ordine->commessa }}</a></td>
                            <td>{{ Str::limit($ordine->cliente_nome ?? '-', 25) }}</td>
                            <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ Str::limit($ordine->descrizione ?? '-', 40) }}</td>
                            <td style="font-weight:500;">{{ $fase->faseCatalogo->nome_display ?? $fase->fase }}</td>
                            <td><span style="font-size:11px; color:var(--text-secondary);">{{ $fase->reparto_nome }}</span></td>
                            <td><span class="badge-status {{ $statoBadge[$fase->stato] ?? 'badge-caricato' }}">{{ $statoLabel[$fase->stato] ?? $fase->stato }}</span></td>
                            <td style="{{ $scaduta ? 'color:var(--danger); font-weight:600;' : '' }}">{{ $consegna ? $consegna->format('d/m') : '-' }}</td>
                            <td>
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <div class="progress-mini">
                                        @if($avvFasi > 0 && $pctFasi < 100)
                                        <div class="progress-mini-fill" style="width:{{ min($pctFasi + round($avvFasi/$totFasi*100), 100) }}%; background:var(--warning); position:absolute;"></div>
                                        @endif
                                        <div class="progress-mini-fill" style="width:{{ $pctFasi }}%; background:{{ $pctFasi >= 100 ? 'var(--success)' : 'var(--accent)' }};"></div>
                                    </div>
                                    <span style="font-size:11px; white-space:nowrap;">{{ $termFasi }}/{{ $totFasi }}</span>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- LINK ALLA DASHBOARD ATTUALE -->
        <div style="margin-top:24px; text-align:center;">
            <a href="{{ route('owner.dashboard') }}" style="color:var(--text-secondary); font-size:13px;">Vai alla dashboard attuale &rarr;</a>
        </div>
    </div>
</div>

<script>
// Dark mode persistence
if (localStorage.getItem('mes-dark') === '1') document.body.classList.add('dark');
document.querySelector('.dark-toggle').addEventListener('click', function() {
    localStorage.setItem('mes-dark', document.body.classList.contains('dark') ? '1' : '0');
    document.getElementById('themeIcon').textContent = document.body.classList.contains('dark') ? '\u2600' : '\u263D';
});
</script>
</body>
</html>
