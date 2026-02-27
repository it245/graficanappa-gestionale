@extends('layouts.app')

@section('content')
<style>
    /* ===== FORM (no-print) ===== */
    .etichetta-form {
        max-width: 600px;
        margin: 20px auto;
        padding: 20px;
    }
    .etichetta-form .form-label { font-weight: bold; }

    /* ===== Dropdown ricerca EAN ===== */
    .ean-search-wrapper { position: relative; }
    .ean-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        max-height: 250px;
        overflow-y: auto;
        background: #fff;
        border: 1px solid #ced4da;
        border-top: none;
        border-radius: 0 0 6px 6px;
        z-index: 1000;
        display: none;
    }
    .ean-dropdown .ean-item {
        padding: 8px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f0f0f0;
        font-size: 14px;
    }
    .ean-dropdown .ean-item:hover,
    .ean-dropdown .ean-item.active {
        background: #0d6efd;
        color: #fff;
    }
    .ean-dropdown .ean-item small {
        opacity: 0.7;
    }

    /* ===== Scanner area ===== */
    .scanner-area {
        border: 2px dashed #6c757d;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
        margin-bottom: 15px;
        background: #f8f9fa;
    }
    .scanner-area.scanning {
        border-color: #0d6efd;
        background: #e7f1ff;
    }
    #scanner-reader {
        width: 100%;
        max-width: 400px;
        margin: 0 auto;
    }

    /* ===== ANTEPRIMA ETICHETTA ===== */
    .etichetta-preview {
        width: 150mm;
        height: 100mm;
        border: 1px solid #ccc;
        margin: 30px auto;
        padding: 5mm 7mm;
        font-family: 'Segoe UI', Arial, Helvetica, sans-serif;
        box-sizing: border-box;
        background: #fff;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    }

    /* --- Header: info azienda sx + logo dx --- */
    .etichetta-preview .header-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 2.5mm;
        border-bottom: 0.5pt solid #bbb;
        margin-bottom: 3mm;
    }
    .etichetta-preview .header-row .azienda-info {
        font-size: 7.5pt;
        line-height: 1.4;
        color: #444;
    }
    .etichetta-preview .header-row .azienda-info strong {
        font-size: 9pt;
        color: #222;
        letter-spacing: 0.3px;
    }
    .etichetta-preview .header-row img {
        height: 13mm;
    }

    /* --- Corpo info --- */
    .etichetta-preview .info-row {
        font-size: 10.5pt;
        line-height: 1.5;
        flex: 1;
        color: #222;
    }
    .etichetta-preview .info-row .field {
        margin-bottom: 0.8mm;
    }
    .etichetta-preview .info-row .label {
        font-weight: 600;
        color: #333;
        display: inline-block;
        min-width: 22mm;
    }

    /* --- Articolo centrato e bold --- */
    .etichetta-preview .articolo-row {
        text-align: center;
        font-weight: 700;
        font-size: 13pt;
        padding: 2mm 0;
        margin: 1.5mm 0;
        background: #f5f5f5;
        border-radius: 1.5mm;
        color: #111;
        letter-spacing: 0.2px;
    }

    /* --- Footer: QR sx + codice EAN --- */
    .etichetta-preview .bottom-row {
        display: flex;
        align-items: center;
        margin-top: auto;
        padding-top: 2mm;
        border-top: 0.5pt solid #bbb;
        gap: 4mm;
    }
    .etichetta-preview .bottom-row canvas {
        width: 22mm;
        height: 22mm;
        flex-shrink: 0;
    }
    .etichetta-preview .bottom-row .ean-info {
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
    .etichetta-preview .bottom-row .ean-label {
        font-size: 7pt;
        color: #888;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .etichetta-preview .bottom-row .ean-text {
        font-size: 10pt;
        font-weight: 600;
        letter-spacing: 1px;
        color: #222;
    }

    /* ===== PRINT ===== */
    @media print {
        body, html { margin: 0; padding: 0; }
        .etichetta-form, .no-print, .alert, .top-bar, nav, header, footer { display: none !important; }
        .container-fluid { padding: 0 !important; margin: 0 !important; }
        .etichetta-preview {
            border: none;
            margin: 0;
            width: 150mm;
            height: 100mm;
            box-shadow: none;
            page-break-after: always;
        }
        .etichetta-preview .articolo-row {
            background: #f0f0f0 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        @page {
            size: 150mm 100mm;
            margin: 0;
        }
    }
</style>

{{-- ===== FORM (nascosto in stampa) ===== --}}
<div class="etichetta-form no-print">
    <div class="d-flex align-items-center mb-3">
        <a href="{{ route('operatore.dashboard') }}" class="btn btn-outline-secondary btn-sm me-3">&larr; Dashboard</a>
        <h4 class="mb-0">Stampa Etichetta</h4>
    </div>

    <div class="mb-3">
        <label class="form-label">Cliente</label>
        <input type="text" id="campo-cliente" class="form-control" value="{{ $cliente }}">
    </div>

    @if($isItalianaConfetti)
        {{-- ITALIANA CONFETTI: dropdown ricerca EAN --}}
        <div class="mb-3">
            <label class="form-label">Articolo <small class="text-muted">(cerca per nome o codice EAN)</small></label>
            <div class="ean-search-wrapper">
                <input type="text" id="ean-search" class="form-control" placeholder="Digita per cercare articolo..."
                       autocomplete="off">
                <div class="ean-dropdown" id="ean-dropdown"></div>
            </div>
            <input type="hidden" id="campo-ean" value="">
            <input type="hidden" id="campo-articolo" value="">
            <div id="ean-selezionato" class="mt-2" style="display:none;">
                <span class="badge bg-success fs-6" id="ean-badge"></span>
                <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="clearEan()">X</button>
            </div>
        </div>
    @else
        {{-- ALTRI CLIENTI: articolo da descrizione + scansione/input EAN --}}
        <div class="mb-3">
            <label class="form-label">Articolo</label>
            <input type="text" id="campo-articolo-manuale" class="form-control" value="{{ $articoloDefault }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Codice EAN <small class="text-muted">(scansiona o digita)</small></label>
            @if($eanSalvato)
                <div class="alert alert-success py-1 px-2 mb-2" style="font-size:13px;">
                    EAN salvato: <strong>{{ $eanSalvato->codice_ean }}</strong> (precompilato)
                </div>
            @endif
            <div class="scanner-area" id="scanner-area">
                <div id="scanner-reader"></div>
                <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="btn-scan" onclick="toggleScanner()">
                    Scansiona barcode
                </button>
            </div>
            <input type="text" id="campo-ean-manuale" class="form-control mt-2"
                   value="{{ $eanSalvato->codice_ean ?? '' }}"
                   placeholder="Oppure digita il codice EAN..." autocomplete="off">
        </div>
    @endif

    <div class="row">
        <div class="col-4 mb-3">
            <label class="form-label">Pz x cassa</label>
            <input type="number" id="campo-pzcassa" class="form-control" min="1">
        </div>
        <div class="col-4 mb-3">
            <label class="form-label">Lotto</label>
            <input type="text" id="campo-lotto" class="form-control" value="{{ $lotto }}">
        </div>
        <div class="col-4 mb-3">
            <label class="form-label">Data</label>
            <input type="text" id="campo-data" class="form-control" value="{{ $data }}">
        </div>
    </div>

    <button type="button" class="btn btn-primary btn-lg w-100" onclick="stampa()">
        Stampa Etichetta
    </button>
</div>

{{-- ===== ANTEPRIMA ETICHETTA (stampabile) ===== --}}
<div class="etichetta-preview" id="etichetta">
    <div class="header-row">
        <div class="azienda-info">
            <strong>Grafica Nappa S.r.l.</strong><br>
            Via Gramsci, 19 — 81031 Aversa (CE)<br>
            Tel +39 081 8906734
        </div>
        <img src="{{ asset('images/logo_gn.png') }}" alt="Grafica Nappa">
    </div>
    <div class="info-row">
        <div class="field"><span class="label">Cliente</span> <span id="print-cliente">{{ $cliente }}</span></div>
        <div class="articolo-row" id="print-articolo"></div>
        <div class="field"><span class="label">Pz x cassa</span> <span id="print-pzcassa"></span></div>
        <div class="field"><span class="label">Lotto</span> <span id="print-lotto">{{ $lotto }}</span></div>
        <div class="field"><span class="label">Data</span> <span id="print-data">{{ $data }}</span></div>
    </div>
    <div class="bottom-row">
        <canvas id="qrcode"></canvas>
        <div class="ean-info">
            <span class="ean-label">Codice EAN</span>
            <span class="ean-text" id="print-ean"></span>
        </div>
    </div>
</div>

{{-- QRious CDN (QR code generator) --}}
<script src="https://cdn.jsdelivr.net/npm/qrious@4.0.2/dist/qrious.min.js"></script>

<script>
// ===== Aggiornamento anteprima in tempo reale =====
function aggiornaAnteprima() {
    var cliente, articolo, ean;

    @if($isItalianaConfetti)
        cliente = document.getElementById('campo-cliente').value;
        articolo = document.getElementById('campo-articolo').value;
        ean = document.getElementById('campo-ean').value;
    @else
        cliente = document.getElementById('campo-cliente').value;
        articolo = document.getElementById('campo-articolo-manuale').value;
        ean = document.getElementById('campo-ean-manuale').value;
    @endif

    var pzcassa = document.getElementById('campo-pzcassa').value;
    var lotto = document.getElementById('campo-lotto').value;
    var data = document.getElementById('campo-data').value;

    document.getElementById('print-cliente').textContent = cliente;
    document.getElementById('print-articolo').textContent = articolo;
    document.getElementById('print-pzcassa').textContent = pzcassa;
    document.getElementById('print-lotto').textContent = lotto;
    document.getElementById('print-data').textContent = data;
    document.getElementById('print-ean').textContent = ean;

    // Genera QR code se c'è un codice EAN
    var canvas = document.getElementById('qrcode');
    if (ean && ean.length >= 4) {
        new QRious({
            element: canvas,
            value: ean,
            size: 120,
            level: 'M'
        });
        canvas.style.display = '';
    } else {
        canvas.style.display = 'none';
    }
}

// ===== Bind eventi input =====
document.querySelectorAll('.etichetta-form input').forEach(function(el) {
    el.addEventListener('input', aggiornaAnteprima);
    el.addEventListener('change', aggiornaAnteprima);
});

@if($isItalianaConfetti)
// ===== Dropdown ricerca EAN (Italiana Confetti) =====
var eanData = @json($eanProdotti);
var searchInput = document.getElementById('ean-search');
var dropdown = document.getElementById('ean-dropdown');
var activeIndex = -1;

searchInput.addEventListener('input', function() {
    var q = this.value.toLowerCase().trim();
    dropdown.innerHTML = '';
    activeIndex = -1;

    if (q.length < 2) {
        dropdown.style.display = 'none';
        return;
    }

    var risultati = eanData.filter(function(item) {
        return item.articolo.toLowerCase().includes(q) || item.codice_ean.toLowerCase().includes(q);
    }).slice(0, 30);

    if (risultati.length === 0) {
        dropdown.style.display = 'none';
        return;
    }

    risultati.forEach(function(item, idx) {
        var div = document.createElement('div');
        div.className = 'ean-item';
        div.innerHTML = item.articolo + ' <small>(' + item.codice_ean + ')</small>';
        div.dataset.index = idx;
        div.addEventListener('click', function() {
            selezionaEan(item);
        });
        dropdown.appendChild(div);
    });

    dropdown.style.display = 'block';
});

searchInput.addEventListener('keydown', function(e) {
    var items = dropdown.querySelectorAll('.ean-item');
    if (e.key === 'ArrowDown') {
        e.preventDefault();
        activeIndex = Math.min(activeIndex + 1, items.length - 1);
        items.forEach(function(el, i) { el.classList.toggle('active', i === activeIndex); });
        if (items[activeIndex]) items[activeIndex].scrollIntoView({block:'nearest'});
    } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        activeIndex = Math.max(activeIndex - 1, 0);
        items.forEach(function(el, i) { el.classList.toggle('active', i === activeIndex); });
        if (items[activeIndex]) items[activeIndex].scrollIntoView({block:'nearest'});
    } else if (e.key === 'Enter') {
        e.preventDefault();
        if (activeIndex >= 0 && items[activeIndex]) {
            var filtrato = eanData.filter(function(item) {
                var q = searchInput.value.toLowerCase().trim();
                return item.articolo.toLowerCase().includes(q) || item.codice_ean.toLowerCase().includes(q);
            });
            if (filtrato[activeIndex]) selezionaEan(filtrato[activeIndex]);
        }
    } else if (e.key === 'Escape') {
        dropdown.style.display = 'none';
    }
});

