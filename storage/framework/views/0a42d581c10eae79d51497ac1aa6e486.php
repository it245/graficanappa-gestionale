<?php $__env->startSection('content'); ?>
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
    width: 2560px;              /* OTTIMIZZATO PER 2560x1440 */
    max-width: 2560px;
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
   LARGHEZZA COLONNE (24 colonne) — ordine attuale:
   1=Commessa 2=Stato 3=Cliente 4=CodArt 5=Colori 6=Fustella
   7=Descrizione 8=Qta 9=UM 10=Priorità 11=Fase 12=Reparto
   13=DataReg 14=DataConsegna 15=CodCarta 16=Carta
   17=QtaCarta 18=UMCarta 19=Operatori 20=QtaProd
   21=Esterno 22=Note 23=DataInizio 24=DataFine
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
th:nth-child(5), td:nth-child(5) { width: 110px; white-space: normal; }

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

/* 13. Data Registrazione / 14. Data Prevista Consegna */
th:nth-child(13), td:nth-child(13),
th:nth-child(14), td:nth-child(14) {
    width: 100px;
}

/* 15. Cod Carta */
th:nth-child(15), td:nth-child(15) { width: 170px; white-space: normal; }

/* 16. Carta */
th:nth-child(16), td:nth-child(16) { width: 190px; white-space: normal; }

/* 17. Qta Carta */
th:nth-child(17), td:nth-child(17) { width: 50px; text-align: center; }

/* 18. UM Carta */
th:nth-child(18), td:nth-child(18) { width: 40px; text-align: center; }

/* 19. Operatori */
th:nth-child(19), td:nth-child(19) {
    width: 80px;
    white-space: normal;
    font-size: 11px;
}

/* 20. Qta Prod. */
th:nth-child(20), td:nth-child(20) {
    width: 60px;
    text-align: center;
}

/* 21. Esterno */
th:nth-child(21), td:nth-child(21) { width: 90px; }

/* 22. Note */
th:nth-child(22), td:nth-child(22) {
    width: 170px;
    white-space: normal;
}

