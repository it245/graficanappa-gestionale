@extends('layouts.app')

@section('content')
<style>
    /* ===== FORM (no-print) ===== */
    .etichetta-form {
        max-width: 600px;
        margin: 20px auto 20px 40px;
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
        margin: 30px auto 30px 40px;
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
        padding-bottom: 2mm;
        margin-bottom: 3mm;
    }
    .etichetta-preview .header-row .azienda-info {
        font-size: 9pt;
        font-weight: 700;
        line-height: 1.4;
        color: #111;
    }
    .etichetta-preview .header-row .azienda-info strong {
        font-size: 11pt;
        color: #000;
        letter-spacing: 0.3px;
    }
    .etichetta-preview .header-row img {
        height: 18mm;
        image-rendering: -webkit-optimize-contrast;
        image-rendering: crisp-edges;
    }

    /* --- Corpo info --- */
    .etichetta-preview .info-top {
        font-size: 14pt;
        font-weight: 700;
        line-height: 1.5;
        color: #111;
    }
    .etichetta-preview .info-top .field {
        margin-bottom: 0.8mm;
    }
    .etichetta-preview .info-top .label {
        display: inline-block;
        min-width: 22mm;
    }

    /* --- Articolo bold --- */
    .etichetta-preview .articolo-row {
        text-align: left;
        font-family: 'Vani', 'Segoe UI', Arial, sans-serif;
        font-weight: 700;
        font-size: 24pt;
        padding: 2mm 0;
        margin: 1.5mm 0;
        color: #000;
        letter-spacing: 0.2px;
        word-wrap: break-word;
        text-transform: uppercase;
    }

    /* --- Zona bassa: campi sx + QR dx --- */
    .etichetta-preview .info-bottom {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex: 1;
        margin-top: 1mm;
    }
    .etichetta-preview .info-bottom .fields-left {
        font-size: 11pt;
        font-weight: 700;
        line-height: 1.6;
        color: #111;
    }
    .etichetta-preview .info-bottom .fields-left .label {
        display: inline-block;
        min-width: 22mm;
    }
    .etichetta-preview .info-bottom .qr-right {
        display: flex;
        flex-direction: column;
        align-items: center;
        flex-shrink: 0;
    }
    .etichetta-preview .info-bottom .qr-right canvas {
        width: 22mm;
        height: 22mm;
        image-rendering: pixelated;
    }
    .etichetta-preview .info-bottom .qr-right .ean-text {
        font-size: 8.5pt;
        font-weight: 600;
        letter-spacing: 0.8px;
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
            background: none !important;
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
        @if(request('from') === 'etichette')
            <a href="{{ route('etichette.lista') }}" class="btn btn-outline-secondary btn-sm me-2">&larr; Lista Etichette</a>
        @else
            <a href="{{ route('operatore.dashboard') }}" class="btn btn-outline-secondary btn-sm me-2">&larr; Dashboard</a>
            <a href="/commesse/{{ $ordine->commessa }}?op_token={{ request('op_token') }}" class="btn btn-outline-primary btn-sm me-3">&larr; Commessa</a>
        @endif
        <h4 class="mb-0">Stampa Etichetta</h4>
    </div>

    @if(!$isSimpleLabel)
    <div class="mb-3">
        <label class="form-label">Cliente</label>
        <input type="text" id="campo-cliente" class="form-control" value="{{ $cliente }}">
    </div>
    @if($ordine->descrizione)
    <div class="alert alert-light py-2 px-3 mb-3" style="font-size:13px; border:1px solid #dee2e6;">
        <strong>Descrizione ordine:</strong> {{ $ordine->descrizione }}
    </div>
    @endif
    @endif

    @if($isSimpleLabel)
        {{-- CLIENTI SEMPLICI: cliente + descrizione + campi base --}}
        <div class="alert alert-info py-2 px-3 mb-3" style="font-size:13px;">
            Etichetta semplificata: Cliente, Descrizione, Pz x cassa, Lotto, Data
        </div>
        <div class="mb-3">
            <label class="form-label">Cliente</label>
            <input type="text" id="campo-cliente-simple" class="form-control" value="{{ $cliente }}">
        </div>
        <div class="mb-3">
            <label class="form-label">Descrizione articolo</label>
            <input type="text" id="campo-descrizione-simple" class="form-control" value="{{ $ordine->descrizione ?? '' }}">
        </div>
    @elseif($isItalianaConfetti)
        {{-- ITALIANA CONFETTI: dropdown ricerca EAN + opzioni extra --}}
        <div class="mb-3">
            <div class="form-check mb-2">
                <input type="checkbox" class="form-check-input" id="noEanCheck" onchange="toggleNoEan()">
                <label class="form-check-label" for="noEanCheck" style="font-weight:600;">Senza codice EAN</label>
            </div>

            {{-- Sezione senza EAN: descrizione editabile --}}
            <div id="no-ean-section" style="display:none;">
                <label class="form-label">Descrizione articolo</label>
                <input type="text" id="campo-articolo-noean" class="form-control" value="{{ $articoloDefault }}" placeholder="Scrivi la descrizione...">
            </div>

            <div id="ean-section">
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

                {{-- Inserimento manuale EAN nuovo --}}
                <div class="mt-3 p-2 border rounded" style="background:#fff8e1;">
                    <label class="form-label mb-1" style="font-size:13px; font-weight:600;">📝 Oppure inserisci nuovo EAN</label>
                    <div class="row g-2">
                        <div class="col-5">
                            <input type="text" id="nuovo-articolo" class="form-control form-control-sm" placeholder="Nome articolo">
                        </div>
                        <div class="col-5">
                            <input type="text" id="nuovo-ean" class="form-control form-control-sm" placeholder="Codice EAN">
                        </div>
                        <div class="col-2">
                            <button type="button" class="btn btn-sm btn-warning w-100" onclick="salvaEusaNuovoEan()">Usa</button>
                        </div>
                    </div>
                    <small class="text-muted">Il nuovo EAN verrà salvato per usi futuri.</small>
                </div>
            </div>
        </div>
    @elseif($isTifataPlastica ?? false)
        {{-- TIFATA PLASTICA: solo articolo, senza EAN --}}
        <div class="mb-3">
            <label class="form-label">Articolo</label>
            <input type="text" id="campo-articolo-manuale" class="form-control" value="{{ $articoloDefault }}">
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
<div class="etichetta-preview" id="etichetta" @if($isSimpleLabel) style="justify-content: center;" @endif>
    @if(!$isSimpleLabel)
    <div class="header-row">
        <div class="azienda-info">
            <strong>Grafica Nappa S.r.l.</strong><br>
            Via Gramsci, 19 — 81031 Aversa (CE)<br>
            Tel +39 081 8906734
        </div>
        <img src="{{ asset('images/logo_graficanappa.png') }}" alt="Grafica Nappa">
    </div>
    <div class="info-top">
        <div class="field"><span class="label">Cliente:</span> <span id="print-cliente">{{ $cliente }}</span></div>
    </div>
    <div class="articolo-row" id="print-articolo"></div>
    @endif
    @if($isSimpleLabel)
    <div style="font-size: 18pt; font-weight: 700; text-align: left;">
        <span id="print-cliente-simple">{{ $cliente }}</span>
    </div>
    <div id="print-descrizione-simple" style="font-size: 28pt; font-weight: 700; text-align: center; text-transform: uppercase; flex: 1; display: flex; align-items: center; justify-content: center;">{{ $ordine->descrizione ?? '' }}</div>
    @endif
    @if($isTifataPlastica ?? false)
    {{-- TIFATA PLASTICA: descrizione sopra, lotto/qta/data affiancati, no EAN/DataMatrix --}}
    <div style="margin-top: 18mm; margin-bottom: 8mm; font-size: 20pt; font-weight: bold; text-align: center;" id="print-descrizione-tifata">{{ $ordine->descrizione ?? '' }}</div>
    <div style="display: flex; justify-content: space-between; align-items: center; gap: 3mm; font-size: 13pt; font-weight: bold; white-space: nowrap;">
        <div><span class="label">Lotto:</span> <span id="print-lotto">{{ $lotto }}</span></div>
        <div><span class="label">Pz x cassa:</span> <span id="print-pzcassa"></span></div>
        <div><span class="label">Data:</span> <span id="print-data">{{ $data }}</span></div>
    </div>
    @else
    <div class="info-bottom" @if($isSimpleLabel) style="flex: 0; width: 100%; font-size: 16pt; padding-top: 2mm;" @endif>
        <div class="fields-left" @if($isSimpleLabel) style="font-size: 16pt; line-height: 2;" @endif>
            <div><span class="label">Pz x cassa:</span> <span id="print-pzcassa"></span></div>
            <div><span class="label">Lotto:</span> <span id="print-lotto">{{ $lotto }}</span></div>
            <div><span class="label">Data:</span> <span id="print-data">{{ $data }}</span></div>
        </div>
        @if(!$isSimpleLabel)
        <div class="qr-right">
            <canvas id="datamatrix" style="display:none;"></canvas>
            <img id="datamatrix-img" style="width:30mm; height:30mm; image-rendering:pixelated;" />
            <span class="ean-text" id="print-ean" style="font-size:4.5pt; max-width:30mm; word-break:break-all; text-align:center; line-height:1.2;"></span>
        </div>
        @endif
    </div>
    @endif
{{-- ===== PANNELLO LATERALE: Card gestione fase ===== --}}
@if(($fasiOperatore ?? collect())->isNotEmpty())
<div class="no-print" id="pannello-fase" style="position:fixed; top:10px; right:10px; width:520px; max-height:calc(100vh - 20px); overflow-y:auto; z-index:50;">
    <style>
    .azioni-btn-et { display:flex; gap:14px; justify-content:center; padding:18px 0; }
    .azioni-btn-et label {
        display:inline-flex; justify-content:center; align-items:center;
        width:110px; height:110px; border-radius:50%; color:#fff;
        font-weight:bold; font-size:15px; cursor:pointer; user-select:none;
        box-shadow: 0 3px 8px rgba(0,0,0,0.2); transition: transform 0.15s, box-shadow 0.15s;
    }
    .azioni-btn-et label:hover { transform:scale(1.08); box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
    .azioni-btn-et label:active { transform:scale(0.95); }
    .azioni-btn-et .badge-avvia { background: linear-gradient(135deg, #28a745, #20c040); }
    .azioni-btn-et .badge-pausa { background: linear-gradient(135deg, #ffc107, #ffb300); color:#333; }
    .azioni-btn-et .badge-termina { background: linear-gradient(135deg, #dc3545, #c82333); }
    .azioni-btn-et input[type="checkbox"] { display:none; }
    .azioni-btn-et input[type="checkbox"]:checked + label { opacity:0.7; box-shadow:inset 0 0 3px rgba(0,0,0,0.5); transform:scale(0.95); }
    @keyframes lampeggio-et { 0%,100%{opacity:1;} 50%{opacity:0.4;} }
    .azioni-btn-et .badge-avvia.lampeggia { animation:lampeggio-et 1s ease-in-out infinite; }
    .card-fase-et { border-radius:14px; overflow:hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.13); border:none; }
    .card-fase-et .card-header { border-radius:0; border-bottom:none; padding:14px 20px; }
    .card-fase-et .card-body { padding:16px 20px; }
    </style>

    {{-- Card per ogni fase dell'operatore --}}
    @foreach($fasiOperatore as $fase)
    @php $badgeBg = [0=>'bg-secondary',1=>'bg-info',2=>'bg-warning text-dark',3=>'bg-success',5=>'bg-purple text-white']; @endphp
    <div class="card card-fase-et mb-3">
        <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between">
            <div>
                <strong style="font-size:20px;">{{ $fase->faseCatalogo->nome_display ?? '-' }}</strong>
                <span class="badge {{ $badgeBg[$fase->stato] ?? 'bg-dark' }} ms-2" style="font-size:16px;" id="badge-fase-{{ $fase->id }}">{{ $fase->stato }}</span>
            </div>
            <div id="operatori-fase-{{ $fase->id }}">
                @foreach($fase->operatori as $op)
                    <small class="badge bg-light text-dark">{{ $op->nome }}</small>
                @endforeach
            </div>
        </div>

        {{-- Descrizione --}}
        <div class="card-body py-3" style="background:#f0f4ff; font-size:16px;">
            {{ $fase->ordine->descrizione ?? $ordine->descrizione ?? '-' }}
        </div>

        {{-- Note fasi successive --}}
        <div class="card-body">
            <label class="fw-bold" style="font-size:16px;">Info per fasi successive</label>
            @if(!empty($righeFS))
                <div class="mb-2" style="max-height:150px; overflow-y:auto; background:#f8f9fa; border-radius:6px; padding:10px; font-size:14px;">
                    @foreach($righeFS as $riga)
                        <div class="mb-1">
                            <small class="text-muted">{{ $riga['data'] ?? '' }}</small>
                            <strong>{{ $riga['reparto'] ?? '' }} - {{ $riga['nome'] ?? '' }}:</strong>
                            {{ $riga['testo'] ?? '' }}
                        </div>
                    @endforeach
                </div>
            @else
                <div class="mb-2 text-muted" style="font-size:14px;">Nessuna nota</div>
            @endif
            <div class="d-flex gap-2">
                <textarea id="nuova-nota-fs-{{ $fase->id }}" class="form-control" rows="1"
                          placeholder="Scrivi una nota..." style="font-size:14px;"></textarea>
                <button type="button" class="btn btn-outline-primary" style="white-space:nowrap; font-size:14px;"
                        onclick="inviaNotaFS({{ $ordine->id }}, {{ $fase->id }})">Invia</button>
            </div>
        </div>

        {{-- Dati Prinect (solo stampa offset) --}}
        @if(strtolower(optional(optional($fase->faseCatalogo)->reparto)->nome ?? '') === 'stampa offset')
        <div class="card-body pt-0">
            <div class="d-flex align-items-center gap-3 flex-wrap" style="font-size:16px;">
                <div><strong>Fogli Buoni:</strong>
                    <span class="badge bg-success" style="font-size:15px; padding:5px 12px;">{{ $fase->fogli_buoni ?? 0 }}</span>
                </div>
                <div><strong>Scarti Prinect:</strong>
                    <span class="badge bg-secondary" style="font-size:15px; padding:5px 12px;">{{ $fase->fogli_scarto ?? 0 }}</span>
                </div>
                <div><strong>Scarti Reali:</strong>
                    <input type="number" min="0" style="width:90px; padding:5px 8px; font-size:16px; border:1px solid #ced4da; border-radius:4px;"
                           value="{{ $fase->scarti ?? '' }}"
                           onchange="salvaScartiEtichetta({{ $fase->id }}, this.value)"
                           onkeydown="if(event.key==='Enter'){this.blur();}">
                </div>
            </div>
        </div>
        @endif

        {{-- Pulsanti Avvia / Pausa / Termina --}}
        <div class="azioni-btn-et">
            <input type="checkbox" id="avvia-{{ $fase->id }}" onchange="aggiornaStatoEt({{ $fase->id }}, 'avvia', this.checked)">
            <label for="avvia-{{ $fase->id }}" class="badge-avvia{{ $fase->stato == 2 ? ' lampeggia' : '' }}">{{ $fase->stato == 2 ? 'Avviato' : 'Avvia' }}</label>

            <input type="checkbox" id="pausa-{{ $fase->id }}" onchange="gestisciPausaEt({{ $fase->id }}, this.checked)">
            <label for="pausa-{{ $fase->id }}" class="badge-pausa">Pausa</label>

            <input type="checkbox" id="termina-{{ $fase->id }}"
                   data-qta-fase="{{ $ordine->qta_richiesta ?? 0 }}"
                   data-fogli-buoni="{{ $fase->fogli_buoni ?? 0 }}"
                   data-fogli-scarto="{{ $fase->fogli_scarto ?? 0 }}"
                   data-qta-prod="{{ $fase->qta_prod ?? 0 }}"
                   onchange="aggiornaStatoEt({{ $fase->id }}, 'termina', this.checked)">
            <label for="termina-{{ $fase->id }}" class="badge-termina">Termina</label>
        </div>
    </div>
    @endforeach
</div>

<!-- Modal Termina -->
<div class="modal fade" id="modalTermina" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Termina Fase</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="terminaFaseId">
                <div class="mb-3">
                    <label class="form-label fw-bold">Qta prodotta <span class="text-danger">*</span></label>
                    <input type="number" id="terminaQtaProdotta" class="form-control" min="0" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Scarti</label>
                    <input type="number" id="terminaScarti" class="form-control" min="0" value="0">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-danger fw-bold" onclick="confermaTerminaEt()">Conferma e Termina</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pausa -->
<div class="modal fade" id="modalPausa" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">Pausa Fase</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="pausaFaseId">
                <div class="mb-3">
                    <label class="form-label fw-bold">Motivo della pausa</label>
                    <select id="pausaMotivoSelect" class="form-select" onchange="document.getElementById('pausaAltroWrap').style.display=this.value==='__altro__'?'':'none'">
                        <option value="">-- Seleziona --</option>
                        <option>Attesa materiale</option>
                        <option>Problema macchina</option>
                        <option>Pranzo</option>
                        <option>Fine turno</option>
                        <option value="__altro__">Altro...</option>
                    </select>
                </div>
                <div class="mb-3" id="pausaAltroWrap" style="display:none;">
                    <label class="form-label fw-bold">Specifica motivo</label>
                    <input type="text" id="pausaAltroInput" class="form-control" placeholder="Scrivi il motivo...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-warning fw-bold" onclick="confermaPausaEt()">Conferma Pausa</button>
            </div>
        </div>
    </div>
</div>
</div>{{-- fine pannello fase --}}
@endif

{{-- bwip-js CDN (barcode/DataMatrix generator) --}}
<script src="https://cdn.jsdelivr.net/npm/bwip-js@4.5.1/dist/bwip-js-min.js"></script>

<script>
// ===== Aggiornamento anteprima in tempo reale =====
function aggiornaAnteprima() {
    var cliente, articolo, ean;

    @if($isSimpleLabel)
        cliente = document.getElementById('campo-cliente-simple').value;
        articolo = '';
        ean = '';
        document.getElementById('print-cliente-simple').textContent = cliente;
        var desc = document.getElementById('campo-descrizione-simple').value;
        var descEl = document.getElementById('print-descrizione-simple');
        descEl.textContent = desc;
        // Auto-ridimensiona descrizione
        descEl.style.fontSize = '28pt';
        var etichetta = document.getElementById('etichetta');
        var sizes = [28, 24, 22, 20, 18, 16, 14, 12];
        for (var i = 0; i < sizes.length; i++) {
            descEl.style.fontSize = sizes[i] + 'pt';
            if (etichetta.scrollHeight <= etichetta.clientHeight) break;
        }
    @elseif($isItalianaConfetti)
        cliente = document.getElementById('campo-cliente').value;
        var noEan = document.getElementById('noEanCheck').checked;
        if (noEan) {
            articolo = document.getElementById('campo-articolo-noean').value;
            ean = '';
        } else {
            articolo = document.getElementById('campo-articolo').value;
            ean = document.getElementById('campo-ean').value;
        }
    @elseif($isTifataPlastica ?? false)
        cliente = document.getElementById('campo-cliente').value;
        articolo = document.getElementById('campo-articolo-manuale').value;
        ean = '';
    @else
        cliente = document.getElementById('campo-cliente').value;
        articolo = document.getElementById('campo-articolo-manuale').value;
        ean = document.getElementById('campo-ean-manuale').value;
    @endif

    var pzcassa = document.getElementById('campo-pzcassa').value;
    var lotto = document.getElementById('campo-lotto').value;
    var data = document.getElementById('campo-data').value;

    @if($isTifataPlastica ?? false)
    // Tifata: articolo aggiorna la descrizione nell'anteprima + auto-resize
    var descTifata = document.getElementById('campo-articolo-manuale').value;
    var descElTifata = document.getElementById('print-descrizione-tifata');
    descElTifata.textContent = descTifata;
    var etichettaTifata = document.getElementById('etichetta');
    var sizesTifata = [20, 18, 16, 14, 12, 11, 10, 9];
    for (var st = 0; st < sizesTifata.length; st++) {
        descElTifata.style.fontSize = sizesTifata[st] + 'pt';
        if (etichettaTifata.scrollHeight <= etichettaTifata.clientHeight) break;
    }
    @elseif(!$isSimpleLabel)
    document.getElementById('print-cliente').textContent = cliente;
    document.getElementById('print-articolo').textContent = articolo;
    // Auto-ridimensiona articolo se troppo lungo
    var articoloEl = document.getElementById('print-articolo');
    var etichetta = document.getElementById('etichetta');
    articoloEl.style.fontSize = '24pt';
    var sizes = [24, 22, 20, 18, 16, 14, 12];
    for (var i = 0; i < sizes.length; i++) {
        articoloEl.style.fontSize = sizes[i] + 'pt';
        if (etichetta.scrollHeight <= etichetta.clientHeight) break;
    }

    // Genera DataMatrix GS1 con EAN + Qta + Lotto (un solo codice scansionabile)
    var canvas = document.getElementById('datamatrix');
    var dmImg = document.getElementById('datamatrix-img');
    if (ean && ean.length >= 4) {
        // GTIN: mantieni la A nel codice EAN, padding a 14 caratteri
        var gtin = ean.trim();
        while (gtin.length < 14) gtin = '0' + gtin;
        if (gtin.length > 14) gtin = gtin.substring(0, 14);

        // AI(30) = quantità zero-paddata a 8 cifre (come BarTender)
        var qty = pzcassa ? String(parseInt(pzcassa, 10)).padStart(8, '0') : '';
        // Lotto senza trattino
        var lottoClean = lotto ? lotto.replace(/-/g, '') : '';

        // Costruisci stringa dati come testo puro (come BarTender)
        var plainData = '01' + gtin;
        if (qty) plainData += '30' + qty;
        if (lottoClean) plainData += '10' + lottoClean;

        try {
            bwipjs.toCanvas(canvas, {
                bcid: 'datamatrix',
                text: plainData,
                scale: 10,
                padding: 4,
            });
            dmImg.src = canvas.toDataURL('image/png');
            dmImg.style.display = '';
            document.getElementById('print-ean').textContent = plainData;
        } catch(e) {
            console.error('DataMatrix error:', e);
            dmImg.style.display = 'none';
        }
    } else {
        dmImg.style.display = 'none';
        document.getElementById('print-ean').textContent = '';
    }
    @endif

    document.getElementById('print-pzcassa').textContent = pzcassa;
    document.getElementById('print-lotto').textContent = lotto;
    document.getElementById('print-data').textContent = data;
}

// ===== Bind eventi input =====
document.querySelectorAll('.etichetta-form input').forEach(function(el) {
    el.addEventListener('input', aggiornaAnteprima);
    el.addEventListener('change', aggiornaAnteprima);
});

// Toggle "Senza codice EAN"
function toggleNoEan() {
    var checked = document.getElementById('noEanCheck').checked;
    var section = document.getElementById('ean-section');
    var noEanSection = document.getElementById('no-ean-section');
    if (section) section.style.display = checked ? 'none' : '';
    if (noEanSection) noEanSection.style.display = checked ? '' : 'none';
    if (checked) {
        document.getElementById('campo-ean').value = '';
        document.getElementById('campo-articolo').value = '';
    }
}

// Salva nuovo EAN e usalo
function salvaEusaNuovoEan() {
    var articolo = document.getElementById('nuovo-articolo').value.trim();
    var ean = document.getElementById('nuovo-ean').value.trim();
    if (!articolo || !ean) { alert('Inserisci articolo e codice EAN'); return; }

    // Salva nel DB
    fetch('{{ route("operatore.etichetta.salvaEan") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({articolo: articolo, codice_ean: ean})
    }).then(r => r.json()).then(d => {
        if (d.ok) {
            // Usa immediatamente
            document.getElementById('campo-ean').value = ean;
            document.getElementById('campo-articolo').value = articolo;
            document.getElementById('ean-selezionato').style.display = '';
            document.getElementById('ean-badge').textContent = articolo + ' — ' + ean;
            document.getElementById('nuovo-articolo').value = '';
            document.getElementById('nuovo-ean').value = '';
            // Aggiungi alla lista locale
            if (typeof eanData !== 'undefined') {
                eanData.push({articolo: articolo, codice_ean: ean});
            }
            alert('EAN salvato e selezionato!');
        } else {
            alert('Errore: ' + (d.msg || 'salvataggio fallito'));
        }
    }).catch(() => alert('Errore di connessione'));
}

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

    var pzcassa = document.getElementById('campo-pzcassa').value;

    @if($isSimpleLabel)
    if (!pzcassa) {
        alert('Inserisci i pezzi per cassa.');
        return;
    }
    @else
    @if($isItalianaConfetti)
    var noEanChecked = document.getElementById('noEanCheck').checked;
    var ean = noEanChecked ? '' : document.getElementById('campo-ean').value;
    var articolo = noEanChecked ? document.getElementById('campo-articolo-noean').value : document.getElementById('campo-articolo').value;
    @elseif($isTifataPlastica ?? false)
    var ean = '';
    var articolo = document.getElementById('campo-articolo-manuale').value;
    @else
    var ean = document.getElementById('campo-ean-manuale').value;
    var articolo = document.getElementById('campo-articolo-manuale').value;
    @endif

    @if($isItalianaConfetti)
    if (!noEanChecked && !ean) {
        alert('Inserisci o scansiona il codice EAN.');
        return;
    }
    if (noEanChecked && !articolo) {
        alert('Inserisci la descrizione articolo.');
        return;
    }
    @endif
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
    @endif

    window.print();
}

// Aggiorna anteprima iniziale
aggiornaAnteprima();

// ===== Gestione fase (Avvia/Pausa/Termina/Note/Scarti) =====
@if($faseOperatore ?? false)
function csrfTokenEt() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
}

function updateBadgeEt(faseId, stato) {
    var badge = document.getElementById('badge-fase-'+faseId);
    if (badge) { badge.textContent = stato; badge.className = 'badge ms-2 fs-5 ' + ({0:'bg-secondary',1:'bg-info',2:'bg-warning text-dark',3:'bg-success',5:'bg-purple text-white'}[stato] || 'bg-dark'); }
}

function updateOperatoriEt(faseId, operatori) {
    var c = document.getElementById('operatori-fase-'+faseId);
    if (!c || !operatori) return;
    c.innerHTML = operatori.map(function(op) { return '<small class="badge bg-light text-dark">' + op.nome + ' (' + op.data_inizio + ')</small>'; }).join(' ');
}

function aggiornaStatoEt(faseId, azione, checked) {
    if (!checked) return;
    if (azione === 'termina') {
        var cb = document.getElementById('termina-'+faseId);
        var fogliBuoni = parseInt(cb?.getAttribute('data-fogli-buoni') || 0) || 0;
        var fogliScarto = parseInt(cb?.getAttribute('data-fogli-scarto') || 0) || 0;
        var qtaProd = parseInt(cb?.getAttribute('data-qta-prod') || 0) || 0;
        document.getElementById('terminaFaseId').value = faseId;
        document.getElementById('terminaQtaProdotta').value = fogliBuoni > 0 ? fogliBuoni : (qtaProd > 0 ? qtaProd : '');
        document.getElementById('terminaScarti').value = fogliScarto > 0 ? fogliScarto : 0;
        new bootstrap.Modal(document.getElementById('modalTermina')).show();
        return;
    }
    fetch('{{ route("produzione.avvia") }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': csrfTokenEt(), 'Content-Type': 'application/json'},
        body: JSON.stringify({fase_id: faseId})
    }).then(r => r.json()).then(data => {
        if (data.success) { updateBadgeEt(faseId, 2); if (data.operatori) updateOperatoriEt(faseId, data.operatori); }
        else alert('Errore: ' + (data.messaggio || 'operazione fallita'));
    });
}

function confermaTerminaEt() {
    var faseId = document.getElementById('terminaFaseId').value;
    var qta = document.getElementById('terminaQtaProdotta').value;
    var scarti = document.getElementById('terminaScarti').value;
    if (qta === '' || parseInt(qta) <= 0) { alert('Inserire la quantità prodotta'); return; }
    bootstrap.Modal.getInstance(document.getElementById('modalTermina')).hide();
    fetch('{{ route("produzione.termina") }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': csrfTokenEt(), 'Content-Type': 'application/json'},
        body: JSON.stringify({fase_id: faseId, qta_prodotta: parseInt(qta), scarti: parseInt(scarti) || 0})
    }).then(r => r.json()).then(data => {
        if (data.success) { updateBadgeEt(faseId, 3); }
        else { alert('Errore: ' + (data.messaggio || 'operazione fallita')); document.getElementById('termina-'+faseId).checked = false; }
    });
}

document.getElementById('modalTermina').addEventListener('hidden.bs.modal', function() {
    var faseId = document.getElementById('terminaFaseId').value;
    var cb = document.getElementById('termina-'+faseId);
    if (cb) cb.checked = false;
});

function gestisciPausaEt(faseId, checked) {
    if (!checked) return;
    document.getElementById('pausaFaseId').value = faseId;
    document.getElementById('pausaMotivoSelect').value = '';
    document.getElementById('pausaAltroInput').value = '';
    document.getElementById('pausaAltroWrap').style.display = 'none';
    new bootstrap.Modal(document.getElementById('modalPausa')).show();
}

document.getElementById('modalPausa').addEventListener('hidden.bs.modal', function() {
    var faseId = document.getElementById('pausaFaseId').value;
    var cb = document.getElementById('pausa-'+faseId);
    if (cb) cb.checked = false;
});

function confermaPausaEt() {
    var sel = document.getElementById('pausaMotivoSelect').value;
    var motivo = sel === '__altro__' ? (document.getElementById('pausaAltroInput').value.trim() || 'Altro') : sel;
    if (!motivo) { alert('Seleziona un motivo'); return; }
    var faseId = document.getElementById('pausaFaseId').value;
    bootstrap.Modal.getInstance(document.getElementById('modalPausa')).hide();
    fetch('{{ route("produzione.pausa") }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': csrfTokenEt(), 'Content-Type': 'application/json'},
        body: JSON.stringify({fase_id: faseId, motivo: motivo})
    }).then(r => r.json()).then(data => {
        if (data.success) { updateBadgeEt(faseId, data.nuovo_stato); }
        else alert('Errore: ' + (data.messaggio || 'operazione fallita'));
    });
}

