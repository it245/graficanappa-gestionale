@extends('layouts.mes')

@section('viewport')@endsection

@section('topbar-title', 'Dashboard Produzione')

@section('sidebar-items')
<div class="mes-sidebar-section">
    <div class="mes-sidebar-section-label">Produzione</div>
    <a href="{{ route('owner.dashboard') }}?op_token={{ request('op_token') }}" class="mes-sidebar-item active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        Dashboard
    </a>
    <a href="{{ route('owner.repartiOverview') }}?op_token={{ request('op_token') }}" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 20h20"/><rect x="4" y="8" width="4" height="12"/><rect x="10" y="4" width="4" height="16"/><rect x="16" y="11" width="4" height="9"/></svg>
        Panoramica Reparti
    </a>
    <a href="{{ route('owner.scheduling') }}?op_token={{ request('op_token') }}" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        Scheduling
    </a>
    <a href="{{ route('owner.esterne') }}?op_token={{ request('op_token') }}" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
        Lav. Esterne
    </a>
    <a href="{{ route('owner.fustelle') }}?op_token={{ request('op_token') }}" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
        Fustelle
    </a>
</div>

<div class="mes-sidebar-section">
    <div class="mes-sidebar-section-label">Analisi</div>
    <a href="{{ route('owner.reportOre') }}?op_token={{ request('op_token') }}" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        Report Ore
    </a>
    <a href="{{ route('owner.fasiTerminate') }}?op_token={{ request('op_token') }}" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        Fasi Terminate
        @if($fasiCompletateOggi > 0)<span class="badge bg-success ms-auto" style="font-size:10px">{{ $fasiCompletateOggi }}</span>@endif
    </a>
    <button type="button" class="mes-sidebar-item" data-bs-toggle="modal" data-bs-target="#modalStorico">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/><path d="M4.93 4.93l4.24 4.24"/></svg>
        Storico Consegne
    </button>
</div>

<div class="mes-sidebar-section">
    <div class="mes-sidebar-section-label">Strumenti</div>
    <button type="button" class="mes-sidebar-item" data-bs-toggle="modal" data-bs-target="#modalSpedizioniOggi">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
        Consegnati Oggi
        @if($commesseSpediteOggi > 0)<span class="badge bg-info ms-auto" style="font-size:10px">{{ $commesseSpediteOggi }}</span>@endif
    </button>
    <button type="button" class="mes-sidebar-item" data-bs-toggle="modal" data-bs-target="#modalBRT">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
        BRT Tracking
        @if($spedizioniBRT->count() > 0)<span class="badge bg-warning text-dark ms-auto" style="font-size:10px">{{ $spedizioniBRT->count() }}</span>@endif
    </button>
    <button type="button" class="mes-sidebar-item" data-bs-toggle="modal" data-bs-target="#modalNoteSpedizione" id="sidebarNoteBtn">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        Note Consegne
        <span class="badge bg-danger ms-auto" style="font-size:10px; display:none" id="noteConsegneBadge">!</span>
    </button>
    <button type="button" class="mes-sidebar-item" data-bs-toggle="modal" data-bs-target="#modalPresenti">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Presenti
        <span class="badge bg-success ms-auto" style="font-size:10px" id="presentiCount">...</span>
    </button>
    <a href="{{ route('owner.presenze') }}?op_token={{ request('op_token') }}" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/></svg>
        Storico Presenze
    </a>
    @if(!($isReadonly ?? false))
    <button type="button" class="mes-sidebar-item" onclick="document.getElementById('formSyncOnda').submit()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6"/><path d="M2.5 22v-6h6"/><path d="M2.5 11.5a10 10 0 0 1 18.8-4.3"/><path d="M21.5 12.5a10 10 0 0 1-18.8 4.2"/></svg>
        Sincronizza Onda
    </button>
    <button type="button" class="mes-sidebar-item" data-bs-toggle="modal" data-bs-target="#aggiungiRigaModal">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
        Aggiungi Riga
    </button>
    @endif
    @if($isReadonly ?? false)
    <button type="button" class="mes-sidebar-item" onclick="filtraRiferimentiMarco()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Rif. Marco
    </button>
    <button type="button" class="mes-sidebar-item" onclick="resetRiferimentiMarco()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        Mostra tutte
    </button>
    @endif
</div>
@endsection

@section('styles')
<style>
/* ============================================
   Owner Dashboard — Table Styles
   ============================================ */

/* Tabella (Excel style) */
#tabellaOrdini {
    width: 2970px;
    max-width: 2970px;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 12px;
}

#tabellaOrdini thead, #tabellaOrdini tbody, #tabellaOrdini tr {
    width: 100%;
}

#tabellaOrdini th, #tabellaOrdini td {
    border: 1px solid var(--border-color);
    padding: 3px 6px;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.3;
    transition: background 0.1s ease;
    white-space: normal;
    max-height: 3.9em;
}

/* Cella cliccata: mostra testo intero */
#tabellaOrdini td:focus, #tabellaOrdini td[contenteditable]:focus {
    white-space: normal !important;
    overflow: visible !important;
    text-overflow: clip !important;
    max-height: none !important;
    position: relative;
    z-index: 10;
    background: #fffde7 !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

#tabellaOrdini thead th {
    background: var(--bg-sidebar);
    color: #fff;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    border: none;
    border-bottom: 2px solid var(--accent);
    padding: 8px 6px;
}

/* =========================
   LARGHEZZA COLONNE (27 colonne)
   1=Commessa 2=Stato 3=Cliente 4=CodArt 5=Colori 6=Fustella
   7=Descrizione 8=Qta 9=UM 10=Priorita 11=Fase 12=Reparto
   13=Carta 14=QtaCarta 15=DataConsegna 16=CodCarta
   17=UMCarta 18=Operatori 19=QtaProd
   20=Esterno 21=Note 22=DataInizio 23=DataFine 24=OrePrev 25=OreLav 26=DataReg 27=Progresso
   ========================= */

/* 1. Commessa */
#tabellaOrdini th:nth-child(1), #tabellaOrdini td:nth-child(1) { width: 100px; }
/* 2. Stato */
#tabellaOrdini th:nth-child(2), #tabellaOrdini td:nth-child(2) { width: 50px; text-align: center; }
/* 3. Cliente */
#tabellaOrdini th:nth-child(3), #tabellaOrdini td:nth-child(3) { width: 170px; white-space: normal; }
/* 4. Codice Articolo */
#tabellaOrdini th:nth-child(4), #tabellaOrdini td:nth-child(4) { width: 95px; }
/* 5. Colori */
#tabellaOrdini th:nth-child(5), #tabellaOrdini td:nth-child(5) { width: 180px; white-space: normal; }
/* 6. Fustella */
#tabellaOrdini th:nth-child(6), #tabellaOrdini td:nth-child(6) { width: 75px; }
/* 7. Descrizione */
#tabellaOrdini th:nth-child(7), #tabellaOrdini td:nth-child(7) { width: 250px; max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
/* 8. Qta */
#tabellaOrdini th:nth-child(8), #tabellaOrdini td:nth-child(8) { width: 55px; text-align: center; }
/* 9. UM */
#tabellaOrdini th:nth-child(9), #tabellaOrdini td:nth-child(9) { width: 40px; text-align: center; }
/* 10. Priorita */
#tabellaOrdini th:nth-child(10), #tabellaOrdini td:nth-child(10) { width: 65px; text-align: center; }
/* 11. Fase */
#tabellaOrdini th:nth-child(11), #tabellaOrdini td:nth-child(11) { width: 125px; }
/* 12. Reparto */
#tabellaOrdini th:nth-child(12), #tabellaOrdini td:nth-child(12) { width: 110px; }
/* 13. Carta */
#tabellaOrdini th:nth-child(13), #tabellaOrdini td:nth-child(13) { width: 190px; white-space: normal; }
/* 14. Qta Carta */
#tabellaOrdini th:nth-child(14), #tabellaOrdini td:nth-child(14) { width: 50px; text-align: center; }
/* 15. Data Prevista Consegna */
#tabellaOrdini th:nth-child(15), #tabellaOrdini td:nth-child(15) { width: 100px; }
/* 16. Cod Carta */
#tabellaOrdini th:nth-child(16), #tabellaOrdini td:nth-child(16) { width: 170px; white-space: normal; }
/* 17. UM Carta */
#tabellaOrdini th:nth-child(17), #tabellaOrdini td:nth-child(17) { width: 30px; text-align: center; font-size: 11px; }
/* 18. Operatori */
#tabellaOrdini th:nth-child(18), #tabellaOrdini td:nth-child(18) { width: 110px; white-space: normal; font-size: 11px; }
/* 19. Qta Prod. */
#tabellaOrdini th:nth-child(19), #tabellaOrdini td:nth-child(19) { width: 60px; text-align: center; }
/* 20. Esterno */
#tabellaOrdini th:nth-child(20), #tabellaOrdini td:nth-child(20) { width: 90px; }
/* 21. Note */
#tabellaOrdini th:nth-child(21), #tabellaOrdini td:nth-child(21) { width: 170px; white-space: normal; }
/* 22. Data Inizio / 23. Data Fine */
#tabellaOrdini th:nth-child(22), #tabellaOrdini td:nth-child(22),
#tabellaOrdini th:nth-child(23), #tabellaOrdini td:nth-child(23) { width: 110px; }
/* 24. Ore Prev. */
#tabellaOrdini th:nth-child(24), #tabellaOrdini td:nth-child(24) { width: 70px; text-align: center; }
/* 25. Ore Lav. */
#tabellaOrdini th:nth-child(25), #tabellaOrdini td:nth-child(25) { width: 70px; text-align: center; }
/* 26. Data Reg. */
#tabellaOrdini th:nth-child(26), #tabellaOrdini td:nth-child(26) { width: 100px; }
/* 27. Progresso */
#tabellaOrdini th:nth-child(27), #tabellaOrdini td:nth-child(27) { width: 100px; }

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
#filterBox {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: flex-start;
    margin-bottom: 12px;
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
    background: var(--bg-sidebar);
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
    background: var(--danger);
    color: #fff;
    border: none;
    padding: 6px 14px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: bold;
    cursor: pointer;
    height: 38px;
    white-space: nowrap;
    transition: background 0.15s;
}
.btn-reset-filters:hover {
    background: #b91c1c;
}