/* 23. Data Inizio / 24. Data Fine */
th:nth-child(23), td:nth-child(23),
th:nth-child(24), td:nth-child(24) {
    width: 110px;
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
            <img src="<?php echo e(asset('images/logo_gn.png')); ?>" alt="Logo" style="height:40px;">
            <h2 class="mb-0">Dashboard Owner</h2>
        </div>
        <div class="operatore-info" id="operatoreInfo" style="position:relative; display:flex; align-items:center; gap:10px; cursor:pointer;">
            <img src="<?php echo e(asset('images/icons8-utente-uomo-cerchiato-50.png')); ?>" alt="Operatore" style="width:50px; height:50px; border-radius:50%;">
            <div class="operatore-popup" id="operatorePopup" style="position:absolute; top:60px; right:0; background:#fff; border:1px solid #ccc; padding:10px; border-radius:5px; box-shadow:0 2px 10px rgba(0,0,0,0.2); display:none; z-index:1000; min-width:200px;">
                <div><strong><?php echo e($operatore->nome ?? ''); ?> <?php echo e($operatore->cognome ?? ''); ?></strong></div>
                <div><p class="mb-1">Ruolo: <strong>Owner</strong></p></div>
                <form action="<?php echo e(route('operatore.logout')); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="btn btn-secondary btn-sm mt-2">Logout</button>
                </form>
            </div>
        </div>
    </div>

    
    <div class="d-flex gap-2 mb-2 mx-0" style="max-width:920px;">
        <a href="<?php echo e(route('owner.fasiTerminate', ['oggi' => 1])); ?>" class="d-flex align-items-center p-2 rounded flex-fill text-decoration-none" style="background:#d1e7dd; height:56px; min-width:200px; cursor:pointer;" title="Visualizza fasi completate oggi">
            <div>
                <div style="font-size:11px; color:#555; line-height:1.2;">Fasi completate oggi</div>
                <div style="font-size:22px; font-weight:700; color:#198754; line-height:1;"><?php echo e($fasiCompletateOggi); ?></div>
            </div>
        </a>
        <div class="d-flex align-items-center p-2 rounded flex-fill" style="background:#cfe2ff; height:56px; min-width:200px;">
            <div>
                <div style="font-size:11px; color:#555; line-height:1.2;">Ore lavorate oggi</div>
                <div style="font-size:22px; font-weight:700; color:#0d6efd; line-height:1;"><?php echo e($oreLavorateOggi); ?>h</div>
            </div>
        </div>
        <div class="d-flex align-items-center p-2 rounded flex-fill" style="background:#d5d5d5; height:56px; min-width:200px;">
            <div>
                <div style="font-size:11px; color:#555; line-height:1.2;">Consegnate oggi</div>
                <div style="font-size:22px; font-weight:700; color:#333; line-height:1;"><?php echo e($commesseSpediteOggi); ?></div>
            </div>
        </div>
        <div class="d-flex align-items-center p-2 rounded flex-fill" style="background:#fff3cd; height:56px; min-width:200px;">
            <div>
                <div style="font-size:11px; color:#555; line-height:1.2;">Fasi in lavorazione</div>
                <div style="font-size:22px; font-weight:700; color:#e67e22; line-height:1;"><?php echo e($fasiAttive); ?></div>
            </div>
        </div>
    </div>

    
    <div class="mb-3 d-flex align-items-center action-icons">

        
        <button class="hamburger-btn" id="hamburgerBtn" title="Menu">
            <span></span><span></span><span></span>
        </button>

        
        <img
            src="<?php echo e(asset('images/icons8-filtro-50.png')); ?>"
            id="toggleFilter"
            title="Mostra / Nascondi filtri"
            alt="Filtri"
        >

        
        <button id="printButton" class="btn p-0" style="background:none; border:none;" title="Stampa celle selezionate">
            <img src="<?php echo e(asset('images/printer.png')); ?>" alt="Stampa">
        </button>
    </div>

    
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    
    <div class="sidebar-menu" id="sidebarMenu">
        <div class="sidebar-header">
            <h5>Menu</h5>
            <button class="sidebar-close" id="sidebarClose">&times;</button>
        </div>

        
        <a href="<?php echo e(route('owner.fasiTerminate')); ?>" class="sidebar-item">
            <img src="<?php echo e(asset('images/out-of-the-box.png')); ?>" alt="">
            <span>Fasi terminate</span>
        </a>

        
        <a href="<?php echo e(route('owner.scheduling')); ?>" class="sidebar-item">
            <img src="<?php echo e(asset('images/icons8-report-grafico-a-torta-50.png')); ?>" alt="">
            <span>Scheduling Produzione</span>
        </a>

        
        <a href="<?php echo e(route('owner.esterne')); ?>" class="sidebar-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#17a2b8" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="7.5 4.21 12 6.81 16.5 4.21"/><polyline points="7.5 19.79 7.5 14.6 3 12"/><polyline points="21 12 16.5 14.6 16.5 19.79"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>
            </svg>
            <span>Lavorazioni Esterne</span>
        </a>

        
        <a href="<?php echo e(route('mes.prinect')); ?>" class="sidebar-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="6" y="2" width="12" height="6" rx="1"/><rect x="2" y="8" width="20" height="8" rx="1"/><rect x="6" y="16" width="12" height="6" rx="1"/><line x1="6" y1="12" x2="2" y2="12"/><line x1="22" y1="12" x2="18" y2="12"/>
            </svg>
            <span>Prinect Live (Offset)</span>
        </a>

        
        <a href="<?php echo e(route('mes.fiery')); ?>" class="sidebar-item">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#e65100" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="6" width="20" height="12" rx="2"/><line x1="6" y1="10" x2="6" y2="14"/><line x1="10" y1="10" x2="10" y2="14"/><line x1="14" y1="10" x2="14" y2="14"/><line x1="18" y1="10" x2="18" y2="14"/><line x1="2" y1="20" x2="22" y2="20"/>
            </svg>
            <span>Fiery V900 (Digitale)</span>
        </a>

        
        <?php if(!($isReadonly ?? false)): ?>
        <a href="#" class="sidebar-item" onclick="alert('Apri da Esplora Risorse:\n\n\\\\gestionale\\mes\\dashboard_mes.xlsx'); closeSidebar(); return false;">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#198754" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/><polyline points="10 9 9 9 8 9"/>
            </svg>
            <span>Apri Excel Dashboard</span>
        </a>
        <?php endif; ?>

        
        <?php if(!($isReadonly ?? false)): ?>
        <a href="#" class="sidebar-item" data-bs-toggle="modal" data-bs-target="#aggiungiRigaModal" onclick="closeSidebar()">
            <img src="<?php echo e(asset('images/icons8-ddt-64 (1).png')); ?>" alt="">
            <span>Aggiungi riga</span>
        </a>
        <?php endif; ?>

        
        <a href="#" class="sidebar-item" data-bs-toggle="modal" data-bs-target="#modalSpedizioniOggi" onclick="closeSidebar()" id="btnConsegnati">
            <img src="<?php echo e(asset('images/icons8-consegnato-50.png')); ?>" alt="">
            <span>Consegnati oggi
                <?php if($spedizioniOggi->count() > 0): ?>
                    <span class="badge rounded-pill bg-danger" style="font-size:11px; vertical-align:middle;"><?php echo e($spedizioniOggi->count()); ?></span>
                <?php endif; ?>
            </span>
        </a>

        
        <a href="#" class="sidebar-item" data-bs-toggle="modal" data-bs-target="#modalBRT" onclick="closeSidebar()">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#d4380d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
            </svg>
            <span>Spedizioni BRT
                <?php if($spedizioniBRT->count() > 0): ?>
                    <span class="badge rounded-pill bg-danger" style="font-size:11px; vertical-align:middle;"><?php echo e($spedizioniBRT->count()); ?></span>
                <?php endif; ?>
            </span>
        </a>

        
        <a href="#" class="sidebar-item" data-bs-toggle="modal" data-bs-target="#modalStorico" onclick="closeSidebar()">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#6c757d" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
            <span>Storico consegne
                <?php if($storicoConsegne->count() > 0): ?>
                    <span class="badge rounded-pill bg-secondary" style="font-size:11px; vertical-align:middle;"><?php echo e($storicoConsegne->count()); ?></span>
                <?php endif; ?>
            </span>
        </a>

        
        <a href="#" class="sidebar-item" data-bs-toggle="modal" data-bs-target="#modalNoteSpedizione" onclick="closeSidebar(); caricaNoteSpedizione();">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#0d6efd" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
            </svg>
            <span>Note Consegne</span>
        </a>

        
        <a href="#" class="sidebar-item" onclick="closeSidebar(); toggleAiChat(); return false;">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 2a8 8 0 0 0-8 8c0 3.4 2.1 6.3 5 7.4V20a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1v-2.6c2.9-1.1 5-4 5-7.4a8 8 0 0 0-8-8z"/><line x1="9" y1="22" x2="15" y2="22"/><line x1="12" y1="2" x2="12" y2="6"/><line x1="8" y1="14" x2="16" y2="14"/>
            </svg>
            <span style="color:#7c3aed; font-weight:600;">Assistente AI</span>
        </a>

        
        <?php if(!($isReadonly ?? false)): ?>
        <form method="POST" action="<?php echo e(route('owner.syncOnda')); ?>" style="margin:0;" onsubmit="this.querySelector('button').disabled=true;">
            <?php echo csrf_field(); ?>
            <button type="submit" class="sidebar-item" style="width:100%; background:none; border:none; border-bottom:1px solid #f0f0f0; text-align:left; font-size:14px; font-weight:500; color:#333;">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#333" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21.5 2v6h-6"/><path d="M2.5 22v-6h6"/><path d="M2.5 11.5a10 10 0 0 1 18.8-4.3"/><path d="M21.5 12.5a10 10 0 0 1-18.8 4.2"/>
                </svg>
                <span>Sincronizza Onda</span>
            </button>
        </form>
        <?php endif; ?>
    </div>
    
        
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
    <?php
        $fasiNomi = $fasiCatalogo->pluck('nome_display')->map(function($n) {
            if ($n === 'STAMPA') return null;
            return $n;
        })->filter()->unique()->sort()->values();
    ?>
    <?php $__currentLoopData = $fasiNomi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $nome): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e(strtolower($nome)); ?>"><?php echo e($nome); ?></option>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</select>