function salvaScartiEtichetta(faseId, valore) {
    fetch('{{ route("produzione.aggiornaCampo") }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': csrfTokenEt(), 'X-Op-Token': new URLSearchParams(window.location.search).get('op_token') || '', 'Content-Type': 'application/json', 'Accept': 'application/json'},
        body: JSON.stringify({fase_id: faseId, campo: 'scarti', valore: valore})
    }).then(function(r) {
        if (r.ok) { var input = document.querySelector('input[onchange*="salvaScartiEtichetta('+faseId+'"]'); if (input) { input.style.borderColor='#28a745'; setTimeout(function(){input.style.borderColor='#ced4da';},1500); } }
        else alert('Errore nel salvataggio');
    });
}

function inviaNotaFS(ordineId, faseId) {
    var textarea = document.getElementById('nuova-nota-fs-'+faseId);
    var testo = textarea.value.trim();
    if (!testo) { alert('Scrivi una nota prima di inviare'); return; }

    var noteEsistenti = @json($righeFS ?? []);
    noteEsistenti.push({
        data: new Date().toLocaleString('it-IT'),
        reparto: @json($operatore?->reparti?->pluck('nome')->first() ?? 'N/D'),
        nome: @json(trim(($operatore->nome ?? '') . ' ' . ($operatore->cognome ?? ''))),
        testo: testo
    });

    fetch('{{ route("produzione.aggiornaOrdineCampo") }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': csrfTokenEt(), 'Content-Type': 'application/json'},
        body: JSON.stringify({ordine_id: ordineId, campo: 'note_fasi_successive', valore: JSON.stringify(noteEsistenti)})
    }).then(r => r.json()).then(data => {
        if (data.success) location.reload();
        else alert('Errore: ' + (data.messaggio || JSON.stringify(data.errors)));
    });
}
@endif
</script>
@endsection
