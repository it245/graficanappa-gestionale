@extends('layouts.app')

@section('viewport')
{{-- Nessun viewport: il browser mobile usa il default ~980px, la pagina è scrollabile --}}
@endsection

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
    touch-action: manipulation;
}
.action-icons a, .action-icons button, .sidebar-menu a {
    touch-action: manipulation;
}
.action-icons img:hover {
    transform: scale(1.15);
}

/* Hamburger */
.hamburger-btn {
    background: none;
    border: none;
    cursor: pointer;
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 5px;
    transition: transform 0.15s ease;
    z-index: 100;
    position: relative;
    min-width: 44px;
    min-height: 44px;
    align-items: center;
    justify-content: center;
    touch-action: manipulation;
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
    top: 0; left: 0;
    width: 280px;
    height: 100%;
    background: #fff;
    z-index: 9999;
    box-shadow: 2px 0 12px rgba(0,0,0,0.2);
    transform: translateX(-300px);
    transition: transform 0.2s ease;
    overflow-y: auto;
    padding-top: 15px;
    will-change: transform;
}
.sidebar-menu.open { transform: translateX(0); }

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
    width: 2970px;
    max-width: 2970px;
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

/* Cella cliccata: mostra testo intero */
td:focus, td[contenteditable]:focus {
    white-space: normal !important;
    overflow: visible !important;
    text-overflow: clip !important;
    max-height: none !important;
    position: relative;
    z-index: 10;
    background: #fffde7 !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

thead th {
    background: #000000;
    color: #ffffff;
    font-size: 11.5px;
}

/* =========================
   LARGHEZZA COLONNE (27 colonne) — ordine attuale:
   1=Commessa 2=Stato 3=Cliente 4=CodArt 5=Colori 6=Fustella
   7=Descrizione 8=Qta 9=UM 10=Priorità 11=Fase 12=Reparto
   13=Carta 14=QtaCarta 15=DataConsegna 16=CodCarta
   17=UMCarta 18=Operatori 19=QtaProd
   20=Esterno 21=Note 22=DataInizio 23=DataFine 24=OrePrev 25=OreLav 26=DataReg 27=Progresso
   ========================= */

/* 1. Commessa */
th:nth-child(1), td:nth-child(1) { width: 100px; }

/* 2. Stato */
th:nth-child(2), td:nth-child(2) { width: 50px; text-align: center; }

/* 3. Cliente */
th:nth-child(3), td:nth-child(3) { width: 170px; white-space: normal; }

/* 4. Codice Articolo */
th:nth-child(4), td:nth-child(4) { width: 95px; }

/* 5. Colori */
th:nth-child(5), td:nth-child(5) { width: 180px; white-space: normal; }

/* 6. Fustella */
th:nth-child(6), td:nth-child(6) { width: 75px; }

/* 7. Descrizione — troncata con ... */
th:nth-child(7), td:nth-child(7) { width: 250px; max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

/* 8. Qta */
th:nth-child(8), td:nth-child(8) { width: 55px; text-align: center; }

/* 9. UM */
th:nth-child(9), td:nth-child(9) { width: 40px; text-align: center; }

/* 10. Priorità */
th:nth-child(10), td:nth-child(10) { width: 65px; text-align: center; }

/* 11. Fase */
th:nth-child(11), td:nth-child(11) { width: 125px; }

/* 12. Reparto */
th:nth-child(12), td:nth-child(12) { width: 110px; }

/* 13. Carta */
th:nth-child(13), td:nth-child(13) { width: 190px; white-space: normal; }

/* 14. Qta Carta */
th:nth-child(14), td:nth-child(14) { width: 50px; text-align: center; }

/* 15. Data Prevista Consegna */
th:nth-child(15), td:nth-child(15) { width: 100px; }

/* 16. Cod Carta */
th:nth-child(16), td:nth-child(16) { width: 170px; white-space: normal; }

/* 17. UM Carta */
th:nth-child(17), td:nth-child(17) { width: 30px; text-align: center; font-size: 11px; }

/* 18. Operatori */
th:nth-child(18), td:nth-child(18) {
    width: 110px;
    white-space: normal;
    font-size: 11px;
}

/* 19. Qta Prod. */
th:nth-child(19), td:nth-child(19) {
    width: 60px;
    text-align: center;
}

/* 20. Esterno */
th:nth-child(20), td:nth-child(20) { width: 90px; }

/* 21. Note */
th:nth-child(21), td:nth-child(21) {
    width: 170px;
    white-space: normal;
}

/* 22. Data Inizio / 23. Data Fine */
th:nth-child(22), td:nth-child(22),
th:nth-child(23), td:nth-child(23) {
    width: 110px;
}

/* 24. Ore Prev. */
th:nth-child(24), td:nth-child(24) { width: 70px; text-align: center; }

/* 25. Ore Lav. */
th:nth-child(25), td:nth-child(25) { width: 70px; text-align: center; }

/* 26. Data Reg. */
th:nth-child(26), td:nth-child(26) { width: 100px; }

/* 27. Progresso */
th:nth-child(27), td:nth-child(27) { width: 100px; }

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
            <div class="operatore-info" id="operatoreInfo" style="position:relative; display:flex; align-items:center; gap:10px; cursor:pointer;">
                <img src="{{ asset('images/icons8-utente-uomo-cerchiato-50.png') }}" alt="Operatore" style="width:40px; height:40px; border-radius:50%;">
                <div class="operatore-popup" id="operatorePopup" style="position:absolute; top:50px; left:0; background:#fff; border:1px solid #ccc; padding:10px; border-radius:5px; box-shadow:0 2px 10px rgba(0,0,0,0.2); display:none; z-index:1000; min-width:200px;">
                    <div><strong>{{ $operatore->nome ?? '' }} {{ $operatore->cognome ?? '' }}</strong></div>
                    <div><p class="mb-1">Ruolo: <strong>Owner</strong></p></div>
                    <a href="{{ route('operatore.logout') }}" class="btn btn-secondary btn-sm mt-2">Logout</a>
                </div>
            </div>
        </div>
        {{-- LEGENDA --}}
        <div style="background:#fff; border:1px solid #dee2e6; border-radius:8px; padding:8px 14px; box-shadow:0 1px 4px rgba(0,0,0,0.08);">
            <div class="d-flex gap-4" style="font-size:11px;">
                <div>
                    <div style="font-weight:700; font-size:10px; color:#666; text-transform:uppercase; margin-bottom:4px;">Stati Fase</div>
                    <div class="d-flex flex-column gap-1">
                        <div class="d-flex align-items-center gap-1"><span style="display:inline-block;width:12px;height:12px;background:#e9ecef;border:1px solid #ccc;border-radius:2px;"></span> 0 Caricato</div>
                        <div class="d-flex align-items-center gap-1"><span style="display:inline-block;width:12px;height:12px;background:#cfe2ff;border:1px solid #9ec5fe;border-radius:2px;"></span> 1 Pronto</div>
                        <div class="d-flex align-items-center gap-1"><span style="display:inline-block;width:12px;height:12px;background:#fff3cd;border:1px solid #ffc107;border-radius:2px;"></span> 2 Avviato</div>
                        <div class="d-flex align-items-center gap-1"><span style="display:inline-block;width:12px;height:12px;background:#d1e7dd;border:1px solid #198754;border-radius:2px;"></span> 3 Terminato</div>
                        <div class="d-flex align-items-center gap-1"><span style="display:inline-block;width:12px;height:12px;background:#c3c3c3;border:1px solid #999;border-radius:2px;"></span> 4 Consegnato</div>
                        <div class="d-flex align-items-center gap-1"><span style="display:inline-block;width:12px;height:12px;background:#ede9fe;border:1px solid #7c3aed;border-radius:2px;"></span> EXT Da inviare</div>
                        <div class="d-flex align-items-center gap-1"><span style="display:inline-block;width:12px;height:12px;background:#d1fae5;border:1px solid #065f46;border-radius:2px;"></span> EXT Inviato</div>
                    </div>
                </div>
                <div style="border-left:1px solid #dee2e6; padding-left:12px;">
                    <div style="font-weight:700; font-size:10px; color:#666; text-transform:uppercase; margin-bottom:4px;">Barra Progresso</div>
                    <div class="d-flex flex-column gap-1">
                        <div class="d-flex align-items-center gap-1"><span style="display:inline-block;width:12px;height:12px;background:#0d6efd;border-radius:2px;"></span> Completate</div>
                        <div class="d-flex align-items-center gap-1"><span style="display:inline-block;width:12px;height:12px;background:#fd7e14;border-radius:2px;"></span> In corso</div>
                        <div class="d-flex align-items-center gap-1"><span style="display:inline-block;width:12px;height:12px;background:#198754;border-radius:2px;"></span> Tutte completate</div>
                    </div>
                </div>
                <div style="border-left:1px solid #dee2e6; padding-left:12px;">
                    <div style="font-weight:700; font-size:10px; color:#666; text-transform:uppercase; margin-bottom:4px;">Percorso Produttivo</div>
                    <div class="d-flex flex-column gap-1">
                        <div class="d-flex align-items-center gap-1"><span style="display:inline-block;width:12px;height:12px;background:#d4edda;border:1px solid #198754;border-radius:2px;"></span> Base (no caldo, no rilievi)</div>
                        <div class="d-flex align-items-center gap-1"><span style="display:inline-block;width:12px;height:12px;background:#fff3cd;border:1px solid #ffc107;border-radius:2px;"></span> Rilievi (no caldo)</div>
                        <div class="d-flex align-items-center gap-1"><span style="display:inline-block;width:12px;height:12px;background:#f96f2a;border:1px solid #e65c00;border-radius:2px;"></span> Caldo (no rilievi)</div>
                        <div class="d-flex align-items-center gap-1"><span style="display:inline-block;width:12px;height:12px;background:#f8d7da;border:1px solid #dc3545;border-radius:2px;"></span> Completo (caldo + rilievi)</div>
                    </div>
                </div>
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
        <div class="d-flex align-items-center p-2 rounded flex-fill" style="background:#cfe2ff; height:56px; min-width:200px; cursor:pointer;" data-bs-toggle="modal" data-bs-target="#modalOreOggi">
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
        <div class="d-flex align-items-center p-2 rounded flex-fill" style="background:#ede9fe; height:56px; min-width:200px; cursor:pointer;" data-bs-toggle="modal" data-bs-target="#modalRiempimento">
            <div>
                <div style="font-size:11px; color:#555; line-height:1.2;">Riempimento macchine</div>
                <div style="font-size:22px; font-weight:700; color:#7c3aed; line-height:1;">{{ round(collect($riempimento)->sum('ore_totali')) }}h</div>
            </div>
        </div>
    </div>

    {{-- ICONE AZIONI --}}
    <div class="mb-3 d-flex align-items-center action-icons">

        {{-- HAMBURGER --}}
        <button class="hamburger-btn" id="hamburgerBtn" title="Menu" tabindex="1">
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

        {{-- Fustelle --}}
        <a href="{{ route('owner.fustelle', ['op_token' => $opToken ?? request()->query('op_token')]) }}" class="sidebar-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#6f42c1" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/><path d="M9 3v18"/>
            </svg>
            <span>Fustelle</span>
        </a>

        {{-- Panoramica Reparti --}}
        <a href="{{ route('owner.repartiOverview', ['op_token' => $opToken ?? request()->query('op_token')]) }}" class="sidebar-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
            </svg>
            <span>Panoramica Reparti</span>
        </a>

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

        {{-- Report Ore --}}
        <a href="{{ route('owner.reportOre', ['op_token' => $opToken ?? request()->query('op_token')]) }}" class="sidebar-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0d6efd" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
            <span>Report Ore</span>
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
        @if(!($isReadonly ?? false))
        <a href="#" class="sidebar-item" onclick="alert('Apri da Esplora Risorse:\n\n\\\\gestionale\\mes\\dashboard_mes.xlsx'); closeSidebar(); return false;">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#198754" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/><polyline points="10 9 9 9 8 9"/>
            </svg>
            <span>Apri Excel Dashboard</span>
        </a>

        {{-- TV Kiosk --}}
        <a href="/kiosk" target="_blank" class="sidebar-item" onclick="closeSidebar();">
            <span style="font-size:20px;">📺</span>
            <span>TV Kiosk</span>
        </a>
        @endif

        {{-- Aggiungi riga --}}
        @if(!($isReadonly ?? false))
        <a href="#" class="sidebar-item" data-bs-toggle="modal" data-bs-target="#aggiungiRigaModal" onclick="closeSidebar()">
            <img src="{{ asset('images/icons8-ddt-64 (1).png') }}" alt="">
            <span>Aggiungi riga</span>
        </a>
        @endif

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

        {{-- Storico consegne --}}
        <a href="#" class="sidebar-item" data-bs-toggle="modal" data-bs-target="#modalStorico" onclick="closeSidebar()">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#6c757d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
            <span>Storico consegne
                @if($storicoConsegne->count() > 0)
                    <span class="badge rounded-pill bg-secondary" style="font-size:11px; vertical-align:middle;">{{ $storicoConsegne->count() }}</span>
                @endif
            </span>
        </a>

        {{-- Note Consegne --}}
        <a href="#" class="sidebar-item" data-bs-toggle="modal" data-bs-target="#modalNoteSpedizione" onclick="closeSidebar(); caricaNoteSpedizione();">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0d6efd" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
            </svg>
            <span>Note Consegne</span>
            <span id="noteConsegneBadge" style="display:none; background:#dc3545; color:#fff; border-radius:50%; width:20px; height:20px; font-size:11px; font-weight:bold; text-align:center; line-height:20px; margin-left:6px;">!</span>
        </a>

        {{-- Presenti in azienda (modal rapido) --}}
        <a href="#" class="sidebar-item" data-bs-toggle="modal" data-bs-target="#modalPresenti" onclick="closeSidebar(); caricaPresenti();">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#198754" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            <span>Presenti in azienda</span>
            <span id="presentiCount" class="badge rounded-pill bg-success" style="font-size:11px; vertical-align:middle;"></span>
        </a>

        {{-- Presenze (pagina completa con storico) --}}
        <a href="{{ route('owner.presenze') }}" class="sidebar-item" onclick="closeSidebar();">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>
            </svg>
            <span>Storico Presenze</span>
        </a>

        {{-- Riferimenti Marco (solo readonly) --}}
        @if($isReadonly ?? false)
        <a href="#" class="sidebar-item" onclick="filtraRiferimentiMarco(); closeSidebar(); return false;">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#198754" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            <span>Riferimenti Marco</span>
        </a>
        <a href="#" class="sidebar-item" onclick="resetRiferimentiMarco(); closeSidebar(); return false;">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#6c757d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
            <span>Mostra tutte le commesse</span>
        </a>
        @endif

        {{-- Sync Onda --}}
        @if(!($isReadonly ?? false))
        <form method="POST" action="{{ route('owner.syncOnda') }}" style="margin:0;" onsubmit="this.querySelector('button').disabled=true;">
            @csrf
            <button type="submit" class="sidebar-item" style="width:100%; background:none; border:none; border-bottom:1px solid #f0f0f0; text-align:left; font-size:14px; font-weight:500; color:#333;">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21.5 2v6h-6"/><path d="M2.5 22v-6h6"/><path d="M2.5 11.5a10 10 0 0 1 18.8-4.3"/><path d="M21.5 12.5a10 10 0 0 1-18.8 4.2"/>
                </svg>
                <span>Sincronizza Onda</span>
            </button>
        </form>
        @endif

        {{-- NOTA TV --}}
        <button type="button" class="sidebar-item" onclick="closeSidebar(); setTimeout(function(){ new bootstrap.Modal(document.getElementById('modalNotaTv')).show(); }, 300);" style="width:100%; background:none; border:none; border-bottom:1px solid #f0f0f0; text-align:left; font-size:14px; font-weight:500; color:#333; display:flex; align-items:center; gap:12px; padding:12px 18px; cursor:pointer;">
            <span style="font-size:20px;">📢</span>
            <span>Nota TV (ticker)</span>
        </button>
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
    <option value="EXT">EXT</option>
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
</div>

    {{-- MODALE AGGIUNGI RIGA --}}
    <div class="modal fade" id="aggiungiRigaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('owner.aggiungiRiga') }}{{ request('op_token') ? '?op_token='.request('op_token') : '' }}">
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
    <div id="tableScroll" style="width:100%; overflow-x:auto;">
        <table id="tabellaOrdini" class="table table-bordered table-sm table-striped" style="white-space:nowrap;">
            <thead class="table-dark">
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
                    <th>Priorità</th>
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
                @if(!$fase->ordine) @continue @endif
                @php
                    $rowClass = $fase->ordine->getPercorsoClass();
                @endphp
                @php
                    $statiLabel = [0 => 'Caricato', 1 => 'Pronto', 2 => 'Avviato', 3 => 'Terminato', 4 => 'Consegnato'];
                    $statoBg = [0 => '#e9ecef', 1 => '#cfe2ff', 2 => '#fff3cd', 3 => '#d1e7dd', 4 => '#c3c3c3', 5 => '#e0cffc'];
                @endphp
                <tr class="{{ $rowClass }}" data-id="{{ $fase->id }}">
                    <td><a href="{{ route('owner.dettaglioCommessa', $fase->ordine->commessa ?? '-') }}" style="color:#000;font-weight:bold;text-decoration:underline;">{{ $fase->ordine->commessa ?? '-' }}</a></td>
                    @if($fase->esterno && ((int)$fase->stato === 5 || $fase->stato < 3) && ($fase->ddt_fornitore_id || (int)$fase->stato === 5))
                    <td contenteditable onblur="aggiornaStato({{ $fase->id }}, this.innerText)" style="background:#d1fae5 !important;font-weight:bold;text-align:center;color:#065f46;font-size:10px;" title="Inviato al fornitore">EXT</td>
                    @elseif($fase->esterno && ((int)$fase->stato === 5 || $fase->stato < 3))
                    <td contenteditable onblur="aggiornaStato({{ $fase->id }}, this.innerText)" style="background:#ede9fe !important;font-weight:bold;text-align:center;color:#7c3aed;font-size:10px;" title="Esterno - da inviare">EXT</td>
                    @else
                    <td contenteditable onblur="aggiornaStato({{ $fase->id }}, this.innerText)" style="background:{{ $statoBg[$fase->stato] ?? '#e9ecef' }} !important;font-weight:bold;text-align:center;">{{ $fase->stato }}</td>
                    @endif
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'cliente_nome', this.innerText)">{{ $fase->ordine->cliente_nome ?? '-' }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'cod_art', this.innerText)">{{ $fase->ordine->cod_art ?? '-' }}</td>
                    @php
                        $descOwner = $fase->ordine->descrizione ?? '';
                        $clienteOwner = $fase->ordine->cliente_nome ?? '';
                        $repartoOwner = strtolower($fase->faseCatalogo->reparto->nome ?? '');
                        $coloriOwner = \App\Helpers\DescrizioneParser::parseColori($descOwner, $clienteOwner, $repartoOwner);
                        $fustellaOwner = \App\Helpers\DescrizioneParser::parseFustella($descOwner, $clienteOwner, $fase->ordine->note_prestampa ?? '');
                    @endphp
                    <td>{{ $coloriOwner ?: '-' }}{{ str_contains(strtolower($clienteOwner), 'tifata') ? ' - IML' : '' }}</td>
                    <td>{{ $fustellaOwner ?: '-' }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'descrizione', this.innerText)">{{ $fase->ordine->descrizione ?? '-' }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'qta_richiesta', this.innerText)">{{ $fase->ordine->qta_richiesta ?? '-' }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'um', this.innerText)">{{ $fase->ordine->um ?? '-' }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'priorita', this.innerText)">{{ $fase->priorita !== null ? number_format($fase->priorita, 2) : '-' }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'fase', this.innerText)">{{ $fase->faseCatalogo->nome_display ?? '-' }}</td>
                    <td>{{ $fase->faseCatalogo->reparto->nome ?? '-' }}</td>
                    @php
                        $cartaDesc = $fase->ordine->carta ?? '-';
                        // Se formato supporto reale disponibile, sostituisci le dimensioni nella descrizione
                        if (($fase->ordine->supp_base_cm ?? 0) > 0 && ($fase->ordine->supp_altezza_cm ?? 0) > 0) {
                            $formatoReale = intval($fase->ordine->supp_base_cm) . ' X ' . intval($fase->ordine->supp_altezza_cm);
                            // Sostituisci dimensioni tipo "64 X 100" o "64X100" con formato reale
                            $cartaDesc = preg_replace('/\d+\s*[Xx]\s*\d+/', $formatoReale, $cartaDesc, 1);
                        }
                    @endphp
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'carta', this.innerText)">{{ $cartaDesc }}</td>
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
                            @if($fonteTempo)<small class="text-muted">{{ $fonteTempo }}</small>@endif
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
                        <div style="position:relative; background:#e9ecef; border-radius:4px; height:16px; min-width:60px;" title="{{ $prog['terminate'] }}/{{ $prog['totale'] }} terminate{{ $prog['avviate'] > 0 ? ', '.$prog['avviate'].' in corso' : '' }}">
                            @if($prog['avviate'] > 0 && $progPerc < 100)
                            <div style="position:absolute; top:0; left:0; height:100%; width:{{ min($progPerc + $progAvv, 100) }}%; background:#fd7e14; border-radius:4px;"></div>
                            @endif
                            <div style="position:absolute; top:0; left:0; height:100%; width:{{ $progPerc }}%; background:{{ $progPerc >= 100 ? '#198754' : '#0d6efd' }}; border-radius:4px;"></div>
                            <span style="position:relative; z-index:1; font-size:10px; font-weight:bold; color:{{ $progPerc >= 40 ? '#fff' : '#333' }}; line-height:16px; padding-left:4px;">{{ $prog['terminate'] }}/{{ $prog['totale'] }}</span>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