<select id="filterReparto" multiple>
    <?php $__currentLoopData = $reparti; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $rep): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if($rep !== 'fustella'): ?>
        <option value="<?php echo e($rep); ?>"><?php echo e($rep); ?></option>
        <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</select>
<button type="button" class="btn-reset-filters" id="btnResetFilters">Rimuovi filtri</button>
</div>

    
    <div class="modal fade" id="aggiungiRigaModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="<?php echo e(route('owner.aggiungiRiga')); ?>">
                <?php echo csrf_field(); ?>
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
                                    <?php $__currentLoopData = \App\Models\FasiCatalogo::with('reparto')->orderBy('nome')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($fc->id); ?>"><?php echo e($fc->nome); ?> (<?php echo e($fc->reparto->nome ?? '-'); ?>)</option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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

<?php
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
?>

    
    <div id="tableScroll" style="width:100%;">
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
                    <th>Data Registrazione</th>
                    <th>Data Prevista Consegna</th>
                    <th>Cod Carta</th>
                    <th>Carta</th>
                    <th>Qta Carta</th>
                    <th>UM Carta</th>
                    <th>Operatori</th>
                    <th>Qta Prod.</th>
                    <th>Esterno</th>
                    <th>Note</th>
                    <th>Data Inizio</th>
                    <th>Data Fine</th>
                </tr>
            </thead>
            <tbody>
            <?php $__currentLoopData = $fasi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fase): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $rowClass = '';
                    if ($fase->ordine->data_prevista_consegna) {
                        $dataPrevista = \Carbon\Carbon::parse($fase->ordine->data_prevista_consegna)->startOfDay();
                        $oggi = \Carbon\Carbon::today();
                        $diffGiorni = $oggi->diffInDays($dataPrevista, false);
                        if ($diffGiorni <= -5) $rowClass = 'scaduta';
                        elseif ($diffGiorni <= 3) $rowClass = 'warning-strong';
                        elseif ($diffGiorni <= 5) $rowClass = 'warning-light';
                    }
                ?>
                <?php
                    $statiLabel = [0 => 'Caricato', 1 => 'Pronto', 2 => 'Avviato', 3 => 'Terminato', 4 => 'Consegnato'];
                    $statoBg = [0 => '#e9ecef', 1 => '#cfe2ff', 2 => '#fff3cd', 3 => '#d1e7dd', 4 => '#c3c3c3'];
                ?>
                <tr class="<?php echo e($rowClass); ?>" data-id="<?php echo e($fase->id); ?>">
                    <td><a href="<?php echo e(route('owner.dettaglioCommessa', $fase->ordine->commessa ?? '-')); ?>" style="color:#000;font-weight:bold;text-decoration:underline;"><?php echo e($fase->ordine->commessa ?? '-'); ?></a></td>
                    <td contenteditable onblur="aggiornaStato(<?php echo e($fase->id); ?>, this.innerText)" style="background:<?php echo e($statoBg[$fase->stato] ?? '#e9ecef'); ?> !important;font-weight:bold;text-align:center;"><?php echo e($fase->stato); ?></td>
                    <td contenteditable onblur="aggiornaCampo(<?php echo e($fase->id); ?>, 'cliente_nome', this.innerText)"><?php echo e($fase->ordine->cliente_nome ?? '-'); ?></td>
                    <td contenteditable onblur="aggiornaCampo(<?php echo e($fase->id); ?>, 'cod_art', this.innerText)"><?php echo e($fase->ordine->cod_art ?? '-'); ?></td>
                    <?php
                        $descOwner = $fase->ordine->descrizione ?? '';
                        $clienteOwner = $fase->ordine->cliente_nome ?? '';
                        $repartoOwner = strtolower($fase->faseCatalogo->reparto->nome ?? '');
                        $coloriOwner = \App\Helpers\DescrizioneParser::parseColori($descOwner, $clienteOwner, $repartoOwner);
                        $fustellaOwner = \App\Helpers\DescrizioneParser::parseFustella($descOwner, $clienteOwner);
                    ?>
                    <td><?php echo e($coloriOwner ?: '-'); ?></td>
                    <td><?php echo e($fustellaOwner ?: '-'); ?></td>
                    <td contenteditable onblur="aggiornaCampo(<?php echo e($fase->id); ?>, 'descrizione', this.innerText)"><?php echo e($fase->ordine->descrizione ?? '-'); ?></td>
                    <td contenteditable onblur="aggiornaCampo(<?php echo e($fase->id); ?>, 'qta_richiesta', this.innerText)"><?php echo e($fase->ordine->qta_richiesta ?? '-'); ?></td>
                    <td contenteditable onblur="aggiornaCampo(<?php echo e($fase->id); ?>, 'um', this.innerText)"><?php echo e($fase->ordine->um ?? '-'); ?></td>
                    <td contenteditable onblur="aggiornaCampo(<?php echo e($fase->id); ?>, 'priorita', this.innerText)"><?php echo e($fase->priorita !== null ? number_format($fase->priorita, 2) : '-'); ?></td>
                    <td contenteditable onblur="aggiornaCampo(<?php echo e($fase->id); ?>, 'fase', this.innerText)"><?php echo e($fase->faseCatalogo->nome_display ?? '-'); ?></td>
                    <td><?php echo e($fase->faseCatalogo->reparto->nome ?? '-'); ?></td>
                    <td contenteditable onblur="aggiornaCampo(<?php echo e($fase->id); ?>, 'data_registrazione', this.innerText)"><?php echo e(formatItalianDate($fase->ordine->data_registrazione)); ?></td>
                    <td contenteditable onblur="aggiornaCampo(<?php echo e($fase->id); ?>, 'data_prevista_consegna', this.innerText)"><?php echo e(formatItalianDate($fase->ordine->data_prevista_consegna)); ?></td>
                    <td contenteditable onblur="aggiornaCampo(<?php echo e($fase->id); ?>, 'cod_carta', this.innerText)"><?php echo e($fase->ordine->cod_carta ?? '-'); ?></td>
                    <td contenteditable onblur="aggiornaCampo(<?php echo e($fase->id); ?>, 'carta', this.innerText)"><?php echo e($fase->ordine->carta ?? '-'); ?></td>
                    <td contenteditable onblur="aggiornaCampo(<?php echo e($fase->id); ?>, 'qta_carta', this.innerText)"><?php echo e($fase->ordine->qta_carta ?? '-'); ?></td>
                    <td contenteditable onblur="aggiornaCampo(<?php echo e($fase->id); ?>, 'UM_carta', this.innerText)"><?php echo e($fase->ordine->UM_carta ?? '-'); ?></td>
                    <td>
                        <?php $__empty_1 = true; $__currentLoopData = $fase->operatori; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $op): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php echo e($op->nome); ?><br>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td contenteditable onblur="aggiornaCampo(<?php echo e($fase->id); ?>, 'qta_prod', this.innerText)"><?php echo e($fase->qta_prod ?? '-'); ?></td>
                    <?php
                        $fornitoreEsterno = preg_match('/Inviato a:\s*(.+)/i', $fase->note ?? '', $mEst) ? trim($mEst[1]) : null;
                        $notePulitaOwner = preg_replace('/,?\s*Inviato a:\s*.+/i', '', $fase->note ?? '');
                        $notePulitaOwner = trim($notePulitaOwner, ", \t\n\r") ?: null;
                    ?>
                    <td><?php echo e($fornitoreEsterno ?? '-'); ?></td>
                    <td>
                        <span contenteditable onblur="aggiornaCampo(<?php echo e($fase->id); ?>, 'note', this.innerText)"><?php echo e($notePulitaOwner ?? '-'); ?></span>
                    </td>
                    <td contenteditable onblur="aggiornaCampo(<?php echo e($fase->id); ?>, 'data_inizio', this.innerText)"><?php echo e(formatItalianDate($fase->data_inizio, true)); ?></td>
                    <td contenteditable onblur="aggiornaCampo(<?php echo e($fase->id); ?>, 'data_fine', this.innerText)"><?php echo e(formatItalianDate($fase->data_fine, true)); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>

