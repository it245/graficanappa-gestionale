@extends('layouts.app')

@section('content')
<div class="container-fluid px-3">
<style>
    h2 { margin: 10px 0; }
    .btn-back {
        background: #333;
        color: #fff;
        border: none;
        padding: 6px 16px;
        border-radius: 4px;
        font-size: 13px;
        cursor: pointer;
        text-decoration: none;
    }
    .btn-back:hover { background: #555; color: #fff; }
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        table-layout: fixed;
    }
    thead th {
        background: #000;
        color: #fff;
        padding: 6px 8px;
        border: 1px solid #dee2e6;
        font-size: 12px;
    }
    td {
        border: 1px solid #dee2e6;
        padding: 4px 8px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: normal;
        max-height: 3.9em;
        line-height: 1.3;
    }
    tr:hover td { background: rgba(0,0,0,0.03); }
    td[contenteditable] {
        user-select: text;
        cursor: text;
    }
    td[contenteditable]:focus {
        outline: 2px solid #0d6efd;
        outline-offset: -2px;
        background: #f0f7ff !important;
    }
    .stato-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: bold;
        cursor: pointer;
    }
    /* Tabella fissa: 15 colonne, larghezze esplicite, somma ~1515px */
    table { table-layout: fixed; }
    th:nth-child(1), td:nth-child(1) { width: 70px; text-align: center; }       /* Priorità */
    th:nth-child(2), td:nth-child(2) { width: 90px; text-align: center; }       /* Stato */
    th:nth-child(3), td:nth-child(3) { width: 130px; }                          /* Fase */
    th:nth-child(4), td:nth-child(4) { width: 110px; }                          /* Reparto */
    th:nth-child(5), td:nth-child(5) { width: 80px; text-align: center; }       /* Qta Carta */
    th:nth-child(6), td:nth-child(6) { width: 80px; text-align: center; }       /* Qta Prod */
    th:nth-child(7), td:nth-child(7) { width: 100px; text-align: center; }      /* Qta Prod Prinect */
    th:nth-child(8), td:nth-child(8) { width: 85px; text-align: center; }       /* Scarti Prinect */
    th:nth-child(9), td:nth-child(9) { width: 80px; text-align: center; }       /* Scarti R */
    th:nth-child(10), td:nth-child(10) { width: 140px; }                        /* Operatori */
    th:nth-child(11), td:nth-child(11) { width: 160px; }                        /* Note */
    th:nth-child(12), td:nth-child(12) { width: 220px; }                        /* Descrizione */
    th:nth-child(13), td:nth-child(13) { width: 130px; text-align: center; font-size: 11px; }    /* Data Inizio */
    th:nth-child(14), td:nth-child(14) { width: 130px; text-align: center; font-size: 11px; }    /* Data Fine */
    th:nth-child(15), td:nth-child(15) { width: 60px; text-align: center; }     /* × */
    td { white-space: nowrap; }
    /* Descrizione: spezza su più righe ma limita altezza riga */
    td.desc-col, td:nth-child(12) {
        white-space: normal;
        max-height: 3.9em;
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
    }
    .dc-wrapper { overflow-x: auto; }
    .btn-elimina {
        background: #dc3545;
        color: #fff;
        border: none;
        border-radius: 4px;
        padding: 2px 8px;
        font-size: 11px;
        cursor: pointer;
    }
    .btn-elimina:hover { background: #c82333; }
    .preview-card {
        border-radius: 12px;
        border: 1px solid #dee2e6;
        overflow: hidden;
        margin-bottom: 15px;
    }
    .preview-card img {
        max-width: 100%;
        max-height: 220px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-2 mt-2">
    <div>
        <a href="{{ route('owner.dashboard') }}" class="btn-back">&larr; Torna alla dashboard</a>
        <h2 class="d-inline ms-3">Commessa: <strong>{{ $commessa }}</strong></h2>
        <span class="badge bg-primary ms-2" style="font-size:14px; vertical-align:middle;">OC: {{ $ordine->ordine_cliente ?? '-' }}</span>
    </div>
    <div class="d-flex gap-2">
        @php $jobIdNum = ltrim(substr($commessa, 0, 7), '0'); @endphp
        @if($jobIdNum && is_numeric($jobIdNum))
            <a href="{{ route('mes.prinect.jobDetail', $jobIdNum) }}" class="btn btn-outline-secondary btn-sm">Dettaglio Prinect</a>
        @endif
        <a href="{{ route('mes.prinect.report', $commessa) }}" class="btn btn-outline-success btn-sm">Report Stampa</a>
        <button class="btn btn-outline-info btn-sm" onclick="document.getElementById('invioEsternoBox').style.display = document.getElementById('invioEsternoBox').style.display === 'none' ? 'block' : 'none';">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="7.5 4.21 12 6.81 16.5 4.21"/></svg>
            Invia all'esterno
        </button>
    </div>
</div>

{{-- Box invio esterno --}}
<div id="invioEsternoBox" style="display:none;" class="mb-3 mt-2">
    <div class="border rounded p-3" style="background:#e8f4f8;">
        <strong style="font-size:14px; color:#17a2b8;">Invia lavorazione all'esterno</strong>
        <div class="row g-2 mt-2">
            <div class="col-md-4">
                <label class="form-label" style="font-size:12px; font-weight:600;">Fase da inviare</label>
                <select id="esternoFaseId" class="form-select form-select-sm">
                    @foreach($fasi as $f)
                        @if($f->stato < 3)
                        <option value="{{ $f->id }}">{{ $f->faseCatalogo->nome_display ?? $f->fase }} (stato: {{ $f->stato }})</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" style="font-size:12px; font-weight:600;">Fornitore esterno</label>
                <select id="esternoFornitoreSelect" class="form-select form-select-sm" onchange="document.getElementById('esternoFornitoreAltro').style.display=this.value==='__altro__'?'':'none';">
                    <option value="">-- Seleziona --</option>
                    <option>4GRAPH S.R.L.</option>
                    <option>CARD S.R.L.</option>
                    <option>CLEVEX S.R.L.</option>
                    <option>KRESIA SRL</option>
                    <option>LASER LINE FUSTELLE S.R.L.</option>
                    <option>LEGATORIA SALVATORE TONTI SRL</option>
                    <option>LEGOKART S.A.S.</option>
                    <option>LEGRAF S.R.L.</option>
                    <option>LP FUSTELLE S.R.L.</option>
                    <option>PACKINGRAF SRL</option>
                    <option>POLYEDRA S.P.A.</option>
                    <option>SAE SRL</option>
                    <option>SOL GROUP SRL</option>
                    <option>SOLUZIONI IMBALLAGGI SRL</option>
                    <option>TECNOCART S.R.L.</option>
                    <option>TIPOGRAFIA BIANCO S.R.L.</option>
                    <option>TIPOGRAFIA EFFEGI SRL</option>
                    <option>TIPOLITOGRAFIA NEO PRINT SERVICE</option>
                    <option value="__altro__">Altro...</option>
                </select>
                <input type="text" id="esternoFornitoreAltro" class="form-control form-control-sm mt-1" style="display:none;" placeholder="Nome fornitore...">
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button class="btn btn-info btn-sm text-white" onclick="inviaEsterno()">Conferma invio esterno</button>
            </div>
        </div>
    </div>
</div>

{{-- Anteprima foglio di stampa --}}
@if($preview)
<div class="row mb-3">
    <div class="col-md-4">
        <div class="preview-card p-3 text-center bg-white shadow-sm">
            <div class="fw-bold mb-2" style="font-size:13px;">Anteprima foglio di stampa</div>
            <img src="data:{{ $preview['mimeType'] }};base64,{{ $preview['data'] }}" alt="Preview">
        </div>
    </div>
</div>
@endif

{{-- Colori, Fustella e Cliché (box affiancati) --}}
@if($ordine)
@php
    $tutteDescDett = $ordini->pluck('descrizione')->filter()->unique()->implode(' | ');
    $coloriDett = \App\Helpers\DescrizioneParser::parseColori($tutteDescDett, $ordine->cliente_nome ?? '');
    $fustellaDett = \App\Helpers\DescrizioneParser::parseFustella($tutteDescDett, $ordine->cliente_nome ?? '', $ordine->note_prestampa ?? '');
    // Raggruppa ordini per cliche_numero (dedup). Un box per cliché con lista articoli.
    $clicheGruppi = $ordini->filter(fn($o) => $o->cliche)->groupBy('cliche_numero');
@endphp
<div class="d-flex gap-2 mb-3 flex-wrap align-items-stretch">
    @if($coloriDett)
    <div class="border rounded p-2 d-flex align-items-center gap-2" style="background:#e8f5e9; border-color:#66bb6a !important;">
        <strong style="color:#2e7d32; font-size:13px;">🎨 Colori:</strong>
        <span class="badge" style="background:#2e7d32; color:white; font-size:12px;">{{ $coloriDett }}</span>
    </div>
    @endif
    @if($fustellaDett)
    <div class="border rounded p-2 d-flex align-items-center gap-2" style="background:#e3f2fd; border-color:#42a5f5 !important;">
        <strong style="color:#1565c0; font-size:13px;">✂️ Fustella:</strong>
        <span class="badge" style="background:#1565c0; color:white; font-size:12px;">{{ $fustellaDett }}</span>
    </div>
    @endif
    @if($clicheGruppi->isNotEmpty())
        @foreach($clicheGruppi as $numero => $gruppo)
        @php $cl = $gruppo->first()->cliche; $descrizioni = $gruppo->pluck('descrizione')->filter()->unique(); @endphp
        <div class="border rounded p-2 d-flex align-items-center gap-2 flex-wrap" style="background:#fff8e1; border-color:#fbc02d !important;">
            <strong style="color:#f57f17; font-size:13px;">🏷️ Cliché:</strong>
            <span class="badge" style="background:#f57f17; color:white; font-size:12px;">{{ $cl->numero }}</span>
            @if($cl->scatola)
                <span class="badge" style="background:#8d6e63; color:white; font-size:12px;">Scatola {{ $cl->scatola }}</span>
            @endif
            @if($cl->qta)
                <span class="badge" style="background:#6c757d; color:white; font-size:12px;">Qta Cliché {{ $cl->qta }}</span>
            @endif
            <small class="text-muted" style="font-size:11px;">→ {{ $descrizioni->implode(' | ') }}</small>
        </div>
        @endforeach
    @else
    <div class="border rounded p-2 d-flex align-items-center gap-2" style="background:#fff8e1; border-color:#fbc02d !important;">
        <strong style="color:#f57f17; font-size:13px;">🏷️ Cliché:</strong>
        <small class="text-muted">Cliché non impostato</small>
    </div>
    @endif
</div>
@endif

{{-- Info Commessa --}}
@if($ordine)
@php
    $qtaOffset = $fasi
        ->filter(fn($f) => ($f->reparto_nome ?? '') === 'stampa offset' && (int)$f->stato >= 2 && $f->qta_prod !== null)
        ->sum('qta_prod');
    $qtaDigitale = $fasi
        ->filter(fn($f) => ($f->reparto_nome ?? '') === 'digitale' && (int)$f->stato >= 2 && $f->qta_prod !== null)
        ->sum('qta_prod');
    $qtaProdottaStampa = $qtaOffset > 0 ? $qtaOffset : $qtaDigitale;
@endphp
<div class="row g-2 mb-2" style="font-size:13px;">
    <div class="col-md-3">
        <div class="border rounded p-2 h-100" style="background:#e8f4fd">
            <strong class="d-block mb-1">Descrizione</strong>
            <span>{{ $ordine->descrizione ?: '-' }}</span>
        </div>
    </div>
    <div class="col-md-2">
        <div class="border rounded p-2 h-100" style="background:#e8f4fd">
            <strong class="d-block mb-1">Cliente</strong>
            <span>{{ $ordine->cliente_nome ?: '-' }}</span>
        </div>
    </div>
    <div class="col-md-2">
        <div class="border rounded p-2 h-100" style="background:#e8f4fd">
            <strong class="d-block mb-1">Cod. Articolo</strong>
            <span>{{ $ordine->cod_art ?: '-' }}</span>
        </div>
    </div>
    <div class="col-md-1">
        <div class="border rounded p-2 h-100" style="background:#e8f4fd">
            <strong class="d-block mb-1">Quantit&agrave;</strong>
            <span>{{ $ordine->qta_richiesta ? number_format($ordine->qta_richiesta, 0, ',', '.') : '-' }}</span>
        </div>
    </div>
    <div class="col-md-2">
        <div class="border rounded p-2 h-100" style="background:#fff4e6; border-color:#f59e0b !important;">
            <strong class="d-block mb-1" style="color:#b45309;">Qta Prodotta</strong>
            <span>{{ $qtaProdottaStampa > 0 ? number_format($qtaProdottaStampa, 0, ',', '.') : '-' }}</span>
        </div>
    </div>
    <div class="col-md-2">
        <div class="border rounded p-2 h-100" style="background:#e8f4fd">
            <strong class="d-block mb-1">Data Consegna</strong>
            <span>{{ $ordine->data_prevista_consegna ? \Carbon\Carbon::parse($ordine->data_prevista_consegna)->format('d/m/Y') : '-' }}</span>
        </div>
    </div>
</div>

{{-- Info Onda --}}
<div class="row g-2 mb-3" style="font-size:13px;">
    <div class="col-md-4">
        <div class="border rounded p-2 h-100" style="background:#f8f9fa">
            <strong class="d-block mb-1">Operatore Prestampa</strong>
            <span class="{{ $ordine->responsabile ? '' : 'text-muted' }}">{{ $ordine->responsabile ?: '-' }}</span>
        </div>
    </div>
    <div class="col-md-4">
        <div class="border rounded p-2 h-100" style="background:#f8f9fa">
            <strong class="d-block mb-1">Note Prestampa</strong>
            <span class="{{ $ordine->note_prestampa ? '' : 'text-muted' }}">{{ $ordine->note_prestampa ?: '-' }}</span>
        </div>
    </div>
    <div class="col-md-4">
        <div class="border rounded p-2 h-100" style="background:#f8f9fa">
            <strong class="d-block mb-1">Commento Produzione</strong>
            <span class="{{ $ordine->commento_produzione ? '' : 'text-muted' }}">{{ $ordine->commento_produzione ?: '-' }}</span>
        </div>
    </div>
</div>

{{-- Note Fustelle (note inserite da Mirko/prestampa sulle singole fasi) --}}
@php
    $noteFasi = $fasi->filter(function($f) {
        if (empty($f->note)) return false;
        $n = trim($f->note);
        // Escludi note di sistema (DDT fornitore, colori, fustelle)
        if (str_starts_with($n, 'Inviato a:')) return false;
        if (str_starts_with($n, '[COL:')) return false;
        if (str_starts_with($n, '[FS:')) return false;
        return true;
    })->values();
@endphp
@if($noteFasi->isNotEmpty())
<div class="row g-2 mb-3">
    <div class="col-12">
        <div class="border rounded p-2" style="background:#ede9fe; border-color:#c4b5fd !important;">
            <strong class="d-block mb-1" style="color:#7c3aed;">Note Fustelle</strong>
            @foreach($noteFasi as $nf)
                <div style="font-size:13px; padding:2px 0; {{ !$loop->last ? 'border-bottom:1px solid #ddd6fe;' : '' }}">
                    <strong>{{ $nf->faseCatalogo->nome_display ?? $nf->fase }}</strong>: {{ $nf->note }}
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif
@endif

@php
    $statoBg = [0 => '#e9ecef', 1 => '#cfe2ff', 2 => '#fff3cd', 3 => '#d1e7dd', 4 => '#c3c3c3', 5 => '#e0cffc'];
    $statoColor = [0 => '#333', 1 => '#084298', 2 => '#664d03', 3 => '#0f5132', 4 => '#1a1a1a', 5 => '#6f42c1'];
    $statoLabel = [0 => 'Caricato', 1 => 'Pronto', 2 => 'Avviato', 3 => 'Terminato', 4 => 'Consegnato', 5 => 'Esterno'];

    $totaleFasi = $fasi->count();
    $fasiTerminateCont = $fasi->filter(fn($f) => is_numeric($f->stato) && (int)$f->stato >= 3 && (int)$f->stato != 5)->count();
    $fasiAvviate = $fasi->where('stato', 2)->count();
    $pctCompletamento = $totaleFasi > 0 ? round(($fasiTerminateCont / $totaleFasi) * 100) : 0;
    $pctAvviate = $totaleFasi > 0 ? round(($fasiAvviate / $totaleFasi) * 100) : 0;
@endphp

{{-- Barra progresso fasi --}}
<div class="row g-2 mb-3">
    <div class="col-12">
        <div class="border rounded p-3" style="background:#fff;">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <strong style="font-size:14px;">Progresso fasi</strong>
                <span style="font-size:13px; color:#6c757d;">{{ $fasiTerminateCont }}/{{ $totaleFasi }} terminate {{ $fasiAvviate > 0 ? '· '.$fasiAvviate.' in corso' : '' }}</span>
            </div>
            <div style="height:24px; border-radius:12px; background:#e9ecef; overflow:hidden; position:relative;">
                @if($pctCompletamento > 0)
                <div style="height:100%; width:{{ $pctCompletamento }}%; background:linear-gradient(90deg, #198754, #28a745); border-radius:12px 0 0 12px; position:absolute; left:0; top:0; z-index:2; transition:width 0.5s;">
                    <span style="position:absolute; right:8px; top:50%; transform:translateY(-50%); font-size:11px; font-weight:bold; color:#fff;">{{ $pctCompletamento }}%</span>
                </div>
                @endif
                @if($pctAvviate > 0)
                <div style="height:100%; width:{{ $pctCompletamento + $pctAvviate }}%; background:#ffc107; border-radius:12px 0 0 12px; position:absolute; left:0; top:0; z-index:1; transition:width 0.5s;"></div>
                @endif
            </div>
            <div class="d-flex gap-3 mt-2" style="font-size:11px; color:#6c757d;">
                <span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#28a745;"></span> Terminate</span>
                <span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#ffc107;"></span> In corso</span>
                <span><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#e9ecef;border:1px solid #ccc;"></span> Da fare</span>
            </div>
        </div>
    </div>
</div>

<div style="overflow-x:auto; margin-top:10px;">
    <table>
        <thead>
            <tr>
                <th>Priorit&agrave;</th>
                <th>Stato</th>
                <th>Fase</th>
                <th>Reparto</th>
                <th>Qta Carta</th>
                <th>Qta Prod.</th>
                <th>Qta Prod. Prinect</th>
                <th>Scarti Prinect</th>
                <th>Scarti R.</th>
                <th>Operatori</th>
                <th>Note</th>
                <th>Descrizione</th>
                <th>Data Inizio</th>
                <th>Data Fine</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @foreach($fasi as $fase)
            @php
                $isEsterno = $fase->esterno || $fase->ddt_fornitore_id;
                $ddtInviato = (bool) $fase->ddt_fornitore_id;
            @endphp
            <tr id="fase-row-{{ $fase->id }}" data-id="{{ $fase->id }}" data-fase-nome="{{ $fase->fase ?? '' }}">
                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'priorita', this.innerText)">{{ $fase->priorita !== null ? number_format($fase->priorita, 2) : '-' }}</td>
                @if($isEsterno && (int)$fase->stato < 3)
                    <td contenteditable onblur="aggiornaStato({{ $fase->id }}, this.innerText)"
                        style="background:{{ $ddtInviato ? '#d1fae5' : '#ede9fe' }};color:{{ $ddtInviato ? '#065f46' : '#7c3aed' }};font-weight:bold;text-align:center;font-size:10px;"
                        title="{{ $ddtInviato ? 'Inviato al fornitore (DDT creato)' : 'Esterno - da inviare' }}">EXT</td>
                @else
                    <td contenteditable onblur="aggiornaStato({{ $fase->id }}, this.innerText)">
                        <span class="stato-badge" style="background:{{ $statoBg[$fase->stato] ?? '#e9ecef' }};color:{{ $statoColor[$fase->stato] ?? '#333' }}">
                            {{ $fase->stato }}
                        </span>
                    </td>
                @endif
                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'fase', this.innerText)">{{ $fase->faseCatalogo->nome_display ?? $fase->fase ?? '-' }}</td>
                <td title="Reparto derivato dalla fase: per cambiarlo modifica il nome della fase" style="color:#666; background:#f5f5f5;">{{ $fase->reparto_nome ?? '-' }}</td>
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
                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'qta_prod', this.innerText)">{{ $fase->qta_prod !== null ? number_format($fase->qta_prod, 0, ',', '.') : '-' }}</td>
                <td style="text-align:center; background:#f0f7ff;">{{ $fase->fogli_buoni !== null ? number_format($fase->fogli_buoni, 0, ',', '.') : '-' }}</td>
                <td style="text-align:center;">{{ $fase->fogli_scarto !== null ? number_format($fase->fogli_scarto, 0, ',', '.') : '-' }}</td>
                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'scarti', this.innerText)" style="text-align:center;">{{ $fase->scarti ?? '-' }}</td>
                <td>
                    @forelse($fase->operatori as $op)
                        {{ $op->nome }} {{ $op->cognome }}@if(!$loop->last), @endif
                    @empty
                        -
                    @endforelse
                </td>
                @php
                    $descDett = $fase->ordine->descrizione ?? '';
                    $clienteDett = $fase->ordine->cliente_nome ?? '';
                    $repartoDett = strtolower($fase->reparto_nome ?? '');
                    $noteExtraDett = '';
                    if (in_array($repartoDett, ['stampa offset', 'digitale'])) {
                        $coloriDett2 = \App\Helpers\DescrizioneParser::parseColori($descDett, $clienteDett, $repartoDett);
                        if ($coloriDett2) $noteExtraDett .= '[COL: '.$coloriDett2.'] ';
                    }
                    if (str_contains($repartoDett, 'fustella')) {
                        $fustellaDett2 = \App\Helpers\DescrizioneParser::parseFustella($descDett, $clienteDett, $fase->ordine->note_prestampa ?? '');
                        if ($fustellaDett2) $noteExtraDett .= '[FS: '.$fustellaDett2.'] ';
                    }
                @endphp
                <td>
                    @if($noteExtraDett)<small class="fw-bold">{{ $noteExtraDett }}</small><br>@endif<span contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'note', this.innerText)">{{ $fase->note ?? '-' }}</span>
                </td>
                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'descrizione', this.innerText)">{{ $fase->ordine->descrizione ?? '-' }}</td>
                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'data_inizio', this.innerText)" title="{{ $fase->data_inizio ?? '' }}">{{ $fase->data_inizio ? \Carbon\Carbon::parse($fase->data_inizio)->format('d/m/Y H:i') : '-' }}</td>
                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'data_fine', this.innerText)" title="{{ $fase->data_fine ?? '' }}">{{ $fase->data_fine ? \Carbon\Carbon::parse($fase->data_fine)->format('d/m/Y H:i') : '-' }}</td>
                <td><button class="btn-elimina" onclick="eliminaFase({{ $fase->id }})">&times;</button></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
