<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>MES Grafica Nappa — Produzione Live</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
html, body { height: 100%; overflow: hidden; }
body {
    font-family: 'Inter', -apple-system, sans-serif;
    background: #0f172a;
    color: #f1f5f9;
    -webkit-font-smoothing: antialiased;
}

/* Layout 4 zone */
.kiosk-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    grid-template-rows: 48px 1fr 1fr;
    height: 100vh;
    gap: 0;
}

/* Header */
.kiosk-header {
    grid-column: 1 / -1;
    background: linear-gradient(135deg, #1e293b, #0f172a);
    border-bottom: 2px solid #2563eb;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 30px;
}
.kiosk-header-left { display: flex; align-items: center; gap: 12px; }
.kiosk-header-left img { height: 28px; }
.kiosk-header-left span { font-size: 16px; font-weight: 800; color: #f1f5f9; }
.kiosk-clock { font-size: 20px; font-weight: 700; color: #38bdf8; font-variant-numeric: tabular-nums; }
.kiosk-date { font-size: 11px; color: #64748b; margin-left: 12px; }

/* Zone */
.zone {
    padding: 20px 24px;
    overflow: hidden;
    border: 1px solid #1e293b;
}
.zone-title {
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.zone-dot { width: 8px; height: 8px; border-radius: 50%; }

/* Zona 1 — Cosa stiamo facendo adesso */
.z1 { background: #0f172a; }
.z1 .zone-title { color: #38bdf8; }
.z1 .zone-dot { background: #38bdf8; animation: pulse 2s infinite; }

.macchina-card {
    background: #1e293b;
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 10px;
    border-left: 4px solid #334155;
}
.macchina-card.attiva { border-left-color: #4ade80; }
.macchina-card.vuota { border-left-color: #475569; opacity: 0.6; }

.mc-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
.mc-macchina { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.05em; }
.mc-operatore { font-size: 11px; color: #4ade80; font-weight: 600; }
.mc-commessa { font-size: 16px; font-weight: 700; color: #38bdf8; }
.mc-cliente { font-size: 13px; color: #e2e8f0; font-weight: 600; margin-left: 8px; }
.mc-desc { font-size: 11px; color: #94a3b8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 4px 0; }
.mc-fase { font-size: 11px; color: #fbbf24; font-weight: 600; }
.mc-vuoto { font-size: 13px; color: #475569; font-style: italic; padding: 8px 0; }

.mc-progress { height: 6px; background: #334155; border-radius: 3px; margin-top: 6px; overflow: hidden; }
.mc-progress-fill { height: 100%; border-radius: 3px; background: #4ade80; transition: width 1s ease; }

/* Zona 2 — Prossimi lavori */
.z2 { background: #0f172a; }
.z2 .zone-title { color: #a78bfa; }
.z2 .zone-dot { background: #a78bfa; }

.prossimo { display: flex; align-items: center; gap: 12px; padding: 6px 0; border-bottom: 1px solid #1e293b; }
.prossimo:last-child { border-bottom: none; }
.pr-macchina { font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; min-width: 80px; }
.pr-commessa { font-size: 13px; font-weight: 700; color: #a78bfa; }
.pr-cliente { font-size: 12px; color: #cbd5e1; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.pr-consegna { font-size: 11px; color: #64748b; min-width: 50px; text-align: right; }

/* Zona 3 — Fasi completate oggi */
.z3 { background: #0f172a; display: flex; flex-direction: column; align-items: center; justify-content: center; }
.z3 .zone-title { color: #4ade80; }
.z3 .zone-dot { background: #4ade80; }

.contatore {
    text-align: center;
    margin: 10px 0;
}
.contatore-valore {
    font-size: 120px;
    font-weight: 900;
    color: #4ade80;
    line-height: 1;
    text-shadow: 0 0 40px rgba(74, 222, 128, 0.3);
}
.contatore-label {
    font-size: 16px;
    color: #94a3b8;
    font-weight: 600;
    margin-top: 4px;
}
.obiettivo {
    font-size: 18px;
    color: #64748b;
    margin-top: 12px;
}
.obiettivo strong { color: #fbbf24; }

.contatore-sub {
    display: flex;
    gap: 30px;
    margin-top: 16px;
}
.sub-item { text-align: center; }
.sub-val { font-size: 28px; font-weight: 800; }
.sub-lbl { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
.sub-blue .sub-val { color: #38bdf8; }
.sub-amber .sub-val { color: #fbbf24; }
.sub-red .sub-val { color: #f87171; }

/* Zona 4 — Utilizzo macchine */
.z4 { background: #0f172a; }
.z4 .zone-title { color: #fbbf24; }
.z4 .zone-dot { background: #fbbf24; }

.utilizzo-row {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}
.ut-macchina { font-size: 12px; font-weight: 700; color: #94a3b8; min-width: 100px; }
.ut-bar { flex: 1; height: 20px; background: #1e293b; border-radius: 10px; overflow: hidden; position: relative; }
.ut-fill { height: 100%; border-radius: 10px; transition: width 1s ease; }
.ut-pct { font-size: 14px; font-weight: 800; min-width: 45px; text-align: right; }

.ut-fill.low { background: linear-gradient(90deg, #dc2626, #ef4444); }
.ut-fill.mid { background: linear-gradient(90deg, #d97706, #fbbf24); }
.ut-fill.high { background: linear-gradient(90deg, #16a34a, #4ade80); }

.ut-pct.low { color: #f87171; }
.ut-pct.mid { color: #fbbf24; }
.ut-pct.high { color: #4ade80; }

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.3; }
}
</style>
</head>
<body>

<div class="kiosk-grid">

    <!-- Header -->
    <div class="kiosk-header">
        <div class="kiosk-header-left">
            <img src="/images/logo_gn.png" alt="Logo">
            <span>Produzione Live</span>
        </div>
        <div>
            <span class="kiosk-clock" id="clock">--:--:--</span>
            <span class="kiosk-date" id="dateStr"></span>
        </div>
    </div>

    <!-- Zona 1: Cosa stiamo facendo adesso -->
    <div class="zone z1">
        <div class="zone-title"><div class="zone-dot"></div> Cosa stiamo facendo adesso</div>
        @foreach($macchine as $m)
        <div class="macchina-card {{ $m['attiva'] ? 'attiva' : 'vuota' }}">
            <div class="mc-header">
                <span class="mc-macchina">{{ $m['nome'] }}</span>
                @if($m['operatore'])<span class="mc-operatore">{{ $m['operatore'] }}</span>@endif
            </div>
            @if($m['attiva'])
                <div><span class="mc-commessa">{{ $m['commessa'] }}</span><span class="mc-cliente">{{ $m['cliente'] }}</span></div>
                <div class="mc-desc">{{ $m['descrizione'] }}</div>
                <div class="mc-fase">{{ $m['fase'] }}</div>
                <div class="mc-progress"><div class="mc-progress-fill" style="width:{{ $m['progresso'] }}%"></div></div>
            @else
                <div class="mc-vuoto">Nessun lavoro segnato</div>
            @endif
        </div>
        @endforeach
    </div>

    <!-- Zona 2: Prossimi lavori -->
    <div class="zone z2">
        <div class="zone-title"><div class="zone-dot"></div> Prossimi lavori</div>
        @foreach($prossimi as $p)
        <div class="prossimo">
            <span class="pr-macchina">{{ $p['macchina'] }}</span>
            <span class="pr-commessa">{{ $p['commessa'] }}</span>
            <span class="pr-cliente">{{ $p['cliente'] }}</span>
            <span class="pr-consegna">{{ $p['consegna'] }}</span>
        </div>
        @endforeach
        @if(empty($prossimi))
        <div style="color:#475569; font-style:italic; padding:20px 0;">Nessun lavoro in coda</div>
        @endif
    </div>

    <!-- Zona 3: Fasi completate oggi -->
    <div class="zone z3">
        <div class="zone-title"><div class="zone-dot"></div> Fasi completate oggi</div>
        <div class="contatore">
            <div class="contatore-valore">{{ $fasiCompletate }}</div>
            <div class="contatore-label">fasi completate</div>
        </div>
        <div class="obiettivo">Obiettivo: <strong>{{ $obiettivo }}</strong></div>
        <div class="contatore-sub">
            <div class="sub-item sub-blue"><div class="sub-val">{{ $oreLavorate }}h</div><div class="sub-lbl">Ore lavorate</div></div>
            <div class="sub-item sub-amber"><div class="sub-val">{{ $spedite }}</div><div class="sub-lbl">Spedite</div></div>
            <div class="sub-item sub-red"><div class="sub-val">{{ $inRitardo }}</div><div class="sub-lbl">In ritardo</div></div>
        </div>
    </div>

    <!-- Zona 4: Utilizzo macchine oggi -->
    <div class="zone z4">
        <div class="zone-title"><div class="zone-dot"></div> Utilizzo macchine oggi</div>
        @foreach($utilizzo as $u)
        @php
            $cls = $u['pct'] >= 70 ? 'high' : ($u['pct'] >= 40 ? 'mid' : 'low');
        @endphp
        <div class="utilizzo-row">
            <span class="ut-macchina">{{ $u['nome'] }}</span>
            <div class="ut-bar"><div class="ut-fill {{ $cls }}" style="width:{{ $u['pct'] }}%"></div></div>
            <span class="ut-pct {{ $cls }}">{{ $u['pct'] }}%</span>
        </div>
        @endforeach
    </div>

</div>

<script>
function updateClock() {
    var now = new Date();
    document.getElementById('clock').textContent =
        String(now.getHours()).padStart(2,'0') + ':' +
        String(now.getMinutes()).padStart(2,'0') + ':' +
        String(now.getSeconds()).padStart(2,'0');
    var giorni = ['Domenica','Lunedì','Martedì','Mercoledì','Giovedì','Venerdì','Sabato'];
    var mesi = ['Gen','Feb','Mar','Apr','Mag','Giu','Lug','Ago','Set','Ott','Nov','Dic'];
    document.getElementById('dateStr').textContent =
        giorni[now.getDay()] + ' ' + now.getDate() + ' ' + mesi[now.getMonth()];
}
updateClock();
setInterval(updateClock, 1000);

// Auto-refresh ogni 60 secondi
setTimeout(function() { location.reload(); }, 60000);
</script>
</body>
</html>