</div>

<div class="modal fade" id="modalSpedizioniOggi" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Consegnati oggi (<?php echo e($spedizioniOggi->count()); ?>)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="overflow-x:auto;">
                <?php if($spedizioniOggi->count() > 0): ?>
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
                        <?php $__currentLoopData = $spedizioniOggi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><strong><?php echo e($sp->ordine->commessa ?? '-'); ?></strong></td>
                            <td><?php echo e($sp->ordine->cliente_nome ?? '-'); ?></td>
                            <td style="white-space:normal; max-width:350px;"><?php echo e($sp->ordine->descrizione ?? '-'); ?></td>
                            <td><?php echo e($sp->ordine->numero_ddt_vendita ? ltrim($sp->ordine->numero_ddt_vendita, '0') : '-'); ?></td>
                            <td><?php echo e($sp->faseCatalogo->nome_display ?? $sp->fase ?? '-'); ?></td>
                            <td>
                                <?php $__currentLoopData = $sp->operatori; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $op): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php echo e($op->nome); ?> <?php echo e($op->cognome); ?><?php if(!$loop->last): ?>, <?php endif; ?>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </td>
                            <td><?php echo e($sp->data_fine ? \Carbon\Carbon::parse($sp->data_fine)->format('d/m/Y H:i:s') : '-'); ?></td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="text-muted text-center py-3">Nessuna consegna effettuata oggi</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal Spedizioni BRT -->
