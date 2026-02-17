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

/* Rimuovi padding del container Bootstrap */
.container-fluid {
    padding-left: 1px !important;
    padding-right: 1px !important;
    margin-left: 0 !important;
}

/* Titoli */
h2, p {
    margin: 4px 1px !important;

}

/* Icone */
.action-icons {
    margin-left: 1px !important;
    padding-left: 0 !important;
}

.action-icons img {
    height: 35px;
    cursor: pointer;
    margin-right: 14px;
    transition: transform 0.15s ease;
}
.action-icons img:hover {
    transform: scale(1.15);
}

/* =========================
   TABELLA (EXCEL STYLE)
   ========================= */

table {
    width: 2555px;              /* OTTIMIZZATO PER 2560x1440 */
    max-width: 2555px;
    border-collapse: collapse;
    table-layout: fixed;        /* FONDAMENTALE */
    font-size: 12px;
}

thead, tbody, tr {
    width: 100%;
}

th, td {
    border: 1px solid #dee2e6;
    padding: 3px 6px;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.3;
    transition: background 0.1s ease;
    white-space: normal;
    max-height: 3.9em;
}

thead th {
    background: #000000;
    color: #ffffff;
    font-size: 11.5px;
}

/* =========================
   LARGHEZZA COLONNE (21 colonne ≈ 2555px per monitor 2560x1440)
   1=Commessa 2=Stato 3=Cliente 4=CodArt
   5=Descrizione 6=Qta 7=UM 8=Priorità
   9=DataReg 10=DataConsegna 11=CodCarta 12=Carta
   13=QtaCarta 14=UMCarta 15=Fase 16=Reparto
   17=Operatori 18=QtaProd 19=Note 20=DataInizio 21=DataFine
   ========================= */

/* 1. Commessa */
th:nth-child(1), td:nth-child(1) { width: 105px; }

/* 2. Stato */
th:nth-child(2), td:nth-child(2) { width: 60px; text-align: center; }

/* 3. Cliente */
th:nth-child(3), td:nth-child(3) { width: 190px; white-space: normal; }

/* 4. Codice Articolo */
th:nth-child(4), td:nth-child(4) { width: 105px; }

/* 5. Descrizione */
th:nth-child(5), td:nth-child(5) { width: 360px; max-width: 360px; white-space: normal; }

/* 6. Qta */
th:nth-child(6), td:nth-child(6) { width: 60px; text-align: center; }

/* 7. UM */
th:nth-child(7), td:nth-child(7) { width: 55px; text-align: center; }

/* 8. Priorità */
th:nth-child(8), td:nth-child(8) { width: 70px; text-align: center; }

/* 9. Data Registrazione / 10. Data Prevista Consegna */
th:nth-child(9), td:nth-child(9),
th:nth-child(10), td:nth-child(10) {
    width: 115px;
}

/* 11. Cod Carta */
th:nth-child(11), td:nth-child(11) { width: 145px; white-space: normal; }

/* 12. Carta */
th:nth-child(12), td:nth-child(12) { width: 165px; white-space: normal; }

/* 13. Qta Carta */
th:nth-child(13), td:nth-child(13) { width: 65px; text-align: center; }

/* 14. UM Carta */
th:nth-child(14), td:nth-child(14) { width: 60px; text-align: center; }

/* 15. Fase */
th:nth-child(15), td:nth-child(15) { width: 135px; }

/* 16. Reparto */
th:nth-child(16), td:nth-child(16) { width: 125px; }

/* 17. Operatori */
th:nth-child(17), td:nth-child(17) {
    width: 125px;
    white-space: normal;
}

/* 18. Qta Prod. */
th:nth-child(18), td:nth-child(18) {
    width: 70px;
    text-align: center;
}

/* 19. Note */
th:nth-child(19), td:nth-child(19) {
    width: 190px;
    white-space: normal;
}

/* 20. Data Inizio / 21. Data Fine */
th:nth-child(20), td:nth-child(20),
th:nth-child(21), td:nth-child(21) {
    width: 120px;
}