.btn-marco-filter {
    background: var(--accent);
    color: #fff;
    border: none;
    padding: 6px 14px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: bold;
    cursor: pointer;
    height: 38px;
    white-space: nowrap;
    transition: background 0.15s;
}
.btn-marco-filter:hover {
    background: #1d4ed8;
}

/* =========================
   PERFORMANCE & CONTENTEDITABLE
   ========================= */
#tabellaOrdini td[contenteditable] {
    user-select: text;
    cursor: text;
}

#tabellaOrdini td[contenteditable]:focus {
    outline: 2px solid var(--accent);
    outline-offset: -2px;
    background: #f0f7ff !important;
}

#tabellaOrdini tbody tr:hover td {
    background: rgba(37, 99, 235, 0.04);
}

#tabellaOrdini tbody tr:nth-child(even) td {
    background: rgba(0,0,0,0.015);
}
#tabellaOrdini tbody tr:nth-child(even):hover td {
    background: rgba(37, 99, 235, 0.04);
}

/* Row highlight classes */
tr.scaduta td {
    background-color: #e8747a !important;
    color: #000 !important;
    font-weight: 700;
}
tr.warning-strong td {
    background-color: #f96f2a !important;
    color: #000 !important;
    font-weight: 700;
}
tr.warning-light td {
    background-color: #ffd07a !important;
    color: #000 !important;
    font-weight: 700;
}

/* Modal fluido */
.modal.fade .modal-dialog {
    transition: transform 0.2s ease;
}

/* Print button */
#printButton {
    background: none;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    padding: 5px 10px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    color: var(--text-secondary);
    transition: background 0.15s, color 0.15s;
}
#printButton:hover {
    background: var(--border-color);
    color: var(--text-primary);
}

/* Sticky scrollbar: account for sidebar on desktop */
@media (min-width: 1024px) {
    #stickyScroll { left: var(--sidebar-width); }
}

/* KPI clickable ore */
#kpiOreLavorate { cursor: pointer; }
#kpiOreLavorate:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.12);
}

/* Toast animation */
@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
</style>
@endsection

@section('content')

{{-- KPI GIORNALIERI --}}
<div class="d-flex gap-3 mb-4 flex-wrap">
    <x-mes.kpi-card value="{{ $fasiCompletateOggi }}" label="Fasi completate oggi" color="success" />
    <x-mes.kpi-card value="{{ $oreLavorateOggi }}h" label="Ore lavorate oggi" color="accent" id="kpiOreLavorate" subtitle="click per dettaglio" />
    <x-mes.kpi-card value="{{ $commesseSpediteOggi }}" label="Spedite oggi" color="info" />
    <x-mes.kpi-card value="{{ $fasiAttive }}" label="Fasi in lavorazione" color="warning" />
</div>