</div>

<script>
function aggiornaCampo(faseId, campo, valore) {
    valore = valore.trim();
    if (valore === '-') valore = '';

    const campiNumerici = ['qta_richiesta','qta_prod','priorita','qta_carta','ore'];
    if (campiNumerici.includes(campo)) {
        valore = valore.replace(',', '.');
        if (valore && isNaN(parseFloat(valore))) {
            alert('Valore numerico non valido');
            return;
        }
    }

    fetch('{{ route("owner.aggiornaCampo") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            'Content-Type': 'application/json'
        },
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
    .catch(err => { console.error(err); alert('Errore di connessione'); });
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
            const colorMap = {0:'#333', 1:'#084298', 2:'#664d03', 3:'#0f5132', 4:'#1a1a1a'};
            const row = document.querySelector('tr[data-id="' + faseId + '"]');
            if (row) {
                const badge = row.querySelector('.stato-badge');
                if (badge) {
                    badge.style.background = bgMap[nuovoStato] || '#e9ecef';
                    badge.style.color = colorMap[nuovoStato] || '#333';
                    badge.innerText = nuovoStato;
                }
            }
        }
    })
    .catch(err => { console.error(err); alert('Errore di connessione'); });
}

function inviaEsterno() {
    var faseId = document.getElementById('esternoFaseId').value;
    var sel = document.getElementById('esternoFornitoreSelect').value;
    var fornitore = sel === '__altro__'
        ? document.getElementById('esternoFornitoreAltro').value.trim()
        : sel;
    if (!faseId) { alert('Seleziona una fase'); return; }
    if (!fornitore) { alert('Seleziona un fornitore esterno'); return; }

    if (!confirm('Confermi invio all\'esterno a "' + fornitore + '"?')) return;

    // 1. Segna come esterno
    fetch('{{ route("owner.aggiornaCampo") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken(), 'Content-Type': 'application/json' },
        body: JSON.stringify({ fase_id: faseId, campo: 'esterno', valore: 1 })
    })
    .then(function(r) { return r.json(); })
    .then(function() {
        // 2. Aggiungi nota "Inviato a: fornitore"
        return fetch('{{ route("owner.aggiornaCampo") }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken(), 'Content-Type': 'application/json' },
            body: JSON.stringify({ fase_id: faseId, campo: 'note', valore: 'Inviato a: ' + fornitore })
        });
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success) {
            alert('Fase inviata all\'esterno a: ' + fornitore);
            window.location.reload();
        } else {
            alert('Errore: ' + (d.messaggio || ''));
        }
    })
    .catch(function(err) { console.error(err); alert('Errore di connessione'); });
}

function eliminaFase(faseId) {
    if (!confirm('Sei sicuro di voler eliminare questa fase?')) return;

    fetch('{{ route("owner.eliminaFase") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ fase_id: faseId })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            document.getElementById('fase-row-' + faseId).remove();
        } else {
            alert('Errore: ' + (d.messaggio || 'eliminazione fallita'));
        }
    })
    .catch(err => { console.error(err); alert('Errore di connessione'); });
}

</script>
@endsection