<div class="modal fade" id="modalBRT" tabindex="-1">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header" style="background:#d4380d; color:#fff; padding:18px 24px;">
                <h5 class="modal-title" style="font-size:22px; font-weight:700;">Spedizioni BRT (<?php echo e($spedizioniBRT->count()); ?> DDT)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="overflow-x:auto; padding:20px 24px;">
                <?php if($spedizioniBRT->count() > 0): ?>
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
                        <?php $__currentLoopData = $spedizioniBRT; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $numDDT => $ordiniGruppo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $primo = $ordiniGruppo->first();
                            $commesse = $ordiniGruppo->pluck('commessa')->unique()->implode(', ');
                        ?>
                        <tr id="brt_row_<?php echo e(md5($numDDT)); ?>">
                            <td class="fw-bold" style="padding:10px 14px; font-size:16px;"><?php echo e(ltrim($numDDT, '0')); ?></td>
                            <td style="padding:10px 14px;"><?php echo e($commesse); ?></td>
                            <td style="padding:10px 14px;"><?php echo e($primo->cliente_nome ?? '-'); ?></td>
                            <td id="brt_stato_<?php echo e(md5($numDDT)); ?>" style="padding:10px 14px;">
                                <span class="badge bg-light text-muted" style="font-size:13px; padding:6px 10px;">Da verificare</span>
                            </td>
                            <td id="brt_data_<?php echo e(md5($numDDT)); ?>" style="padding:10px 14px;">-</td>
                            <td id="brt_dest_<?php echo e(md5($numDDT)); ?>" style="padding:10px 14px;">-</td>
                            <td id="brt_colli_<?php echo e(md5($numDDT)); ?>" style="padding:10px 14px;">-</td>
                            <td style="padding:10px 14px;">
                                <button class="btn btn-outline-danger fw-bold" style="font-size:14px; padding:6px 16px;" onclick="apriTrackingDDT('<?php echo e($numDDT); ?>', this)">
                                    Dettagli
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="text-muted text-center py-4" style="font-size:16px;">Nessuna spedizione BRT</p>
                <?php endif; ?>
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
                <?php if($storicoConsegne->count() > 0): ?>
                <?php $__currentLoopData = $storicoConsegne->groupBy(fn($f) => \Carbon\Carbon::parse($f->data_fine)->format('Y-m-d')); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dataStorico => $fasiGiorno): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <h6 class="mt-3 mb-2 fw-bold" style="color:#333;">
                    <?php echo e(\Carbon\Carbon::parse($dataStorico)->format('d/m/Y')); ?>

                    <span class="badge bg-secondary ms-1"><?php echo e($fasiGiorno->count()); ?></span>
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
                        <?php $__currentLoopData = $fasiGiorno; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $faseStorico): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><strong><?php echo e($faseStorico->ordine->commessa ?? '-'); ?></strong></td>
                            <td><?php echo e($faseStorico->ordine->cliente_nome ?? '-'); ?></td>
                            <td><?php echo e($faseStorico->ordine->cod_art ?? '-'); ?></td>
                            <td><?php echo e($faseStorico->ordine->descrizione ?? '-'); ?></td>
                            <td><?php echo e($faseStorico->ordine->qta_richiesta ?? '-'); ?></td>
                            <td>
                                <?php if($faseStorico->tipo_consegna === 'parziale'): ?>
                                    <span class="badge bg-warning text-dark">Parziale</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Totale</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e($faseStorico->data_fine ? \Carbon\Carbon::parse($faseStorico->data_fine)->format('H:i') : '-'); ?></td>
                            <td>
                                <?php $__currentLoopData = $faseStorico->operatori; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $op): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php echo e($op->nome); ?> <?php echo e($op->cognome); ?><br>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <?php else: ?>
                <p class="text-muted text-center py-3">Nessuna consegna negli ultimi 30 giorni</p>
                <?php endif; ?>
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
    <?php $__currentLoopData = $spedizioniBRT; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $numDDT => $ordiniGruppo): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        { ddt: '<?php echo e($numDDT); ?>', hash: '<?php echo e(md5($numDDT)); ?>' },
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
    fetch('<?php echo e(route("owner.trackingByDDT")); ?>', {
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

    fetch('<?php echo e(route("owner.trackingByDDT")); ?>', {
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
</script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>

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

    fetch('<?php echo e(route("owner.aggiornaCampo")); ?>', {
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
    fetch('<?php echo e(route("owner.aggiornaStato")); ?>', {
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

// === Note Consegne (readonly per owner) ===
function caricaNoteSpedizione() {
    fetch('<?php echo e(route("owner.noteSpedizione")); ?>?data=<?php echo e(now()->toDateString()); ?>', {
        headers: {'Accept': 'application/json'}
    })
    .then(r => r.json())
    .then(d => {
        document.getElementById('ownerNotaAM').value = d.contenuto_am || '';
        document.getElementById('ownerNotaPM').value = d.contenuto_pm || '';
        document.getElementById('ownerNoteSaveStatus').textContent = '';
    })
    .catch(() => {
        document.getElementById('ownerNoteSaveStatus').textContent = 'Errore caricamento';
        document.getElementById('ownerNoteSaveStatus').style.color = '#dc3545';
    });
}

function salvaNoteSped() {
    var btn = event.target;
    btn.disabled = true;
    fetch('<?php echo e(route("owner.salvaNotaSpedizione")); ?>', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            data: '<?php echo e(now()->toDateString()); ?>',
            contenuto_am: document.getElementById('ownerNotaAM').value,
            contenuto_pm: document.getElementById('ownerNotaPM').value
        })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            document.getElementById('ownerNoteSaveStatus').textContent = 'Salvato alle ' + new Date().toLocaleTimeString('it-IT');
            document.getElementById('ownerNoteSaveStatus').style.color = '#198754';
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
                <h5 class="modal-title">Note Consegne - <?php echo e(now()->format('d/m/Y')); ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div style="margin-bottom:12px;">
                    <label style="font-weight:bold; color:#198754;">AM (Mattina)</label>
                    <textarea id="ownerNotaAM" rows="4" class="form-control" style="border-color:#198754; font-size:14px;" placeholder="Mattina..."></textarea>
                </div>
                <div>
                    <label style="font-weight:bold; color:#fd7e14;">PM (Pomeriggio)</label>
                    <textarea id="ownerNotaPM" rows="4" class="form-control" style="border-color:#fd7e14; font-size:14px;" placeholder="Pomeriggio..."></textarea>
                </div>
            </div>
            <div class="modal-footer" style="justify-content:space-between;">
                <span id="ownerNoteSaveStatus" style="font-size:12px; color:#6c757d;"></span>
                <button onclick="salvaNoteSped()" class="btn btn-primary btn-sm">Salva</button>
            </div>
        </div>
    </div>
</div>
<?php if($isReadonly ?? false): ?>
<script>
// Owner readonly: rimuovi contenteditable e nascondi azioni
document.addEventListener('DOMContentLoaded', function() {
    // Rimuovi contenteditable da tutte le celle
    document.querySelectorAll('[contenteditable]').forEach(function(el) {
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
<?php endif; ?>


<div id="aiChatPanel" style="display:none; position:fixed; bottom:20px; right:20px; width:420px; height:560px; background:#fff; border-radius:16px; box-shadow:0 8px 32px rgba(0,0,0,0.25); z-index:10000; display:none; flex-direction:column; overflow:hidden; border:2px solid #7c3aed;">

    
    <div style="background:linear-gradient(135deg,#667eea,#7c3aed); color:#fff; padding:14px 18px; display:flex; align-items:center; justify-content:space-between;">
        <div style="display:flex; align-items:center; gap:10px;">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M12 2a8 8 0 0 0-8 8c0 3.4 2.1 6.3 5 7.4V20a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1v-2.6c2.9-1.1 5-4 5-7.4a8 8 0 0 0-8-8z"/></svg>
            <span style="font-weight:700; font-size:15px;">Assistente AI</span>
            <span style="font-size:10px; background:rgba(255,255,255,0.25); padding:2px 8px; border-radius:10px;">Llama 3.3</span>
        </div>
        <div style="display:flex; gap:8px;">
            <button onclick="cancellaStoricoAi()" title="Cancella storico" style="background:none; border:none; color:#fff; cursor:pointer; font-size:16px; padding:4px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
            </button>
            <button onclick="toggleAiChat()" title="Chiudi" style="background:none; border:none; color:#fff; cursor:pointer; font-size:22px; padding:4px; line-height:1;">&times;</button>
        </div>
    </div>

    
    <div id="aiChatMessages" style="flex:1; overflow-y:auto; padding:16px; display:flex; flex-direction:column; gap:12px; background:#f8f7ff;">
        <div class="ai-msg ai-msg-bot">
            <div class="ai-msg-content">Ciao! Sono l'assistente AI del MES. Puoi chiedermi informazioni sulla produzione, commesse in ritardo, carichi di lavoro e altro.<br><br>
            <em style="font-size:12px; color:#888;">Esempi:</em><br>
            <span style="font-size:12px; color:#7c3aed; cursor:pointer;" onclick="sendAiSuggestion(this.textContent)">"Quali commesse sono in ritardo?"</span><br>
            <span style="font-size:12px; color:#7c3aed; cursor:pointer;" onclick="sendAiSuggestion(this.textContent)">"Qual e' il carico di lavoro per reparto?"</span><br>
            <span style="font-size:12px; color:#7c3aed; cursor:pointer;" onclick="sendAiSuggestion(this.textContent)">"Quante fasi sono state completate oggi?"</span>
            </div>
        </div>
    </div>

    
    <div style="padding:12px 14px; border-top:1px solid #e5e3f1; background:#fff; display:flex; gap:8px;">
        <input type="text" id="aiChatInput" placeholder="Scrivi un messaggio..." style="flex:1; border:1px solid #d4d0e8; border-radius:10px; padding:10px 14px; font-size:14px; outline:none;" onkeydown="if(event.key==='Enter')sendAiMessage()">
        <button onclick="sendAiMessage()" id="aiSendBtn" style="background:linear-gradient(135deg,#667eea,#7c3aed); color:#fff; border:none; border-radius:10px; padding:10px 16px; cursor:pointer; font-weight:600; font-size:14px;">
            Invia
        </button>
    </div>
</div>


<button id="aiFloatingBtn" onclick="toggleAiChat()" style="position:fixed; bottom:20px; right:20px; width:56px; height:56px; border-radius:50%; background:linear-gradient(135deg,#667eea,#7c3aed); color:#fff; border:none; cursor:pointer; box-shadow:0 4px 16px rgba(124,58,237,0.4); z-index:9999; display:flex; align-items:center; justify-content:center; transition:transform 0.2s;">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2"><path d="M12 2a8 8 0 0 0-8 8c0 3.4 2.1 6.3 5 7.4V20a1 1 0 0 0 1 1h4a1 1 0 0 0 1-1v-2.6c2.9-1.1 5-4 5-7.4a8 8 0 0 0-8-8z"/><line x1="9" y1="22" x2="15" y2="22"/></svg>
</button>

<style>
#aiFloatingBtn:hover { transform: scale(1.1); }
.ai-msg { display:flex; }
.ai-msg-user { justify-content:flex-end; }
.ai-msg-bot { justify-content:flex-start; }
.ai-msg-content {
    max-width:85%; padding:10px 14px; border-radius:14px; font-size:13px; line-height:1.5;
    word-wrap:break-word; overflow-wrap:break-word;
}
.ai-msg-user .ai-msg-content { background:#7c3aed; color:#fff; border-bottom-right-radius:4px; }
.ai-msg-bot .ai-msg-content { background:#fff; color:#333; border:1px solid #e5e3f1; border-bottom-left-radius:4px; }
.ai-msg-bot .ai-msg-content strong { color:#7c3aed; }
.ai-msg-bot .ai-msg-content code { background:#f0eeff; padding:1px 5px; border-radius:4px; font-size:12px; }
.ai-msg-bot .ai-msg-content ul, .ai-msg-bot .ai-msg-content ol { margin:6px 0; padding-left:18px; }
.ai-msg-bot .ai-msg-content li { margin:2px 0; }
.ai-typing { display:flex; gap:4px; align-items:center; padding:8px 14px; }
.ai-typing span { width:8px; height:8px; background:#7c3aed; border-radius:50%; animation:aiBounce 1.4s infinite; }
.ai-typing span:nth-child(2) { animation-delay:0.2s; }
.ai-typing span:nth-child(3) { animation-delay:0.4s; }
@keyframes aiBounce { 0%,80%,100%{transform:translateY(0)} 40%{transform:translateY(-8px)} }
</style>

<script>
var aiChatOpen = false;
var aiStorico = [];

function toggleAiChat() {
    aiChatOpen = !aiChatOpen;
    var panel = document.getElementById('aiChatPanel');
    var btn = document.getElementById('aiFloatingBtn');
    if (aiChatOpen) {
        panel.style.display = 'flex';
        btn.style.display = 'none';
        document.getElementById('aiChatInput').focus();
    } else {
        panel.style.display = 'none';
        btn.style.display = 'flex';
    }
}

function sendAiSuggestion(text) {
    document.getElementById('aiChatInput').value = text.replace(/[""]/g, '');
    sendAiMessage();
}

function sendAiMessage() {
    var input = document.getElementById('aiChatInput');
    var msg = input.value.trim();
    if (!msg) return;

    input.value = '';
    appendAiMsg('user', msg);
    aiStorico.push({role: 'user', content: msg});

    // Mostra typing
    var typing = document.createElement('div');
    typing.className = 'ai-msg ai-msg-bot';
    typing.id = 'aiTyping';
    typing.innerHTML = '<div class="ai-typing"><span></span><span></span><span></span></div>';
    document.getElementById('aiChatMessages').appendChild(typing);
    scrollAiChat();

    // Disabilita input
    input.disabled = true;
    document.getElementById('aiSendBtn').disabled = true;

    fetch('<?php echo e(route("owner.ai.chat")); ?>', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ message: msg, storico: aiStorico.slice(-10) })
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        var t = document.getElementById('aiTyping');
        if (t) t.remove();

        if (d.success) {
            appendAiMsg('bot', d.response);
            aiStorico.push({role: 'assistant', content: d.response});
        } else {
            appendAiMsg('bot', 'Errore: ' + (d.message || 'risposta non valida'));
        }
    })
    .catch(function(e) {
        var t = document.getElementById('aiTyping');
        if (t) t.remove();
        appendAiMsg('bot', 'Errore di connessione: ' + e.message);
    })
    .finally(function() {
        input.disabled = false;
        document.getElementById('aiSendBtn').disabled = false;
        input.focus();
    });
}

function appendAiMsg(role, content) {
    var container = document.getElementById('aiChatMessages');
    var div = document.createElement('div');
    div.className = 'ai-msg ai-msg-' + (role === 'user' ? 'user' : 'bot');

    var inner = document.createElement('div');
    inner.className = 'ai-msg-content';

    if (role === 'user') {
        inner.textContent = content;
    } else {
        // Render markdown basilare
        inner.innerHTML = renderAiMarkdown(content);
    }

    div.appendChild(inner);
    container.appendChild(div);
    scrollAiChat();
}

function renderAiMarkdown(text) {
    // Markdown basilare
    var html = text
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
        .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
        .replace(/\*(.+?)\*/g, '<em>$1</em>')
        .replace(/`(.+?)`/g, '<code>$1</code>')
        .replace(/^### (.+)$/gm, '<strong style="font-size:14px;color:#7c3aed">$1</strong>')
        .replace(/^## (.+)$/gm, '<strong style="font-size:15px;color:#7c3aed">$1</strong>')
        .replace(/^# (.+)$/gm, '<strong style="font-size:16px;color:#7c3aed">$1</strong>')
        .replace(/^- (.+)$/gm, '<li>$1</li>')
        .replace(/^(\d+)\. (.+)$/gm, '<li>$2</li>')
        .replace(/\n/g, '<br>');

    // Wrap consecutive <li> in <ul>
    html = html.replace(/((?:<li>.+?<\/li><br>?)+)/g, '<ul>$1</ul>');
    html = html.replace(/<br><\/ul>/g, '</ul>');
    html = html.replace(/<ul><br>/g, '<ul>');

    return html;
}

function scrollAiChat() {
    var c = document.getElementById('aiChatMessages');
    setTimeout(function() { c.scrollTop = c.scrollHeight; }, 50);
}

function cancellaStoricoAi() {
    if (!confirm('Cancellare tutto lo storico chat AI?')) return;
    fetch('<?php echo e(route("owner.ai.cancella")); ?>', {
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    }).then(function() {
        aiStorico = [];
        var c = document.getElementById('aiChatMessages');
        c.innerHTML = '<div class="ai-msg ai-msg-bot"><div class="ai-msg-content">Storico cancellato. Come posso aiutarti?</div></div>';
    });
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Giovanni\graficanappa-gestionale\resources\views/owner/dashboard.blade.php ENDPATH**/ ?>