{{-- FILTRI (always visible) --}}
<div id="filterBox">
    <input type="text" id="filterCommessa" class="form-control form-control-sm" placeholder="Filtra Commessa (piu valori ,)" style="max-width:200px;">
    <input type="text" id="filterCliente" class="form-control form-control-sm" placeholder="Filtra Cliente (piu valori ,)" style="max-width:200px;">
    <input type="text" id="filterDescrizione" class="form-control form-control-sm" placeholder="Filtra Descrizione (piu valori ,)" style="max-width:300px;">

    <select id="filterStato" multiple>
        <option value="0">0</option>
        <option value="1">1</option>
        <option value="2">2</option>
        <option value="3">3</option>
    </select>

    <select id="filterFase" multiple>
        @php
            $fasiNomi = $fasiCatalogo->pluck('nome_display')->map(function($n) {
                if ($n === 'STAMPA') return null;
                return $n;
            })->filter()->unique()->sort()->values();
        @endphp
        @foreach($fasiNomi as $nome)
            <option value="{{ strtolower($nome) }}">{{ $nome }}</option>
        @endforeach
    </select>

    <select id="filterReparto" multiple>
        @foreach($reparti as $id => $rep)
            @if($rep !== 'fustella')
            <option value="{{ $rep }}">{{ $rep }}</option>
            @endif
        @endforeach
    </select>

    <button type="button" class="btn-reset-filters" id="btnResetFilters">Rimuovi filtri</button>
    @if($isReadonly ?? false)
    <button type="button" class="btn-marco-filter" onclick="filtraRiferimentiMarco()">Rif. Marco</button>
    @endif
    <button id="printButton" title="Stampa celle selezionate">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
        Stampa
    </button>
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
<div id="tableScroll" style="width:100%; overflow-x:auto;">
    <table id="tabellaOrdini" style="white-space:nowrap;">
        <thead>
            <tr>
                <th>Commessa</th>
                <th>Stato</th>
                <th>Cliente</th>
                <th>Codice Articolo</th>
                <th>Colori</th>
                <th>Fustella</th>
                <th>Descrizione</th>
                <th>Qta</th>
                <th>UM</th>
                <th>Priorita</th>
                <th>Fase</th>
                <th>Reparto</th>
                <th>Carta</th>
                <th>Qta Carta</th>
                <th>Data Prevista Consegna</th>
                <th>Cod Carta</th>
                <th>UM Carta</th>
                <th>Operatori</th>
                <th>Qta Prod.</th>
                <th>Esterno</th>
                <th>Note</th>
                <th>Data Inizio</th>
                <th>Data Fine</th>
                <th>Ore Prev.</th>
                <th>Ore Lav.</th>
                <th>Data Reg.</th>
                <th>Progresso</th>
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
                $statiLabel = [0 => 'Caricato', 1 => 'Pronto', 2 => 'Avviato', 3 => 'Terminato', 4 => 'Consegnato'];
                $statoBg = [0 => '#e9ecef', 1 => '#cfe2ff', 2 => '#fff3cd', 3 => '#d1e7dd', 4 => '#c3c3c3'];
            @endphp
            <tr class="{{ $rowClass }}" data-id="{{ $fase->id }}">
                <td><a href="{{ route('owner.dettaglioCommessa', $fase->ordine->commessa ?? '-') }}" style="color:var(--text-primary);font-weight:bold;text-decoration:underline;">{{ $fase->ordine->commessa ?? '-' }}</a></td>
                <td contenteditable onblur="aggiornaStato({{ $fase->id }}, this.innerText)" style="background:{{ $statoBg[$fase->stato] ?? '#e9ecef' }} !important;font-weight:bold;text-align:center;">{{ $fase->stato }}</td>
                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'cliente_nome', this.innerText)">{{ $fase->ordine->cliente_nome ?? '-' }}</td>
                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'cod_art', this.innerText)">{{ $fase->ordine->cod_art ?? '-' }}</td>
                @php
                    $descOwner = $fase->ordine->descrizione ?? '';
                    $clienteOwner = $fase->ordine->cliente_nome ?? '';
                    $repartoOwner = strtolower($fase->faseCatalogo->reparto->nome ?? '');
                    $coloriOwner = \App\Helpers\DescrizioneParser::parseColori($descOwner, $clienteOwner, $repartoOwner);
                    $fustellaOwner = \App\Helpers\DescrizioneParser::parseFustella($descOwner, $clienteOwner, $fase->ordine->note_prestampa ?? '');
                @endphp
                <td>{{ $coloriOwner ?: '-' }}</td>
                <td>{{ $fustellaOwner ?: '-' }}</td>
                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'descrizione', this.innerText)">{{ $fase->ordine->descrizione ?? '-' }}</td>
                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'qta_richiesta', this.innerText)">{{ $fase->ordine->qta_richiesta ?? '-' }}</td>
                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'um', this.innerText)">{{ $fase->ordine->um ?? '-' }}</td>
                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'priorita', this.innerText)">{{ $fase->priorita !== null ? number_format($fase->priorita, 2) : '-' }}</td>
                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'fase', this.innerText)">{{ $fase->faseCatalogo->nome_display ?? '-' }}</td>
                <td>{{ $fase->faseCatalogo->reparto->nome ?? '-' }}</td>
                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'carta', this.innerText)">{{ $fase->ordine->carta ?? '-' }}</td>
                @php
                    $umFase = strtoupper(trim($fase->um ?? 'FG'));
                    $isPezzi = in_array($umFase, ['TR', 'PZ', 'KG']);
                    $umLabel = $isPezzi ? 'pz' : 'fg';
                    if ($umFase === 'KG') {
                        $qtaFaseVal = $fase->ordine->qta_richiesta ?? 0;
                    } else {
                        $qtaFaseVal = $fase->qta_fase ?: ($isPezzi ? ($fase->ordine->qta_richiesta ?? 0) : ($fase->ordine->qta_carta ?? 0));
                    }
                @endphp
                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'qta_fase', this.innerText)">{{ $qtaFaseVal ? number_format($qtaFaseVal, 0, ',', '.') : '-' }}</td>
                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'data_prevista_consegna', this.innerText)">{{ formatItalianDate($fase->ordine->data_prevista_consegna) }}</td>
                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'cod_carta', this.innerText)">{{ $fase->ordine->cod_carta ?? '-' }}</td>
                <td style="font-weight:600;color:{{ $isPezzi ? '#2563eb' : '#059669' }}">{{ $umLabel }}</td>
                <td>
                    @forelse($fase->operatori as $op)
                        {{ $op->nome }}<br>
                    @empty
                        -
                    @endforelse
                </td>
                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'qta_prod', this.innerText)">{{ $fase->qta_prod ?? '-' }}</td>
                @php
                    $fornitoreEsterno = preg_match('/Inviato a:\s*(.+)/i', $fase->note ?? '', $mEst) ? trim($mEst[1]) : null;
                    $notePulitaOwner = preg_replace('/,?\s*Inviato a:\s*.+/i', '', $fase->note ?? '');
                    $notePulitaOwner = trim($notePulitaOwner, ", \t\n\r") ?: null;
                @endphp
                <td>{{ $fornitoreEsterno ?? '-' }}</td>
                <td>
                    @php
                        $nfsOwner = $fase->ordine->note_fasi_successive ?? '';
                        $righeNfsOwner = $nfsOwner ? json_decode($nfsOwner, true) : [];
                    @endphp
                    @if(!empty($righeNfsOwner) && is_array($righeNfsOwner))
                        @foreach($righeNfsOwner as $r)
                            <strong>{{ $r['nome'] ?? '' }}</strong>: {{ $r['testo'] ?? '' }}@if(!$loop->last) — @endif
                        @endforeach
                        @if($notePulitaOwner)<br>@endif
                    @endif
                    <span contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'note', this.innerText)">{{ $notePulitaOwner ?? '-' }}</span>
                </td>
                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'data_inizio', this.innerText)">{{ formatItalianDate($fase->data_inizio, true) }}</td>
                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'data_fine', this.innerText)">{{ formatItalianDate($fase->data_fine, true) }}</td>
                @php
                    // Ore previste: avviamento + qta_carta / copieh
                    $fasiInfoOw = config('fasi_ore');
                    $infoFaseOw = $fasiInfoOw[$fase->fase] ?? null;
                    $orePreviste = null;
                    if ($infoFaseOw) {
                        $qtaCartaOw = $fase->ordine->qta_carta ?? 0;
                        $copiehOw = $infoFaseOw['copieh'] ?: 1;
                        $orePreviste = round($infoFaseOw['avviamento'] + ($qtaCartaOw / $copiehOw), 1);
                    }
                @endphp
                <td>
                    @if($orePreviste !== null)
                        @if($orePreviste >= 1)
                            {{ floor($orePreviste) }}h {{ round(($orePreviste - floor($orePreviste)) * 60) }}m
                        @else
                            {{ round($orePreviste * 60) }}m
                        @endif
                    @else
                        -
                    @endif
                </td>
                @php
                    // Prinect (stampa XL): tempo_avviamento_sec + tempo_esecuzione_sec
                    $secPrinect = ($fase->tempo_avviamento_sec ?? 0) + ($fase->tempo_esecuzione_sec ?? 0);
                    if ($secPrinect > 0) {
                        $oreNetteOw = $secPrinect / 3600;
                        $secNettoOw = $secPrinect;
                        $fonteTempo = 'P';
                    } else {
                        // Fallback: pivot operatore (data_fine - data_inizio - pause)
                        $totSecPausa = $fase->operatori->sum(fn($op) => $op->pivot->secondi_pausa ?? 0);
                        $secLordoOw = 0;
                        $diOw = $fase->operatori->whereNotNull('pivot.data_inizio')->sortBy('pivot.data_inizio')->first()?->pivot->data_inizio;
                        $dfOw = $fase->operatori->whereNotNull('pivot.data_fine')->sortByDesc('pivot.data_fine')->first()?->pivot->data_fine;
                        if ($diOw && $dfOw) {
                            $secLordoOw = abs(\Carbon\Carbon::parse($dfOw)->getTimestamp() - \Carbon\Carbon::parse($diOw)->getTimestamp());
                        }
                        $secNettoOw = max($secLordoOw - $totSecPausa, 0);
                        $oreNetteOw = $secNettoOw / 3600;
                        $fonteTempo = '';
                    }
                @endphp
                <td>
                    @if($secNettoOw > 0)
                        @if($secNettoOw >= 3600)
                            {{ floor($secNettoOw / 3600) }}h {{ floor(($secNettoOw % 3600) / 60) }}m
                        @elseif($secNettoOw >= 60)
                            {{ floor($secNettoOw / 60) }}m
                        @else
                            {{ $secNettoOw }}s
                        @endif
                        @if($fonteTempo)<small class="text-muted-mes">{{ $fonteTempo }}</small>@endif
                    @else
                        -
                    @endif
                </td>
                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'data_registrazione', this.innerText)">{{ formatItalianDate($fase->ordine->data_registrazione) }}</td>
                @php
                    $prog = $progressoCommesse[$fase->ordine->commessa ?? ''] ?? ['totale'=>0,'terminate'=>0,'avviate'=>0,'percentuale'=>0];
                    $progPerc = $prog['percentuale'];
                    $progAvv = $prog['totale'] > 0 ? round(($prog['avviate'] / $prog['totale']) * 100) : 0;
                @endphp
                <td style="padding:2px 4px; vertical-align:middle;">
                    <x-mes.progress-bar
                        :percentuale="$progPerc"
                        :avviate="$progAvv"
                        :totale="$prog['totale']"
                        :terminate="$prog['terminate']"
                    />
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

{{-- HIDDEN FORMS --}}
<form method="POST" action="{{ route('owner.syncOnda') }}" id="formSyncOnda" style="display:none;">
    @csrf
</form>

{{-- MODALE AGGIUNGI RIGA --}}
<div class="modal fade" id="aggiungiRigaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('owner.aggiungiRiga') }}{{ request('op_token') ? '?op_token='.request('op_token') : '' }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header" style="background:var(--bg-sidebar);color:#fff;">
                    <h5 class="modal-title">Aggiungi Riga</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
                            <label class="form-label">Priorita</label>
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