// Chiudi dropdown cliccando fuori
document.addEventListener('click', function(e) {
    if (!e.target.closest('.ean-search-wrapper')) {
        dropdown.style.display = 'none';
    }
});

function selezionaEan(item) {
    document.getElementById('campo-ean').value = item.codice_ean;
    document.getElementById('campo-articolo').value = item.articolo;
    searchInput.value = item.articolo;
    dropdown.style.display = 'none';

    document.getElementById('ean-selezionato').style.display = '';
    document.getElementById('ean-badge').textContent = item.articolo + ' — ' + item.codice_ean;

    aggiornaAnteprima();
}

function clearEan() {
    document.getElementById('campo-ean').value = '';
    document.getElementById('campo-articolo').value = '';
    searchInput.value = '';
    document.getElementById('ean-selezionato').style.display = 'none';
    aggiornaAnteprima();
}

@else
// ===== Scanner barcode (altri clienti) =====
var html5QrCode = null;
var scannerAttivo = false;

function toggleScanner() {
    var area = document.getElementById('scanner-area');
    var btn = document.getElementById('btn-scan');

    if (scannerAttivo) {
        stopScanner();
        return;
    }

    area.classList.add('scanning');
    btn.textContent = 'Chiudi scanner';
    scannerAttivo = true;

    html5QrCode = new Html5Qrcode("scanner-reader");
    html5QrCode.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: { width: 300, height: 100 } },
        function(decodedText) {
            document.getElementById('campo-ean-manuale').value = decodedText;
            stopScanner();
            aggiornaAnteprima();
        },
        function(errorMessage) {
            // Ignora errori di scansione continua
        }
    ).catch(function(err) {
        alert("Impossibile accedere alla fotocamera: " + err);
        stopScanner();
    });
}

