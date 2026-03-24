<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>MES Grafica Nappa — Dashboard Produzione</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
html, body { height: 100%; overflow: hidden; }
body {
    font-family: 'Inter', -apple-system, sans-serif;
    background: #0a0f1a;
    color: #e2e8f0;
    -webkit-font-smoothing: antialiased;
    font-feature-settings: 'tnum';
}

.kiosk { display: grid; grid-template-rows: 64px 1fr; height: 100vh; }

/* === HEADER === */
.header {
    background: linear-gradient(135deg, #111827, #0a0f1a);
    border-bottom: 1px solid #1e293b;
    display: flex;
    align-items: center;
    padding: 0 28px;
    gap: 40px;
}
.header-brand {
    display: flex;
    flex-direction: column;
    min-width: 180px;
}
.header-brand-name { font-size: 18px; font-weight: 800; color: #38bdf8; letter-spacing: 1px; }
.header-brand-sub { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: 2px; }

.header-kpis { display: flex; gap: 32px; flex: 1; justify-content: center; }
.hkpi { text-align: center; }
.hkpi-val { font-size: 26px; font-weight: 800; line-height: 1; }
.hkpi-lbl { font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: 0.08em; margin-top: 2px; }
.hkpi-green .hkpi-val { color: #4ade80; }
.hkpi-blue .hkpi-val { color: #38bdf8; }
.hkpi-amber .hkpi-val { color: #fbbf24; }
.hkpi-purple .hkpi-val { color: #a78bfa; }

.header-clock { text-align: right; min-width: 160px; }
.clock-time { font-size: 28px; font-weight: 800; color: #f1f5f9; }
.clock-date { font-size: 10px; color: #64748b; }

/* === GRID 4 ZONE === */
.zones {
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-template-rows: 1fr 1fr;
    gap: 2px;
    background: #1e293b;
}

.zone {
    background: #0f1629;
    padding: 16px 20px;
    overflow: hidden;
}

.zone-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}
.zone-title {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    display: flex;
    align-items: center;
    gap: 8px;
}
.zone-badge {
    font-size: 9px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 10px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* === ZONA 1: IN CORSO ORA === */
.z1 .zone-title { color: #fbbf24; }
.z1 .zone-badge { background: #16a34a; color: #fff; }

.macchina {
    display: flex;
    align-items: center;
    padding: 8px 12px;
    border-radius: 8px;
    margin-bottom: 4px;
    border-left: 3px solid #334155;
    background: #111827;
}
.macchina.attiva { border-left-color: #4ade80; }
.macchina.attesa { border-left-color: #475569; opacity: 0.5; }

.m-info-left { min-width: 110px; }
.m-nome { font-size: 12px; font-weight: 700; color: #f1f5f9; }
.m-stato { font-size: 9px; font-weight: 600; }
.m-stato.lav { color: #4ade80; }
.m-stato.att { color: #475569; }

.m-info-center { flex: 1; padding: 0 12px; }
.m-commessa { font-size: 12px; font-weight: 700; color: #38bdf8; }
.m-desc { font-size: 10px; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.m-cliente { font-size: 9px; color: #64748b; }

.m-info-right { text-align: right; min-width: 90px; }
.m-ore { font-size: 11px; color: #94a3b8; font-weight: 600; }
.m-bar { width: 70px; height: 4px; background: #1e293b; border-radius: 2px; margin-top: 4px; margin-left: auto; }
.m-bar-fill { height: 100%; border-radius: 2px; background: #38bdf8; }

/* === ZONA 2: PROSSIMI LAVORI === */
.z2 .zone-title { color: #94a3b8; }
.z2 .zone-badge { background: #2563eb; color: #fff; }

.coda-macchina { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; margin: 10px 0 4px; letter-spacing: 0.05em; }
.coda-macchina:first-child { margin-top: 0; }

.coda-item {
    display: flex;
    align-items: center;
    padding: 4px 8px;
    font-size: 11px;
}
.coda-num { color: #475569; min-width: 20px; font-weight: 600; }
.coda-desc { flex: 1; color: #cbd5e1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.coda-badge {
    font-size: 8px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 8px;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    margin-left: 8px;
    white-space: nowrap;
}
.coda-badge.verde { background: #065f46; color: #6ee7b7; }
.coda-badge.arancio { background: #92400e; color: #fcd34d; }
.coda-badge.rosso { background: #7f1d1d; color: #fca5a5; }
.coda-badge.blu { background: #1e3a5f; color: #93c5fd; }

/* === ZONA 3: OBIETTIVO GIORNALIERO === */
.z3 .zone-title { color: #f87171; }

.obiettivo-wrap {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: calc(100% - 30px);
}
.obj-pct { font-size: 14px; font-weight: 800; color: #4ade80; }
.obj-label { font-size: 11px; color: #64748b; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 8px; }
.obj-big {
    display: flex;
    align-items: baseline;
    gap: 4px;
}
.obj-num { font-size: 100px; font-weight: 900; color: #4ade80; line-height: 1; }
.obj-slash { font-size: 40px; color: #334155; font-weight: 300; }
.obj-target { font-size: 40px; color: #475569; font-weight: 300; }
.obj-bar { width: 80%; max-width: 300px; height: 8px; background: #1e293b; border-radius: 4px; margin: 12px 0; overflow: hidden; }
.obj-bar-fill { height: 100%; border-radius: 4px; background: linear-gradient(90deg, #4ade80, #22d3ee); }

.obj-stats { display: flex; gap: 24px; margin-top: 12px; }
.obj-stat { text-align: center; }
.obj-stat-val { font-size: 22px; font-weight: 800; color: #f1f5f9; }
.obj-stat-val.green { color: #4ade80; }
.obj-stat-val.blue { color: #38bdf8; }
.obj-stat-lbl { font-size: 8px; color: #64748b; text-transform: uppercase; letter-spacing: 0.08em; }

/* === ZONA 4: ORE SEGNATE OGGI === */
.z4 .zone-title { color: #38bdf8; }
.z4 .zone-badge { border-radius: 10px; font-size: 9px; font-weight: 700; padding: 3px 10px; }
.z4 .zone-badge.low { background: #7f1d1d; color: #fca5a5; }
.z4 .zone-badge.mid { background: #92400e; color: #fcd34d; }
.z4 .zone-badge.high { background: #065f46; color: #6ee7b7; }

.ore-row {
    display: flex;
    align-items: center;
    margin-bottom: 6px;
}
.ore-nome { font-size: 11px; font-weight: 600; color: #94a3b8; min-width: 110px; }
.ore-bar { flex: 1; height: 14px; background: #1e293b; border-radius: 7px; overflow: hidden; margin: 0 10px; }
.ore-fill { height: 100%; border-radius: 7px; }
.ore-fill.red { background: linear-gradient(90deg, #dc2626, #ef4444); }
.ore-fill.orange { background: linear-gradient(90deg, #d97706, #f59e0b); }
.ore-fill.green { background: linear-gradient(90deg, #16a34a, #4ade80); }
.ore-pct { font-size: 12px; font-weight: 700; min-width: 35px; text-align: right; }
.ore-pct.red { color: #f87171; }
.ore-pct.orange { color: #fbbf24; }
.ore-pct.green { color: #4ade80; }
</style>
</head>
<body>

<div class="kiosk">

<!-- HEADER -->
<div class="header">
    <div class="header-brand">
        <div class="header-brand-name">GRAFICA NAPPA</div>
        <div class="header-brand-sub">Dashboard Produzione</div>
    </div>
    <div class="header-kpis">
        <div class="hkpi hkpi-green"><div class="hkpi-val">{{ $kpi['completate'] }}</div><div class="hkpi-lbl">Completate oggi</div></div>
        <div class="hkpi hkpi-blue"><div class="hkpi-val">{{ $kpi['in_corso'] }}</div><div class="hkpi-lbl">In corso ora</div></div>
        <div class="hkpi hkpi-amber"><div class="hkpi-val">{{ $kpi['in_coda'] }}</div><div class="hkpi-lbl">Pronte in coda</div></div>
        <div class="hkpi hkpi-purple"><div class="hkpi-val">{{ $kpi['fustelle'] }}</div><div class="hkpi-lbl">Fustelle attive</div></div>
    </div>
    <div class="header-clock">
        <div class="clock-time" id="clock">--:--</div>
        <div class="clock-date" id="dateStr"></div>
    </div>
</div>

<!-- 4 ZONE -->
<div class="zones">

<!-- Z1: IN CORSO ORA -->
<div class="zone z1">
    <div class="zone-header">
        <div class="zone-title">⚡ In corso ora</div>
        <span class="zone-badge">LIVE</span>
    </div>
    @foreach($macchine as $m)
    <div class="macchina {{ $m['attiva'] ? 'attiva' : 'attesa' }}">
        <div class="m-info-left">
            <div class="m-nome">{{ $m['nome'] }}</div>
            <div class="m-stato {{ $m['attiva'] ? 'lav' : 'att' }}">● {{ $m['attiva'] ? 'IN LAVORAZIONE' : 'IN ATTESA' }}</div>
        </div>
        <div class="m-info-center">
            @if($m['attiva'])
                <div class="m-commessa">{{ $m['commessa'] }}</div>
                <div class="m-desc">{{ $m['descrizione'] }}</div>
                <div class="m-cliente">{{ $m['cliente'] }}</div>
            @else
                <div class="m-commessa">—</div>
                <div class="m-desc">In attesa prossimo lavoro</div>
            @endif
        </div>
        <div class="m-info-right">
            @if($m['attiva'])
                <div class="m-ore">{{ $m['ore_lav'] }}h / {{ $m['ore_prev'] }}h</div>
                @php $pct = $m['ore_prev'] > 0 ? min(round(($m['ore_lav'] / $m['ore_prev']) * 100), 100) : 0; @endphp
                <div class="m-bar"><div class="m-bar-fill" style="width:{{ $pct }}%"></div></div>
            @else
                <div class="m-ore">—</div>
                <div class="m-bar"><div class="m-bar-fill" style="width:0%"></div></div>
            @endif
        </div>
    </div>
    @endforeach
</div>

<!-- Z2: PROSSIMI LAVORI -->
<div class="zone z2">
    <div class="zone-header">
        <div class="zone-title">📋 Prossimi lavori</div>
        <span class="zone-badge">CODA</span>
    </div>
    @foreach($prossimi as $gruppo => $items)
        <div class="coda-macchina">{{ $gruppo }}</div>
        @foreach($items as $p)
        <div class="coda-item">
            <span class="coda-num">#{{ $loop->iteration }}</span>
            <span class="coda-desc">{{ $p['desc'] }}</span>
            <span class="coda-badge {{ $p['badge_cls'] }}">{{ $p['badge'] }}</span>
        </div>
        @endforeach
    @endforeach
</div>

<!-- Z3: OBIETTIVO GIORNALIERO -->
<div class="zone z3">
    <div class="zone-header">
        <div class="zone-title">🎯 Obiettivo giornaliero</div>
        <span class="obj-pct">{{ $obiettivo['pct'] }}%</span>
    </div>
    <div class="obiettivo-wrap">
        <div class="obj-label">Fasi completate oggi</div>
        <div class="obj-big">
            <span class="obj-num">{{ $obiettivo['completate'] }}</span>
            <span class="obj-slash">/</span>
            <span class="obj-target">{{ $obiettivo['target'] }}</span>
        </div>
        <div class="obj-bar"><div class="obj-bar-fill" style="width:{{ $obiettivo['pct'] }}%"></div></div>
        <div class="obj-stats">
            <div class="obj-stat"><div class="obj-stat-val green">+{{ $obiettivo['ultima_ora'] }}</div><div class="obj-stat-lbl">Ultima ora</div></div>
            <div class="obj-stat"><div class="obj-stat-val blue">{{ $kpi['in_corso'] }}</div><div class="obj-stat-lbl">In corso</div></div>
            <div class="obj-stat"><div class="obj-stat-val">{{ $kpi['in_coda'] }}</div><div class="obj-stat-lbl">In coda</div></div>
            <div class="obj-stat"><div class="obj-stat-val blue">{{ $obiettivo['ore'] }}h</div><div class="obj-stat-lbl">Ore segnate</div></div>
        </div>
    </div>
</div>

<!-- Z4: ORE SEGNATE OGGI -->
<div class="zone z4">
    @php
        $mediaPct = count($utilizzo) > 0 ? round(collect($utilizzo)->avg('pct')) : 0;
        $mediaCls = $mediaPct >= 60 ? 'high' : ($mediaPct >= 35 ? 'mid' : 'low');
    @endphp
    <div class="zone-header">
        <div class="zone-title">📊 Ore segnate oggi</div>
        <span class="zone-badge {{ $mediaCls }}">{{ $mediaPct }}% medio</span>
    </div>
    @foreach($utilizzo as $u)
    @php
        $cls = $u['pct'] >= 60 ? 'green' : ($u['pct'] >= 35 ? 'orange' : 'red');
    @endphp
    <div class="ore-row">
        <span class="ore-nome">{{ $u['nome'] }}</span>
        <div class="ore-bar"><div class="ore-fill {{ $cls }}" style="width:{{ $u['pct'] }}%"></div></div>
        <span class="ore-pct {{ $cls }}">{{ $u['pct'] }}%</span>
    </div>
    @endforeach
</div>

</div>
</div>

<script>
function updateClock() {
    var now = new Date();
    document.getElementById('clock').textContent =
        String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0');
    var giorni = ['Domenica','Lunedì','Martedì','Mercoledì','Giovedì','Venerdì','Sabato'];
    var mesi = ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
    document.getElementById('dateStr').textContent =
        giorni[now.getDay()] + ' ' + now.getDate() + ' ' + mesi[now.getMonth()] + ' ' + now.getFullYear();
}
updateClock();
setInterval(updateClock, 1000);
setTimeout(function() { location.reload(); }, 60000);
</script>
</body>
</html>
