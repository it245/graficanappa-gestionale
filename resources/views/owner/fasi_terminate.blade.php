@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
<style>
* {
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

html, body {
    margin: 0 !important;
    padding: 0 !important;
    width: 100%;
    height: 100%;
    overflow-x: hidden;
}

.container-fluid {
    padding-left: 1px !important;
    padding-right: 1px !important;
    margin-left: 0 !important;
}

h2 {
    margin: 8px 4px !important;
    font-size: 20px;
}

.top-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin: 4px;
}

.btn-back {
    background: #000;
    color: #fff;
    border: none;
    padding: 6px 16px;
    border-radius: 4px;
    font-size: 13px;
    font-weight: bold;
    cursor: pointer;
    text-decoration: none;
    transition: background 0.15s ease;
}
.btn-back:hover {
    background: #333;
    color: #fff;
}

/* =========================
   TABELLA (EXCEL STYLE)
   ========================= */
.table-wrapper {
    width: 100%;
    max-width: 100%;
    overflow: auto;
    margin: 0 1px;
    max-height: calc(100vh - 230px);
}

table {
    width: 2560px;
    max-width: 2560px;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 12px;
}

th, td {
    border: 1px solid #dee2e6;
    padding: 3px 6px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.3;
}

thead th {
    background: #000000;
    color: #ffffff;
    font-size: 11.5px;
    position: sticky;
    top: 0;
    z-index: 2;
}

tr:hover td {
    background: rgba(0, 0, 0, 0.03);
}

/* Righe alternate */
tbody tr:nth-child(even) {
    background: #f8f9fa;
}

/* =========================
   COLORI SCADENZA
   ========================= */
tr.scaduta td {
    background-color: #f8d7da !important;
}
tr.warning-strong td {
    background-color: #fff3cd !important;
}
tr.warning-light td {
    background-color: #fefce8 !important;
}

/* =========================
   LARGHEZZA COLONNE
   ========================= */

/* Commessa */
th:nth-child(1), td:nth-child(1) { width: 90px; }

/* Cliente */
th:nth-child(2), td:nth-child(2) {
    width: 180px;
    white-space: normal;
}

/* Codice articolo */
th:nth-child(3), td:nth-child(3) { width: 110px; }

/* Descrizione */
th:nth-child(4), td:nth-child(4) {
    width: 280px;
    max-width: 280px;
    white-space: normal;
}

/* Qta / UM / Priorità */
th:nth-child(5), td:nth-child(5),
th:nth-child(6), td:nth-child(6),
th:nth-child(7), td:nth-child(7) {
    width: 70px;
    text-align: center;
}

/* Date registrazione / consegna */
th:nth-child(8), td:nth-child(8),
th:nth-child(9), td:nth-child(9) {
    width: 115px;
}

/* Cod Carta / Carta */
th:nth-child(10), td:nth-child(10),
th:nth-child(11), td:nth-child(11) {
    width: 160px;
    white-space: normal;
}

/* Qta Carta / UM Carta */
th:nth-child(12), td:nth-child(12),
th:nth-child(13), td:nth-child(13) {
    width: 80px;
    text-align: center;
}

/* Fase / Reparto */
th:nth-child(14), td:nth-child(14),
th:nth-child(15), td:nth-child(15) {
    width: 110px;
}

/* Operatori */
th:nth-child(16), td:nth-child(16) {
    width: 130px;
    white-space: normal;
}

/* Qta Prodotta */
th:nth-child(17), td:nth-child(17) {
    width: 80px;
    text-align: center;
}

/* Note */
th:nth-child(18), td:nth-child(18) {
    width: 160px;
    white-space: normal;
}

/* Data Inizio / Data Fine */
th:nth-child(19), td:nth-child(19),
th:nth-child(20), td:nth-child(20) {
    width: 130px;
}

/* Pausa */
th:nth-child(21), td:nth-child(21) {
    width: 80px;
    text-align: center;
}

/* Ore Lavorate */
th:nth-child(22), td:nth-child(22) {
    width: 90px;
    text-align: center;
    font-weight: bold;
}

/* Stato */
th:nth-child(23), td:nth-child(23) {
    width: 60px;
    text-align: center;
}

/* Badge stato */
.badge-stato {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: bold;
    color: #fff;
    background: #28a745;
}

/* KPI box */
.kpi-box {
    background: #fff;
    border: 1px solid #dee2e6;
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
    color: #6c757d;
    font-size: 12px;
}

/* Ricerca */
#searchBox input {
    max-width: 350px;
    font-size: 13px;
}
</style>

<div class="top-bar">
    <a href="{{ route('owner.dashboard') }}" class="btn-back">← Torna alla Dashboard</a>
</div>

<h2>Fasi Terminate{{ !empty($soloOggi) ? ' - Oggi' : '' }}</h2>
@if(!empty($soloOggi))
    <a href="{{ route('owner.fasiTerminate') }}" class="btn btn-sm btn-outline-secondary mb-2 ms-1">Mostra tutte le fasi terminate</a>
@endif