/* =========================
   SELEZIONE EXCEL
   ========================= */

td.selected {
    outline: 1px solid #000000;
    background: rgba(0, 0, 0, 0.12) !important;
}
th.selected {
    outline: 1px solid #ffffff;
}

/* Box selezione */
.selection-box {
    position: absolute;
    border: 2px dashed #000000;
    background-color: rgba(0, 0, 0, 0.08);
    pointer-events: none;
    z-index: 9999;
    will-change: left, top, width, height;
}

/* =========================
   FILTRI
   ========================= */
/* Filtri */
#filterBox {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: flex-start;
    margin: 5px 1px !important;
    padding-left: 0 !important;
    margin-left:1px !important;
}

#filterBox input,
#filterBox .choices {
    flex: 1 1 200px;
    max-width: 250px;
}

#filterBox input {
    height: 38px;
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

#filterBox .choices {
    display: inline-flex;
    align-items: center;
}

#filterBox .choices__inner {
    min-height: 38px;
    height: auto;
    max-height: 120px;
    overflow-y: auto;
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    padding: 4px 8px;
    box-sizing: border-box;
    width: 100%;
    gap: 3px;
}

.choices__list--dropdown {
    max-height: 250px;
    overflow-y: auto;
}

#filterBox .choices__list--multiple {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
}

#filterBox .choices__list--multiple .choices__item {
    display: inline-flex;
    align-items: center;
    padding: 3px 10px;
    font-size: 12px;
    background: #333;
    color: #fff;
    border-radius: 12px;
    margin: 1px 0;
}

#filterBox .choices__list--multiple .choices__button {
    padding-left: 10px;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    border-left: 1px solid rgba(255,255,255,0.3);
    margin-left: 6px;
    min-width: 16px;
    min-height: 16px;
}

.btn-reset-filters {
    background: #dc3545;
    color: #fff;
    border: none;
    padding: 6px 14px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    cursor: pointer;
    height: 38px;
    white-space: nowrap;
}
.btn-reset-filters:hover {
    background: #c82333;
}

/* =========================
   PERFORMANCE
   ========================= */

table * {
    user-select: none;
}

td[contenteditable] {
    user-select: text;
    cursor: text;
}

td[contenteditable]:focus {
    outline: 2px solid #0d6efd;
    outline-offset: -2px;
    background: #f0f7ff !important;
}

tr:hover td {
    background: rgba(0, 0, 0, 0.03);
}

/* Animazione filtri */
#filterBox {
    transition: opacity 0.25s ease, transform 0.25s ease;
}

/* Modal fluido */
.modal.fade .modal-dialog {
    transition: transform 0.2s ease;
}