{{-- MODALE SPEDIZIONI OGGI --}}
<div class="modal fade" id="modalSpedizioniOggi" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header" style="background:var(--bg-sidebar);color:#fff;">
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
                            <th>DDT</th>
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
                            <td>{{ $sp->ordine->numero_ddt_vendita ? ltrim($sp->ordine->numero_ddt_vendita, '0') : '-' }}</td>
                            <td>{{ $sp->faseCatalogo->nome_display ?? $sp->fase ?? '-' }}</td>
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

<!-- Modal Spedizioni BRT -->
<div class="modal fade" id="modalBRT" tabindex="-1">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header" style="background:#b91c1c; color:#fff; padding:18px 24px;">
                <h5 class="modal-title" style="font-size:22px; font-weight:700;">Spedizioni BRT ({{ $spedizioniBRT->count() }} DDT)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="overflow-x:auto; padding:20px 24px;">
                @if($spedizioniBRT->count() > 0)
                <div class="mb-3">
                    <button class="btn btn-outline-danger fw-bold" style="font-size:15px; padding:8px 18px;" id="btnCaricaTuttiBRT" onclick="caricaTuttiTrackingBRT()">
                        <span class="spinner-border spinner-border-sm d-none" id="spinnerTuttiBRT" role="status"></span>
                        Carica tutti i tracking
                    </button>
                    <span id="brtProgressLabel" class="ms-2 text-muted" style="font-size:14px;"></span>
                </div>
                <table class="table table-bordered" style="white-space:nowrap; font-size:15px;">
                    <thead style="background:#b91c1c; color:#fff;">
                        <tr>
                            <th style="padding:12px 14px;">DDT</th>
                            <th style="padding:12px 14px;">Commesse</th>
                            <th style="padding:12px 14px;">Descrizione</th>
                            <th style="padding:12px 14px;">Cliente</th>
                            <th style="padding:12px 14px;">Stato BRT</th>
                            <th style="padding:12px 14px;">Data Consegna</th>
                            <th style="padding:12px 14px;">Destinatario</th>
                            <th style="padding:12px 14px;">Colli</th>
                            <th style="padding:12px 14px;">Azione</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($spedizioniBRT as $numDDT => $ordiniGruppo)
                        @php
                            $primo = $ordiniGruppo->first();
                            $commesse = $ordiniGruppo->pluck('commessa')->unique()->implode(', ');
                            $cached = $primo->brt_cache_at ? true : false;
                            $statoCache = $primo->brt_stato ?? null;
                            $badgeClass = 'bg-light text-muted';
                            $badgeText = 'Da verificare';
                            if ($cached && $statoCache) {
                                $badgeText = $statoCache;
                                if (str_contains($statoCache, 'CONSEGNATA')) $badgeClass = 'bg-success';
                                elseif (str_contains($statoCache, 'IN TRANSITO') || str_contains($statoCache, 'PARTITA')) $badgeClass = 'bg-primary';
                                elseif (str_contains($statoCache, 'CONSEGNA')) $badgeClass = 'bg-warning text-dark';
                                elseif (str_contains($statoCache, 'RITIRATA')) $badgeClass = 'bg-info';
                                elseif (str_contains($statoCache, 'MULTI')) $badgeClass = 'bg-purple" style="background:#7c3aed!important;color:#fff';
                                else $badgeClass = 'bg-secondary';
                            }
                        @endphp
                        <tr id="brt_row_{{ md5($numDDT) }}">
                            <td class="fw-bold" style="padding:10px 14px; font-size:16px;">{{ ltrim($numDDT, '0') }}</td>
                            <td style="padding:10px 14px;">{{ $commesse }}</td>
                            <td style="padding:10px 14px; max-width:250px; white-space:normal;">{!! $ordiniGruppo->map(fn($d) => $d->ordine->descrizione ?? '')->filter()->unique()->map(fn($d) => e(Str::limit($d, 60)))->implode('<hr style="margin:4px 0; border-color:#ccc;">') !!}</td>
                            <td style="padding:10px 14px;">{{ $primo->cliente_nome ?? '-' }}</td>
                            <td id="brt_stato_{{ md5($numDDT) }}" style="padding:10px 14px;">
                                <span class="badge {{ $badgeClass }}" style="font-size:13px; padding:6px 10px;">{{ $badgeText }}</span>
                            </td>
                            <td id="brt_data_{{ md5($numDDT) }}" style="padding:10px 14px;">{{ $cached ? ($primo->brt_data_consegna ?? '-') : '-' }}</td>
                            <td id="brt_dest_{{ md5($numDDT) }}" style="padding:10px 14px;">{{ $cached ? ($primo->brt_destinatario ?? '-') : '-' }}</td>
                            <td id="brt_colli_{{ md5($numDDT) }}" style="padding:10px 14px;">{{ $cached ? ($primo->brt_colli ?? '-') : '-' }}</td>
                            <td style="padding:10px 14px;">
                                <button class="btn btn-outline-danger fw-bold" style="font-size:14px; padding:6px 16px;" onclick="apriTrackingDDT('{{ $numDDT }}', this)">
                                    Dettagli
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <p class="text-muted text-center py-4" style="font-size:16px;">Nessuna spedizione BRT</p>
                @endif
            </div>
            <div class="modal-footer" style="padding:14px 24px;">
                <button type="button" class="btn btn-secondary" style="font-size:15px; padding:8px 20px;" data-bs-dismiss="modal">Chiudi</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Storico Consegne -->
<div class="modal fade" id="modalStorico" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header" style="background:var(--bg-sidebar);color:#fff;">
                <h5 class="modal-title">Storico consegne (ultimi 30 giorni)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="overflow-x:auto;">
                <div class="mb-3">
                    <input type="text" id="filtro-storico" class="form-control" placeholder="Cerca per commessa, cliente, descrizione..." autocomplete="off">
                </div>
                @if($storicoConsegne->count() > 0)
                @foreach($storicoConsegne->groupBy(fn($f) => \Carbon\Carbon::parse($f->data_fine)->format('Y-m-d')) as $dataStorico => $fasiGiorno)
                <h6 class="mt-3 mb-2 fw-bold" style="color:var(--text-primary);">
                    {{ \Carbon\Carbon::parse($dataStorico)->format('d/m/Y') }}
                    <span class="badge bg-secondary ms-1">{{ $fasiGiorno->count() }}</span>
                </h6>
                <table class="table table-bordered table-sm mb-3" style="white-space:nowrap;">
                    <thead class="table-light">
                        <tr>
                            <th>Commessa</th>
                            <th>Cliente</th>
                            <th>Cod. Articolo</th>
                            <th>Descrizione</th>
                            <th>Qta</th>
                            <th>Tipo</th>
                            <th>Ora</th>
                            <th>Operatore</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($fasiGiorno as $faseStorico)
                        <tr>
                            <td><strong>{{ $faseStorico->ordine->commessa ?? '-' }}</strong></td>
                            <td>{{ $faseStorico->ordine->cliente_nome ?? '-' }}</td>
                            <td>{{ $faseStorico->ordine->cod_art ?? '-' }}</td>
                            <td>{{ $faseStorico->ordine->descrizione ?? '-' }}</td>
                            <td>{{ $faseStorico->ordine->qta_richiesta ?? '-' }}</td>
                            <td>
                                @if($faseStorico->tipo_consegna === 'parziale')
                                    <span class="badge bg-warning text-dark">Parziale</span>
                                @else
                                    <span class="badge bg-success">Totale</span>
                                @endif
                            </td>
                            <td>{{ $faseStorico->data_fine ? \Carbon\Carbon::parse($faseStorico->data_fine)->format('H:i') : '-' }}</td>
                            <td>
                                @foreach($faseStorico->operatori as $op)
                                    {{ $op->nome }} {{ $op->cognome }}<br>
                                @endforeach
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endforeach
                @else
                <p class="text-muted text-center py-3">Nessuna consegna negli ultimi 30 giorni</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal Tracking BRT Dettagli -->
<div class="modal fade" id="modalTracking" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:#b91c1c; color:#fff;">
                <h5 class="modal-title">Tracking BRT - <span id="trackingSegnacollo"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="trackingLoading" class="text-center py-4">
                    <div class="spinner-border text-danger" role="status"></div>
                    <p class="mt-2">Caricamento tracking...</p>
                </div>
                <div id="trackingErrore" class="alert alert-danger d-none"></div>
                <div id="trackingContenuto" class="d-none">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Stato spedizione:</strong>
                            <span id="trackingStato" class="badge bg-secondary ms-1"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Data consegna BRT:</strong>
                            <span id="trackingDataConsegna"></span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Destinatario:</strong>
                            <span id="trackingDestinatario"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Filiale:</strong>
                            <span id="trackingFiliale"></span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Colli:</strong>
                            <span id="trackingColli"></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Peso (kg):</strong>
                            <span id="trackingPeso"></span>
                        </div>
                    </div>
                    <hr>
                    <h6>Eventi</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead class="table-light">
                                <tr><th>Data</th><th>Ora</th><th>Descrizione</th><th>Filiale</th></tr>
                            </thead>
                            <tbody id="trackingEventi"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
            </div>
        </div>
    </div>
