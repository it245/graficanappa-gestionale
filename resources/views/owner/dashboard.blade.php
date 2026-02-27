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

.action-icons {
    gap: 14px;
}
.action-icons img,
.action-icons svg,
.action-icons a,
.action-icons button,
.action-icons form {
    margin: 0 !important;
}
.action-icons img {
    height: 35px;
    cursor: pointer;
    transition: transform 0.15s ease;
}
.action-icons img:hover {
    transform: scale(1.15);
}

/* Hamburger */
.hamburger-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    display: flex;
    flex-direction: column;
    gap: 5px;
    transition: transform 0.15s ease;
}
.hamburger-btn:hover { transform: scale(1.1); }
.hamburger-btn span {
    display: block;
    width: 28px;
    height: 3px;
    background: #333;
    border-radius: 2px;
}

/* Sidebar */
.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.4);
    z-index: 9998;
}
.sidebar-overlay.open { display: block; }

.sidebar-menu {
    position: fixed;
    top: 0; left: -300px;
    width: 280px;
    height: 100%;
    background: #fff;
    z-index: 9999;
    box-shadow: 2px 0 12px rgba(0,0,0,0.2);
    transition: left 0.25s ease;
    overflow-y: auto;
    padding-top: 15px;
}
.sidebar-menu.open { left: 0; }