</style>
    <h2>Dashboard Owner</h2>
    <p>
        Benvenuto: {{ auth()->user()->nome ?? session('operatore_nome') }}
        | Ruolo: {{ auth()->user()->ruolo ?? session('operatore_ruolo') }}
    </p>

    {{-- ICONE AZIONI --}}
    <div class="mb-3 d-flex align-items-center action-icons">

        {{-- Importa Ordini --}}
        <form action="{{ route('owner.importOrdini') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <label for="file-upload" title="Importa Ordini">
                <img src="{{ asset('images/import.png') }}" alt="Importa">
            </label>
            <input id="file-upload" type="file" name="file" style="display:none" onchange="this.form.submit()">
        </form>
        {{-- ICONA FILTRO --}}
        <img
            src="{{ asset('images/icons8-filtro-50.png') }}"
            id="toggleFilter"
            title="Mostra / Nascondi filtri"
            alt="Filtri"
        >

        {{-- Aggiungi Operatore --}}
        <a href="#" data-bs-toggle="modal" data-bs-target="#aggiungiOperatoreModal" title="Aggiungi Operatore">
            <img src="{{ asset('images/add-user.png') }}" alt="Aggiungi Operatore">
        </a>

        {{-- Visualizza fasi terminate --}}
        <a href="{{ route('owner.fasiTerminate') }}" title="Visualizza fasi terminate">
            <img src="{{ asset('images/out-of-the-box.png') }}" alt="Fasi terminate">
        </a>

        {{-- Scheduling Produzione --}}
        <a href="{{ route('owner.scheduling') }}" title="Scheduling Produzione (Gantt)">
            <img src="{{ asset('images/icons8-report-grafico-a-torta-50.png') }}" alt="Scheduling">
        </a>

        {{-- Stampa celle selezionate --}}
        <button id="printButton" class="btn p-0" style="background:none; border:none;" title="Stampa celle selezionate">
            <img src="{{ asset('images/printer.png') }}" alt="Stampa">
        </button>

        {{-- Aggiungi riga manuale --}}
        <a href="#" data-bs-toggle="modal" data-bs-target="#aggiungiRigaModal" title="Aggiungi riga">
            <img src="{{ asset('images/icons8-ddt-64 (1).png') }}" alt="Aggiungi riga" style="height:35px">
        </a>

        {{-- Report consegnati oggi --}}
        <a href="#" data-bs-toggle="modal" data-bs-target="#modalSpedizioniOggi" title="Consegnati oggi" class="position-relative" id="btnConsegnati">
            <img src="{{ asset('images/icons8-consegnato-50.png') }}" alt="Consegnati oggi" style="height:35px">
            @if($spedizioniOggi->count() > 0)
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:10px;" id="badgeConsegnati">
                    {{ $spedizioniOggi->count() }}
                </span>
            @endif
        </a>

    </div>
    
        {{-- FILTRI --}}
<div class="mb-3" id="filterBox" style="display:none;">
    <!-- Filtri multi-valore (virgola) -->
    <input type="text" id="filterCommessa" class="form-control form-control-sm" placeholder="Filtra Commessa (più valori ,)" style="max-width:200px;">
    <input type="text" id="filterCliente" class="form-control form-control-sm" placeholder="Filtra Cliente (più valori ,)" style="max-width:200px;">
    <input type="text" id="filterDescrizione" class="form-control form-control-sm" placeholder="Filtra Descrizione (più valori ,)" style="max-width:300px;">

    <!-- Filtri multi-selezione -->
   <select id="filterStato" multiple>
    <option value="0">0</option>
    <option value="1">1</option>
    <option value="2">2</option>
    <option value="3">3</option>
</select>

<select id="filterFase" multiple>
    @foreach($fasiCatalogo as $faseCat)
        <option value="{{ $faseCat->nome }}">{{ $faseCat->nome }}</option>
    @endforeach
</select>

<select id="filterReparto" multiple>
    @foreach($reparti as $id => $rep)
        <option value="{{ $rep }}">{{ $rep }}</option>
    @endforeach