</div>

<!-- Modale Note Consegne -->
<div class="modal fade" id="modalNoteSpedizione" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:var(--bg-sidebar);color:#fff;">
                <h5 class="modal-title">Note Consegne - {{ now()->format('d/m/Y') }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <textarea id="ownerNotaContenuto" rows="10" class="form-control" style="font-size:14px;" placeholder="Note consegne..."></textarea>
            </div>
            <div class="modal-footer" style="justify-content:space-between;">
                <span id="ownerNoteSaveStatus" style="font-size:12px; color:var(--text-secondary);"></span>
                <button onclick="salvaNoteSped()" class="btn btn-primary btn-sm">Salva</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Presenti in azienda --}}
<div class="modal fade" id="modalPresenti" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:var(--bg-sidebar);color:#fff;">
                <h5 class="modal-title">Presenti in azienda - <span id="presentiData">{{ now()->format('d/m/Y') }}</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="max-height:70vh; overflow-y:auto;">
                <div id="presentiLoading" class="text-center py-3"><div class="spinner-border text-success"></div></div>
                <div id="presentiContent" style="display:none;">
                    <h6 style="color:var(--success); margin-bottom:8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="var(--success)" stroke="none"><circle cx="12" cy="12" r="6"/></svg>
                        Presenti (<span id="presentiTotale">0</span>)
                    </h6>
                    <table class="table table-sm table-striped" style="font-size:13px;">
                        <thead><tr><th>Nome</th><th>Entrata</th><th>Ultima</th></tr></thead>
                        <tbody id="presentiBody"></tbody>
                    </table>
                    <h6 style="color:var(--text-secondary); margin-top:16px; margin-bottom:8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="var(--text-secondary)" stroke="none"><circle cx="12" cy="12" r="6"/></svg>
                        Usciti (<span id="uscitiTotale">0</span>)
                    </h6>
                    <table class="table table-sm" style="font-size:13px; opacity:0.7;">
                        <thead><tr><th>Nome</th><th>Entrata</th><th>Uscita</th></tr></thead>
                        <tbody id="uscitiBody"></tbody>
                    </table>
                    <div class="text-end" style="font-size:11px; color:var(--text-secondary);">Ultimo aggiornamento: <span id="presentiSync">-</span></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Scrollbar orizzontale sticky in fondo alla viewport --}}
<div id="stickyScroll" style="position:fixed;bottom:0;left:0;right:0;overflow-x:auto;overflow-y:hidden;z-index:1000;height:16px;background:var(--bg-page);border-top:1px solid var(--border-color);">
    <div id="stickyScrollInner" style="height:1px;"></div>
</div>

@endsection

@section('scripts')
<script>
// === Token per fetch autenticate ===
var _opToken = '{{ $opToken ?? request()->query("op_token", "") }}';
function urlToken(url) {
    if (!_opToken) return url;
    return url + (url.indexOf('?') >= 0 ? '&' : '?') + 'op_token=' + encodeURIComponent(_opToken);
}

// === Filtro storico consegne ===
document.getElementById('filtro-storico')?.addEventListener('input', function() {
    var q = this.value.toLowerCase().trim();
    var modal = document.getElementById('modalStorico');
    var sezioni = modal.querySelectorAll('h6.mt-3');
    sezioni.forEach(function(h6) {
        var table = h6.nextElementSibling;
        while (table && table.tagName !== 'TABLE') table = table.nextElementSibling;
        if (!table) return;
        var righe = table.querySelectorAll('tbody tr');
        var visibili = 0;
        righe.forEach(function(tr) {
            var testo = tr.textContent.toLowerCase();
            var match = !q || testo.includes(q);
            tr.style.display = match ? '' : 'none';
            if (match) visibili++;
        });
        h6.style.display = visibili > 0 ? '' : 'none';
        table.style.display = visibili > 0 ? '' : 'none';
    });
});

// === BRT Tracking ===
function getBrtHdrs() {
    return {
        'X-CSRF-TOKEN': csrfToken(),
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    };
}

var brtDDTList = [
    @foreach($spedizioniBRT as $numDDT => $ordiniGruppo)
        { ddt: '{{ $numDDT }}', hash: '{{ md5($numDDT) }}' },
    @endforeach
];

function caricaTuttiTrackingBRT() {
    var btnAll = document.getElementById('btnCaricaTuttiBRT');
    var spinnerAll = document.getElementById('spinnerTuttiBRT');
    var labelProgress = document.getElementById('brtProgressLabel');
    if (!btnAll) return;
    btnAll.disabled = true;
    spinnerAll.classList.remove('d-none');

    var i = 0;
    var total = brtDDTList.length;

    function next() {
        if (i >= total) {
            spinnerAll.classList.add('d-none');
            btnAll.disabled = false;
            btnAll.textContent = 'Completato';
            labelProgress.textContent = total + '/' + total;
            return;
        }
        labelProgress.textContent = (i + 1) + '/' + total + '...';
        caricaStatoBRT(brtDDTList[i].ddt, brtDDTList[i].hash, function() {
            i++;
            setTimeout(next, 300);
        });
    }
    next();
}

function caricaStatoBRT(numeroDDT, hash, callback) {
    fetch(urlToken('{{ route("owner.trackingByDDT") }}'), {
        method: 'POST', headers: getBrtHdrs(),
        body: JSON.stringify({ numero_ddt: numeroDDT })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        var statoEl = document.getElementById('brt_stato_' + hash);
        var dataEl = document.getElementById('brt_data_' + hash);
        var destEl = document.getElementById('brt_dest_' + hash);
        var colliEl = document.getElementById('brt_colli_' + hash);

        if (data.multi_spedizione) {
            statoEl.innerHTML = '<span class="badge" style="background:#7c3aed!important;color:#fff;">Multi-spedizione</span>';
            callback(); return;
        }
        if (data.error) {
            statoEl.innerHTML = '<span class="badge bg-warning text-dark">In attesa</span>';
            callback(); return;
        }
        if (!data.bolla || !data.bolla.spedizione_id) {
            statoEl.innerHTML = '<span class="badge bg-info">In elaborazione</span>';
            callback(); return;
        }

        var bolla = data.bolla;
        var stato = data.stato || 'SCONOSCIUTO';
        var badgeClass = 'bg-secondary';
        if (stato.indexOf('CONSEGNATA') >= 0) badgeClass = 'bg-success';
        else if (stato.indexOf('IN TRANSITO') >= 0 || stato.indexOf('PARTITA') >= 0) badgeClass = 'bg-primary';
        else if (stato.indexOf('CONSEGNA') >= 0) badgeClass = 'bg-warning text-dark';
        else if (stato.indexOf('RITIRATA') >= 0) badgeClass = 'bg-info';

        statoEl.innerHTML = '<span class="badge ' + badgeClass + '">' + stato + '</span>';
        dataEl.textContent = bolla.data_consegna ? (bolla.data_consegna + ' ' + (bolla.ora_consegna || '')) : '-';
        destEl.textContent = [bolla.destinatario_ragione_sociale, bolla.destinatario_localita].filter(Boolean).join(' - ') || '-';
        colliEl.textContent = bolla.colli || '-';
        callback();
    })
    .catch(function() {
        var statoEl = document.getElementById('brt_stato_' + hash);
        if (statoEl) statoEl.innerHTML = '<span class="badge bg-danger">Errore</span>';
        callback();
    });
}

