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

    .timestamp-live { font-size: 12px; color: #6c757d; margin-top: 8px; }

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
</style>

<div class="fiery-header">
    <div>
        <span class="machine-name">Canon imagePRESS V900 <small>Contatori</small></span>
    </div>
    <div class="nav-links">
        @if(request()->attributes->get('operatore_ruolo') !== 'fiery_contatori')
        <a href="{{ route('mes.fiery') }}">Dashboard</a>
        @endif
        <a href="{{ route('mes.fiery.contatori') }}" class="active">Contatori</a>
        @if(request()->attributes->get('operatore_ruolo') !== 'fiery_contatori')
        <a href="{{ route('owner.dashboard') }}">Owner</a>
        @endif
    </div>
</div>

{{-- Contatori Live --}}
<div class="fc">
    <div class="fc-label">Contatori Impressioni (SNMP) <span style="font-weight:400;text-transform:none;letter-spacing:0;font-size:11px;color:#9ca3af;">— ogni lato stampato = 1 impressione (duplex = 2 per foglio)</span></div>

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

{{-- Report Scatti per Categoria (formato fattura SAE) --}}
@if(!empty($reportCategorie) && $reportCategorie['totale'] > 0)
<div class="fc">
    <div class="fc-label">Report Scatti per Categoria — Periodo {{ \Carbon\Carbon::parse($da)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($a)->format('d/m/Y') }}</div>
    <table style="width:100%; max-width:500px; border-collapse:collapse; font-size:13px;">
        <thead>
            <tr style="background:#f1f5f9;">
                <th style="padding:8px; text-align:left; border:1px solid #cbd5e1;">Contatore</th>
                <th style="padding:8px; text-align:right; border:1px solid #cbd5e1;">Scatti</th>
            </tr>
        </thead>
        <tbody>
            <tr><td style="padding:6px 8px; border:1px solid #cbd5e1;">B/N A4</td><td style="padding:6px 8px; text-align:right; border:1px solid #cbd5e1;">{{ number_format($reportCategorie['bn_a4'], 0, ',', '.') }}</td></tr>
            <tr><td style="padding:6px 8px; border:1px solid #cbd5e1;">Colore A4</td><td style="padding:6px 8px; text-align:right; border:1px solid #cbd5e1;">{{ number_format($reportCategorie['colore_a4'], 0, ',', '.') }}</td></tr>
            <tr><td style="padding:6px 8px; border:1px solid #cbd5e1;">B/N A3</td><td style="padding:6px 8px; text-align:right; border:1px solid #cbd5e1;">{{ number_format($reportCategorie['bn_a3'], 0, ',', '.') }}</td></tr>
            <tr><td style="padding:6px 8px; border:1px solid #cbd5e1;">Colore A3</td><td style="padding:6px 8px; text-align:right; border:1px solid #cbd5e1;">{{ number_format($reportCategorie['colore_a3'], 0, ',', '.') }}</td></tr>
            <tr><td style="padding:6px 8px; border:1px solid #cbd5e1;">Banner</td><td style="padding:6px 8px; text-align:right; border:1px solid #cbd5e1;">{{ number_format($reportCategorie['banner'], 0, ',', '.') }}</td></tr>
            <tr style="background:#fef9c3; font-weight:bold;">
                <td style="padding:8px; border:1px solid #cbd5e1;">TOTALE</td>
                <td style="padding:8px; text-align:right; border:1px solid #cbd5e1;">{{ number_format($reportCategorie['totale'], 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>
</div>
@endif

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
            <strong>{{ number_format($totFogli, 0, ',', '.') }}</strong> fogli
            (<strong>{{ number_format(collect($clickPerCommessa)->sum('fogli_grande'), 0, ',', '.') }}</strong> grandi
            + <strong>{{ number_format(collect($clickPerCommessa)->sum('fogli_piccolo'), 0, ',', '.') }}</strong> piccoli)
            &middot; <strong>{{ number_format($totColore, 0, ',', '.') }}</strong> colore
            &middot; <strong>{{ number_format($totBN, 0, ',', '.') }}</strong> B/N
            &middot; {{ collect($clickPerCommessa)->where('commessa', '!=', '(Senza commessa)')->count() }} commesse
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
                    <th>Grande</th>
                    <th>Piccolo</th>
                    <th>Pag. Colore</th>
                    <th>Pag. B/N</th>
                    <th>Copie</th>
                    <th>Run</th>
                    <th>Formato</th>
                </tr>
            </thead>
            <tbody>
                @foreach($clickPerCommessa as $c)
                <tr style="{{ $c['commessa'] === '(Senza commessa)' ? 'background:#fff8f0; font-style:italic;' : '' }}">
                    <td>{{ $c['commessa'] }}</td>
                    <td>{{ $c['cliente'] }}</td>
                    <td>{{ $c['descrizione'] }}</td>
                    <td>{{ number_format($c['fogli'], 0, ',', '.') }}</td>
                    <td>{{ number_format($c['fogli_grande'] ?? 0, 0, ',', '.') }}</td>
                    <td>{{ number_format($c['fogli_piccolo'] ?? 0, 0, ',', '.') }}</td>
                    <td>{{ number_format($c['colore'], 0, ',', '.') }}</td>
                    <td>{{ number_format($c['bn'], 0, ',', '.') }}</td>
                    <td>{{ number_format($c['copie'], 0, ',', '.') }}</td>
                    <td>{{ $c['run'] }}</td>
                    <td>
                        @foreach($c['formati'] as $fmt)
                        @php
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
                    <td>{{ number_format(collect($clickPerCommessa)->sum('fogli_grande'), 0, ',', '.') }}</td>
                    <td>{{ number_format(collect($clickPerCommessa)->sum('fogli_piccolo'), 0, ',', '.') }}</td>
                    <td>{{ number_format($totColore, 0, ',', '.') }}</td>
                    <td>{{ number_format($totBN, 0, ',', '.') }}</td>
                    <td>{{ number_format(collect($clickPerCommessa)->sum('copie'), 0, ',', '.') }}</td>
                    <td>{{ collect($clickPerCommessa)->sum('run') }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
        </div>

        {{-- Confronto SNMP vs Accounting --}}
        @if(!isset($live['errore']))
        @php
            $totImprAccounting = $totColore + $totBN;
            $totImprSNMP = ($live['colore_grande'] ?? 0) + ($live['nero_grande'] ?? 0) + ($live['colore_piccolo'] ?? 0) + ($live['nero_piccolo'] ?? 0);
            $delta = $totImprSNMP - $totImprAccounting;
        @endphp
        <div style="margin-top:16px; padding:14px 18px; background:#f8f9fa; border:1px solid #e9ecef; border-radius:8px; font-size:12px; color:#495057;">
            <strong style="color:#1a1a2e;">Confronto SNMP vs Accounting</strong>
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(200px, 1fr)); gap:12px; margin-top:10px;">
                <div>
                    <div style="font-size:10px;font-weight:600;text-transform:uppercase;color:#6c757d;letter-spacing:0.5px;">Impressioni SNMP (totale macchina)</div>
                    <div style="font-size:18px;font-weight:800;color:#1a1a2e;">{{ number_format($totImprSNMP, 0, ',', '.') }}</div>
                    <div style="font-size:10px;color:#9ca3af;">Col/G {{ number_format($live['colore_grande'] ?? 0, 0, ',', '.') }} + Nero/G {{ number_format($live['nero_grande'] ?? 0, 0, ',', '.') }} + Col/P {{ number_format($live['colore_piccolo'] ?? 0, 0, ',', '.') }} + Nero/P {{ number_format($live['nero_piccolo'] ?? 0, 0, ',', '.') }}</div>
                </div>
                <div>
                    <div style="font-size:10px;font-weight:600;text-transform:uppercase;color:#6c757d;letter-spacing:0.5px;">Impressioni Accounting (periodo)</div>
                    <div style="font-size:18px;font-weight:800;color:#1d4ed8;">{{ number_format($totImprAccounting, 0, ',', '.') }}</div>
                    <div style="font-size:10px;color:#9ca3af;">Colore {{ number_format($totColore, 0, ',', '.') }} + B/N {{ number_format($totBN, 0, ',', '.') }}</div>
                </div>
                <div>
                    <div style="font-size:10px;font-weight:600;text-transform:uppercase;color:#6c757d;letter-spacing:0.5px;">Differenza (non tracciato)</div>
                    <div style="font-size:18px;font-weight:800;color:{{ $delta > 0 ? '#d97706' : '#059669' }};">{{ $delta > 0 ? '+' : '' }}{{ number_format($delta, 0, ',', '.') }}</div>
                    <div style="font-size:10px;color:#9ca3af;">{{ $delta > 0 ? 'Calibrazioni, prove, job senza titolo' : 'Allineato' }}</div>
                </div>
            </div>
        </div>
        @endif
    @endif
</div>
</div>

<script>
// Refresh contatori ogni 60 secondi
setInterval(function() {
    fetch('{{ route("mes.fiery.contatori.json") }}')
        .then(r => r.json())
        .then(data => {
            if (data.errore) return;
            ['totale_1','colore_grande','nero_grande','colore_piccolo','nero_piccolo','foglio_lungo','scansioni'].forEach(function(f) {
                var el = document.getElementById('cnt-' + f);
                if (el && data[f] !== null) el.textContent = Number(data[f]).toLocaleString('it-IT');
            });
            var ts = document.getElementById('live-timestamp');
            if (ts) ts.textContent = 'Ultimo aggiornamento: ' + data.timestamp;
        })
        .catch(function() {});
}, 60000);
</script>
@endsection