</div>
{{-- MODALE ORE LAVORATE OGGI PER OPERATORE --}}
<div class="modal fade" id="modalOreOggi" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Ore Lavorate Oggi — Dettaglio per Operatore ({{ $oreLavorateOggi }}h)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:16px 20px;">
                <table class="table table-sm table-bordered table-striped" style="font-size:13px;">
                    <thead class="table-dark">
                        <tr>
                            <th>Operatore</th>
                            <th style="text-align:center;">Fasi</th>
                            <th style="text-align:center;">Ore Nette</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($orePerOperatoreOggi as $op)
                        <tr>
                            <td><strong>{{ $op->nome }} {{ $op->cognome }}</strong></td>
                            <td style="text-align:center;">{{ $op->fasi_count }}</td>
                            <td style="text-align:center; font-weight:700; color:#0d6efd;">{{ $op->ore_nette }}h</td>
                        </tr>
                        @endforeach
                        @if($orePerOperatoreOggi->isEmpty())
                        <tr><td colspan="3" class="text-center text-muted">Nessun dato</td></tr>
                        @endif
                    </tbody>
                    <tfoot>
                        <tr style="font-weight:700; background:#e8f4fd;">
                            <td>TOTALE</td>
                            <td style="text-align:center;">{{ $orePerOperatoreOggi->sum('fasi_count') }}</td>
                            <td style="text-align:center; color:#0d6efd;">{{ $orePerOperatoreOggi->sum('ore_nette') }}h</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
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
            <div class="modal-header" style="background:#6c757d; color:#fff;">
                <h5 class="modal-title">Storico consegne (ultimi 30 giorni)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="overflow-x:auto;">
                <div class="mb-3">
                    <input type="text" id="filtro-storico" class="form-control" placeholder="Cerca per commessa, cliente, descrizione..." autocomplete="off">
                </div>
                @if($storicoConsegne->count() > 0)
                @foreach($storicoConsegne->groupBy(fn($f) => \Carbon\Carbon::parse($f->data_fine)->format('Y-m-d')) as $dataStorico => $fasiGiorno)
                <h6 class="mt-3 mb-2 fw-bold" style="color:#333;">
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
function salvaNotaTv() {
    var nota = document.getElementById('notaTvInput').value.trim();
    fetch(urlToken('/kiosk/nota'), {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': csrfToken(), 'Content-Type': 'application/json'},
        body: JSON.stringify({nota: nota})
    }).then(r => r.json()).then(d => {
        if (d.success) {
            var btn = document.querySelector('#modalNotaTv .btn-danger');
            btn.textContent = '✓ Salvata!';
            btn.classList.replace('btn-danger', 'btn-success');
            setTimeout(function() { bootstrap.Modal.getInstance(document.getElementById('modalNotaTv')).hide(); btn.textContent = 'Salva e pubblica'; btn.classList.replace('btn-success', 'btn-danger'); }, 1000);
        } else { alert('Errore'); }
    });
}
// Carica nota TV corrente
fetch(urlToken('/kiosk/nota')).then(r => r.json()).then(d => {
    if (d.nota) document.getElementById('notaTvInput').value = d.nota;
});