<!-- KPI -->
<div class="row mx-1 mb-2">
    <div class="col-md-4">
        <div class="kpi-box">
            <h3>{{ $fasiTerminate->count() }}</h3>
            <small>Totale fasi terminate</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="kpi-box">
            <h3>{{ $fasiTerminate->unique(function($f) { return $f->ordine->commessa ?? ''; })->count() }}</h3>
            <small>Commesse coinvolte</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="kpi-box">
            <h3>{{ $fasiTerminate->filter(function($f) {
                if (!$f->data_fine) return false;
                try {
                    return \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $f->data_fine)?->isToday() ?? \Carbon\Carbon::parse($f->data_fine)->isToday();
                } catch (\Exception $e) {
                    try { return \Carbon\Carbon::parse($f->data_fine)->isToday(); } catch (\Exception $e2) { return false; }
                }
            })->count() }}</h3>
            <small>Terminate oggi</small>
        </div>
    </div>
</div>

<!-- Ricerca -->
<div id="searchBox" style="margin: 6px 4px;">
    <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Cerca commessa o cliente...">
</div>

<!-- Tabella -->
<div class="table-wrapper">
    <table>
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
                <th>UM Carta</th>
                <th>Fase</th>
                <th>Reparto</th>
                <th>Operatori</th>
                <th>Qta Prod.</th>
                <th>Note</th>
                <th>Data Inizio</th>
                <th>Data Fine</th>
                <th>Pausa</th>
                <th>Ore Lavorate</th>
                <th>Stato</th>
            </tr>
        </thead>
        <tbody>
            @forelse($fasiTerminate as $fase)
                @php
                    $rowClass = '';
                    if ($fase->ordine && $fase->ordine->data_prevista_consegna) {
                        $oggi = \Carbon\Carbon::today();
                        $dataPrevista = \Carbon\Carbon::parse($fase->ordine->data_prevista_consegna);
                        $diff = $oggi->diffInDays($dataPrevista, false);
                        if ($diff < -5) $rowClass = 'scaduta';
                        elseif ($diff <= 3) $rowClass = 'warning-strong';
                        elseif ($diff <= 5) $rowClass = 'warning-light';
                    }
                @endphp
                <tr class="{{ $rowClass }}">
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
                    <td>{{ $fase->ordine->qta_carta ?? '-' }}</td>
                    <td>{{ $fase->ordine->UM_carta ?? '-' }}</td>
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
                    <td>{{ $fase->note ?? '-' }}</td>
                    <td>{{ $fase->data_inizio ?? ($fase->operatori->count() > 0 ? $fase->operatori->min('pivot.data_inizio') : '-') }}</td>
                    <td>{{ $fase->data_fine ?? ($fase->operatori->count() > 0 ? $fase->operatori->max('pivot.data_fine') : '-') }}</td>
                    @php
                        $totSecondiPausa = $fase->secondi_pausa_totale ?? 0;
                        $secLordo = $fase->secondi_lordo ?? 0;

                        // Fallback: calcola dalle date mostrate se secondi_lordo non calcolato
                        if ($secLordo == 0) {
                            $diStr = $fase->data_inizio ?? ($fase->operatori->count() > 0 ? $fase->operatori->min('pivot.data_inizio') : null);
                            $dfStr = $fase->data_fine ?? ($fase->operatori->count() > 0 ? $fase->operatori->max('pivot.data_fine') : null);
                            if ($diStr && $dfStr) {
                                try {
                                    $di = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $diStr);
                                    $df = \Carbon\Carbon::createFromFormat('d/m/Y H:i:s', $dfStr);
                                    if ($di && $df) $secLordo = abs($df->getTimestamp() - $di->getTimestamp());
                                } catch (\Exception $e) {
                                    try {
                                        $secLordo = abs(\Carbon\Carbon::parse($dfStr)->getTimestamp() - \Carbon\Carbon::parse($diStr)->getTimestamp());
                                    } catch (\Exception $e2) {}
                                }
                            }
                        }

                        $secNetto = max($secLordo - $totSecondiPausa, 0);
                        $oreNette = $secNetto / 3600;
                        $pausaH = floor($totSecondiPausa / 3600);
                        $pausaM = floor(($totSecondiPausa % 3600) / 60);
                    @endphp
                    <td>{{ $totSecondiPausa > 0 ? sprintf('%dh %02dm', $pausaH, $pausaM) : '-' }}</td>
                    <td>
                        @if($secLordo > 0)
                            @if($oreNette >= 1)
                                {{ number_format($oreNette, 1) }}h
                            @elseif($secNetto >= 60)
                                {{ floor($secNetto / 60) }}m
                            @else
                                {{ $secNetto }}s
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td><span class="badge-stato">{{ $fase->stato == 4 ? 'Consegnata' : 'Terminata' }}</span></td>
                </tr>
            @empty
                <tr>
                    <td colspan="23" style="text-align:center; color:#6c757d; padding:20px;">Nessuna fase terminata</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
</div>

<script>
document.getElementById('searchInput').addEventListener('keyup', function() {
    const filtro = this.value.toLowerCase();
    document.querySelectorAll('tbody tr').forEach(riga => {
        const commessa = riga.cells[0]?.innerText.toLowerCase() || '';
        const cliente = riga.cells[1]?.innerText.toLowerCase() || '';
        riga.style.display = (commessa.includes(filtro) || cliente.includes(filtro)) ? '' : 'none';
    });
});
</script>
@endsection
