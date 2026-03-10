@extends('layouts.app')

@section('content')
<div class="container-fluid px-3">
<style>
    body { background: #f0f2f5; }
    .fiery-header {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 24px; padding: 16px 0;
    }
    .fiery-header .machine-name {
        font-size: 22px; font-weight: 700; color: #1a1a2e; letter-spacing: -0.5px;
    }
    .fiery-header .machine-name small {
        font-size: 13px; font-weight: 400; color: #6c757d; margin-left: 12px;
    }
    .fiery-header .nav-links a {
        color: #495057; text-decoration: none; font-size: 13px;
        padding: 6px 14px; border: 1px solid #dee2e6; border-radius: 6px;
        margin-left: 8px; transition: all 0.2s;
    }
    .fiery-header .nav-links a:hover { background: #e9ecef; border-color: #adb5bd; }
    .fiery-header .nav-links a.active { background: #1d4ed8; color: #fff; border-color: #1d4ed8; }

    .fc {
        background: #fff; border-radius: 14px; border: 1px solid #dee2e6;
        padding: 20px 24px; margin-bottom: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }
    .fc-label {
        font-size: 11px; font-weight: 600; text-transform: uppercase;
        color: #6c757d; letter-spacing: 1px; margin-bottom: 12px;
    }

    .counter-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 16px;
    }
    .counter-card {
        background: #f8f9fa; border-radius: 12px; padding: 16px 20px;
        border: 1px solid #e9ecef; text-align: center;
    }
    .counter-card.total { background: #dbeafe; border-color: #93c5fd; }
    .counter-card .counter-label {
        font-size: 11px; font-weight: 600; text-transform: uppercase;
        color: #6c757d; letter-spacing: 0.5px;
    }
    .counter-card.total .counter-label { color: #1d4ed8; }
    .counter-card .counter-value {
        font-size: 28px; font-weight: 800; color: #1a1a2e; margin-top: 4px;
        font-variant-numeric: tabular-nums;
    }
    .counter-card.total .counter-value { color: #1d4ed8; }
    .counter-card .counter-delta {
        font-size: 12px; color: #059669; margin-top: 4px; font-weight: 600;
    }

    .storico-table {
        width: 100%; border-collapse: separate; border-spacing: 0;
    }
    .storico-table thead th {
        font-size: 10px; text-transform: uppercase; letter-spacing: 1px;
        color: #6c757d; font-weight: 600; padding: 8px 12px;
        border-bottom: 2px solid #dee2e6; text-align: right;
    }
    .storico-table thead th:first-child { text-align: left; }
    .storico-table tbody td {
        font-size: 13px; color: #495057; padding: 10px 12px;
        border-bottom: 1px solid #f1f3f5; text-align: right;
        font-variant-numeric: tabular-nums;
    }
    .storico-table tbody td:first-child { text-align: left; font-weight: 500; color: #212529; }
    .storico-table tbody tr:hover { background: #f8f9fa; }
    .delta-col { color: #059669; font-weight: 600; font-size: 12px; }
    .delta-col.zero { color: #9ca3af; }

    .timestamp-live {
        font-size: 12px; color: #6c757d; margin-top: 8px;
    }

    /* Click per commessa */
    .filter-bar {
        display: flex; align-items: center; gap: 12px; flex-wrap: wrap;
        margin-bottom: 16px; padding: 12px 16px;
        background: #f8f9fa; border-radius: 8px; border: 1px solid #e9ecef;
    }
    .filter-bar label { font-size: 12px; font-weight: 600; color: #495057; margin: 0; }
    .filter-bar input[type="date"] {
        font-size: 13px; padding: 4px 10px; border: 1px solid #dee2e6;
        border-radius: 6px; color: #212529;
    }
    .filter-bar button {
        background: #1d4ed8; color: #fff; border: none; padding: 6px 16px;
        border-radius: 6px; font-size: 13px; font-weight: 600; cursor: pointer;
    }
    .filter-bar button:hover { background: #1e40af; }
    .filter-bar .totals { margin-left: auto; font-size: 12px; color: #6c757d; }
    .filter-bar .totals strong { color: #1a1a2e; }

    .click-table { width: 100%; border-collapse: separate; border-spacing: 0; }
    .click-table thead th {
        font-size: 10px; text-transform: uppercase; letter-spacing: 1px;
        color: #6c757d; font-weight: 600; padding: 8px 10px;
        border-bottom: 2px solid #dee2e6; text-align: right; white-space: nowrap;
    }
    .click-table thead th:nth-child(1),
    .click-table thead th:nth-child(2),
    .click-table thead th:nth-child(3) { text-align: left; }
    .click-table tbody td {
        font-size: 13px; color: #495057; padding: 8px 10px;
        border-bottom: 1px solid #f1f3f5; text-align: right;
        font-variant-numeric: tabular-nums;
    }
    .click-table tbody td:nth-child(1) { text-align: left; font-weight: 600; color: #1d4ed8; }
    .click-table tbody td:nth-child(2) { text-align: left; font-weight: 500; color: #212529; max-width: 160px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .click-table tbody td:nth-child(3) { text-align: left; font-size: 11px; color: #6c757d; max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .click-table tbody tr:hover { background: #f0f7ff; }
    .click-table tfoot td {
        font-size: 13px; font-weight: 700; color: #1a1a2e; padding: 10px;
        border-top: 2px solid #dee2e6; text-align: right;
    }
    .click-table tfoot td:first-child { text-align: left; }
    .formato-tag {
        display: inline-block; font-size: 10px; background: #e9ecef; color: #495057;
        padding: 1px 6px; border-radius: 4px; margin: 1px 2px;
    }

    /* Toner bars */
    .toner-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; }
    .toner-item { background: #f8f9fa; border-radius: 10px; padding: 12px 16px; border: 1px solid #e9ecef; }
    .toner-item .toner-name { font-size: 11px; font-weight: 600; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; }
    .toner-item .toner-bar { height: 10px; background: #e9ecef; border-radius: 5px; margin-top: 8px; overflow: hidden; }
    .toner-item .toner-fill { height: 100%; border-radius: 5px; transition: width 0.5s ease; }
    .toner-item .toner-pct { font-size: 20px; font-weight: 800; margin-top: 6px; font-variant-numeric: tabular-nums; }

    /* Vassoi / Trays */
    .tray-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }
    .tray-item { background: #f8f9fa; border-radius: 10px; padding: 12px 16px; border: 1px solid #e9ecef; text-align: center; }
    .tray-item .tray-name { font-size: 11px; font-weight: 600; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; }
    .tray-item .tray-bar { height: 8px; background: #e9ecef; border-radius: 4px; margin-top: 8px; overflow: hidden; }
    .tray-item .tray-fill { height: 100%; border-radius: 4px; background: #3b82f6; transition: width 0.5s ease; }
    .tray-item .tray-info { font-size: 13px; font-weight: 600; color: #1a1a2e; margin-top: 6px; }
    .tray-item .tray-type { font-size: 10px; color: #9ca3af; margin-top: 2px; }

    /* Finisher */
    .finisher-grid { display: flex; gap: 16px; flex-wrap: wrap; }
    .finisher-item { background: #f8f9fa; border-radius: 10px; padding: 12px 20px; border: 1px solid #e9ecef; min-width: 160px; text-align: center; }
    .finisher-item .fin-name { font-size: 11px; font-weight: 600; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; }
    .finisher-item .fin-pct { font-size: 22px; font-weight: 800; color: #1a1a2e; margin-top: 4px; }

    /* Alert */
    .alert-box { background: #fef3c7; border: 1px solid #fbbf24; border-radius: 8px; padding: 10px 16px; font-size: 13px; color: #92400e; font-weight: 500; margin-top: 12px; }
    .alert-box.ok { background: #d1fae5; border-color: #6ee7b7; color: #065f46; }

    .status-section { margin-top: 16px; }
    .status-section-label { font-size: 11px; font-weight: 600; color: #9ca3af; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; }
</style>

<div class="fiery-header">
    <div>
        <span class="machine-name">Canon imagePRESS V900 <small>Contatori</small></span>
    </div>
    <div class="nav-links">
        <a href="{{ route('mes.fiery') }}">Dashboard</a>
        <a href="{{ route('mes.fiery.contatori') }}" class="active">Contatori</a>
        <a href="{{ route('owner.dashboard') }}">Owner</a>
    </div>
</div>

{{-- Contatori Live --}}
<div class="fc">
    <div class="fc-label">Contatori Live (SNMP)</div>

    @if(isset($live['errore']))
        <div style="color:#dc2626; font-size:14px;">{{ $live['errore'] }}</div>
    @else
        <div class="counter-grid" id="counter-grid">
            <div class="counter-card total">
                <div class="counter-label">Totale</div>
                <div class="counter-value" id="cnt-totale_1">{{ number_format($live['totale_1'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="counter-card">
                <div class="counter-label">Colore / Grande</div>
                <div class="counter-value" id="cnt-colore_grande">{{ number_format($live['colore_grande'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="counter-card">
                <div class="counter-label">Nero / Grande</div>
                <div class="counter-value" id="cnt-nero_grande">{{ number_format($live['nero_grande'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="counter-card">
                <div class="counter-label">Colore / Piccolo</div>
                <div class="counter-value" id="cnt-colore_piccolo">{{ number_format($live['colore_piccolo'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="counter-card">
                <div class="counter-label">Nero / Piccolo</div>
                <div class="counter-value" id="cnt-nero_piccolo">{{ number_format($live['nero_piccolo'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="counter-card">
                <div class="counter-label">Foglio Lungo</div>
                <div class="counter-value" id="cnt-foglio_lungo">{{ number_format($live['foglio_lungo'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="counter-card">
                <div class="counter-label">Scansioni</div>
                <div class="counter-value" id="cnt-scansioni">{{ number_format($live['scansioni'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>
        {{-- Toner Levels --}}
        @if(!empty($live['toner']))
        <div class="status-section" id="toner-section">
            <div class="status-section-label">Livelli Toner</div>
            <div class="toner-grid">
                @foreach($live['toner'] as $t)
                @php
                    $color = match($t['nome']) {
                        'Nero' => '#1a1a2e',
                        'Cyan' => '#06b6d4',
                        'Magenta' => '#ec4899',
                        'Yellow' => '#eab308',
                        'Waste Toner' => '#78716c',
                        default => '#6b7280',
                    };
                    $pct = $t['livello'];
                    $warn = $pct >= 0 && $pct <= 15;
                @endphp
                <div class="toner-item" style="{{ $warn ? 'border-color:#fbbf24; background:#fffbeb;' : '' }}">
                    <div class="toner-name">{{ $t['nome'] }}</div>
                    <div class="toner-bar">
                        <div class="toner-fill" style="width:{{ max($pct, 0) }}%; background:{{ $color }};"></div>
                    </div>
                    <div class="toner-pct" style="color:{{ $color }}">{{ $pct >= 0 ? $pct . '%' : '?' }}</div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Vassoi Carta --}}
        @if(!empty($live['vassoi']))
        <div class="status-section" id="vassoi-section">
            <div class="status-section-label">Vassoi Carta</div>
            <div class="tray-grid">
                @foreach($live['vassoi'] as $v)
                @php
                    $pct = $v['percentuale'];
                    $barColor = $pct !== null && $pct >= 0 && $pct <= 20 ? '#ef4444' : '#3b82f6';
                @endphp
                <div class="tray-item" style="{{ $pct !== null && $pct >= 0 && $pct <= 20 ? 'border-color:#fca5a5; background:#fef2f2;' : '' }}">
                    <div class="tray-name">{{ $v['nome'] ?: 'Vassoio ' . ($loop->index + 1) }}</div>
                    <div class="tray-bar">
                        <div class="tray-fill" style="width:{{ $pct !== null && $pct >= 0 ? $pct : 0 }}%; background:{{ $barColor }};"></div>
                    </div>
                    <div class="tray-info">
                        @if($pct === -1)
                            Presente
                        @elseif($pct !== null)
                            {{ $pct }}% &middot; {{ number_format($v['livello'], 0, ',', '.') }}/{{ number_format($v['capacita'], 0, ',', '.') }}
                        @else
                            -
                        @endif
                    </div>
                    @if($v['tipo'])
                    <div class="tray-type">{{ $v['tipo'] }}</div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Finisher Punti --}}
        @if(!empty($live['punti']))
        <div class="status-section" id="punti-section">
            <div class="status-section-label">Finisher &mdash; Punti Metallici</div>
            <div class="finisher-grid">
                @foreach($live['punti'] as $p)
                @php $warn = $p['livello'] >= 0 && $p['livello'] <= 20; @endphp
                <div class="finisher-item" style="{{ $warn ? 'border-color:#fbbf24; background:#fffbeb;' : '' }}">
                    <div class="fin-name">{{ $p['nome'] }}</div>
                    <div class="fin-pct" style="{{ $warn ? 'color:#dc2626;' : '' }}">{{ $p['livello'] >= 0 ? $p['livello'] . '%' : '?' }}</div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Alert --}}
        @if(!empty($live['alert']))
        <div id="alert-box" class="alert-box" style="margin-top:12px;">
            ⚠ {{ $live['alert'] }}
        </div>
        @else
        <div id="alert-box" class="alert-box ok" style="margin-top:12px;">
            Nessun avviso attivo
        </div>
        @endif

        <div class="timestamp-live" id="live-timestamp">Ultimo aggiornamento: {{ $live['timestamp'] ?? '-' }}</div>
    @endif
</div>

{{-- Storico Snapshot --}}
<div class="fc">
    <div class="fc-label">Storico Snapshot Settimanali</div>

    @if($storico->isEmpty())
        <div style="color:#6c757d; font-size:14px; padding:20px 0;">
            Nessuno snapshot ancora salvato. Il primo verrà registrato lunedi alle 8:00.<br>
            Per creare uno snapshot manualmente: <code>php artisan fiery:snapshot-contatori</code>
        </div>
    @else
        <div style="overflow-x:auto;">
        <table class="storico-table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Totale</th>
                    <th>Col/Grande</th>
                    <th>Nero/Grande</th>
                    <th>Col/Piccolo</th>
                    <th>Nero/Piccolo</th>
                    <th>Foglio Lungo</th>
                    <th>Scansioni</th>
                    <th>Delta Totale</th>
                </tr>
            </thead>
            <tbody>
                @foreach($storico as $i => $s)
                @php
                    $prev = $storico[$i + 1] ?? null;
                    $delta = $prev ? $s->totale_1 - $prev->totale_1 : null;
                @endphp
                <tr>
                    <td>{{ $s->rilevato_at->format('d/m/Y H:i') }}</td>
                    <td>{{ number_format($s->totale_1, 0, ',', '.') }}</td>
                    <td>{{ number_format($s->colore_grande, 0, ',', '.') }}</td>
                    <td>{{ number_format($s->nero_grande, 0, ',', '.') }}</td>
                    <td>{{ number_format($s->colore_piccolo, 0, ',', '.') }}</td>
                    <td>{{ number_format($s->nero_piccolo, 0, ',', '.') }}</td>
                    <td>{{ number_format($s->foglio_lungo, 0, ',', '.') }}</td>
                    <td>{{ number_format($s->scansioni, 0, ',', '.') }}</td>
                    <td class="delta-col {{ $delta === null || $delta === 0 ? 'zero' : '' }}">
                        {{ $delta !== null ? '+' . number_format($delta, 0, ',', '.') : '-' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    @endif
</div>

{{-- Click per Commessa --}}
<div class="fc">
    <div class="fc-label">Click per Commessa (Fiery Accounting)</div>

    <form method="GET" action="{{ route('mes.fiery.contatori') }}" class="filter-bar">
        <label>Dal</label>
        <input type="date" name="da" value="{{ $da }}">
        <label>Al</label>
        <input type="date" name="a" value="{{ $a }}">
        <button type="submit">Filtra</button>
        @if(!empty($clickPerCommessa))
        @php
            $totFogli = collect($clickPerCommessa)->sum('fogli');
            $totColore = collect($clickPerCommessa)->sum('colore');
            $totBN = collect($clickPerCommessa)->sum('bn');
        @endphp
        <div class="totals">
            Totale: <strong>{{ number_format($totFogli, 0, ',', '.') }}</strong> fogli
            &middot; <strong>{{ number_format($totColore, 0, ',', '.') }}</strong> colore
            &middot; <strong>{{ number_format($totBN, 0, ',', '.') }}</strong> B/N
            &middot; {{ count($clickPerCommessa) }} commesse
        </div>
        @endif
    </form>

    @if(empty($clickPerCommessa))
        <div style="color:#6c757d; font-size:14px; padding:12px 0;">Nessun dato nel periodo selezionato.</div>
    @else
        <div style="overflow-x:auto;">
        <table class="click-table">
            <thead>
                <tr>
                    <th>Commessa</th>
                    <th>Cliente</th>
                    <th>Descrizione</th>
                    <th>Fogli</th>
                    <th>Pag. Colore</th>
                    <th>Pag. B/N</th>
                    <th>Copie</th>
                    <th>Run</th>
                    <th>Formato</th>
                </tr>
            </thead>
            <tbody>
                @foreach($clickPerCommessa as $c)
                <tr>
                    <td>{{ $c['commessa'] }}</td>
                    <td>{{ $c['cliente'] }}</td>
                    <td>{{ $c['descrizione'] }}</td>
                    <td>{{ number_format($c['fogli'], 0, ',', '.') }}</td>
                    <td>{{ number_format($c['colore'], 0, ',', '.') }}</td>
                    <td>{{ number_format($c['bn'], 0, ',', '.') }}</td>
                    <td>{{ number_format($c['copie'], 0, ',', '.') }}</td>
                    <td>{{ $c['run'] }}</td>
                    <td>
                        @foreach($c['formati'] as $fmt)
                        @php
                            // Semplifica il formato da "Formato personal. (330.000x480.000 mm)" a "330x480"
                            $fmtShort = $fmt;
                            if (preg_match('/\((\d+)[\.,]\d+\s*x\s*(\d+)[\.,]\d+/', $fmt, $fm)) {
                                $fmtShort = $fm[1] . 'x' . $fm[2];
                            } elseif (preg_match('/^(SRA3|A4|A3)$/i', $fmt)) {
                                $fmtShort = $fmt;
                            }
                        @endphp
                        <span class="formato-tag">{{ $fmtShort }}</span>
                        @endforeach
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3">TOTALE</td>
                    <td>{{ number_format($totFogli, 0, ',', '.') }}</td>
                    <td>{{ number_format($totColore, 0, ',', '.') }}</td>
                    <td>{{ number_format($totBN, 0, ',', '.') }}</td>
                    <td>{{ number_format(collect($clickPerCommessa)->sum('copie'), 0, ',', '.') }}</td>
                    <td>{{ collect($clickPerCommessa)->sum('run') }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        </div>
    @endif
</div>
</div>

<script>
var tonerColors = {'Nero':'#1a1a2e','Cyan':'#06b6d4','Magenta':'#ec4899','Yellow':'#eab308','Waste Toner':'#78716c'};

function fmtNum(n) { return Number(n).toLocaleString('it-IT'); }

function refreshContatori() {
    fetch('{{ route("mes.fiery.contatori.json") }}')
        .then(r => r.json())
        .then(data => {
            if (data.errore) return;

            // Contatori numerici
            ['totale_1','colore_grande','nero_grande','colore_piccolo','nero_piccolo','foglio_lungo','scansioni'].forEach(function(f) {
                var el = document.getElementById('cnt-' + f);
                if (el && data[f] !== null) el.textContent = fmtNum(data[f]);
            });

            // Toner
            if (data.toner && data.toner.length) {
                var html = '';
                data.toner.forEach(function(t) {
                    var color = tonerColors[t.nome] || '#6b7280';
                    var warn = t.livello >= 0 && t.livello <= 15;
                    html += '<div class="toner-item" style="' + (warn ? 'border-color:#fbbf24;background:#fffbeb;' : '') + '">' +
                        '<div class="toner-name">' + t.nome + '</div>' +
                        '<div class="toner-bar"><div class="toner-fill" style="width:' + Math.max(t.livello, 0) + '%;background:' + color + ';"></div></div>' +
                        '<div class="toner-pct" style="color:' + color + '">' + (t.livello >= 0 ? t.livello + '%' : '?') + '</div></div>';
                });
                var sec = document.getElementById('toner-section');
                if (sec) sec.querySelector('.toner-grid').innerHTML = html;
            }

            // Vassoi
            if (data.vassoi && data.vassoi.length) {
                var html = '';
                data.vassoi.forEach(function(v, i) {
                    var pct = v.percentuale;
                    var low = pct !== null && pct >= 0 && pct <= 20;
                    var barColor = low ? '#ef4444' : '#3b82f6';
                    var info = '-';
                    if (pct === -1) info = 'Presente';
                    else if (pct !== null) info = pct + '% &middot; ' + fmtNum(v.livello) + '/' + fmtNum(v.capacita);
                    html += '<div class="tray-item" style="' + (low ? 'border-color:#fca5a5;background:#fef2f2;' : '') + '">' +
                        '<div class="tray-name">' + (v.nome || 'Vassoio ' + (i+1)) + '</div>' +
                        '<div class="tray-bar"><div class="tray-fill" style="width:' + (pct !== null && pct >= 0 ? pct : 0) + '%;background:' + barColor + ';"></div></div>' +
                        '<div class="tray-info">' + info + '</div>' +
                        (v.tipo ? '<div class="tray-type">' + v.tipo + '</div>' : '') + '</div>';
                });
                var sec = document.getElementById('vassoi-section');
                if (sec) sec.querySelector('.tray-grid').innerHTML = html;
            }

            // Punti finisher
            if (data.punti && data.punti.length) {
                var html = '';
                data.punti.forEach(function(p) {
                    var warn = p.livello >= 0 && p.livello <= 20;
                    html += '<div class="finisher-item" style="' + (warn ? 'border-color:#fbbf24;background:#fffbeb;' : '') + '">' +
                        '<div class="fin-name">' + p.nome + '</div>' +
                        '<div class="fin-pct" style="' + (warn ? 'color:#dc2626;' : '') + '">' + (p.livello >= 0 ? p.livello + '%' : '?') + '</div></div>';
                });
                var sec = document.getElementById('punti-section');
                if (sec) sec.querySelector('.finisher-grid').innerHTML = html;
            }

            // Alert
            var alertBox = document.getElementById('alert-box');
            if (alertBox) {
                if (data.alert) {
                    alertBox.className = 'alert-box';
                    alertBox.innerHTML = '⚠ ' + data.alert;
                } else {
                    alertBox.className = 'alert-box ok';
                    alertBox.innerHTML = 'Nessun avviso attivo';
                }
            }

            var ts = document.getElementById('live-timestamp');
            if (ts) ts.textContent = 'Ultimo aggiornamento: ' + data.timestamp;
        })
        .catch(function() {});
}

setInterval(refreshContatori, 60000);
</script>
@endsection
