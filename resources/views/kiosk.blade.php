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
    background: #000000; /* OLED nero puro = pixel spenti */
    color: #e2e8f0;
    -webkit-font-smoothing: antialiased;
    font-feature-settings: 'tnum';
}

/* TV scaling per leggibilità a 3-4m */
html { font-size: 22px; }
@media (min-width: 1920px) {
    html { font-size: 26px; }
}
@media (min-width: 2560px) {
    html { font-size: 34px; }
}

.kiosk { display: grid; grid-template-rows: auto 1fr auto; height: 100vh; }

/* === HEADER === */
.header {
    background: linear-gradient(135deg, #111827, #000);
    border-bottom: 1px solid #1e293b;
    display: flex;
    align-items: center;
    padding: 0.5rem 1.5rem;
    gap: 2rem;
}
.header-brand { display: flex; flex-direction: column; min-width: 8rem; }
.header-brand-name { font-size: 0.75rem; font-weight: 800; color: #38bdf8; letter-spacing: 1px; }
.header-brand-sub { font-size: 0.35rem; color: #64748b; text-transform: uppercase; letter-spacing: 2px; }

.header-kpis { display: flex; gap: 2.5rem; flex: 1; justify-content: center; }
.hkpi { text-align: center; }
.hkpi-val { font-size: 1.1rem; font-weight: 800; line-height: 1; }
.hkpi-lbl { font-size: 0.32rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.08em; margin-top: 0.15rem; }
.hkpi { min-width: 5rem; }
.hkpi-green .hkpi-val { color: #4ade80; }
.hkpi-blue .hkpi-val { color: #38bdf8; }
.hkpi-amber .hkpi-val { color: #fbbf24; }
.hkpi-purple .hkpi-val { color: #a78bfa; }

.header-solar {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #111827;
    padding: 0.2rem 0.6rem;
    border-radius: 0.3rem;
    border: 1px solid #1e293b;
}
.solar-icon { font-size: 0.7rem; }
.solar-val { font-size: 0.6rem; font-weight: 800; color: #fbbf24; }
.solar-lbl { font-size: 0.26rem; color: #64748b; }
.solar-inv { font-size: 0.26rem; color: #4ade80; }

.header-clock { text-align: right; min-width: 7rem; }
.clock-time { font-size: 1.2rem; font-weight: 800; color: #f1f5f9; }
.clock-date { font-size: 0.35rem; color: #64748b; }

/* === GRID 4 ZONE === */
.zones {
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-template-rows: 52% 48%;
    gap: 2px;
    background: #1e293b;
}

.zone {
    background: #0a0f1a;
    padding: 0.4rem 0.6rem;
    overflow: hidden;
}

.zone-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.25rem;
}
.zone-title {
    font-size: 0.45rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    display: flex;
    align-items: center;
    gap: 0.3rem;
}
.zone-badge {
    font-size: 0.3rem;
    font-weight: 700;
    padding: 0.1rem 0.4rem;
    border-radius: 0.4rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* === ZONA 1: IN CORSO ORA === */
.z1 .zone-title { color: #fbbf24; }
.z1 .zone-badge { background: #16a34a; color: #fff; }

.macchina {
    display: flex;
    align-items: center;
    padding: 0.15rem 0.35rem;
    border-radius: 0.3rem;
    margin-bottom: 0.08rem;
    border-left: 3px solid #334155;
    background: #111827;
}
.macchina.attiva { border-left-color: #4ade80; }
.macchina.attesa { border-left-color: #475569; opacity: 0.5; }

.m-info-left { min-width: 4.5rem; }
.m-nome { font-size: 0.42rem; font-weight: 700; color: #f1f5f9; }
.m-stato { font-size: 0.28rem; font-weight: 600; }
.m-stato.lav { color: #4ade80; }
.m-stato.att { color: #475569; }

.m-info-center { flex: 1; padding: 0 0.4rem; }
.m-commessa { font-size: 0.42rem; font-weight: 700; color: #38bdf8; }
.m-desc { font-size: 0.34rem; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 12rem; display: block; }
.m-cliente { font-size: 0.28rem; color: #64748b; }

.m-info-right { text-align: right; min-width: 4rem; }
.m-ore { font-size: 0.38rem; color: #94a3b8; font-weight: 600; }
.m-bar { width: 3rem; height: 0.15rem; background: #1e293b; border-radius: 0.1rem; margin-top: 0.1rem; margin-left: auto; }
.m-bar-fill { height: 100%; border-radius: 0.1rem; background: #38bdf8; }

/* === ZONA 2: PROSSIMI LAVORI === */
.z2 .zone-title { color: #94a3b8; }
.z2 .zone-badge { background: #2563eb; color: #fff; }
.z2-scroll {
    overflow: hidden;
    position: relative;
    flex: 1;
}
.z2-scroll-inner {
    animation: scrollUp 30s linear infinite;
}
@keyframes scrollUp {
    0% { transform: translateY(0); }
    45% { transform: translateY(0); }
    50% { transform: translateY(-50%); }
    95% { transform: translateY(-50%); }
    100% { transform: translateY(0); }
}

/* === TICKER NOTA TV === */
.ticker {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 9999;
    background: #dc2626;
    overflow: hidden;
    white-space: nowrap;
    padding: 0.3rem 0;
}
.ticker-inner {
    display: inline-block;
    animation: tickerScroll 20s linear infinite;
    font-size: 1.1rem;
    font-weight: 800;
    color: #fff;
    padding-left: 100%;
}
@keyframes tickerScroll {
    0% { transform: translateX(0); }
    100% { transform: translateX(-100%); }
}
.ticker-empty { display: none; }

.coda-macchina { font-size: 0.44rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin: 0.2rem 0 0.06rem; letter-spacing: 0.05em; }
.coda-macchina:first-child { margin-top: 0; }

.coda-item {
    display: flex;
    align-items: center;
    padding: 0.06rem 0.25rem;
    font-size: 0.4rem;
}
.coda-num { color: #475569; min-width: 0.9rem; font-weight: 600; }
.coda-desc { flex: 1; color: #cbd5e1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.coda-badge {
    font-size: 0.32rem;
    font-weight: 700;
    padding: 0.06rem 0.3rem;
    border-radius: 0.3rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    margin-left: 0.3rem;
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
.obj-pct { font-size: 0.5rem; font-weight: 800; color: #4ade80; }
.obj-label { font-size: 0.38rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 0.3rem; }
.obj-big { display: flex; align-items: baseline; gap: 0.15rem; }
.obj-num { font-size: 2.5rem; font-weight: 900; color: #4ade80; line-height: 1; }
.obj-slash { font-size: 1.4rem; color: #334155; font-weight: 300; }
.obj-target { font-size: 1.4rem; color: #475569; font-weight: 300; }
.obj-bar { width: 80%; max-width: 12rem; height: 0.3rem; background: #1e293b; border-radius: 0.15rem; margin: 0.4rem 0; overflow: hidden; }
.obj-bar-fill { height: 100%; border-radius: 0.15rem; background: linear-gradient(90deg, #4ade80, #22d3ee); }

.obj-stats { display: flex; gap: 3.5rem; margin-top: 0.8rem; }
.obj-stat { text-align: center; min-width: 2.5rem; }
.obj-stat-val { font-size: 0.9rem; font-weight: 800; color: #f1f5f9; }
.obj-stat-val.green { color: #4ade80; }
.obj-stat-val.blue { color: #38bdf8; }
.obj-stat-lbl { font-size: 0.28rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.08em; margin-top: 0.15rem; }

/* === ZONA 4: ORE SEGNATE OGGI === */
.z4 .zone-title { color: #38bdf8; }
.z4 .zone-badge { border-radius: 10px; font-size: 9px; font-weight: 700; padding: 3px 10px; }
.z4 .zone-badge.low { background: #7f1d1d; color: #fca5a5; }
.z4 .zone-badge.mid { background: #92400e; color: #fcd34d; }
.z4 .zone-badge.high { background: #065f46; color: #6ee7b7; }

.ore-row {
    display: flex;
    align-items: center;
    margin-bottom: 0.1rem;
}
.ore-nome { font-size: 0.54rem; font-weight: 600; color: #94a3b8; min-width: 5.5rem; }
.ore-bar { flex: 1; height: 0.6rem; background: #1e293b; border-radius: 0.3rem; overflow: hidden; margin: 0 0.4rem; }
.ore-fill { height: 100%; border-radius: 0.28rem; }
.ore-fill.red { background: linear-gradient(90deg, #dc2626, #ef4444); }
.ore-fill.orange { background: linear-gradient(90deg, #d97706, #f59e0b); }
.ore-fill.green { background: linear-gradient(90deg, #16a34a, #4ade80); }
.ore-pct { font-size: 0.58rem; font-weight: 700; min-width: 2rem; text-align: right; }
.ore-pct.red { color: #f87171; }
.ore-pct.orange { color: #fbbf24; }
.ore-pct.green { color: #4ade80; }

/* === PAGINA SOLAR === */
.solar-page { padding: 1.2rem 2rem; height: calc(100vh - 4rem); display: flex; flex-direction: column; background: #000; }
.solar-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.solar-title { font-size: 0.8rem; font-weight: 800; color: #fbbf24; }
.solar-status { display: flex; gap: 0.6rem; align-items: center; }
.solar-inv-badge { font-size: 0.4rem; background: #065f46; color: #6ee7b7; padding: 0.15rem 0.5rem; border-radius: 0.3rem; font-weight: 700; }
.solar-update { font-size: 0.36rem; color: #64748b; }

.solar-kpis { display: flex; justify-content: center; gap: 2.5rem; margin-bottom: 1.2rem; }
.solar-kpi { text-align: center; background: #111827; padding: 0.8rem 1.5rem; border-radius: 0.5rem; min-width: 7rem; border: 1px solid #1e293b; }
.solar-kpi-val { font-size: 2.2rem; font-weight: 900; line-height: 1; }
.solar-kpi-unit { font-size: 0.5rem; color: #64748b; font-weight: 600; margin-top: 0.1rem; }
.solar-kpi-lbl { font-size: 0.36rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-top: 0.2rem; }

.solar-inverters { flex: 1; }
.solar-inv-title { font-size: 0.5rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.4rem; }
.solar-inv-row { display: flex; align-items: center; gap: 0.5rem; padding: 0.3rem 0.5rem; margin-bottom: 0.2rem; background: #111827; border-radius: 0.3rem; }
.solar-inv-row.offline { opacity: 0.35; }
.solar-inv-name { font-size: 0.5rem; font-weight: 700; color: #f1f5f9; min-width: 3rem; }
.solar-inv-tipo { font-size: 0.4rem; color: #64748b; min-width: 5rem; }
.solar-inv-kwp { font-size: 0.42rem; color: #94a3b8; min-width: 3.5rem; text-align: right; }
.solar-inv-bar { flex: 1; height: 0.55rem; background: #1e293b; border-radius: 0.28rem; overflow: hidden; margin: 0 0.4rem; }
.solar-inv-fill { height: 100%; background: linear-gradient(90deg, #f59e0b, #fbbf24); border-radius: 0.28rem; }
.solar-inv-kwh { font-size: 0.5rem; font-weight: 700; color: #fbbf24; min-width: 3.5rem; text-align: right; }
.solar-inv-status { font-size: 0.45rem; min-width: 0.6rem; }
.solar-inv-row:not(.offline) .solar-inv-status { color: #4ade80; }
.solar-inv-row.offline .solar-inv-status { color: #ef4444; }
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

<!-- PAGINA 1: PRODUZIONE -->
<div class="zones kiosk-page" id="page-produzione">

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
<div class="zone z2" style="display:flex; flex-direction:column;">
    <div class="zone-header">
        <div class="zone-title">📋 Prossimi lavori</div>
        <span class="zone-badge">CODA</span>
    </div>
    <div class="z2-scroll">
        <div class="z2-scroll-inner">
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
    </div>
</div>

<!-- Z3: FASI COMPLETATE OGGI -->
<div class="zone z3">
    <div class="zone-header">
        <div class="zone-title">⚡ Fasi completate oggi</div>
    </div>
    <div class="obiettivo-wrap">
        <div class="obj-big">
            <span class="obj-num">{{ $obiettivo['completate'] }}</span>
        </div>
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

<!-- TICKER NOTA TV -->
@if(!empty($notaTv))
<div class="ticker">
    <div class="ticker-inner">📢 {{ $notaTv }}</div>
</div>
@endif

<!-- PAGINA 2: SOLAR-LOG -->
<div class="kiosk-page" id="page-solar" style="display:none; position:absolute; top:0; left:0; right:0; bottom:0; padding-top:inherit;">
    <div class="solar-page">
        <div class="solar-header">
            <div class="solar-title">☀️ Impianto Fotovoltaico — {{ $solar['impianto_kwp'] ?? 180 }} kWp</div>
            <div class="solar-status">
                <span class="solar-inv-badge">{{ $solar['inverter_online'] ?? 0 }}/{{ $solar['inverter_totali'] ?? 7 }} inverter online</span>
                <span class="solar-update">Agg. {{ $solar['ultimo_aggiornamento'] ?? '--:--' }}</span>
            </div>
        </div>

        <div class="solar-kpis">
            <div class="solar-kpi">
                <div class="solar-kpi-val" style="color:#fbbf24">{{ $solar['oggi_kwh'] ?? 0 }}</div>
                <div class="solar-kpi-unit">kWh</div>
                <div class="solar-kpi-lbl">Produzione oggi</div>
            </div>
            <div class="solar-kpi">
                <div class="solar-kpi-val" style="color:#94a3b8">{{ $solar['ieri_kwh'] ?? 0 }}</div>
                <div class="solar-kpi-unit">kWh</div>
                <div class="solar-kpi-lbl">Ieri</div>
            </div>
            <div class="solar-kpi">
                <div class="solar-kpi-val" style="color:#38bdf8">{{ $solar['settimana_kwh'] ?? 0 }}</div>
                <div class="solar-kpi-unit">kWh</div>
                <div class="solar-kpi-lbl">Ultimi 7 giorni</div>
            </div>
            <div class="solar-kpi">
                <div class="solar-kpi-val" style="color:#4ade80">{{ $solar['mese_kwh'] ?? 0 }}</div>
                <div class="solar-kpi-unit">kWh</div>
                <div class="solar-kpi-lbl">Ultimi 30 giorni</div>
            </div>
        </div>

        <div class="solar-inverters">
            <div class="solar-inv-title">Dettaglio Inverter</div>
            @foreach(($solar['inverter'] ?? []) as $inv)
            <div class="solar-inv-row {{ $inv['online'] ? '' : 'offline' }}">
                <span class="solar-inv-name">{{ $inv['nome'] }}</span>
                <span class="solar-inv-tipo">{{ $inv['tipo'] }}</span>
                <span class="solar-inv-kwp">{{ $inv['kwp'] }} kWp</span>
                <div class="solar-inv-bar">
                    <div class="solar-inv-fill" style="width:{{ $inv['oggi_kwh_kwp'] > 0 ? min(($inv['oggi_kwh_kwp'] / 5) * 100, 100) : 0 }}%"></div>
                </div>
                <span class="solar-inv-kwh">{{ $inv['oggi_kwh'] }} kWh</span>
                <span class="solar-inv-status">{{ $inv['online'] ? '●' : '○' }}</span>
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

// Alternanza pagine ogni 45 secondi
var pages = document.querySelectorAll('.kiosk-page');
var currentPage = 0;
function switchPage() {
    pages[currentPage].style.display = 'none';
    currentPage = (currentPage + 1) % pages.length;
    pages[currentPage].style.display = '';
}
if (pages.length > 1) {
    setInterval(switchPage, 45000);
}

// Refresh dati ogni 30 secondi per dati live
setTimeout(function() { location.reload(); }, 30000);
</script>
</body>
</html>