.sidebar-menu .sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 18px 15px;
    border-bottom: 1px solid #dee2e6;
    margin-bottom: 5px;
}
.sidebar-menu .sidebar-header h5 { margin: 0; font-size: 16px; font-weight: 700; }
.sidebar-close {
    background: none; border: none; font-size: 22px; cursor: pointer; color: #666;
}
.sidebar-close:hover { color: #000; }

.sidebar-menu .sidebar-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 18px;
    text-decoration: none;
    color: #333;
    font-size: 14px;
    font-weight: 500;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: background 0.15s;
}
.sidebar-menu .sidebar-item:hover {
    background: #f5f5f5;
    color: #000;
}
.sidebar-menu .sidebar-item img { height: 28px; width: 28px; object-fit: contain; }
.sidebar-menu .sidebar-item svg { width: 28px; height: 28px; flex-shrink: 0; }

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
   LARGHEZZA COLONNE (21 colonne)
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
    <div class="d-flex align-items-center justify-content-between mb-2 mx-2">
        <div style="display:flex; align-items:center; gap:10px;">
            <img src="{{ asset('images/logo_gn.png') }}" alt="Logo" style="height:40px;">
            <h2 class="mb-0">Dashboard Owner</h2>
        </div>
        <div class="operatore-info" id="operatoreInfo" style="position:relative; display:flex; align-items:center; gap:10px; cursor:pointer;">
            <img src="{{ asset('images/icons8-utente-uomo-cerchiato-50.png') }}" alt="Operatore" style="width:50px; height:50px; border-radius:50%;">
            <div class="operatore-popup" id="operatorePopup" style="position:absolute; top:60px; right:0; background:#fff; border:1px solid #ccc; padding:10px; border-radius:5px; box-shadow:0 2px 10px rgba(0,0,0,0.2); display:none; z-index:1000; min-width:200px;">
                <div><strong>{{ $operatore->nome ?? '' }} {{ $operatore->cognome ?? '' }}</strong></div>
                <div><p class="mb-1">Ruolo: <strong>Owner</strong></p></div>
                <form action="{{ route('operatore.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm mt-2">Logout</button>
                </form>
            </div>
        </div>
    </div>

    {{-- KPI GIORNALIERI --}}
    <div class="d-flex gap-2 mb-2 mx-0" style="max-width:920px;">
        <a href="{{ route('owner.fasiTerminate', ['oggi' => 1]) }}" class="d-flex align-items-center p-2 rounded flex-fill text-decoration-none" style="background:#d1e7dd; height:56px; min-width:200px; cursor:pointer;" title="Visualizza fasi completate oggi">
            <div>
                <div style="font-size:11px; color:#555; line-height:1.2;">Fasi completate oggi</div>
                <div style="font-size:22px; font-weight:700; color:#198754; line-height:1;">{{ $fasiCompletateOggi }}</div>
            </div>
        </a>
        <div class="d-flex align-items-center p-2 rounded flex-fill" style="background:#cfe2ff; height:56px; min-width:200px;">
            <div>
                <div style="font-size:11px; color:#555; line-height:1.2;">Ore lavorate oggi</div>
                <div style="font-size:22px; font-weight:700; color:#0d6efd; line-height:1;">{{ $oreLavorateOggi }}h</div>
            </div>
        </div>
        <div class="d-flex align-items-center p-2 rounded flex-fill" style="background:#d5d5d5; height:56px; min-width:200px;">
            <div>
                <div style="font-size:11px; color:#555; line-height:1.2;">Consegnate oggi</div>
                <div style="font-size:22px; font-weight:700; color:#333; line-height:1;">{{ $commesseSpediteOggi }}</div>
            </div>
        </div>
        <div class="d-flex align-items-center p-2 rounded flex-fill" style="background:#fff3cd; height:56px; min-width:200px;">
            <div>
                <div style="font-size:11px; color:#555; line-height:1.2;">Fasi in lavorazione</div>
                <div style="font-size:22px; font-weight:700; color:#e67e22; line-height:1;">{{ $fasiAttive }}</div>
            </div>
        </div>
    </div>

    {{-- ICONE AZIONI --}}
    <div class="mb-3 d-flex align-items-center action-icons">

        {{-- HAMBURGER --}}
        <button class="hamburger-btn" id="hamburgerBtn" title="Menu">
            <span></span><span></span><span></span>
        </button>

        {{-- ICONA FILTRO --}}
        <img
            src="{{ asset('images/icons8-filtro-50.png') }}"
            id="toggleFilter"
            title="Mostra / Nascondi filtri"
            alt="Filtri"
        >

        {{-- Stampa celle selezionate --}}
        <button id="printButton" class="btn p-0" style="background:none; border:none;" title="Stampa celle selezionate">
            <img src="{{ asset('images/printer.png') }}" alt="Stampa">
        </button>
    </div>

    {{-- SIDEBAR OVERLAY --}}
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    {{-- SIDEBAR MENU --}}
    <div class="sidebar-menu" id="sidebarMenu">
        <div class="sidebar-header">
            <h5>Menu</h5>
            <button class="sidebar-close" id="sidebarClose">&times;</button>
        </div>

        {{-- Visualizza fasi terminate --}}
        <a href="{{ route('owner.fasiTerminate') }}" class="sidebar-item">
            <img src="{{ asset('images/out-of-the-box.png') }}" alt="">
            <span>Fasi terminate</span>
        </a>

        {{-- Scheduling Produzione --}}
        <a href="{{ route('owner.scheduling') }}" class="sidebar-item">
            <img src="{{ asset('images/icons8-report-grafico-a-torta-50.png') }}" alt="">
            <span>Scheduling Produzione</span>
        </a>

        {{-- Lavorazioni Esterne --}}
        <a href="{{ route('owner.esterne') }}" class="sidebar-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#17a2b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="7.5 4.21 12 6.81 16.5 4.21"/><polyline points="7.5 19.79 7.5 14.6 3 12"/><polyline points="21 12 16.5 14.6 16.5 19.79"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>
            </svg>
            <span>Lavorazioni Esterne</span>
        </a>

        {{-- Prinect Live --}}
        <a href="{{ route('mes.prinect') }}" class="sidebar-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="6" y="2" width="12" height="6" rx="1"/><rect x="2" y="8" width="20" height="8" rx="1"/><rect x="6" y="16" width="12" height="6" rx="1"/><line x1="6" y1="12" x2="2" y2="12"/><line x1="22" y1="12" x2="18" y2="12"/>
            </svg>
            <span>Prinect Live (Offset)</span>
        </a>

        {{-- Fiery V900 --}}
        <a href="{{ route('mes.fiery') }}" class="sidebar-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#e65100" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="6" width="20" height="12" rx="2"/><line x1="6" y1="10" x2="6" y2="14"/><line x1="10" y1="10" x2="10" y2="14"/><line x1="14" y1="10" x2="14" y2="14"/><line x1="18" y1="10" x2="18" y2="14"/><line x1="2" y1="20" x2="22" y2="20"/>
            </svg>
            <span>Fiery V900 (Digitale)</span>
        </a>

        {{-- Apri Excel --}}
        <a href="#" class="sidebar-item" onclick="alert('Apri da Esplora Risorse:\n\n\\\\gestionale\\mes\\dashboard_mes.xlsx'); closeSidebar(); return false;">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#198754" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/><polyline points="10 9 9 9 8 9"/>
            </svg>
            <span>Apri Excel Dashboard</span>
        </a>

        {{-- Aggiungi riga --}}
        <a href="#" class="sidebar-item" data-bs-toggle="modal" data-bs-target="#aggiungiRigaModal" onclick="closeSidebar()">
            <img src="{{ asset('images/icons8-ddt-64 (1).png') }}" alt="">
            <span>Aggiungi riga</span>
        </a>

        {{-- Consegnati oggi --}}
        <a href="#" class="sidebar-item" data-bs-toggle="modal" data-bs-target="#modalSpedizioniOggi" onclick="closeSidebar()" id="btnConsegnati">
            <img src="{{ asset('images/icons8-consegnato-50.png') }}" alt="">
            <span>Consegnati oggi
                @if($spedizioniOggi->count() > 0)
                    <span class="badge rounded-pill bg-danger" style="font-size:11px; vertical-align:middle;">{{ $spedizioniOggi->count() }}</span>
                @endif
            </span>
        </a>

        {{-- Spedizioni BRT --}}
        <a href="#" class="sidebar-item" data-bs-toggle="modal" data-bs-target="#modalBRT" onclick="closeSidebar()">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#d4380d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
            </svg>
            <span>Spedizioni BRT
                @if($spedizioniBRT->count() > 0)
                    <span class="badge rounded-pill bg-danger" style="font-size:11px; vertical-align:middle;">{{ $spedizioniBRT->count() }}</span>
                @endif
            </span>
        </a>

        {{-- Sync Onda --}}
        <form method="POST" action="{{ route('owner.syncOnda') }}" style="margin:0;" onsubmit="this.querySelector('button').disabled=true;">
            @csrf
            <button type="submit" class="sidebar-item" style="width:100%; background:none; border:none; border-bottom:1px solid #f0f0f0; text-align:left; font-size:14px; font-weight:500; color:#333;">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21.5 2v6h-6"/><path d="M2.5 22v-6h6"/><path d="M2.5 11.5a10 10 0 0 1 18.8-4.3"/><path d="M21.5 12.5a10 10 0 0 1-18.8 4.2"/>
                </svg>
                <span>Sincronizza Onda</span>
            </button>
        </form>
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
                    $statiLabel = [0 => 'Caricato', 1 => 'Pronto', 2 => 'Avviato', 3 => 'Terminato', 4 => 'Consegnato'];
                    $statoBg = [0 => '#e9ecef', 1 => '#cfe2ff', 2 => '#fff3cd', 3 => '#d1e7dd', 4 => '#c3c3c3'];
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
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'fase', this.innerText)">{{ $fase->faseCatalogo->nome_display ?? '-' }}</td>
                    <td>{{ $fase->faseCatalogo->reparto->nome ?? '-' }}</td>
                    <td>
                        @forelse($fase->operatori as $op)
                            {{ $op->nome }}<br>
                        @empty
                            -
                        @endforelse
                    </td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'qta_prod', this.innerText)">{{ $fase->qta_prod ?? '-' }}</td>
                    @php
                        $descOwner = $fase->ordine->descrizione ?? '';
                        $clienteOwner = $fase->ordine->cliente_nome ?? '';
                        $repartoOwner = strtolower($fase->faseCatalogo->reparto->nome ?? '');
                        $noteExtra = '';
                        if (in_array($repartoOwner, ['stampa offset', 'digitale'])) {
                            $coloriOwner = \App\Helpers\DescrizioneParser::parseColori($descOwner, $clienteOwner, $repartoOwner);
                            if ($coloriOwner) $noteExtra .= '[COL: '.$coloriOwner.'] ';
                        }
                        if (str_contains($repartoOwner, 'fustella')) {
                            $fustellaOwner = \App\Helpers\DescrizioneParser::parseFustella($descOwner);
                            if ($fustellaOwner) $noteExtra .= '[FS: '.$fustellaOwner.'] ';
                        }
                    @endphp
                    <td>
                        @if($noteExtra)<small class="fw-bold">{{ $noteExtra }}</small><br>@endif<span contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'note', this.innerText)">{{ $fase->note ?? '-' }}</span>
                    </td>
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
            <div class="modal-header" style="background:#d4380d; color:#fff; padding:18px 24px;">
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
                    <thead style="background:#d4380d; color:#fff;">
                        <tr>
                            <th style="padding:12px 14px;">DDT</th>
                            <th style="padding:12px 14px;">Commesse</th>
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
                        @endphp
                        <tr id="brt_row_{{ md5($numDDT) }}">
                            <td class="fw-bold" style="padding:10px 14px; font-size:16px;">{{ ltrim($numDDT, '0') }}</td>
                            <td style="padding:10px 14px;">{{ $commesse }}</td>
                            <td style="padding:10px 14px;">{{ $primo->cliente_nome ?? '-' }}</td>
                            <td id="brt_stato_{{ md5($numDDT) }}" style="padding:10px 14px;">
                                <span class="badge bg-light text-muted" style="font-size:13px; padding:6px 10px;">Da verificare</span>
                            </td>
                            <td id="brt_data_{{ md5($numDDT) }}" style="padding:10px 14px;">-</td>
                            <td id="brt_dest_{{ md5($numDDT) }}" style="padding:10px 14px;">-</td>
                            <td id="brt_colli_{{ md5($numDDT) }}" style="padding:10px 14px;">-</td>
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