</select>
<button type="button" class="btn-reset-filters" id="btnResetFilters">Rimuovi filtri</button>
</div>

    {{-- MODALE AGGIUNGI OPERATORE --}}
    <div class="modal fade" id="aggiungiOperatoreModal" tabindex="-1" aria-labelledby="aggiungiOperatoreModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('owner.aggiungiOperatore') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="aggiungiOperatoreModalLabel">Aggiungi Operatore</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nome</label>
                            <input type="text" name="nome" id="nome" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cognome</label>
                            <input type="text" name="cognome" id="cognome" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ruolo</label>
                            <select name="ruolo" class="form-select" required>
                                <option value="operatore">Operatore</option>
                                <option value="owner">Owner</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Codice operatore</label>
                            <input type="text" id="codice_operatore" class="form-control" value="{{ $prossimoCodice }}" data-numero="{{ $prossimoNumero}}" disabled>
                            <small class="text-muted">Il codice sarà confermato alla creazione</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reparto Principale</label>
                            <select name="reparto_principale" class="form-select" required>
                                @foreach($reparti as $id => $rep)
                                    <option value="{{ $id }}">{{ $rep }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reparto Secondario (facoltativo)</label>
                            <select name="reparto_secondario" class="form-select">
                                <option value="">-- Nessuno --</option>
                                @foreach($reparti as $id => $rep)
                                    <option value="{{ $id }}">{{ $rep }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Aggiungi</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- MODALE AGGIUNGI RIGA --}}
    <div class="modal fade" id="aggiungiRigaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('owner.aggiungiRiga') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Aggiungi Riga</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label">Commessa *</label>
                                <input type="text" name="commessa" class="form-control">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Cliente</label>
                                <input type="text" name="cliente_nome" class="form-control">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Codice Articolo</label>
                                <input type="text" name="cod_art" class="form-control">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descrizione</label>
                                <input type="text" name="descrizione" class="form-control">
                            </div>
                            <div class="col-4">
                                <label class="form-label">Quantita</label>
                                <input type="number" name="qta_richiesta" class="form-control" value="0">
                            </div>
                            <div class="col-4">
                                <label class="form-label">UM</label>
                                <input type="text" name="um" class="form-control" value="FG">
                            </div>
                            <div class="col-4">
                                <label class="form-label">Data Consegna</label>
                                <input type="date" name="data_prevista_consegna" class="form-control">
                            </div>
                            <div class="col-4">
                                <label class="form-label">Priorità</label>
                                <input type="number" name="priorita" class="form-control" step="0.01" value="0">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Fase *</label>
                                <select name="fase_catalogo_id" class="form-select">
                                    <option value="">-- Seleziona fase --</option>
                                    @foreach(\App\Models\FasiCatalogo::with('reparto')->orderBy('nome')->get() as $fc)
                                        <option value="{{ $fc->id }}">{{ $fc->nome }} ({{ $fc->reparto->nome ?? '-' }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Aggiungi</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

@php
    /* Helper date italiane */
    function formatItalianDate($date, $withTime = false) {
        if (!$date) return '-';
        try {
            return \Carbon\Carbon::parse($date)
                ->format($withTime ? 'd/m/Y H:i' : 'd/m/Y');
        } catch (\Exception $e) {
            return $date;
        }
    }
@endphp

    {{-- TABELLA --}}
    <div>
        <table id="tabellaOrdini" class="table table-bordered table-sm table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Commessa</th>
                    <th>Stato</th>
                    <th>Cliente</th>
                    <th>Codice Articolo</th>
                    <th>Descrizione</th>
                    <th>Qta</th>
                    <th>UM</th>
                    <th>Priorità</th>
                    <th>Data Registrazione</th>
                    <th>Data Prevista Consegna</th>
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
                </tr>
            </thead>
            <tbody>
            @foreach($fasi as $fase)
                @php
                    $rowClass = '';
                    if ($fase->ordine->data_prevista_consegna) {
                        $dataPrevista = \Carbon\Carbon::parse($fase->ordine->data_prevista_consegna)->startOfDay();
                        $oggi = \Carbon\Carbon::today();
                        $diffGiorni = $oggi->diffInDays($dataPrevista, false);
                        if ($diffGiorni <= -5) $rowClass = 'scaduta';
                        elseif ($diffGiorni <= 3) $rowClass = 'warning-strong';
                        elseif ($diffGiorni <= 5) $rowClass = 'warning-light';
                    }
                @endphp
                @php
                    $statiLabel = [0 => 'Caricato', 1 => 'Pronto', 2 => 'Avviato', 3 => 'Terminato'];
                    $statoBg = [0 => '#e9ecef', 1 => '#cfe2ff', 2 => '#fff3cd', 3 => '#d1e7dd'];
                @endphp
                <tr class="{{ $rowClass }}" data-id="{{ $fase->id }}">
                    <td><a href="{{ route('owner.dettaglioCommessa', $fase->ordine->commessa ?? '-') }}" style="color:#000;font-weight:bold;text-decoration:underline;">{{ $fase->ordine->commessa ?? '-' }}</a></td>
                    <td contenteditable onblur="aggiornaStato({{ $fase->id }}, this.innerText)" style="background:{{ $statoBg[$fase->stato] ?? '#e9ecef' }} !important;font-weight:bold;text-align:center;">{{ $fase->stato }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'cliente_nome', this.innerText)">{{ $fase->ordine->cliente_nome ?? '-' }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'cod_art', this.innerText)">{{ $fase->ordine->cod_art ?? '-' }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'descrizione', this.innerText)">{{ $fase->ordine->descrizione ?? '-' }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'qta_richiesta', this.innerText)">{{ $fase->ordine->qta_richiesta ?? '-' }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'um', this.innerText)">{{ $fase->ordine->um ?? '-' }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'priorita', this.innerText)">{{ $fase->priorita !== null ? number_format($fase->priorita, 2) : '-' }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'data_registrazione', this.innerText)">{{ formatItalianDate($fase->ordine->data_registrazione) }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'data_prevista_consegna', this.innerText)">{{ formatItalianDate($fase->ordine->data_prevista_consegna) }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'cod_carta', this.innerText)">{{ $fase->ordine->cod_carta ?? '-' }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'carta', this.innerText)">{{ $fase->ordine->carta ?? '-' }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'qta_carta', this.innerText)">{{ $fase->ordine->qta_carta ?? '-' }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'UM_carta', this.innerText)">{{ $fase->ordine->UM_carta ?? '-' }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'fase', this.innerText)">{{ $fase->faseCatalogo->nome ?? '-' }}</td>
                    <td>{{ $fase->faseCatalogo->reparto->nome ?? '-' }}</td>
                    <td>
                        @forelse($fase->operatori as $op)
                            {{ $op->nome }}<br>
                        @empty
                            -
                        @endforelse
                    </td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'qta_prod', this.innerText)">{{ $fase->qta_prod ?? '-' }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'note', this.innerText)">{{ $fase->note ?? '-' }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'data_inizio', this.innerText)">{{ formatItalianDate($fase->data_inizio, true) }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'data_fine', this.innerText)">{{ formatItalianDate($fase->data_fine, true) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
{{-- MODALE SPEDIZIONI OGGI --}}
<div class="modal fade" id="modalSpedizioniOggi" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Consegnati oggi ({{ $spedizioniOggi->count() }})</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="overflow-x:auto;">
                @if($spedizioniOggi->count() > 0)
                <table class="table table-bordered table-sm" style="white-space:nowrap; font-size:13px;">
                    <thead class="table-success">
                        <tr>
                            <th>Commessa</th>
                            <th>Cliente</th>
                            <th>Descrizione</th>
                            <th>Fase</th>
                            <th>Operatore</th>
                            <th>Data Consegna</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($spedizioniOggi as $sp)
                        <tr>
                            <td><strong>{{ $sp->ordine->commessa ?? '-' }}</strong></td>
                            <td>{{ $sp->ordine->cliente_nome ?? '-' }}</td>
                            <td style="white-space:normal; max-width:350px;">{{ $sp->ordine->descrizione ?? '-' }}</td>
                            <td>{{ $sp->faseCatalogo->nome ?? $sp->fase ?? '-' }}</td>
                            <td>
                                @foreach($sp->operatori as $op)
                                    {{ $op->nome }} {{ $op->cognome }}@if(!$loop->last), @endif
                                @endforeach
                            </td>
                            <td>{{ $sp->data_fine ? \Carbon\Carbon::parse($sp->data_fine)->format('d/m/Y H:i:s') : '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <p class="text-muted text-center py-3">Nessuna consegna effettuata oggi</p>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    // Badge consegnati
    var modal = document.getElementById('modalSpedizioniOggi');
    if(modal){
        modal.addEventListener('show.bs.modal', function(){
            var badge = document.getElementById('badgeConsegnati');
            if(badge) badge.style.display = 'none';
        });
    }

    // Codice operatore live
    var nomeInput = document.getElementById('nome');
    var cognomeInput = document.getElementById('cognome');
    var codiceInput = document.getElementById('codice_operatore');
    var numero = codiceInput ? codiceInput.getAttribute('data-numero') : '001';
    numero = String(numero).padStart(3, '0');

    function aggiornaCodice(){
        var n = (nomeInput.value || '').trim().toUpperCase();
        var c = (cognomeInput.value || '').trim().toUpperCase();
        var iniziali = (n.charAt(0) || '_') + (c.charAt(0) || '_');
        codiceInput.value = iniziali + numero;
    }

    if(nomeInput && cognomeInput && codiceInput){
        nomeInput.addEventListener('input', aggiornaCodice);
        cognomeInput.addEventListener('input', aggiornaCodice);
    }
});
</script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
{{-- JS --}}
<script>
function aggiornaCampo(faseId, campo, valore){
    valore = valore.trim();

    // Se il campo è numerico o priorità, sostituisci la virgola con punto
    const campiNumerici = ['qta_richiesta','qta_prod','priorita','qta_carta','ore'];
    if(campiNumerici.includes(campo)){
        valore = valore.replace(',', '.');
        if(isNaN(parseFloat(valore))){
            alert('Valore numerico non valido');
            return;
        }
    }

    fetch('{{ route("owner.aggiornaCampo") }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json'},
        body: JSON.stringify({ fase_id: faseId, campo: campo, valore: valore })
    })
    .then(r => r.json())
    .then(d => {
        if (!d.success) {
            alert('Errore salvataggio: ' + (d.messaggio || ''));
        } else if (d.reload) {
            window.location.reload();
        }
    })
    .catch(err => {
        console.error(err);
        alert('Errore di connessione');
    });
}

function aggiornaStato(faseId, testo) {
    const nuovoStato = parseInt(testo.trim());
    if (isNaN(nuovoStato) || nuovoStato < 0 || nuovoStato > 3) {
        alert('Stato non valido. Usa: 0, 1, 2, 3');
        return;
    }
    fetch('{{ route("owner.aggiornaStato") }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json'},
        body: JSON.stringify({ fase_id: faseId, stato: nuovoStato })
    })
    .then(r => r.json())
    .then(d => {
        if (!d.success) {
            alert('Errore: ' + (d.messaggio || ''));
        } else {
            const bgMap = {0: '#e9ecef', 1: '#cfe2ff', 2: '#fff3cd', 3: '#d1e7dd'};
            const row = document.querySelector('tr[data-id="' + faseId + '"]');
            if (row) {
                const statoCell = row.cells[1];
                statoCell.style.setProperty('background', bgMap[nuovoStato], 'important');
                statoCell.innerText = nuovoStato;
            }
        }
    })
    .catch(err => { console.error(err); alert('Errore di connessione'); });
}
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const table = document.getElementById('tabellaOrdini');
    const allCells = Array.from(table.querySelectorAll('td, th'));
    const selectedCells = new Set();

    let isSelecting = false;
    let startX = 0, startY = 0;
    let currentX = 0, currentY = 0;
    let selectionBox = null;
    let rafPending = false;
    let cachedRects = [];

    function cacheRects() {
        const sx = window.scrollX, sy = window.scrollY;
        cachedRects = allCells.map(cell => {
            const r = cell.getBoundingClientRect();
            return {
                cell,
                left: r.left + sx,
                top: r.top + sy,
                right: r.right + sx,
                bottom: r.bottom + sy
            };
        });
    }

    function updateSelection() {
        rafPending = false;
        if (!selectionBox) return;

        const x = Math.min(startX, currentX);
        const y = Math.min(startY, currentY);
        const w = Math.abs(currentX - startX);
        const h = Math.abs(currentY - startY);

        selectionBox.style.transform = 'translate(' + x + 'px,' + y + 'px)';
        selectionBox.style.width = w + 'px';
        selectionBox.style.height = h + 'px';

        const bRight = x + w, bBottom = y + h;

        selectedCells.forEach(c => c.classList.remove('selected'));
        selectedCells.clear();

        for (let i = 0; i < cachedRects.length; i++) {
            const r = cachedRects[i];
            if (!(r.right < x || r.left > bRight || r.bottom < y || r.top > bBottom)) {
                r.cell.classList.add('selected');
                selectedCells.add(r.cell);
            }
        }
    }

    // Doppio click/tap = avvia selezione per stampa
    function startSelection(x, y) {
        isSelecting = true;
        startX = currentX = x;
        startY = currentY = y;

        selectedCells.forEach(c => c.classList.remove('selected'));
        selectedCells.clear();

        cacheRects();

        selectionBox = document.createElement('div');
        selectionBox.className = 'selection-box';
        selectionBox.style.left = '0';
        selectionBox.style.top = '0';
        document.body.appendChild(selectionBox);
    }

    function moveSelection(x, y) {
        if (!isSelecting) return;
        currentX = x;
        currentY = y;
        if (!rafPending) {
            rafPending = true;
            requestAnimationFrame(updateSelection);
        }
    }

    function endSelection() {
        isSelecting = false;
        rafPending = false;
        if (selectionBox) {
            selectionBox.remove();
            selectionBox = null;
        }
    }

    // Mouse: doppio click avvia, drag seleziona
    table.addEventListener('dblclick', e => {
        if (!e.target.matches('td, th')) return;
        startSelection(e.pageX, e.pageY);
        e.preventDefault();
    });

    document.addEventListener('mousemove', e => moveSelection(e.pageX, e.pageY));
    document.addEventListener('mouseup', endSelection);

    // Touch: doppio tap avvia, drag seleziona
    let lastTap = 0;
    table.addEventListener('touchstart', e => {
        const now = Date.now();
        const touch = e.touches[0];
        const target = document.elementFromPoint(touch.clientX, touch.clientY);

        if (now - lastTap < 350 && target && target.matches('td, th')) {
            // Doppio tap
            startSelection(touch.pageX, touch.pageY);
            e.preventDefault();
        }
        lastTap = now;
    }, { passive: false });

    document.addEventListener('touchmove', e => {
        if (!isSelecting) return;
        const touch = e.touches[0];
        moveSelection(touch.pageX, touch.pageY);
        e.preventDefault();
    }, { passive: false });

    document.addEventListener('touchend', endSelection);

    // STAMPA SOLO LE CELLE NEL BOX
    document.getElementById('printButton').addEventListener('click', () => {
        if (selectedCells.size === 0) {
            alert('Seleziona un\'area!');
            return;
        }

        const rows = new Map();

        selectedCells.forEach(cell => {
            const row = cell.parentElement;
            if (!rows.has(row)) rows.set(row, []);
            const style = getComputedStyle(cell);
            rows.get(row).push({
                tag: cell.tagName,
                html: cell.innerHTML,
                bg: style.backgroundColor,
                color: style.color
            });
        });

        const win = window.open('', '', 'width=1200,height=800');
        win.document.write(`
            <html>
            <head>
                <title>Stampa</title>
                <style>
                    table { border-collapse: collapse; }
                    td, th { border:1px solid #ccc; padding:4px; }
                </style>
            </head>
            <body><table>
        `);

        rows.forEach(cells => {
            win.document.write('<tr>');
            cells.forEach(c => {
                win.document.write(
                    `<${c.tag} style="background:${c.bg};color:${c.color}">${c.html}</${c.tag}>`
                );
            });
            win.document.write('</tr>');
        });

        win.document.write('</table></body></html>');
        win.document.close();
        win.print();

        // Deseleziona dopo che la finestra di stampa viene chiusa
        win.onafterprint = function() {
            selectedCells.forEach(c => c.classList.remove('selected'));
            selectedCells.clear();
            win.close();
        };
        // Fallback: deseleziona quando la finestra viene chiusa
        var checkClosed = setInterval(function() {
            if (win.closed) {
                clearInterval(checkClosed);
                selectedCells.forEach(c => c.classList.remove('selected'));
                selectedCells.clear();
            }
        }, 300);
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Inizializza Choices.js
    const choicesOptions = {
        removeItemButton: true,
        searchEnabled: true,
        itemSelectText: '',
        placeholder:true,
    };
    const choiceStato = new Choices('#filterStato',{...choicesOptions,placeholderValue:'Seleziona Stato'});
    const choiceFase = new Choices('#filterFase',{...choicesOptions,placeholderValue:'Seleziona Fase'})
    const choiceReparto = new Choices('#filterReparto',{...choicesOptions,placeholderValue:'Seleziona Reparto'})

    const toggleFilter = document.getElementById('toggleFilter');
    const filterBox = document.getElementById('filterBox');

    const fCommessa = document.getElementById('filterCommessa');
    const fCliente = document.getElementById('filterCliente');
    const fDescrizione = document.getElementById('filterDescrizione');
    const fStato = document.getElementById('filterStato');
    const fFase = document.getElementById('filterFase');
    const fReparto = document.getElementById('filterReparto');

    const rows = Array.from(document.querySelectorAll('#tabellaOrdini tbody tr'));

    const rowData = rows.map(row => ({
        row,
        commessa: row.cells[0].innerText.toLowerCase(),
        stato: row.cells[1].innerText.toLowerCase(),
        cliente: row.cells[2].innerText.toLowerCase(),
        descrizione: row.cells[4].innerText.toLowerCase(),
        fase: row.cells[14].innerText.toLowerCase(),
        reparto: row.cells[15].innerText.toLowerCase()
    }));

    function parseValues(input) {
        return input.split(',').map(v => v.trim().toLowerCase()).filter(v => v);
    }

    function getSelectedOptions(select) {
        return Array.from(select.selectedOptions).map(opt => opt.value.toLowerCase());
    }

    function filtra() {
        const commesse = parseValues(fCommessa.value);
        const clienti = parseValues(fCliente.value);
        const descrizioni = parseValues(fDescrizione.value);
        const stati = getSelectedOptions(fStato);
        const fasi = getSelectedOptions(fFase);
        const reparti = getSelectedOptions(fReparto);

        requestAnimationFrame(() => {
            rowData.forEach(data => {
                let match = true;
                if(commesse.length) match = match && commesse.some(v => data.commessa.includes(v));
                if(clienti.length) match = match && clienti.some(v => data.cliente.includes(v));
                if(descrizioni.length) match = match && descrizioni.some(v => data.descrizione.includes(v));
                if(stati.length) match = match && stati.includes(data.stato);
                if(fasi.length) match = match && fasi.includes(data.fase);
                if(reparti.length) match = match && reparti.includes(data.reparto);
                data.row.style.display = match ? '' : 'none';
            });
        });
    }

    function debounce(func, delay) {
        let timeout;
        return function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, arguments), delay);
        }
    }

    const filtraDebounced = debounce(filtra, 100);

    fCommessa.addEventListener('input', filtraDebounced);
    fCliente.addEventListener('input', filtraDebounced);
    fDescrizione.addEventListener('input', filtraDebounced);
    fStato.addEventListener('change', filtraDebounced);
    fFase.addEventListener('change', filtraDebounced);
    fReparto.addEventListener('change', filtraDebounced);

    document.getElementById('btnResetFilters').addEventListener('click', function() {
        fCommessa.value = '';
        fCliente.value = '';
        fDescrizione.value = '';
        choiceStato.removeActiveItems();
        choiceFase.removeActiveItems();
        choiceReparto.removeActiveItems();
        rowData.forEach(data => { data.row.style.display = ''; });
    });

    if (toggleFilter) {
        toggleFilter.addEventListener('click', () => {
            if (filterBox.style.display === 'none') {
                filterBox.style.display = 'flex';
                filterBox.style.opacity = 0;
                filterBox.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    filterBox.style.opacity = 1;
                    filterBox.style.transform = 'translateY(0)';
                }, 10);
            } else {
                filterBox.style.opacity = 0;
                filterBox.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    filterBox.style.display = 'none';
                    fCommessa.value = '';
                    fCliente.value = '';
                    fDescrizione.value = '';
                    choiceStato.removeActiveItems();
                    choiceFase.removeActiveItems();
                    choiceReparto.removeActiveItems();
                    rowData.forEach(data => data.row.style.display = '');
                }, 300);
            }
        });
    }
});
</script>
@endsection