function stopScanner() {
    var area = document.getElementById('scanner-area');
    var btn = document.getElementById('btn-scan');

    if (html5QrCode) {
        html5QrCode.stop().catch(function(){});
        html5QrCode.clear();
        html5QrCode = null;
    }
    scannerAttivo = false;
    area.classList.remove('scanning');
    btn.textContent = 'Scansiona barcode';
}
@endif

// ===== Stampa =====
function stampa() {
    aggiornaAnteprima();

    @if($isItalianaConfetti)
    var ean = document.getElementById('campo-ean').value;
    var articolo = document.getElementById('campo-articolo').value;
    @else
    var ean = document.getElementById('campo-ean-manuale').value;
    var articolo = document.getElementById('campo-articolo-manuale').value;
    @endif

    var pzcassa = document.getElementById('campo-pzcassa').value;

    if (!ean) {
        alert('Inserisci o scansiona il codice EAN.');
        return;
    }
    if (!pzcassa) {
        alert('Inserisci i pezzi per cassa.');
        return;
    }

    @if(!$isItalianaConfetti)
    // Salva EAN nel database per le prossime volte
    if (articolo && ean) {
        fetch("{{ route('operatore.etichetta.salvaEan') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ articolo: articolo, codice_ean: ean })
        });
    }
    @endif

    window.print();
}

// Aggiorna anteprima iniziale
aggiornaAnteprima();
</script>
@endsection