function apriTrackingDDT(numeroDDT, btn) {
    var ddtLabel = numeroDDT.replace(/^0+/, '') || numeroDDT;
    document.getElementById('trackingSegnacollo').textContent = 'DDT ' + ddtLabel;
    document.getElementById('trackingLoading').classList.remove('d-none');
    document.getElementById('trackingErrore').classList.add('d-none');
    document.getElementById('trackingContenuto').classList.add('d-none');
    new bootstrap.Modal(document.getElementById('modalTracking')).show();

    fetch(urlToken('{{ route("owner.trackingByDDT") }}'), {
        method: 'POST', headers: getBrtHdrs(),
        body: JSON.stringify({ numero_ddt: numeroDDT })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        document.getElementById('trackingLoading').classList.add('d-none');
        if (data.error) {
            document.getElementById('trackingErrore').textContent = data.message || 'Nessuna spedizione trovata';
            document.getElementById('trackingErrore').classList.remove('d-none');
            return;
        }
        if (!data.bolla || !data.bolla.spedizione_id) {
            document.getElementById('trackingErrore').innerHTML = '<strong>Spedizione trovata</strong> (ID: ' + (data.spedizione_id || '?') + ')<br>In attesa di elaborazione da BRT.';
            document.getElementById('trackingErrore').className = 'alert alert-warning';
            document.getElementById('trackingErrore').classList.remove('d-none');
            return;
        }
        var bolla = data.bolla;
        document.getElementById('trackingSegnacollo').textContent = 'DDT ' + (bolla.rif_mittente_alfa || ddtLabel) + ' (Sped. ' + bolla.spedizione_id + ')';

        var statoEl = document.getElementById('trackingStato');
        statoEl.textContent = data.stato || 'IN ELABORAZIONE';
        if ((data.stato || '').indexOf('CONSEGNATA') >= 0) statoEl.className = 'badge bg-success ms-1';
        else if ((data.stato || '').indexOf('PARTITA') >= 0) statoEl.className = 'badge bg-warning text-dark ms-1';
        else statoEl.className = 'badge bg-info ms-1';

        document.getElementById('trackingDataConsegna').textContent = bolla.data_consegna ? (bolla.data_consegna + ' ' + (bolla.ora_consegna || '')) : '-';
        document.getElementById('trackingDestinatario').textContent = [bolla.destinatario_ragione_sociale, bolla.destinatario_localita, bolla.destinatario_provincia].filter(Boolean).join(' - ') || '-';
        document.getElementById('trackingFiliale').textContent = bolla.filiale_arrivo || '-';
        document.getElementById('trackingColli').textContent = bolla.colli || '-';
        document.getElementById('trackingPeso').textContent = bolla.peso_kg || '-';

        var eventiBody = document.getElementById('trackingEventi');
        eventiBody.innerHTML = '';
        var eventi = data.eventi || [];
        if (eventi.length === 0) {
            eventiBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Nessun evento</td></tr>';
        } else {
            eventi.forEach(function(ev) {
                var tr = document.createElement('tr');
                tr.innerHTML = '<td>' + (ev.data || '-') + '</td><td>' + (ev.ora || '-') + '</td><td>' + (ev.descrizione || '-') + '</td><td>' + (ev.filiale || '-') + '</td>';
                eventiBody.appendChild(tr);
            });
        }
        document.getElementById('trackingContenuto').classList.remove('d-none');
    })
    .catch(function(err) {
        document.getElementById('trackingLoading').classList.add('d-none');
        document.getElementById('trackingErrore').textContent = 'Errore connessione: ' + err;
        document.getElementById('trackingErrore').className = 'alert alert-danger';
        document.getElementById('trackingErrore').classList.remove('d-none');
    });
}

// Auto-carica tracking BRT quando il modal viene aperto
var brtModalCaricato = false;
document.getElementById('modalBRT')?.addEventListener('shown.bs.modal', function() {
    if (!brtModalCaricato && brtDDTList.length > 0) {
        brtModalCaricato = true;
        caricaTuttiTrackingBRT();
    }
});

// === aggiornaCampo / aggiornaStato ===
function aggiornaCampo(faseId, campo, valore){
    valore = valore.trim();

    // Se il campo e' numerico o priorita, sostituisci la virgola con punto
    const campiNumerici = ['qta_richiesta','qta_prod','priorita','qta_carta','ore'];
    if(campiNumerici.includes(campo)){
        valore = valore.replace(',', '.');
        if(isNaN(parseFloat(valore))){
            alert('Valore numerico non valido');
            return;
        }
    }

    fetch(urlToken('{{ route("owner.aggiornaCampo") }}'), {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': csrfToken(), 'Content-Type': 'application/json'},
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
    fetch(urlToken('{{ route("owner.aggiornaStato") }}'), {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': csrfToken(), 'Content-Type': 'application/json'},
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

// === DOMContentLoaded init ===
document.addEventListener('DOMContentLoaded', function(){
    // Rendi tutte le td focusabili (per espandere testo troncato al click)
    document.querySelectorAll('#tabellaOrdini td:not([contenteditable])').forEach(function(td){
        td.setAttribute('tabindex', '0');
    });

    // Badge consegnati
    var modal = document.getElementById('modalSpedizioniOggi');
    if(modal){
        modal.addEventListener('show.bs.modal', function(){
            var badge = document.getElementById('badgeConsegnati');
            if(badge) badge.style.display = 'none';
        });
    }
});

// === Excel-style Cell Selection ===
document.addEventListener('DOMContentLoaded', () => {
    const table = document.getElementById('tabellaOrdini');
    const allCells = Array.from(table.querySelectorAll('td, th'));
    const selectedCells = new Set();

    let isSelecting = false;
    let startX = 0, startY = 0;
    let mouseClientX = 0, mouseClientY = 0;
    let selectionBox = null;
    let pollTimer = null;
    let cachedRects = [];

    function cacheRects() {
        const sx = window.scrollX, sy = window.scrollY;
        cachedRects = allCells.map(cell => {
            const r = cell.getBoundingClientRect();
            return { cell, left: r.left + sx, top: r.top + sy, right: r.right + sx, bottom: r.bottom + sy };
        });
    }

    function updateSelection() {
        if (!selectionBox) return;

        const curX = mouseClientX + window.scrollX;
        const curY = mouseClientY + window.scrollY;

        const boxL = Math.min(startX, curX), boxT = Math.min(startY, curY);
        const boxR = Math.max(startX, curX), boxB = Math.max(startY, curY);

        selectionBox.style.left = boxL + 'px';
        selectionBox.style.top = boxT + 'px';
        selectionBox.style.width = (boxR - boxL) + 'px';
        selectionBox.style.height = (boxB - boxT) + 'px';

        selectedCells.forEach(c => c.classList.remove('selected'));
        selectedCells.clear();

        for (let i = 0; i < cachedRects.length; i++) {
            const r = cachedRects[i];
            if (r.right >= boxL && r.left <= boxR && r.bottom >= boxT && r.top <= boxB) {
                r.cell.classList.add('selected');
                selectedCells.add(r.cell);
            }
        }
    }

    function startSelection(pageX, pageY, clientX, clientY) {
        isSelecting = true;
        startX = pageX;
        startY = pageY;
        mouseClientX = clientX;
        mouseClientY = clientY;

        selectedCells.forEach(c => c.classList.remove('selected'));
        selectedCells.clear();

        cacheRects();

        selectionBox = document.createElement('div');
        selectionBox.className = 'selection-box';
        document.body.appendChild(selectionBox);

        pollTimer = setInterval(updateSelection, 50);
    }

    function endSelection() {
        isSelecting = false;
        if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
        if (selectionBox) { selectionBox.remove(); selectionBox = null; }
    }

    // Mouse: doppio click avvia, drag seleziona
    table.addEventListener('dblclick', e => {
        if (!e.target.matches('td, th')) return;
        startSelection(e.pageX, e.pageY, e.clientX, e.clientY);
        e.preventDefault();
    });

    document.addEventListener('mousemove', e => {
        mouseClientX = e.clientX;
        mouseClientY = e.clientY;
    });
    document.addEventListener('mouseup', () => { if (isSelecting) endSelection(); });

    // Touch: doppio tap avvia, drag seleziona
    let lastTap = 0;
    table.addEventListener('touchstart', e => {
        const now = Date.now();
        const touch = e.touches[0];
        const target = document.elementFromPoint(touch.clientX, touch.clientY);

        if (now - lastTap < 350 && target && target.matches('td, th')) {
            startSelection(touch.pageX, touch.pageY, touch.clientX, touch.clientY);
            e.preventDefault();
        }
        lastTap = now;
    }, { passive: false });

    document.addEventListener('touchmove', e => {
        if (!isSelecting) return;
        const touch = e.touches[0];
        mouseClientX = touch.clientX;
        mouseClientY = touch.clientY;
        e.preventDefault();
    }, { passive: false });

    document.addEventListener('touchend', () => { if (isSelecting) endSelection(); });

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
                    table { border-collapse: collapse; width: 100%; table-layout: auto; }
                    td, th { border:1px solid #ccc; padding:6px 8px; font-size:11px; white-space: normal; word-wrap: break-word; }
                    @page { size: landscape; margin: 10mm; }
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

        win.onafterprint = function() {
            selectedCells.forEach(c => c.classList.remove('selected'));
            selectedCells.clear();
            win.close();
        };
        var checkClosed = setInterval(function() {
            if (win.closed) {
                clearInterval(checkClosed);
                selectedCells.forEach(c => c.classList.remove('selected'));
                selectedCells.clear();
            }
        }, 300);
    });
});

// === Filter System (Choices.js) ===
document.addEventListener('DOMContentLoaded', () => {
    const choicesOptions = {
        removeItemButton: true,
        searchEnabled: true,
        itemSelectText: '',
        placeholder:true,
    };
    const choiceStato = new Choices('#filterStato',{...choicesOptions,placeholderValue:'Seleziona Stato'});
    const choiceFase = new Choices('#filterFase',{...choicesOptions,placeholderValue:'Seleziona Fase'})
    const choiceReparto = new Choices('#filterReparto',{...choicesOptions,placeholderValue:'Seleziona Reparto'})

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
        descrizione: row.cells[6].innerText.toLowerCase(),
        fase: row.cells[10].innerText.toLowerCase(),
        reparto: row.cells[11].innerText.toLowerCase()
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
                if(fasi.length) match = match && fasi.some(f => data.fase.includes(f));
                if(reparti.length) match = match && reparti.includes(data.reparto);
                data.row.style.display = match ? '' : 'none';
            });
        });
    }
    // Expose filtra globally for Marco filter
    window.filtra = filtra;

    function debounce(func, delay) {
        let timeout;
        return function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, arguments), delay);
        }
    }

    const filtraDebounced = debounce(filtra, 100);

    function salvaFiltri() {
        sessionStorage.setItem('ownerFilters', JSON.stringify({
            commessa: fCommessa.value,
            cliente: fCliente.value,
            descrizione: fDescrizione.value,
            stato: Array.from(fStato.selectedOptions).map(o => o.value),
            fase: Array.from(fFase.selectedOptions).map(o => o.value),
            reparto: Array.from(fReparto.selectedOptions).map(o => o.value),
        }));
    }
    function filtraESalva() { filtraDebounced(); setTimeout(salvaFiltri, 150); }

    fCommessa.addEventListener('input', filtraESalva);
    fCliente.addEventListener('input', filtraESalva);
    fDescrizione.addEventListener('input', filtraESalva);
    fStato.addEventListener('change', filtraESalva);
    fFase.addEventListener('change', filtraESalva);
    fReparto.addEventListener('change', filtraESalva);

    document.getElementById('btnResetFilters').addEventListener('click', function() {
        fCommessa.value = '';
        fCliente.value = '';
        fDescrizione.value = '';
        choiceStato.removeActiveItems();
        choiceFase.removeActiveItems();
        choiceReparto.removeActiveItems();
        rowData.forEach(data => { data.row.style.display = ''; });
        sessionStorage.removeItem('ownerFilters');
    });

    // Ripristina filtri da sessionStorage al caricamento
    try {
        var saved = JSON.parse(sessionStorage.getItem('ownerFilters'));
        if (saved) {
            var hasFilter = false;
            if (saved.commessa) { fCommessa.value = saved.commessa; hasFilter = true; }
            if (saved.cliente) { fCliente.value = saved.cliente; hasFilter = true; }
            if (saved.descrizione) { fDescrizione.value = saved.descrizione; hasFilter = true; }
            if (saved.stato && saved.stato.length) { saved.stato.forEach(v => choiceStato.setChoiceByValue(v)); hasFilter = true; }
            if (saved.fase && saved.fase.length) { saved.fase.forEach(v => choiceFase.setChoiceByValue(v)); hasFilter = true; }
            if (saved.reparto && saved.reparto.length) { saved.reparto.forEach(v => choiceReparto.setChoiceByValue(v)); hasFilter = true; }
            if (hasFilter) {
                filtra();
            }
        }
    } catch(e) {}
});

