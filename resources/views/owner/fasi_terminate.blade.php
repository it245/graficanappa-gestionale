@extends('layouts.mes')

@section('topbar-title', 'Fasi Terminate')

@section('sidebar-items')
<div class="mes-sidebar-section">
    <div class="mes-sidebar-section-label">Produzione</div>
    <a href="{{ route('owner.dashboard') }}?op_token={{ request('op_token') }}" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        Dashboard
    </a>
    <a href="{{ route('owner.scheduling') }}?op_token={{ request('op_token') }}" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        Scheduling
    </a>
</div>
<div class="mes-sidebar-section">
    <div class="mes-sidebar-section-label">Analisi</div>
    <a href="{{ route('owner.reportOre') }}?op_token={{ request('op_token') }}" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        Report Ore
    </a>
    <a href="{{ route('owner.fasiTerminate') }}?op_token={{ request('op_token') }}" class="mes-sidebar-item active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        Fasi Terminate
    </a>
</div>
@endsection

@section('content')
<style>
/* =========================
   TABELLA (EXCEL STYLE)
   ========================= */
.table-wrapper {
    width: 100%;
    max-width: 100%;
    overflow: auto;
    margin: 0;
    max-height: calc(100vh - 280px);
}

table.ft-table {
    width: 3200px;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 12px;
}

