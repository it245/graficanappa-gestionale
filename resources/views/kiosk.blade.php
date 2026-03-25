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
    background: #000;
    color: #f1f5f9;
    -webkit-font-smoothing: antialiased;
    font-feature-settings: 'tnum';
}

html { font-size: 22px; }
@media (min-width: 1920px) { html { font-size: 26px; } }
@media (min-width: 2560px) { html { font-size: 34px; } }

/* Header fisso */
.header {
    position: fixed; top: 0; left: 0; right: 0; z-index: 100;
    background: #0f172a;
    border-bottom: 2px solid #2563eb;
    display: flex; align-items: center; justify-content: space-between;
    padding: 0.4rem 1.2rem;
    height: 3rem;
}
.header-brand { display: flex; align-items: center; gap: 0.4rem; }
.header-brand-name { font-size: 0.65rem; font-weight: 800; color: #38bdf8; }
.header-brand-sub { font-size: 0.3rem; color: #64748b; }
.header-kpis { display: flex; gap: 2rem; }
.hkpi { text-align: center; min-width: 4rem; }
.hkpi-val { font-size: 1rem; font-weight: 800; }
.hkpi-lbl { font-size: 0.28rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
.hkpi-green .hkpi-val { color: #4ade80; }
.hkpi-blue .hkpi-val { color: #38bdf8; }
.hkpi-amber .hkpi-val { color: #fbbf24; }
.hkpi-purple .hkpi-val { color: #a78bfa; }
.clock-time { font-size: 1rem; font-weight: 800; color: #f1f5f9; }
.clock-date { font-size: 0.3rem; color: #64748b; }

/* Ticker fisso in basso */
.ticker {
    position: fixed; bottom: 0; left: 0; right: 0; z-index: 100;
    background: #dc2626;
    overflow: hidden; white-space: nowrap;
    padding: 0.25rem 0;
}
.ticker-inner {
    display: inline-block;
    animation: tickerScroll 20s linear infinite;
    font-size: 0.9rem; font-weight: 800; color: #fff;
    padding-left: 100%;
}
@keyframes tickerScroll { 0% { transform: translateX(0); } 100% { transform: translateX(-100%); } }

/* Area scorrimento verticale */
.scroll-area {
    position: fixed;
    top: 3rem;
    left: 0;
    right: 0;
    bottom: 2rem;
    overflow: hidden;
}
.scroll-inner {
    transition: transform 0.8s ease;
}

/* Sezioni */
.section {
    padding: 0.6rem 1rem;
    height: calc(100vh - 5rem);
    max-height: calc(100vh - 5rem);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.section-title {
    font-size: 0.55rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.08em; margin-bottom: 0.4rem;
    display: flex; align-items: center; gap: 0.3rem;
}
.section-badge {
    font-size: 0.28rem; font-weight: 700; padding: 0.08rem 0.35rem;
    border-radius: 0.3rem; text-transform: uppercase;
}

/* Macchine */
.macchina {
    display: flex; align-items: center;
    padding: 0.3rem 0.5rem; border-radius: 0.3rem;
    margin-bottom: 0.15rem; border-left: 4px solid #334155;
    background: #111827;
}
.macchina.attiva { border-left-color: #4ade80; }
.macchina.attesa { border-left-color: #334155; opacity: 0.4; }
.m-nome { font-size: 0.8rem; font-weight: 700; color: #f1f5f9; min-width: 7rem; }
.m-stato { font-size: 0.42rem; font-weight: 600; }
.m-stato.lav { color: #4ade80; }
.m-stato.att { color: #475569; }
.m-commessa { font-size: 0.75rem; font-weight: 700; color: #38bdf8; }
.m-desc { font-size: 0.55rem; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 18rem; }
.m-cliente { font-size: 0.5rem; color: #64748b; }
.m-ore { font-size: 0.5rem; color: #94a3b8; font-weight: 600; min-width: 2.5rem; text-align: right; }
.m-info-left { min-width: 6rem; }
.m-info-center { flex: 1; padding: 0 0.4rem; }
.m-info-right { text-align: right; }

/* Prossimi lavori */
.coda-macchina { font-size: 0.7rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin: 0.4rem 0 0.15rem; }
.coda-item { display: flex; align-items: center; padding: 0.18rem 0.3rem; font-size: 0.6rem; }
.coda-num { color: #475569; min-width: 0.9rem; font-weight: 600; }
.coda-desc { flex: 1; color: #cbd5e1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.coda-badge { font-size: 0.58rem; font-weight: 700; padding: 0.14rem 0.5rem; border-radius: 0.3rem; margin-left: 0.3rem; white-space: nowrap; }
.coda-badge.verde { background: #065f46; color: #6ee7b7; }

/* Fasi completate + Ore segnate side by side */
.stats-row { display: flex; gap: 1rem; flex: 1; height: 100%; }
.stats-col { flex: 1; background: #111827; border-radius: 0.4rem; padding: 0.8rem 1rem; display: flex; flex-direction: column; justify-content: center; }
.stats-col-title { font-size: 0.7rem; font-weight: 700; margin-bottom: 0.6rem; display: flex; align-items: center; gap: 0.3rem; }

.big-num { font-size: 5rem; font-weight: 900; color: #4ade80; line-height: 1; text-align: center; margin: 0.6rem 0; }
.obj-stats { display: flex; justify-content: space-around; margin-top: 1rem; width: 100%; }
.obj-stat { text-align: center; padding: 0.4rem 1.2rem; }
.obj-stat-val { font-size: 2rem; font-weight: 800; line-height: 1; }
.obj-stat-val.green { color: #4ade80; }
.obj-stat-val.blue { color: #38bdf8; }
.obj-stat-lbl { font-size: 0.6rem; color: #94a3b8; text-transform: uppercase; margin-top: 0.3rem; font-weight: 600; }

/* Ore segnate */
.ore-row { display: flex; align-items: center; margin-bottom: 0.4rem; }
.ore-nome { font-size: 0.6rem; font-weight: 600; color: #94a3b8; min-width: 6.5rem; }
.ore-bar { flex: 1; height: 0.8rem; background: #1e293b; border-radius: 0.3rem; overflow: hidden; margin: 0 0.5rem; }
.ore-fill { height: 100%; border-radius: 0.25rem; }
.ore-fill.red { background: linear-gradient(90deg, #dc2626, #ef4444); }
.ore-fill.orange { background: linear-gradient(90deg, #d97706, #f59e0b); }
.ore-fill.green { background: linear-gradient(90deg, #16a34a, #4ade80); }
.ore-pct { font-size: 0.65rem; font-weight: 700; min-width: 2.2rem; text-align: right; }
.ore-pct.red { color: #f87171; }
.ore-pct.orange { color: #fbbf24; }
.ore-pct.green { color: #4ade80; }

/* Solar */
.solar-page { padding: 0.8rem 1.2rem; min-height: calc(100vh - 5rem); background: #000; }
.solar-title { font-size: 0.8rem; font-weight: 800; color: #fbbf24; margin-bottom: 0.6rem; }
.solar-kpis { display: flex; justify-content: center; gap: 2.5rem; margin-bottom: 1rem; }
.solar-kpi { text-align: center; background: #111827; padding: 0.8rem 1.5rem; border-radius: 0.5rem; border: 1px solid #1e293b; }
.solar-kpi-val { font-size: 2.2rem; font-weight: 900; }
.solar-kpi-unit { font-size: 0.5rem; color: #64748b; }
.solar-kpi-lbl { font-size: 0.38rem; color: #64748b; text-transform: uppercase; margin-top: 0.15rem; }
.solar-inv-row { display: flex; align-items: center; gap: 0.5rem; padding: 0.35rem 0.5rem; margin-bottom: 0.2rem; background: #111827; border-radius: 0.3rem; }
.solar-inv-row.offline { opacity: 0.3; }
.solar-inv-name { font-size: 0.55rem; font-weight: 700; color: #f1f5f9; min-width: 3rem; }
.solar-inv-tipo { font-size: 0.42rem; color: #64748b; min-width: 4.5rem; }
.solar-inv-kwp { font-size: 0.45rem; color: #94a3b8; min-width: 3.5rem; text-align: right; }
.solar-inv-bar { flex: 1; height: 0.55rem; background: #1e293b; border-radius: 0.28rem; overflow: hidden; margin: 0 0.4rem; }
.solar-inv-fill { height: 100%; background: linear-gradient(90deg, #f59e0b, #fbbf24); border-radius: 0.28rem; }
.solar-inv-kwh { font-size: 0.55rem; font-weight: 700; color: #fbbf24; min-width: 3.5rem; text-align: right; }
.solar-inv-status { font-size: 0.5rem; }
.solar-inv-row:not(.offline) .solar-inv-status { color: #4ade80; }
.solar-inv-row.offline .solar-inv-status { color: #ef4444; }
</style>
</head>
<body>

<!-- HEADER -->
<div class="header">
    <div class="header-brand">
        <div>
            <div class="header-brand-name">GRAFICA NAPPA</div>
            <div class="header-brand-sub">DASHBOARD PRODUZIONE</div>
        </div>
    </div>
    <div class="header-kpis">
        <div class="hkpi hkpi-green"><div class="hkpi-val">{{ $kpi['completate'] }}</div><div class="hkpi-lbl">Completate oggi</div></div>
        <div class="hkpi hkpi-blue"><div class="hkpi-val">{{ $kpi['in_corso'] }}</div><div class="hkpi-lbl">In corso ora</div></div>
        <div class="hkpi hkpi-amber"><div class="hkpi-val">{{ $kpi['in_coda'] }}</div><div class="hkpi-lbl">Pronte in coda</div></div>
        <div class="hkpi hkpi-purple"><div class="hkpi-val">{{ $kpi['fustelle'] }}</div><div class="hkpi-lbl">Fustelle attive</div></div>
    </div>
    <div>
        <div class="clock-time" id="clock">--:--</div>
        <div class="clock-date" id="dateStr"></div>
    </div>
</div>

<!-- CONTENUTO SCROLLABILE -->
<div class="scroll-area" id="scrollArea">
<div class="scroll-inner" id="scrollInner">

<!-- PAGINA 1: Z1 + Z2 affiancate -->
<div class="section" data-section="0" style="display:flex; flex-direction:row; gap:0.5rem;"><div style="flex:1; overflow:hidden;">
    <div class="section-title" style="color:#fbbf24;">⚡ In corso ora <span class="section-badge" style="background:#16a34a;color:#fff;">LIVE</span></div>
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
                <div style="color:#475569; font-style:italic; font-size:0.38rem;">In attesa prossimo lavoro</div>
            @endif
        </div>
        <div class="m-info-right">
            @if($m['attiva'] && $m['ore_lav'])
                <div class="m-ore">{{ $m['ore_lav'] }}h</div>
            @endif
        </div>
    </div>
    @endforeach
</div><!-- fine z1 -->
<div style="flex:1; display:flex; flex-direction:column; overflow:hidden;">
<!-- Z2: PROSSIMI LAVORI -->
    <div class="section-title" style="color:#94a3b8;">📋 Prossimi lavori <span class="section-badge" style="background:#2563eb;color:#fff;">CODA</span></div>
    <div id="z2-scroll" style="flex:1; overflow:hidden;">
        <div id="z2-scroll-inner">
            @foreach($prossimi as $gruppo => $items)
                <div class="coda-macchina">{{ $gruppo }}</div>
                @foreach($items as $p)
                <div class="coda-item">
                    <span class="coda-num">#{{ $loop->iteration }}</span>
                    <span class="coda-desc">{{ $p['desc'] }}</span>
                    <span class="coda-badge verde">{{ $p['badge'] }}</span>
                </div>
                @endforeach
            @endforeach
        </div>
    </div>
</div><!-- fine z2 -->
</div><!-- fine pagina 1 -->

<!-- PAGINA 2: FASI COMPLETATE + ORE SEGNATE -->
<div class="section" data-section="1">
    <div class="stats-row">
        <div class="stats-col">
            <div class="stats-col-title" style="color:#4ade80;">⚡ Fasi completate oggi</div>
            <div class="big-num">{{ $obiettivo['completate'] }}</div>
            <div class="obj-stats">
                <div class="obj-stat"><div class="obj-stat-val green">+{{ $obiettivo['ultima_ora'] }}</div><div class="obj-stat-lbl">Ultima ora</div></div>
                <div class="obj-stat"><div class="obj-stat-val blue">{{ $kpi['in_corso'] }}</div><div class="obj-stat-lbl">In corso</div></div>
                <div class="obj-stat"><div class="obj-stat-val">{{ $kpi['in_coda'] }}</div><div class="obj-stat-lbl">In coda</div></div>
                <div class="obj-stat"><div class="obj-stat-val blue">{{ $obiettivo['ore'] }}h</div><div class="obj-stat-lbl">Ore segnate</div></div>
            </div>
        </div>
        <div class="stats-col">
            @php $mediaPct = count($utilizzo) > 0 ? round(collect($utilizzo)->avg('pct')) : 0; @endphp
            <div class="stats-col-title" style="color:#38bdf8;">📊 Ore segnate oggi <span style="margin-left:auto;font-size:0.35rem;color:{{ $mediaPct >= 60 ? '#4ade80' : ($mediaPct >= 35 ? '#fbbf24' : '#f87171') }}">{{ $mediaPct }}%</span></div>
            @foreach($utilizzo as $u)
            @php $cls = $u['pct'] >= 60 ? 'green' : ($u['pct'] >= 35 ? 'orange' : 'red'); @endphp
            <div class="ore-row">
                <span class="ore-nome">{{ $u['nome'] }}</span>
                <div class="ore-bar"><div class="ore-fill {{ $cls }}" style="width:{{ $u['pct'] }}%"></div></div>
                <span class="ore-pct {{ $cls }}">{{ $u['pct'] }}%</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

<!-- PAGINA 3: SOLAR-LOG -->
<div class="section" data-section="2">
    <div class="solar-page">
        <div class="solar-title">☀️ Impianto Fotovoltaico — {{ $solar['impianto_kwp'] ?? 180 }} kWp
            <span style="font-size:0.35rem; color:#64748b; margin-left:0.5rem;">{{ $solar['inverter_online'] ?? 0 }}/{{ $solar['inverter_totali'] ?? 7 }} online · Agg. {{ $solar['ultimo_aggiornamento'] ?? '--:--' }}</span>
        </div>
        <div class="solar-kpis">
            <div class="solar-kpi"><div class="solar-kpi-val" style="color:#fbbf24">{{ $solar['oggi_kwh'] ?? 0 }}</div><div class="solar-kpi-unit">kWh</div><div class="solar-kpi-lbl">Oggi</div></div>
            <div class="solar-kpi"><div class="solar-kpi-val" style="color:#94a3b8">{{ $solar['ieri_kwh'] ?? 0 }}</div><div class="solar-kpi-unit">kWh</div><div class="solar-kpi-lbl">Ieri</div></div>
            <div class="solar-kpi"><div class="solar-kpi-val" style="color:#38bdf8">{{ $solar['settimana_kwh'] ?? 0 }}</div><div class="solar-kpi-unit">kWh</div><div class="solar-kpi-lbl">7 giorni</div></div>
            <div class="solar-kpi"><div class="solar-kpi-val" style="color:#4ade80">{{ $solar['mese_kwh'] ?? 0 }}</div><div class="solar-kpi-unit">kWh</div><div class="solar-kpi-lbl">30 giorni</div></div>
        </div>
        @foreach(($solar['inverter'] ?? []) as $inv)
        <div class="solar-inv-row {{ $inv['online'] ? '' : 'offline' }}">
            <span class="solar-inv-name">{{ $inv['nome'] }}</span>
            <span class="solar-inv-tipo">{{ $inv['tipo'] }}</span>
            <span class="solar-inv-kwp">{{ $inv['kwp'] }} kWp</span>
            <div class="solar-inv-bar"><div class="solar-inv-fill" style="width:{{ $inv['oggi_kwh_kwp'] > 0 ? min(($inv['oggi_kwh_kwp'] / 5) * 100, 100) : 0 }}%"></div></div>
            <span class="solar-inv-kwh">{{ $inv['oggi_kwh'] }} kWh</span>
            <span class="solar-inv-status">{{ $inv['online'] ? '●' : '○' }}</span>
        </div>
        @endforeach
    </div>
</div>

</div>
</div>

<!-- TICKER -->
@if(!empty($notaTv))
<div class="ticker">
    <div class="ticker-inner">📢 {{ $notaTv }}</div>
</div>
@endif

<script>
// Clock
function updateClock() {
    var now = new Date();
    document.getElementById('clock').textContent = String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0');
    var giorni = ['Domenica','Lunedì','Martedì','Mercoledì','Giovedì','Venerdì','Sabato'];
    var mesi = ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno','Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'];
    document.getElementById('dateStr').textContent = giorni[now.getDay()] + ' ' + now.getDate() + ' ' + mesi[now.getMonth()] + ' ' + now.getFullYear();
}
updateClock();
setInterval(updateClock, 1000);

// Scorrimento verticale automatico tra sezioni
var sections = document.querySelectorAll('.section');
var currentSection = 0;
var scrollInner = document.getElementById('scrollInner');
var scrollArea = document.getElementById('scrollArea');

function scrollToSection() {
    var target = sections[currentSection];
    var offset = target.offsetTop;
    scrollInner.style.transform = 'translateY(-' + offset + 'px)';
}

function nextSection() {
    currentSection++;
    if (currentSection >= sections.length) {
        // Fine: ricarica pagina per dati freschi
        location.reload();
        return;
    }
    scrollToSection();
}

// Ogni pagina visibile per 40 secondi
setInterval(nextSection, 40000);

// Scroll verticale automatico per Z2 (Prossimi lavori)
(function() {
    var container = document.getElementById('z2-scroll');
    var inner = document.getElementById('z2-scroll-inner');
    if (!container || !inner) return;
    var scrollPos = 0;
    var speed = 0.3; // pixel per frame (più lento)
    var maxScroll = 0;
    var pausing = true;
    var pauseTimer = null;

    function startPause(ms) {
        pausing = true;
        clearTimeout(pauseTimer);
        pauseTimer = setTimeout(function() { pausing = false; }, ms);
    }

    // Pausa iniziale 5 secondi prima di partire
    startPause(5000);

    function autoScroll() {
        maxScroll = inner.scrollHeight - container.clientHeight;
        if (maxScroll <= 0) { requestAnimationFrame(autoScroll); return; }

        if (!pausing) {
            scrollPos += speed;
            if (scrollPos >= maxScroll) {
                scrollPos = maxScroll;
                startPause(5000); // pausa 5s in fondo prima di risalire
                setTimeout(function() {
                    scrollPos = 0;
                    inner.style.transform = 'translateY(0)';
                    startPause(5000); // pausa 5s in cima prima di riscorrere
                }, 5000);
            }
            inner.style.transform = 'translateY(-' + scrollPos + 'px)';
        }
        requestAnimationFrame(autoScroll);
    }
    requestAnimationFrame(autoScroll);
})();
</script>
</body>
</html>