<!-- Modal Tracking BRT Dettagli -->
<div class="modal fade" id="modalTracking" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:#d4380d; color:#fff;">
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

<script>
// Sidebar menu
function openSidebar() {
    document.getElementById('sidebarMenu').classList.add('open');
    document.getElementById('sidebarOverlay').classList.add('open');
}
function closeSidebar() {
    document.getElementById('sidebarMenu').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('open');
}
document.getElementById('hamburgerBtn').addEventListener('click', openSidebar);
document.getElementById('sidebarOverlay').addEventListener('click', closeSidebar);
document.getElementById('sidebarClose').addEventListener('click', closeSidebar);
document.querySelectorAll('.sidebar-menu a.sidebar-item').forEach(function(el) {
    el.addEventListener('click', function() { setTimeout(closeSidebar, 100); });
});

// === BRT Tracking ===
function getBrtHdrs() {
    return {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
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
    fetch('{{ route("owner.trackingByDDT") }}', {
        method: 'POST', headers: getBrtHdrs(),
        body: JSON.stringify({ numero_ddt: numeroDDT })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        var statoEl = document.getElementById('brt_stato_' + hash);
        var dataEl = document.getElementById('brt_data_' + hash);
        var destEl = document.getElementById('brt_dest_' + hash);
        var colliEl = document.getElementById('brt_colli_' + hash);

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

    fetch('{{ route("owner.trackingByDDT") }}', {
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
document.getElementById('modalBRT').addEventListener('shown.bs.modal', function() {
    if (!brtModalCaricato && brtDDTList.length > 0) {
        brtModalCaricato = true;
        caricaTuttiTrackingBRT();
    }
});
</script>

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
    fetch('{{ route("owner.aggiornaStato") }}', {
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

// Popup operatore
document.getElementById('operatoreInfo').addEventListener('click', function(){
    var popup = document.getElementById('operatorePopup');
    popup.style.display = popup.style.display === 'block' ? 'none' : 'block';
});
document.addEventListener('click', function(e){
    if(!document.getElementById('operatoreInfo').contains(e.target)){
        document.getElementById('operatorePopup').style.display='none';
    }
});
</script>
@endsection
