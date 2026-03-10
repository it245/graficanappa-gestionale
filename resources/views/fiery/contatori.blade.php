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
</div>

<script>
// Refresh contatori ogni 60 secondi
setInterval(function() {
    fetch('{{ route("mes.fiery.contatori.json") }}')
        .then(r => r.json())
        .then(data => {
            if (data.errore) return;
            var fields = ['totale_1','colore_grande','nero_grande','colore_piccolo','nero_piccolo','foglio_lungo','scansioni'];
            fields.forEach(function(f) {
                var el = document.getElementById('cnt-' + f);
                if (el && data[f] !== null) {
                    el.textContent = Number(data[f]).toLocaleString('it-IT');
                }
            });
            var ts = document.getElementById('live-timestamp');
            if (ts) ts.textContent = 'Ultimo aggiornamento: ' + data.timestamp;
        })
        .catch(function() {});
}, 60000);
</script>
@endsection