// === Riferimenti Marco ===
const CLIENTI_MARCO = [
    'WYCON', 'ARMATORE', 'FARMARICCI', 'ELLEBI', 'FEUDI DI SAN GREGORIO',
    'DE NIGRIS', 'VOYAGE PITTORESQUE', 'ANTIMO CAPUTO', 'DI MARTINO AIR',
    "DE' NOBILI", 'MIA COSMETICS', 'VISION SRL', 'PASTIFICIO DI MARTINO',
    'LA RUOTA', 'AMES CENTRO', 'PASTIFICIO ARTIGIANALE LEONESSA', 'A5CREW',
    'VISIONA', 'LINGO COMMUNICATIONS', 'POMOROSSO', 'ESA - ESRIN',
    'PASTICCERIA TROIANO', 'BALTHAZAR', 'GRUPPO CASEARIO', 'NATURAL SOAP',
    'QUESTION MARK', 'PROMOPHARMA', 'LEOPOLDO VILLANO', 'BORGODEA',
    'HILTRON LAND', 'PROMOITALIA', 'AT ADV', 'CARMEN COMMERCIALE',
    'AGRELLI', 'GIAGUARO', 'MUSEO SAN SEVERO', 'DOPPIAVU', 'ITALY STAMPE',
    'SPRINT SRL', 'EXTON', 'FABULA PROJECT', 'EUROSTYLE', 'FRATELLI CUOMO',
    'F.LLI CUOMO', 'DISTILL HUB', 'SAN GREGORIO S.R.L.', 'STARWOOD',
    'CAPOBIANCO', 'VILLA RAIANO'
];

function filtraRiferimentiMarco() {
    const fCliente = document.getElementById('filterCliente');
    fCliente.value = CLIENTI_MARCO.join(', ');
    fCliente.dispatchEvent(new Event('input'));
}

function resetRiferimentiMarco() {
    const fCliente = document.getElementById('filterCliente');
    fCliente.value = '';
    fCliente.dispatchEvent(new Event('input'));
}

// === Notifiche Note Consegne ===
var _noteLastUpdate = localStorage.getItem('noteConsegne_lastUpdate') || '';
var _noteCheckInterval = 15000;

var _audioCtx = null;
document.addEventListener('click', function() {
    if (!_audioCtx) {
        _audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    }
}, {once: true});

if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
}

function checkNoteConsegne() {
    fetch(urlToken('{{ route("owner.noteSpedizioneCheck") }}'), {
        headers: {'Accept': 'application/json'}
    })
    .then(r => r.json())
    .then(d => {
        if (d.updated_at && d.updated_at !== _noteLastUpdate) {
            if (_noteLastUpdate !== '') {
                document.getElementById('noteConsegneBadge').style.display = 'inline-block';

                try {
                    if (!_audioCtx) _audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                    if (_audioCtx.state === 'suspended') _audioCtx.resume();
                    var osc = _audioCtx.createOscillator();
                    var gain = _audioCtx.createGain();
                    osc.connect(gain);
                    gain.connect(_audioCtx.destination);
                    osc.frequency.value = 800;
                    gain.gain.value = 0.3;
                    osc.start();
                    osc.stop(_audioCtx.currentTime + 0.3);
                } catch(e) {}

                if ('Notification' in window && Notification.permission === 'granted') {
                    var preview = (d.contenuto || '').substring(0, 100);
                    new Notification('Note Consegne aggiornate', {
                        body: preview || 'La logistica ha aggiornato le note consegne',
                        icon: '/favicon.ico'
                    });
                }

                showNoteToast('La logistica ha aggiornato le note consegne');
            }
            _noteLastUpdate = d.updated_at;
            localStorage.setItem('noteConsegne_lastUpdate', d.updated_at);
        }
    })
    .catch(() => {});
}

function showNoteToast(msg) {
    var toast = document.createElement('div');
    toast.innerHTML = '<strong>Note Consegne</strong><br>' + msg;
    toast.style.cssText = 'position:fixed; top:20px; right:20px; z-index:9999; background:var(--accent, #2563eb); color:#fff; padding:15px 20px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.3); font-size:14px; cursor:pointer; max-width:350px; animation:slideIn 0.3s ease;';
    toast.onclick = function() {
        toast.remove();
        document.getElementById('noteConsegneBadge').style.display = 'none';
        var modal = new bootstrap.Modal(document.getElementById('modalNoteSpedizione'));
        modal.show();
        caricaNoteSpedizione();
    };
    document.body.appendChild(toast);
    setTimeout(function() { if (toast.parentNode) toast.remove(); }, 30000);
}