function openSidebar() {
    document.getElementById('sidebarMenu').classList.add('open');
    document.getElementById('sidebarOverlay').classList.add('open');
}
function closeSidebar() {
    document.getElementById('sidebarMenu').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('open');
}

// Filtro storico consegne
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
document.getElementById('hamburgerBtn').addEventListener('click', openSidebar);
document.getElementById('hamburgerBtn').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' || e.keyCode === 13) { e.preventDefault(); openSidebar(); }
});
document.getElementById('sidebarOverlay').addEventListener('click', closeSidebar);
document.getElementById('sidebarClose').addEventListener('click', closeSidebar);
document.querySelectorAll('.sidebar-menu a.sidebar-item').forEach(function(el) {
    el.addEventListener('click', function() { setTimeout(closeSidebar, 100); });
    el.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.keyCode === 13) { e.preventDefault(); this.click(); }
    });
});

// === Token per fetch autenticate ===
var _opToken = '{{ $opToken ?? request()->query("op_token", "") }}';
function urlToken(url) {
    if (!_opToken) return url;
    return url + (url.indexOf('?') >= 0 ? '&' : '?') + 'op_token=' + encodeURIComponent(_opToken);
}
function csrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

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
document.getElementById('modalBRT').addEventListener('shown.bs.modal', function() {
    if (!brtModalCaricato && brtDDTList.length > 0) {
        brtModalCaricato = true;
        caricaTuttiTrackingBRT();
    }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function(){
    // Rendi tutte le td focusabili (per espandere testo troncato al click)
    document.querySelectorAll('#tabellaOrdini td:not([contenteditable])').forEach(function(td){
        td.setAttribute('tabindex', '-1');
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
    var val = testo.trim().toUpperCase();
    // Se riscrive EXT, non fare nulla
    if (val === 'EXT') return;
    const nuovoStato = parseInt(val);
    if (isNaN(nuovoStato) || nuovoStato < 0 || (nuovoStato > 3 && nuovoStato !== 5)) {
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
            const bgMap = {0: '#e9ecef', 1: '#cfe2ff', 2: '#fff3cd', 3: '#d1e7dd', 5: '#e0cffc'};
            const row = document.querySelector('tr[data-id="' + faseId + '"]');
            if (row) {
                const statoCell = row.cells[1];
                statoCell.style.setProperty('background', bgMap[nuovoStato], 'important');
                statoCell.style.color = '#000';
                statoCell.style.fontSize = '';
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
    let mouseClientX = 0, mouseClientY = 0;
    let selectionBox = null;
    let pollTimer = null;
    let cachedRects = []; // coordinate pagina (costanti)

    function cacheRects() {
        const sx = window.scrollX, sy = window.scrollY;
        cachedRects = allCells.map(cell => {
            const r = cell.getBoundingClientRect();
            return { cell, left: r.left + sx, top: r.top + sy, right: r.right + sx, bottom: r.bottom + sy };
        });
    }

    function updateSelection() {
        if (!selectionBox) return;

        // Posizione attuale del mouse in coordinate pagina
        const curX = mouseClientX + window.scrollX;
        const curY = mouseClientY + window.scrollY;

        // Box selezione in coordinate pagina
        const boxL = Math.min(startX, curX), boxT = Math.min(startY, curY);
        const boxR = Math.max(startX, curX), boxB = Math.max(startY, curY);

        // Disegna il box (position:absolute → coordinate pagina)
        selectionBox.style.left = boxL + 'px';
        selectionBox.style.top = boxT + 'px';
        selectionBox.style.width = (boxR - boxL) + 'px';
        selectionBox.style.height = (boxB - boxT) + 'px';

        // Confronta rect cachati (coordinate pagina) con il box (coordinate pagina)
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

        // Polling ogni 50ms per catturare scroll rotellina
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
        descrizione: row.cells[6].innerText.toLowerCase(),
        fase: row.cells[10].innerText.toLowerCase(),
        reparto: row.cells[11].innerText.toLowerCase()
    }));

    function parseValues(input) {
        return input.split(',').map(v => v.trim().toLowerCase()).filter(v => v);
    }

    // Separa valori positivi e negativi (con prefisso -)
    function parseWithExclusion(input) {
        var all = input.split(',').map(v => v.trim().toLowerCase()).filter(v => v);
        var include = all.filter(v => !v.startsWith('-'));
        var exclude = all.filter(v => v.startsWith('-')).map(v => v.substring(1).trim()).filter(v => v);
        return { include, exclude };
    }

    function matchField(fieldValue, parsed) {
        // Se ci sono esclusioni, verifica che nessuna matchi
        if (parsed.exclude.length && parsed.exclude.some(v => fieldValue.includes(v))) return false;
        // Se ci sono inclusioni, almeno una deve matchare
        if (parsed.include.length && !parsed.include.some(v => fieldValue.includes(v))) return false;
        return true;
    }

    function getSelectedOptions(select) {
        return Array.from(select.selectedOptions).map(opt => opt.value.toLowerCase());
    }

    function filtra() {
        const commesse = parseWithExclusion(fCommessa.value);
        const clienti = parseWithExclusion(fCliente.value);
        const descrizioni = parseWithExclusion(fDescrizione.value);
        const stati = getSelectedOptions(fStato);
        const fasi = getSelectedOptions(fFase);
        const reparti = getSelectedOptions(fReparto);

        requestAnimationFrame(() => {
            rowData.forEach(data => {
                let match = true;
                if(commesse.include.length || commesse.exclude.length) match = match && matchField(data.commessa, commesse);
                if(clienti.include.length || clienti.exclude.length) match = match && matchField(data.cliente, clienti);
                if(descrizioni.include.length || descrizioni.exclude.length) match = match && matchField(data.descrizione, descrizioni);
                if(stati.length) {
                    var isInPausa = isNaN(data.stato) && data.stato !== 'ext';
                    match = match && (isInPausa || stati.includes(data.stato));
                }
                if(fasi.length) match = match && fasi.some(f => data.fase.includes(f));
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

    // Salva filtri in sessionStorage dopo ogni modifica
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
                filterBox.style.display = 'flex';
                filtra();
            }
        }
    } catch(e) {}

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

// Riferimenti Marco: filtra per clienti specifici
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
    'CAPOBIANCO', 'VILLA RAIANO', 'BE PACKAGING'
];

function filtraRiferimentiMarco() {
    const fCliente = document.getElementById('filterCliente');
    const filterBox = document.getElementById('filterBox');
    if (filterBox.style.display === 'none') {
        filterBox.style.display = 'flex';
        filterBox.style.opacity = 1;
        filterBox.style.transform = 'translateY(0)';
    }
    fCliente.value = CLIENTI_MARCO.join(', ');
    fCliente.dispatchEvent(new Event('input'));
}

function resetRiferimentiMarco() {
    const fCliente = document.getElementById('filterCliente');
    fCliente.value = '';
    fCliente.dispatchEvent(new Event('input'));
}

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

// === Notifiche Note Consegne ===
var _noteLastUpdate = localStorage.getItem('noteConsegne_lastUpdate') || '';
var _noteCheckInterval = 15000; // 15 secondi

// Sblocca AudioContext al primo click (richiesto dai browser)
var _audioCtx = null;
document.addEventListener('click', function() {
    if (!_audioCtx) {
        _audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    }
}, {once: true});

// Chiedi permesso notifiche browser
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
                // Nuova modifica: mostra tutte le notifiche
                // 1. Badge rosso
                document.getElementById('noteConsegneBadge').style.display = 'inline-block';

                // 2. Suono
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

                // 3. Notifica browser
                if ('Notification' in window && Notification.permission === 'granted') {
                    var preview = (d.contenuto || '').substring(0, 100);
                    new Notification('Note Consegne aggiornate', {
                        body: preview || 'La logistica ha aggiornato le note consegne',
                        icon: '/favicon.ico'
                    });
                }

                // 4. Toast popup
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
    toast.style.cssText = 'position:fixed; top:20px; right:20px; z-index:9999; background:#0d6efd; color:#fff; padding:15px 20px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.3); font-size:14px; cursor:pointer; max-width:350px; animation:slideIn 0.3s ease;';
    toast.onclick = function() {
        toast.remove();
        document.getElementById('noteConsegneBadge').style.display = 'none';
        // Apri modale note
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

// Avvia polling
checkNoteConsegne();
setInterval(checkNoteConsegne, _noteCheckInterval);

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
        document.getElementById('ownerNoteSaveStatus').style.color = '#dc3545';
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
            document.getElementById('ownerNoteSaveStatus').style.color = '#198754';
            // Aggiorna timestamp per non auto-notificarsi
            fetch(urlToken('{{ route("owner.noteSpedizioneCheck") }}'), {headers:{'Accept':'application/json'}})
                .then(function(r){return r.json();})
                .then(function(dd){ if(dd.updated_at){ _noteLastUpdate=dd.updated_at; localStorage.setItem('noteConsegne_lastUpdate',_noteLastUpdate); } });
        }
    })
    .catch(() => {
        document.getElementById('ownerNoteSaveStatus').textContent = 'Errore salvataggio';
        document.getElementById('ownerNoteSaveStatus').style.color = '#dc3545';
    })
    .finally(() => { btn.disabled = false; });
}
</script>

<!-- Modale Note Consegne -->
<div class="modal fade" id="modalNoteSpedizione" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:#0d6efd; color:#fff;">
                <h5 class="modal-title">Note Consegne - {{ now()->format('d/m/Y') }}</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <textarea id="ownerNotaContenuto" rows="10" class="form-control" style="font-size:14px;" placeholder="Note consegne..."></textarea>
            </div>
            <div class="modal-footer" style="justify-content:space-between;">
                <span id="ownerNoteSaveStatus" style="font-size:12px; color:#6c757d;"></span>
                <button onclick="salvaNoteSped()" class="btn btn-primary btn-sm">Salva</button>
            </div>
        </div>
    </div>
</div>
{{-- Modal Nota TV --}}
<div class="modal fade" id="modalNotaTv" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:#dc2626; color:#fff;">
                <h5 class="modal-title">📢 Nota TV (ticker)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p style="font-size:13px; color:#666; margin-bottom:10px;">Il testo apparirà come ticker scorrevole sulla TV del reparto.</p>
                <input type="text" id="notaTvInput" class="form-control" placeholder="Es: Consegna urgente TRENITALIA ore 15:00" style="font-size:15px;">
                <div style="margin-top:10px; font-size:12px; color:#999;">Lascia vuoto per rimuovere la nota. Dura 24 ore.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Chiudi</button>
                <button onclick="salvaNotaTv()" class="btn btn-danger btn-sm">Salva e pubblica</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal Presenti in azienda --}}
<div class="modal fade" id="modalPresenti" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background:#198754; color:#fff;">
                <h5 class="modal-title">Presenti in azienda - <span id="presentiData">{{ now()->format('d/m/Y') }}</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="max-height:70vh; overflow-y:auto;">
                <div id="presentiLoading" class="text-center py-3"><div class="spinner-border text-success"></div></div>
                <div id="presentiContent" style="display:none;">
                    <h6 style="color:#198754; margin-bottom:8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#198754" stroke="none"><circle cx="12" cy="12" r="6"/></svg>
                        Presenti (<span id="presentiTotale">0</span>)
                    </h6>
                    <table class="table table-sm table-striped" style="font-size:13px;">
                        <thead><tr><th>Nome</th><th>Entrata</th><th>Ultima</th></tr></thead>
                        <tbody id="presentiBody"></tbody>
                    </table>
                    <h6 style="color:#6c757d; margin-top:16px; margin-bottom:8px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="#6c757d" stroke="none"><circle cx="12" cy="12" r="6"/></svg>
                        Usciti (<span id="uscitiTotale">0</span>)
                    </h6>
                    <table class="table table-sm" style="font-size:13px; opacity:0.7;">
                        <thead><tr><th>Nome</th><th>Entrata</th><th>Uscita</th></tr></thead>
                        <tbody id="uscitiBody"></tbody>
                    </table>
                    <div class="text-end" style="font-size:11px; color:#999;">Ultimo aggiornamento: <span id="presentiSync">-</span></div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
var _presentiInterval = null;
function caricaPresenti() {
    document.getElementById('presentiLoading').style.display = 'block';
    document.getElementById('presentiContent').style.display = 'none';
    fetchPresenti();
    if (_presentiInterval) clearInterval(_presentiInterval);
    _presentiInterval = setInterval(fetchPresenti, 60000);
}
function fetchPresenti() {
    fetch(urlToken('{{ route("owner.presenti") }}'))
        .then(function(r) { return r.json(); })
        .then(function(d) {
            document.getElementById('presentiLoading').style.display = 'none';
            document.getElementById('presentiContent').style.display = 'block';
            document.getElementById('presentiTotale').textContent = d.totale_presenti;
            document.getElementById('uscitiTotale').textContent = d.totale_usciti;
            document.getElementById('presentiSync').textContent = d.ultimo_sync;
            // Badge sidebar
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
// Stop polling quando modal chiuso
document.getElementById('modalPresenti').addEventListener('hidden.bs.modal', function() {
    if (_presentiInterval) { clearInterval(_presentiInterval); _presentiInterval = null; }
});
// Carica badge presenti al load della pagina
fetch(urlToken('{{ route("owner.presenti") }}'))
    .then(function(r) { return r.json(); })
    .then(function(d) {
        var badge = document.getElementById('presentiCount');
        if (badge) badge.textContent = d.totale_presenti;
    }).catch(function() {});
</script>

@if($isReadonly ?? false)
<script>
// Owner readonly: rimuovi contenteditable tranne data consegna
document.addEventListener('DOMContentLoaded', function() {
    // Rimuovi contenteditable da tutte le celle TRANNE data prevista consegna
    document.querySelectorAll('[contenteditable]').forEach(function(el) {
        var onblur = el.getAttribute('onblur') || '';
        if (onblur.indexOf('data_prevista_consegna') !== -1) return; // mantieni editabile
        el.removeAttribute('contenteditable');
        el.removeAttribute('onblur');
        el.style.cursor = 'default';
    });
    // Nascondi bottoni di azione nel sidebar
    document.querySelectorAll('form[action*="sync"], form[action*="import"], [data-bs-target="#aggiungiRigaModal"]').forEach(function(el) {
        el.style.display = 'none';
    });
    // Nascondi bottoni elimina fase
    document.querySelectorAll('.btn-elimina-fase, [onclick*="eliminaFase"]').forEach(function(el) {
        el.style.display = 'none';
    });
});
</script>
@endif
{{-- Scrollbar orizzontale sticky in fondo alla viewport --}}
<div id="stickyScroll" style="position:fixed;bottom:0;left:0;right:0;overflow-x:auto;overflow-y:hidden;z-index:1000;height:16px;background:#f8f8f8;border-top:1px solid #ddd;">
    <div id="stickyScrollInner" style="height:1px;"></div>
</div>
<script>
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
</script>

{{-- MODALE RIEMPIMENTO MACCHINE --}}
<div class="modal fade" id="modalRiempimento" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background:#0f172a; color:#f1f5f9; border:none;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title" style="color:#a78bfa; font-weight:800;">Riempimento Macchine
                    <span style="font-size:0.7rem; color:#64748b; margin-left:0.5rem;">Totale: {{ round(collect($riempimento)->sum('ore_totali')) }}h in coda</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                @php $maxOre = max(collect($riempimento)->max('ore_totali'), 1); @endphp
                @foreach($riempimento as $r)
                <div class="d-flex align-items-center mb-1">
                    <span style="font-size:0.85rem; font-weight:600; color:#94a3b8; min-width:120px;">{{ $r['nome'] }}</span>
                    <div style="flex:1; height:18px; background:#1e293b; border-radius:4px; overflow:hidden; position:relative; margin:0 8px;">
                        @php
                            $pctPronte = ($r['ore_1'] / $maxOre) * 100;
                            $pctCoda = ($r['ore_0'] / $maxOre) * 100;
                            $pctTotale = $pctPronte + $pctCoda;
                        @endphp
                        <div style="position:absolute; left:0; top:0; height:100%; width:{{ $pctTotale }}%; display:flex;">
                            <div style="width:{{ $pctPronte > 0 ? ($r['ore_1'] / ($r['ore_0'] + $r['ore_1'] ?: 1)) * 100 : 0 }}%; height:100%; background:linear-gradient(90deg, #16a34a, #4ade80); border-radius:4px 0 0 4px;"></div>
                            <div style="flex:1; height:100%; background:linear-gradient(90deg, #475569, #64748b); border-radius:0 4px 4px 0;"></div>
                        </div>
                        <span style="position:absolute; left:{{ min($pctTotale + 1, 88) }}%; top:50%; transform:translateY(-50%); font-size:0.75rem; font-weight:700; color:#f1f5f9; white-space:nowrap;">{{ round($r['ore_totali'], 0) }}h</span>
                    </div>
                </div>
                <div class="d-flex mb-3" style="margin-left:120px; font-size:0.75rem; color:#64748b; gap:1.5rem;">
                    <span style="color:#4ade80;">Pronte da lavorare: {{ $r['ore_1'] }}h ({{ $r['fasi_1'] }})</span>
                    <span>In coda: {{ $r['ore_0'] }}h ({{ $r['fasi_0'] }})</span>
                </div>
                @endforeach
                <div class="mt-2 d-flex gap-3" style="font-size:0.75rem; color:#64748b;">
                    <span><span style="color:#4ade80;">&#9632;</span> Pronte da lavorare (stato 1)</span>
                    <span><span style="color:#64748b;">&#9632;</span> In coda (stato 0)</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