.ft-table th, .ft-table td {
    border: 1px solid var(--border-color, #dee2e6);
    padding: 3px 6px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.3;
}

.ft-table thead th {
    background: var(--bg-sidebar, #1e293b);
    color: #ffffff;
    font-size: 11.5px;
    position: sticky;
    top: 0;
    z-index: 2;
}

.ft-table tr:hover td {
    background: rgba(0, 0, 0, 0.03);
}

.ft-table tbody tr:nth-child(even) {
    background: #f8f9fa;
}

/* =========================
   COLORI PERCORSO PRODUTTIVO
   ========================= */
tr.percorso-base td { background-color: #d4edda !important; }
tr.percorso-rilievi td { background-color: #fff3cd !important; }
tr.percorso-caldo td { background-color: #f96f2a !important; }
tr.percorso-completo td { background-color: #f8d7da !important; }

/* =========================
   LARGHEZZA COLONNE
   ========================= */
.ft-table th:nth-child(1), .ft-table td:nth-child(1) { width: 90px; }
.ft-table th:nth-child(2), .ft-table td:nth-child(2) { width: 180px; white-space: normal; }
.ft-table th:nth-child(3), .ft-table td:nth-child(3) { width: 110px; }
.ft-table th:nth-child(4), .ft-table td:nth-child(4) { width: 280px; max-width: 280px; white-space: normal; }
.ft-table th:nth-child(5), .ft-table td:nth-child(5),
.ft-table th:nth-child(6), .ft-table td:nth-child(6),
.ft-table th:nth-child(7), .ft-table td:nth-child(7) { width: 70px; text-align: center; }
.ft-table th:nth-child(8), .ft-table td:nth-child(8),
.ft-table th:nth-child(9), .ft-table td:nth-child(9) { width: 115px; }
.ft-table th:nth-child(10), .ft-table td:nth-child(10),
.ft-table th:nth-child(11), .ft-table td:nth-child(11) { width: 160px; white-space: normal; }
.ft-table th:nth-child(12), .ft-table td:nth-child(12),
.ft-table th:nth-child(13), .ft-table td:nth-child(13) { width: 80px; text-align: center; }
.ft-table th:nth-child(14), .ft-table td:nth-child(14),
.ft-table th:nth-child(15), .ft-table td:nth-child(15) { width: 110px; }
.ft-table th:nth-child(16), .ft-table td:nth-child(16) { width: 130px; white-space: normal; }
.ft-table th:nth-child(17), .ft-table td:nth-child(17) { width: 80px; text-align: center; }
.ft-table th:nth-child(18), .ft-table td:nth-child(18) { width: 160px; white-space: normal; }
.ft-table th:nth-child(19), .ft-table td:nth-child(19),
.ft-table th:nth-child(20), .ft-table td:nth-child(20) { width: 150px; }
.ft-table th:nth-child(21), .ft-table td:nth-child(21) { width: 100px; text-align: center; }
.ft-table th:nth-child(22), .ft-table td:nth-child(22) { width: 100px; text-align: center; }
.ft-table th:nth-child(23), .ft-table td:nth-child(23) { width: 100px; text-align: center; font-weight: bold; }
.ft-table th:nth-child(24), .ft-table td:nth-child(24) { width: 70px; text-align: center; }

/* KPI box */
.kpi-box {
    background: var(--bg-card, #fff);
    border: 1px solid var(--border-color, #dee2e6);
    border-radius: 8px;
    padding: 12px;
    text-align: center;
    margin: 0 4px 10px 4px;
}
.kpi-box h3 {
    margin: 0;
    font-size: 26px;
    font-weight: bold;
}
.kpi-box small {
    color: var(--text-secondary, #6c757d);
    font-size: 12px;
}

/* Ricerca */
#searchBox input {
    max-width: 350px;
    font-size: 13px;
}
</style>

<h2 style="margin:0 0 12px; font-size:20px;">Fasi Terminate{{ !empty($soloOggi) ? ' - Oggi' : '' }}</h2>
@if(!empty($soloOggi))
    <a href="{{ route('owner.fasiTerminate') }}" class="btn btn-sm btn-outline-secondary mb-2 ms-1">Mostra tutte le fasi terminate</a>
@endif

<!-- KPI -->
<div class="row mx-1 mb-2">
    <div class="col-md-4">
        <div class="kpi-box">
            <h3>{{ number_format($kpiTotale) }}</h3>
            <small>Totale fasi terminate</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="kpi-box">
            <h3>{{ number_format($kpiCommesse) }}</h3>
            <small>Commesse coinvolte</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="kpi-box">
            <h3>{{ number_format($kpiOggi) }}</h3>
            <small>Terminate oggi</small>
        </div>
    </div>
</div>

<!-- Filtri (server-side: cercano su TUTTE le fasi, non solo la pagina corrente) -->
<form method="GET" action="{{ route('owner.fasiTerminate') }}" id="searchBox" style="margin: 6px 4px; display:flex; gap:6px; flex-wrap:wrap; align-items:center;">
    @if(!empty($soloOggi))<input type="hidden" name="oggi" value="1">@endif
    <input type="text" name="cerca" class="form-control form-control-sm" style="max-width:250px;" placeholder="Cerca commessa, cliente, descrizione..." value="{{ request('cerca') }}">
    <select name="reparto" class="form-control form-control-sm" style="max-width:180px;" onchange="this.form.submit()">
        <option value="">Tutti i reparti</option>
        @foreach($repartiUnici as $rep)
            <option value="{{ $rep }}" {{ request('reparto') == $rep ? 'selected' : '' }}>{{ ucfirst($rep) }}</option>
        @endforeach
    </select>
    <select name="fase" class="form-control form-control-sm" style="max-width:200px;" onchange="this.form.submit()">
        <option value="">Tutte le fasi</option>
        @foreach($fasiUniche as $fase)
            <option value="{{ $fase }}" {{ request('fase') == $fase ? 'selected' : '' }}>{{ $fase }}</option>
        @endforeach
    </select>
    <select name="operatore" class="form-control form-control-sm" style="max-width:180px;" onchange="this.form.submit()">
        <option value="">Tutti gli operatori</option>
        @foreach($operatoriUnici as $op)
            <option value="{{ $op }}" {{ request('operatore') == $op ? 'selected' : '' }}>{{ $op }}</option>
        @endforeach
    </select>
    <input type="date" name="data_da" class="form-control form-control-sm" style="max-width:150px;" value="{{ request('data_da') }}" title="Data fine da" onchange="this.form.submit()">
    <input type="date" name="data_a" class="form-control form-control-sm" style="max-width:150px;" value="{{ request('data_a') }}" title="Data fine a" onchange="this.form.submit()">
    <button type="submit" class="btn btn-sm btn-dark">Filtra</button>
    <a href="{{ route('owner.fasiTerminate', $soloOggi ? ['oggi' => 1] : []) }}" class="btn btn-sm btn-outline-secondary">Reset</a>
</form>

<!-- Tabella -->
<div class="table-wrapper">
    <table class="ft-table">
        <thead>
            <tr>
                <th>Commessa</th>
                <th>Cliente</th>
                <th>Codice Articolo</th>
                <th>Descrizione</th>
                <th>Qta</th>
                <th>UM</th>
                <th>Priorità</th>
                <th>Data Registrazione</th>
                <th>Data Prev. Consegna</th>
                <th>Cod Carta</th>
                <th>Carta</th>
                <th>Qta Carta</th>
                <th>UM</th>
                <th>Fase</th>
                <th>Reparto</th>
                <th>Operatori</th>
                <th>Qta Prod.</th>
                <th style="min-width:70px;">Ore Prev.</th>
                <th style="min-width:70px;">Ore Lav.</th>
                <th>Scarti Prinect</th>
                <th>Scarti R.</th>
                <th>Note</th>
                <th>Data Inizio</th>
                <th>Data Fine</th>
                <th>Pausa</th>
                <th>Stato</th>
            </tr>
        </thead>
        <tbody>
            @forelse($fasiTerminate as $fase)
                @php
                    $rowClass = $fase->ordine ? $fase->ordine->getPercorsoClass() : '';
                @endphp
                <tr class="{{ $rowClass }}" data-reparto="{{ strtolower($fase->reparto_nome ?? '') }}" data-fase="{{ strtolower($fase->faseCatalogo->nome_display ?? $fase->fase ?? '') }}" data-operatori="{{ strtolower($fase->operatori->map(fn($op) => $op->nome . ' ' . $op->cognome)->implode(', ')) }}">
                    <td><strong>{{ $fase->ordine->commessa ?? '-' }}</strong></td>
                    <td>{{ $fase->ordine->cliente_nome ?? '-' }}</td>
                    <td>{{ $fase->ordine->cod_art ?? '-' }}</td>
                    <td>{{ $fase->ordine->descrizione ?? '-' }}</td>
                    <td>{{ $fase->ordine->qta_richiesta ?? '-' }}</td>
                    <td>{{ $fase->ordine->um ?? '-' }}</td>
                    <td>{{ $fase->priorita ?? '-' }}</td>
                    <td>{{ $fase->ordine->data_registrazione ? \Carbon\Carbon::parse($fase->ordine->data_registrazione)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $fase->ordine->data_prevista_consegna ? \Carbon\Carbon::parse($fase->ordine->data_prevista_consegna)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $fase->ordine->cod_carta ?? '-' }}</td>
                    <td>{{ $fase->ordine->carta ?? '-' }}</td>
                    @php
                        $umFaseFt = strtoupper(trim($fase->um ?? 'FG'));
                        $isPezziFt = in_array($umFaseFt, ['TR', 'PZ', 'KG']);
                        if ($umFaseFt === 'KG') {
                            $qtaFaseFt = $fase->ordine->qta_richiesta ?? 0;
                        } else {
                            $qtaFaseFt = $fase->qta_fase ?: ($isPezziFt ? ($fase->ordine->qta_richiesta ?? 0) : ($fase->ordine->qta_carta ?? 0));
                        }
                    @endphp
                    <td>{{ $qtaFaseFt ? number_format($qtaFaseFt, 0, ',', '.') : '-' }}</td>
                    <td style="font-weight:600;color:{{ $isPezziFt ? '#2563eb' : '#059669' }}">{{ $isPezziFt ? 'pz' : 'fg' }}</td>
                    <td>{{ $fase->faseCatalogo->nome_display ?? '-' }}</td>
                    <td>{{ $fase->reparto_nome ?? '-' }}</td>
                    <td>
                        @forelse($fase->operatori as $op)
                            {{ $op->nome }}
                            ({{ $op->pivot->data_inizio ? \Carbon\Carbon::parse($op->pivot->data_inizio)->format('d/m/Y H:i') : '-' }})<br>
                        @empty
                            -
                        @endforelse
                    </td>
                    <td>{{ $fase->qta_prod ?? '-' }}</td>
                    @php
                        $infoFaseOre = config('fasi_ore')[$fase->fase ?? ''] ?? ['avviamento' => 0.5, 'copieh' => 1000];
                        $copiehFase = $infoFaseOre['copieh'] ?? 1000;
                        $qtaCartaPrev = $fase->qta_fase ?: ($fase->ordine->qta_carta ?? 0);
                        $orePrev = $copiehFase > 0 ? round(($infoFaseOre['avviamento'] ?? 0.5) + ($qtaCartaPrev / $copiehFase), 1) : 0;
                    @endphp
                    <td style="color:#6b7280;text-align:center;font-size:12px;">{{ $orePrev > 0 ? $orePrev . 'h' : '-' }}</td>
                    <td style="font-weight:bold;text-align:center;font-size:12px;">
                        @php
                            $secPrinectFt = ($fase->tempo_avviamento_sec ?? 0) + ($fase->tempo_esecuzione_sec ?? 0);
                            if ($secPrinectFt > 0) {
                                $oreLavFt = round($secPrinectFt / 3600, 1);
                            } else {
                                $oreLavFt = 0;
                                foreach ($fase->operatori as $op) {
                                    $ini = $op->pivot->data_inizio;
                                    $fin = $op->pivot->data_fine;
                                    $pau = $op->pivot->secondi_pausa ?? 0;
                                    if ($ini && $fin) {
                                        $sec = \Carbon\Carbon::parse($ini)->diffInSeconds(\Carbon\Carbon::parse($fin));
                                        $oreLavFt += max(0, $sec - $pau) / 3600;
                                    }
                                }
                                $oreLavFt = round($oreLavFt, 1);
                            }
                        @endphp
                        {{ $oreLavFt > 0 ? $oreLavFt . 'h' : '-' }}
                    </td>
                    <td style="text-align:center;">{{ $fase->fogli_scarto ?? '-' }}</td>
                    <td style="text-align:center;">{{ $fase->scarti ?? '-' }}</td>
                    <td>{{ $fase->note ?? '-' }}</td>
                    <td>{{ $fase->data_inizio ?? ($fase->operatori->count() > 0 ? $fase->operatori->min('pivot.data_inizio') : '-') }}</td>
                    <td>{{ $fase->data_fine ?? ($fase->operatori->count() > 0 ? $fase->operatori->max('pivot.data_fine') : '-') }}</td>
                    @php
                        $totSecondiPausa = $fase->secondi_pausa_totale ?? 0;
                        $pausaH = floor($totSecondiPausa / 3600);
                        $pausaM = floor(($totSecondiPausa % 3600) / 60);
                    @endphp
                    <td>{{ $totSecondiPausa > 0 ? sprintf('%dh %02dm', $pausaH, $pausaM) : '-' }}</td>
                    <td contenteditable data-fase-id="{{ $fase->id }}" onblur="aggiornaStato({{ $fase->id }}, this.innerText)"
                        style="background:{{ $fase->stato == 4 ? '#c3c3c3' : '#d1e7dd' }};font-weight:bold;cursor:pointer;">
                        {{ $fase->stato }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="25" style="text-align:center; color:#6c757d; padding:20px;">Nessuna fase terminata</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Paginazione -->
<div style="margin: 8px 4px; display:flex; justify-content:space-between; align-items:center;">
    <span style="font-size:12px; color:var(--text-secondary, #6c757d);">
        Pagina {{ $fasiTerminate->currentPage() }} di {{ $fasiTerminate->lastPage() }}
        ({{ number_format($fasiTerminate->total()) }} totali)
    </span>
    <div>
        @if($fasiTerminate->onFirstPage())
            <span class="btn btn-sm btn-outline-secondary disabled">&laquo; Prec</span>
        @else
            <a href="{{ $fasiTerminate->previousPageUrl() }}" class="btn btn-sm btn-outline-dark">&laquo; Prec</a>
        @endif
        @if($fasiTerminate->hasMorePages())
            <a href="{{ $fasiTerminate->nextPageUrl() }}" class="btn btn-sm btn-outline-dark">Succ &raquo;</a>
        @else
            <span class="btn btn-sm btn-outline-secondary disabled">Succ &raquo;</span>
        @endif
    </div>
</div>

<script>
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function aggiornaStato(faseId, testo) {
    const nuovoStato = parseInt(testo.trim());
    if (isNaN(nuovoStato) || nuovoStato < 0 || nuovoStato > 4) {
        alert('Stato non valido. Usa: 0, 1, 2, 3, 4');
        return;
    }
    fetch('{{ route("owner.aggiornaStato") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ fase_id: faseId, stato: nuovoStato })
    })
    .then(r => r.json())
    .then(d => {
        if (!d.success) {
            alert('Errore: ' + (d.messaggio || ''));
        } else {
            const bgMap = {0:'#e9ecef', 1:'#cfe2ff', 2:'#fff3cd', 3:'#d1e7dd', 4:'#c3c3c3'};
            const el = document.querySelector('td[data-fase-id="' + faseId + '"]');
            if (el) {
                el.style.background = bgMap[nuovoStato] || '#d1e7dd';
                el.innerText = nuovoStato;
            }
        }
    })
    .catch(e => alert('Errore di rete'));
}

// Ricerca live con debounce (300ms dopo l'ultima digitazione)
let cercaTimer = null;
document.querySelector('input[name="cerca"]')?.addEventListener('input', function() {
    clearTimeout(cercaTimer);
    const form = this.form;
    cercaTimer = setTimeout(() => form.submit(), 300);
});
</script>
@endsection