// Nascondi badge quando apre le note
var _modalNote = document.getElementById('modalNoteSpedizione');
if (_modalNote) {
    _modalNote.addEventListener('show.bs.modal', function() {
        document.getElementById('noteConsegneBadge').style.display = 'none';
    });
} else {
    document.addEventListener('DOMContentLoaded', function() {
        var m = document.getElementById('modalNoteSpedizione');
        if (m) m.addEventListener('show.bs.modal', function() {
            document.getElementById('noteConsegneBadge').style.display = 'none';
        });
    });
}

// WebSocket con fallback a polling per Note Consegne
if (window.listenOrPoll) {
    window.listenOrPoll('note-consegne', 'aggiornate', function(data) {
        // Ricevuto via WebSocket — stessa logica di checkNoteConsegne ma con dati push
        var lastKnown = localStorage.getItem('noteConsegne_lastUpdate') || '';
        if (data.updated_at && data.updated_at !== lastKnown) {
            localStorage.setItem('noteConsegne_lastUpdate', data.updated_at);
            _beep();
            if (Notification.permission === 'granted') {
                new Notification('Note Consegne aggiornate', { body: data.aggiornato_da || 'Spedizione' });
            }
            var badge = document.getElementById('noteConsegneBadge');
            if (badge) badge.style.display = 'inline-block';
            caricaNoteSpedizione();
        }
    }, checkNoteConsegne, _noteCheckInterval);
} else {
    checkNoteConsegne();
    setInterval(checkNoteConsegne, _noteCheckInterval);
}

// === Note Consegne (readonly per owner) ===
function caricaNoteSpedizione() {
    fetch(urlToken('{{ route("owner.noteSpedizione") }}?data={{ now()->toDateString() }}'), {
        headers: {'Accept': 'application/json'}
    })
    .then(r => r.json())
    .then(d => {
        document.getElementById('ownerNotaContenuto').value = d.contenuto || '';
        document.getElementById('ownerNoteSaveStatus').textContent = d.da_data ? '(dal ' + new Date(d.da_data).toLocaleDateString('it-IT') + ')' : '';
    })
    .catch(() => {
        document.getElementById('ownerNoteSaveStatus').textContent = 'Errore caricamento';
        document.getElementById('ownerNoteSaveStatus').style.color = 'var(--danger)';
    });
}

function salvaNoteSped() {
    var btn = event.target;
    btn.disabled = true;
    fetch(urlToken('{{ route("owner.salvaNotaSpedizione") }}'), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            data: '{{ now()->toDateString() }}',
            contenuto: document.getElementById('ownerNotaContenuto').value
        })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            document.getElementById('ownerNoteSaveStatus').textContent = 'Salvato alle ' + new Date().toLocaleTimeString('it-IT');
            document.getElementById('ownerNoteSaveStatus').style.color = 'var(--success)';
            fetch(urlToken('{{ route("owner.noteSpedizioneCheck") }}'), {headers:{'Accept':'application/json'}})
                .then(function(r){return r.json();})
                .then(function(dd){ if(dd.updated_at){ _noteLastUpdate=dd.updated_at; localStorage.setItem('noteConsegne_lastUpdate',_noteLastUpdate); } });
        }
    })
    .catch(() => {
        document.getElementById('ownerNoteSaveStatus').textContent = 'Errore salvataggio';
        document.getElementById('ownerNoteSaveStatus').style.color = 'var(--danger)';
    })
    .finally(() => { btn.disabled = false; });
}

// === Presenti in azienda ===
var _presentiInterval = null;
function caricaPresenti() {
    document.getElementById('presentiLoading').style.display = 'block';
    document.getElementById('presentiContent').style.display = 'none';
    fetchPresenti();
    if (_presentiInterval) clearInterval(_presentiInterval);
    // Se Echo connesso, ascolta via WebSocket; altrimenti polling 60s
    if (window.echoConnected) {
        window.Echo.channel('presenze').listen('.aggiornati', function(data) {
            var badge = document.getElementById('presentiCount');
            if (badge) badge.textContent = data.totale_presenti;
            fetchPresenti(); // ricarica dettaglio
        });
    } else {
        _presentiInterval = setInterval(fetchPresenti, 60000);
    }
}
function fetchPresenti() {
    fetch('{{ route("owner.presenti") }}')
        .then(function(r) { return r.json(); })
        .then(function(d) {
            document.getElementById('presentiLoading').style.display = 'none';
            document.getElementById('presentiContent').style.display = 'block';
            document.getElementById('presentiTotale').textContent = d.totale_presenti;
            document.getElementById('uscitiTotale').textContent = d.totale_usciti;
            document.getElementById('presentiSync').textContent = d.ultimo_sync;
            var badge = document.getElementById('presentiCount');
            if (badge) badge.textContent = d.totale_presenti;

            var pb = document.getElementById('presentiBody');
            pb.innerHTML = '';
            d.presenti.forEach(function(p) {
                pb.innerHTML += '<tr><td><strong>' + p.nome + '</strong></td><td>' + (p.entrata || '-') + '</td><td>' + p.ultima_timbratura + '</td></tr>';
            });
            if (d.presenti.length === 0) pb.innerHTML = '<tr><td colspan="3" class="text-muted">Nessuno presente</td></tr>';

            var ub = document.getElementById('uscitiBody');
            ub.innerHTML = '';
            d.usciti.forEach(function(u) {
                ub.innerHTML += '<tr><td>' + u.nome + '</td><td>' + (u.entrata || '-') + '</td><td>' + u.ultima_timbratura + '</td></tr>';
            });
            if (d.usciti.length === 0) ub.innerHTML = '<tr><td colspan="3" class="text-muted">-</td></tr>';
        }).catch(function() {});
}
document.getElementById('modalPresenti')?.addEventListener('hidden.bs.modal', function() {
    if (_presentiInterval) { clearInterval(_presentiInterval); _presentiInterval = null; }
});
// Carica badge presenti al load della pagina
fetch('{{ route("owner.presenti") }}')
    .then(function(r) { return r.json(); })
    .then(function(d) {
        var badge = document.getElementById('presentiCount');
        if (badge) badge.textContent = d.totale_presenti;
    }).catch(function() {});

// === Load notes on sidebar button and modal open ===
document.getElementById('sidebarNoteBtn')?.addEventListener('click', function() {
    caricaNoteSpedizione();
});
document.getElementById('modalNoteSpedizione')?.addEventListener('show.bs.modal', function() {
    caricaNoteSpedizione();
});

// === Load presenti on sidebar button click ===
document.getElementById('modalPresenti')?.addEventListener('show.bs.modal', function() {
    caricaPresenti();
});

// === Readonly Mode ===
@if($isReadonly ?? false)
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[contenteditable]').forEach(function(el) {
        var onblur = el.getAttribute('onblur') || '';
        if (onblur.indexOf('data_prevista_consegna') !== -1) return;
        el.removeAttribute('contenteditable');
        el.removeAttribute('onblur');
        el.style.cursor = 'default';
    });
    document.querySelectorAll('form[action*="sync"], form[action*="import"], [data-bs-target="#aggiungiRigaModal"]').forEach(function(el) {
        el.style.display = 'none';
    });
    document.querySelectorAll('.btn-elimina-fase, [onclick*="eliminaFase"]').forEach(function(el) {
        el.style.display = 'none';
    });
});
@endif

// === Sticky Scrollbar ===
(function(){
    var ts = document.getElementById('tableScroll');
    var sb = document.getElementById('stickyScroll');
    var si = document.getElementById('stickyScrollInner');
    if(!ts||!sb) return;
    function sync(){ si.style.width = ts.scrollWidth+'px'; }
    sync();
    window.addEventListener('resize', sync);
    new MutationObserver(sync).observe(ts,{childList:true,subtree:true});
    var lock=false;
    sb.addEventListener('scroll',function(){ if(lock)return; lock=true; ts.scrollLeft=sb.scrollLeft; lock=false; });
    ts.addEventListener('scroll',function(){ if(lock)return; lock=true; sb.scrollLeft=ts.scrollLeft; lock=false; });
    function vis(){
        var r=ts.getBoundingClientRect();
        sb.style.display = r.bottom > window.innerHeight ? 'block':'none';
    }
    vis();
    window.addEventListener('scroll',vis);
    window.addEventListener('resize',vis);
})();

// === KPI Ore Lavorate click ===
document.getElementById('kpiOreLavorate')?.addEventListener('click', function() {
    // Scroll to report ore for detailed breakdown
    window.location.href = '{{ route("owner.reportOre") }}?op_token=' + encodeURIComponent(_opToken);
});
</script>
@endsection
