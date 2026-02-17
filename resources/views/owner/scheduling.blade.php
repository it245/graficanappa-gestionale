@extends('layouts.app')

@section('content')
<style>
    html, body { margin:0; padding:0; overflow-x:hidden; background:#f0f2f5; }

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
    .btn-back {
        text-decoration:none; color:#fff; border:1px solid rgba(255,255,255,0.3);
        padding:8px 20px; border-radius:25px; font-size:14px; font-weight:500;
        transition:all 0.2s;
    }
    .btn-back:hover { background:rgba(255,255,255,0.15); color:#fff; }

    /* ===== KPI ===== */
    .kpi-row { display:flex; gap:16px; padding:20px 24px 16px; flex-wrap:wrap; }
    .kpi-card {
        flex:1; min-width:180px; background:#fff; border-radius:14px;
        padding:20px 22px; position:relative; overflow:hidden;
        box-shadow:0 2px 12px rgba(0,0,0,0.06); transition:transform 0.2s, box-shadow 0.2s;
    }
    .kpi-card:hover { transform:translateY(-3px); box-shadow:0 6px 20px rgba(0,0,0,0.1); }
    .kpi-card h3 { margin:0 0 4px; font-size:36px; font-weight:800; letter-spacing:-1px; }
    .kpi-card small { color:#6c757d; font-size:13px; font-weight:500; }
    .kpi-card .kpi-icon {
        position:absolute; top:14px; right:16px; width:44px; height:44px;
        border-radius:12px; display:flex; align-items:center; justify-content:center;
        font-size:20px; font-weight:bold; color:#fff;
    }
    .kpi-blue h3 { color:#0d6efd; }
    .kpi-blue .kpi-icon { background:linear-gradient(135deg,#0d6efd,#6ea8fe); }
    .kpi-red h3 { color:#dc3545; }
    .kpi-red .kpi-icon { background:linear-gradient(135deg,#dc3545,#f1737b); }
    .kpi-orange h3 { color:#e67e22; }
    .kpi-orange .kpi-icon { background:linear-gradient(135deg,#fd7e14,#fdb96a); }
    .kpi-purple h3 { color:#6f42c1; }
    .kpi-purple .kpi-icon { background:linear-gradient(135deg,#6f42c1,#a98eda); }
    .kpi-green h3 { color:#198754; }
    .kpi-green .kpi-icon { background:linear-gradient(135deg,#198754,#5dd39e); }

    /* ===== LEGENDA ===== */
    .legend {
        display:flex; gap:22px; padding:6px 24px 12px; font-size:13px;
        align-items:center; flex-wrap:wrap;
    }
    .legend-title { font-weight:700; color:#333; }
    .legend-item { display:flex; align-items:center; gap:6px; color:#555; }
    .legend-color { width:18px; height:18px; border-radius:5px; box-shadow:0 1px 3px rgba(0,0,0,0.15); }

    /* ===== TABS ===== */
    .tab-nav {
        display:flex; gap:0; margin:0 24px; border-bottom:3px solid #dee2e6;
        background:#fff; border-radius:12px 12px 0 0; padding:0 6px;
        box-shadow:0 -2px 8px rgba(0,0,0,0.03);
    }
    .tab-btn {
        padding:14px 30px; cursor:pointer; border:none; background:none;
        font-size:15px; font-weight:600; color:#8c8c8c;
        border-bottom:3px solid transparent; margin-bottom:-3px;
        transition:all 0.2s;
    }
    .tab-btn.active { color:#0d6efd; border-bottom-color:#0d6efd; }
    .tab-btn:hover { color:#333; background:rgba(0,0,0,0.02); }
    .tab-content { display:none; }
    .tab-content.active { display:block; }

    /* ===== ZOOM & FILTRI ===== */
    .controls-bar {
        display:flex; justify-content:space-between; align-items:center;
        padding:12px 24px; flex-wrap:wrap; gap:10px;
    }
    .zoom-controls { display:flex; align-items:center; gap:10px; }
    .zoom-controls button {
        width:36px; height:36px; border:2px solid #dee2e6; border-radius:8px;
        background:#fff; cursor:pointer; font-size:18px; font-weight:bold;
        color:#333; transition:all 0.2s;
    }
    .zoom-controls button:hover { background:#0d6efd; color:#fff; border-color:#0d6efd; }
    .zoom-controls .zoom-val {
        font-size:13px; color:#6c757d; background:#f0f2f5;
        padding:4px 12px; border-radius:6px; font-weight:600; min-width:60px; text-align:center;
    }
    .filter-row { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
    .filter-chip {
        padding:6px 14px; border:1.5px solid #dee2e6; border-radius:20px;
        font-size:12px; font-weight:600; cursor:pointer; background:#fff;
        transition:all 0.2s; color:#555;
    }
    .filter-chip.active { background:#0d6efd; color:#fff; border-color:#0d6efd; box-shadow:0 2px 8px rgba(13,110,253,0.3); }
    .filter-chip:hover { border-color:#0d6efd; color:#0d6efd; }
    .filter-chip.active:hover { background:#0b5ed7; }

    /* ===== GANTT ===== */
    .gantt-wrapper {
        margin:0 24px 20px; overflow-x:auto; border:1px solid #dee2e6;
        border-radius:0 0 12px 12px; background:#fff;
        box-shadow:0 2px 12px rgba(0,0,0,0.05);
    }
    .gantt-row { display:flex; border-bottom:1px solid #eef0f2; min-height:50px; }
    .gantt-row:nth-child(even) { background:#fafbfc; }
    .gantt-row:hover { background:#eef3ff; }
    .gantt-label {
        width:220px; min-width:220px; padding:10px 14px;
        font-size:14px; font-weight:700; color:#1a1a2e;
        border-right:2px solid #dee2e6;
        display:flex; align-items:center;
        background:#fff; position:sticky; left:0; z-index:5;
    }
    .gantt-label .label-sub { font-size:11px; color:#8c8c8c; font-weight:500; margin-top:2px; }
    .gantt-timeline { position:relative; flex:1; min-height:50px; }

    .gantt-bar {
        position:absolute; top:8px; height:34px; border-radius:6px;
        cursor:pointer; overflow:hidden; display:flex; align-items:center;
        padding:0 8px; font-size:12px; color:#fff; font-weight:700;
        white-space:nowrap; min-width:4px;
        transition:opacity 0.2s, transform 0.15s;
        box-shadow:0 2px 6px rgba(0,0,0,0.2);
    }
    .gantt-bar:hover { opacity:0.9; transform:scaleY(1.1); z-index:20; }
    .gantt-bar span { overflow:hidden; text-overflow:ellipsis; text-shadow:0 1px 2px rgba(0,0,0,0.3); }

    .bar-scaduta { background:linear-gradient(135deg,#dc3545,#e35d6a); }
    .bar-critica { background:linear-gradient(135deg,#e67e22,#f0a04b); }
    .bar-normale { background:linear-gradient(135deg,#0d6efd,#5a9cff); }
    .bar-avviata { background:linear-gradient(135deg,#198754,#3cc07e); }
    .bar-pronta { background:linear-gradient(135deg,#0dcaf0,#56d8f0); }

    /* Day dividers */
    .gantt-day-line { position:absolute; top:0; bottom:0; border-left:1px dashed #e0e0e0; z-index:1; }
    .gantt-night { position:absolute; top:0; bottom:0; z-index:0; background:rgba(30,40,80,0.06); }
    .gantt-night-label { position:absolute; bottom:2px; left:3px; font-size:8px; color:rgba(30,40,80,0.35); font-weight:600; }

    /* Time header */
    .gantt-time-label {
        position:absolute; top:0; font-size:11px; color:#8c8c8c;
        padding:3px 6px; border-left:1px solid #e0e0e0;
        height:100%; display:flex; align-items:flex-end; padding-bottom:6px;
    }
    .gantt-time-label.day-label {
        font-weight:700; color:#1a1a2e; font-size:13px;
        align-items:flex-start; padding-top:8px;
        border-left:2px solid #adb5bd;
    }

    /* ===== TOOLTIP ===== */
    .gantt-tooltip {
        position:fixed; z-index:9999;
        background:linear-gradient(135deg,#1a1a2e,#16213e);
        color:#fff; padding:16px 20px; border-radius:12px;
        font-size:13px; pointer-events:none; display:none;
        max-width:380px; line-height:1.8;
        box-shadow:0 8px 30px rgba(0,0,0,0.35);
        border:1px solid rgba(255,255,255,0.08);
    }
    .gantt-tooltip .tt-title { font-size:16px; font-weight:800; color:#ffc107; margin-bottom:6px; }
    .gantt-tooltip .tt-row { display:flex; gap:6px; }
    .gantt-tooltip .tt-label { color:#8c9ab5; font-weight:500; min-width:110px; }
    .gantt-tooltip .tt-val { color:#fff; font-weight:600; }
    .gantt-tooltip .tt-divider { border-top:1px solid rgba(255,255,255,0.1); margin:6px 0; }

    /* ===== TABELLA ===== */
    .table-wrap { max-height:78vh; overflow:auto; margin:0 24px 20px; border-radius:0 0 12px 12px; box-shadow:0 2px 12px rgba(0,0,0,0.05); }
    .sched-table { width:100%; font-size:13px; border-collapse:collapse; background:#fff; }
    .sched-table th {
        background:#1a1a2e; color:#fff; padding:12px 14px; text-align:left;
        position:sticky; top:0; z-index:5; white-space:nowrap;
        font-size:12px; font-weight:600; letter-spacing:0.3px; text-transform:uppercase;
    }
    .sched-table td { padding:10px 14px; border-bottom:1px solid #eef0f2; white-space:nowrap; }
    .sched-table tr:nth-child(even) { background:#fafbfc; }
    .sched-table tr:hover { background:#eef3ff; }
    .sched-table .row-scaduta { background:#fce4e6 !important; }
    .sched-table .row-scaduta:hover { background:#f9d0d4 !important; }
    .sched-table .row-critica { background:#fff8e1 !important; }
    .sched-table .row-critica:hover { background:#fff3cd !important; }
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
    .gantt-label-comm .comm-title { font-size:13px; font-weight:800; color:#1a1a2e; }
    .gantt-label-comm .comm-sub { font-size:11px; color:#8c8c8c; font-weight:500; margin-top:2px; }

    /* ===== EMPTY STATE ===== */
    .empty-state { text-align:center; padding:60px 20px; color:#8c8c8c; }
    .empty-state .empty-icon { font-size:48px; margin-bottom:12px; }
    .empty-state p { font-size:16px; }
</style>

<!-- HEADER -->
<div class="sched-header">
    <div>
        <h2>Scheduling Produzione</h2>
        <div class="subtitle">Pianificazione e Gantt delle lavorazioni attive</div>
    </div>
    <div class="header-right">
        <input type="text" id="searchGlobal" class="search-box" placeholder="Cerca commessa, cliente, fase...">
        <a href="{{ route('owner.dashboard') }}" class="btn-back">← Dashboard</a>
    </div>
</div>

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
    <div class="table-wrap">
        <table class="sched-table" id="tabellaScheduling">
            <thead>
                <tr>
                    <th>Priorita</th>
                    <th>Commessa</th>
                    <th>Cliente</th>
                    <th>Descrizione</th>
                    <th>Fase</th>
                    <th>Reparto</th>
                    <th>Stato</th>
                    <th>Ore stimate</th>
                    <th>Consegna</th>
                    <th>Giorni</th>
                    <th>Inizio stimato</th>
                    <th>Fine stimata</th>
                </tr>
            </thead>
            <tbody id="tabellaBody"></tbody>
        </table>
    </div>
</div>

<!-- TOOLTIP -->
<div class="gantt-tooltip" id="tooltip"></div>

<script>
const DATA = @json(json_decode($dataJson));
const NOW = new Date();
let pxPerHour = 8;
let filtroReparti = new Set();
let searchQuery = '';

// ===================== CONFIGURAZIONE TURNI =====================
// Stampa offset: lavora 24h (anche notte), 1h pausa → 23h effettive/giorno
// Altri reparti: 8:00-18:00 (10h), 1h pausa → 9h effettive/giorno
// Domenica: tutti chiusi

const ORA_INIZIO = 8;
const ORA_FINE = 17;          // 18:00 meno 1h pausa = lavoro effettivo fino alle 17
const ORA_FINE_OFFSET = 23;   // 24h meno 1h pausa = lavoro fino alle 23:00

function isOffset(reparto) {
    return reparto.toLowerCase() === 'stampa offset';
}

// ===================== DOMENICA + TURNI =====================

function skipToWorkTime(h, reparto) {
    let d = new Date(NOW.getTime() + h * 3600000);
    // Salta domenica
    if (d.getDay() === 0) {
        h += 24 - d.getHours() - d.getMinutes() / 60;
        d = new Date(NOW.getTime() + h * 3600000);
    }
    // Per reparti non-offset, salta la notte
    if (!isOffset(reparto)) {
        const hour = d.getHours() + d.getMinutes() / 60;
        if (hour >= ORA_FINE) {
            // Dopo le 17: vai a domani 8:00
            h += (24 - hour) + ORA_INIZIO;
        } else if (hour < ORA_INIZIO) {
            // Prima delle 8: vai alle 8:00
            h += ORA_INIZIO - hour;
        }
        // Ricontrolla domenica dopo lo spostamento
        d = new Date(NOW.getTime() + h * 3600000);
        if (d.getDay() === 0) {
            h += 24;
        }
    } else {
        // Offset: salta solo la pausa 23:00-00:00
        const hour = d.getHours() + d.getMinutes() / 60;
        if (hour >= ORA_FINE_OFFSET) {
            h += 24 - hour;
            d = new Date(NOW.getTime() + h * 3600000);
            if (d.getDay() === 0) h += 24;
        }
    }
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

        if (isOffset(reparto)) {
            // Offset: lavora fino alle 23:00, pausa 23-00
            const oreDisponibili = ORA_FINE_OFFSET - hour;
            if (oreDisponibili <= 0) {
                pos += 24 - hour; // vai a mezzanotte
                const nd = new Date(NOW.getTime() + pos * 3600000);
                if (nd.getDay() === 0) pos += 24; // salta dom
                continue;
            }
            if (remaining <= oreDisponibili) {
                pos += remaining;
                remaining = 0;
            } else {
                remaining -= oreDisponibili;
                pos += (24 - hour); // a mezzanotte
                const nd = new Date(NOW.getTime() + pos * 3600000);
                if (nd.getDay() === 0) pos += 24;
            }
        } else {
            // Giorno: 8:00-17:00 (9h effettive)
            if (hour < ORA_INIZIO) {
                pos += ORA_INIZIO - hour;
                continue;
            }
            if (hour >= ORA_FINE) {
                pos += (24 - hour) + ORA_INIZIO;
                const nd = new Date(NOW.getTime() + pos * 3600000);
                if (nd.getDay() === 0) pos += 24;
                continue;
            }
            const oreDisponibili = ORA_FINE - hour;
            if (remaining <= oreDisponibili) {
                pos += remaining;
                remaining = 0;
            } else {
                remaining -= oreDisponibili;
                pos += (24 - hour) + ORA_INIZIO; // prossimo giorno 8:00
                const nd = new Date(NOW.getTime() + pos * 3600000);
                if (nd.getDay() === 0) pos += 24;
            }
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

function schedulaPerMacchina(data) {
    const macchine = {};
    data.forEach(f => {
        const key = f.reparto;
        if (!macchine[key]) macchine[key] = { nome: key, reparto_id: f.reparto_id, fasi: [] };
        macchine[key].fasi.push({...f});
    });
    Object.values(macchine).forEach(m => {
        m.fasi.sort((a, b) => a.priorita - b.priorita);
        let cursor = skipToWorkTime(0, m.nome);
        m.fasi.forEach(fase => {
            if (fase.stato === 2 && fase.data_inizio_reale) {
                const reale = new Date(fase.data_inizio_reale);
                const hFromNow = Math.max(0, (reale - NOW) / 3600000);
                fase.start_h = hFromNow;
                fase.end_h = advanceCursor(hFromNow, Math.max(fase.ore, 0.1), m.nome);
                if (fase.end_h > cursor) cursor = fase.end_h;
            } else {
                cursor = skipToWorkTime(cursor, m.nome);
                fase.start_h = cursor;
                fase.end_h = advanceCursor(cursor, Math.max(fase.ore, 0.1), m.nome);
                cursor = fase.end_h;
            }
        });
        m.ore_totali = cursor;
    });
    return Object.values(macchine).sort((a, b) => b.ore_totali - a.ore_totali);
}

function schedulaPerCommessa(data) {
    const commesse = {};
    data.forEach(f => {
        const key = f.commessa;
        if (!commesse[key]) commesse[key] = {
            commessa: key, cliente: f.cliente, descrizione: f.descrizione,
            consegna: f.consegna, giorni_consegna: f.giorni_consegna, fasi: []
        };
        commesse[key].fasi.push({...f});
    });
    Object.values(commesse).forEach(c => {
        c.fasi.sort((a, b) => faseOrdine(a.fase) - faseOrdine(b.fase));
        let cursor = skipToWorkTime(0, c.fasi[0]?.reparto || '');
        c.fasi.forEach(fase => {
            cursor = skipToWorkTime(cursor, fase.reparto);
            fase.start_h = cursor;
            fase.end_h = advanceCursor(cursor, Math.max(fase.ore, 0.1), fase.reparto);
            cursor = fase.end_h;
        });
        c.ore_totali = cursor;
        c.priorita_min = Math.min(...c.fasi.map(f => f.priorita));
    });
    return Object.values(commesse).sort((a, b) => a.priorita_min - b.priorita_min);
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
    const map = { 0:['Caricato','#6c757d'], 1:['Pronto','#0dcaf0'], 2:['Avviato','#198754'], 3:['Terminato','#1a1a2e'] };
    const [label, color] = map[stato] || ['?','#999'];
    return `<span class="badge-stato" style="background:${color}">${label}</span>`;
}

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
            dayLabel.style.color = '#0d6efd';
            dayLabel.style.fontWeight = '800';
            dayLabel.style.background = 'rgba(13,110,253,0.06)';
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

        // Zona notte per reparti non-offset
        if (repartoNome && !isOffset(repartoNome)) {
            const nightStartH = calH + ORA_FINE; // 17:00
            const nightEndH = calH + 24 + ORA_INIZIO; // 8:00 domani
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

// ===================== KPI =====================

function renderKPI() {
    const filtered = filterData(DATA);
    const commesseMap = {};
    filtered.forEach(f => { if (!commesseMap[f.commessa]) commesseMap[f.commessa] = f; });
    const commesse = Object.values(commesseMap);
    const totale = commesse.length;

    const scadute = commesse.filter(f => f.giorni_consegna !== null && f.giorni_consegna < 0).length;
    const critiche = commesse.filter(f => f.giorni_consegna !== null && f.giorni_consegna >= 0 && f.giorni_consegna <= 3).length;
    const inTempo = commesse.filter(f => f.giorni_consegna === null || f.giorni_consegna > 3).length;
    const oreTotali = Math.round(filtered.reduce((s, f) => s + f.ore, 0));

    const pct = (n) => totale > 0 ? Math.round(n / totale * 100) : 0;

    document.getElementById('kpiRow').innerHTML = `
        <div class="kpi-card kpi-blue">
            <div class="kpi-icon">C</div>
            <h3>${totale}</h3><small>Commesse attive</small>
        </div>
        <div class="kpi-card kpi-green">
            <div class="kpi-icon">&check;</div>
            <h3>${inTempo} <span style="font-size:18px;color:#198754;font-weight:700">(${pct(inTempo)}%)</span></h3>
            <small>In tempo</small>
        </div>
        <div class="kpi-card kpi-red">
            <div class="kpi-icon">!</div>
            <h3>${scadute} <span style="font-size:18px;color:#dc3545;font-weight:700">(${pct(scadute)}%)</span></h3>
            <small>In ritardo</small>
        </div>
        <div class="kpi-card kpi-orange">
            <div class="kpi-icon">!!</div>
            <h3>${critiche} <span style="font-size:18px;color:#e67e22;font-weight:700">(${pct(critiche)}%)</span></h3>
            <small>Critiche (&le;3gg)</small>
        </div>
        <div class="kpi-card kpi-purple">
            <div class="kpi-icon">H</div>
            <h3>${oreTotali}h</h3><small>Ore lavoro stimate</small>
        </div>
    `;
}

// ===================== TOOLTIP =====================

function showTooltip(e, fase) {
    const tt = document.getElementById('tooltip');
    const statoNomi = ['Caricato','Pronto','Avviato','Terminato'];
    const turno = isOffset(fase.reparto) ? '24h (notte inclusa)' : '8:00-17:00';
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
        <div class="tt-row"><span class="tt-label">Ore effettive</span><span class="tt-val">${fase.ore}h</span></div>
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
    const filtered = filterData(DATA);
    const macchine = schedulaPerMacchina(filtered);
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
        background:'linear-gradient(180deg,#f8f9fa,#eef0f2)',
        borderBottom:'2px solid #adb5bd', minHeight:'48px'
    });
    const headerLabel = el('div', 'gantt-label', { fontWeight:'700', fontSize:'13px', color:'#1a1a2e', textTransform:'uppercase', letterSpacing:'0.5px' });
    headerLabel.textContent = 'Macchina / Reparto';
    headerRow.appendChild(headerLabel);

    const timeHeader = el('div', 'gantt-timeline', { width:totalWidth+'px', position:'relative' });
    renderDayHeaders(timeHeader, maxCalOre);
    headerRow.appendChild(timeHeader);
    container.appendChild(headerRow);

    // Rows
    macchine.forEach(macchina => {
        const row = el('div', 'gantt-row');
        const label = el('div', 'gantt-label');
        const turnoLabel = isOffset(macchina.nome) ? '24h' : '8-17';
        label.innerHTML = `<div>${macchina.nome}<div class="label-sub">${macchina.fasi.length} fasi &middot; ${Math.round(macchina.fasi.reduce((s,f)=>s+f.ore,0))}h &middot; ${turnoLabel}</div></div>`;
        row.appendChild(label);

        const timeline = el('div', 'gantt-timeline', { width:totalWidth+'px' });
        renderDayLines(timeline, maxCalOre, macchina.nome);

        macchina.fasi.forEach(fase => {
            const dispStart = calToDisplay(fase.start_h);
            const dispEnd = calToDisplay(fase.end_h);
            const bar = el('div', 'gantt-bar ' + getBarClass(fase));
            bar.style.left = (dispStart * pxPerHour) + 'px';
            bar.style.width = Math.max((dispEnd - dispStart) * pxPerHour, 4) + 'px';
            const bw = (dispEnd - dispStart) * pxPerHour;
            if (bw > 70) bar.innerHTML = `<span>${fase.commessa}</span>`;
            else if (bw > 30) bar.innerHTML = `<span>${fase.commessa.slice(-6)}</span>`;
            bar.addEventListener('mouseenter', e => showTooltip(e, fase));
            bar.addEventListener('mouseleave', hideTooltip);
            bar.addEventListener('mousemove', moveTooltip);
            timeline.appendChild(bar);
        });

        row.appendChild(timeline);
        container.appendChild(row);
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
        background:'linear-gradient(180deg,#f8f9fa,#eef0f2)',
        borderBottom:'2px solid #adb5bd', minHeight:'48px'
    });
    const headerLabel = el('div', 'gantt-label gantt-label-comm', { fontWeight:'700', fontSize:'13px', color:'#1a1a2e', textTransform:'uppercase', letterSpacing:'0.5px' });
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
            timeline.appendChild(bar);
        });

        row.appendChild(timeline);
        container.appendChild(row);
    });
}

// ===================== TABELLA =====================

function renderTabella() {
    const filtered = filterData(DATA);
    const macchine = schedulaPerMacchina(filtered);
    const allFasi = [];
    macchine.forEach(m => m.fasi.forEach(f => allFasi.push(f)));
    allFasi.sort((a, b) => a.priorita - b.priorita);

    const tbody = document.getElementById('tabellaBody');
    tbody.innerHTML = '';

    allFasi.forEach(fase => {
        const tr = document.createElement('tr');
        if (fase.giorni_consegna !== null && fase.giorni_consegna < 0) tr.className = 'row-scaduta';
        else if (fase.giorni_consegna !== null && fase.giorni_consegna <= 3) tr.className = 'row-critica';

        tr.innerHTML = `
            <td><strong style="font-size:14px">${fase.priorita}</strong></td>
            <td><a href="/owner/commessa/${fase.commessa}" class="commessa-link">${fase.commessa}</a></td>
            <td>${fase.cliente}</td>
            <td style="white-space:normal;max-width:220px;">${fase.descrizione}</td>
            <td><strong>${fase.fase}</strong></td>
            <td>${fase.reparto}</td>
            <td>${statoLabel(fase.stato)}</td>
            <td style="font-weight:700">${fase.ore}h</td>
            <td>${formatDate(fase.consegna)}</td>
            <td style="font-weight:800;font-size:14px;${fase.giorni_consegna !== null && fase.giorni_consegna < 0 ? 'color:#dc3545' : 'color:#198754'}">${fase.giorni_consegna !== null ? fase.giorni_consegna + 'gg' : '-'}</td>
            <td>${oreToDateStr(fase.start_h)}</td>
            <td>${oreToDateStr(fase.end_h)}</td>
        `;
        tbody.appendChild(tr);
    });
}

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

// ===================== INIT =====================

function renderAll() {
    renderKPI();
    renderFilterChips();
    renderGanttMacchina();
    renderGanttCommessa();
    renderTabella();
}

renderAll();
</script>
@endsection
