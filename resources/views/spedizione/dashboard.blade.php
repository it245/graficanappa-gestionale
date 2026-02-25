@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
<style>
    html, body {
        margin:0; padding:0; overflow-x:hidden; width:100%;
    }
    h2, h4, p { margin-left:8px; margin-right:8px; }
    .top-bar {
        display:flex;
        align-items:center;
        justify-content:space-between;
        margin-bottom:10px;
    }
    .operatore-info {
        position:relative;
        display:flex;
        align-items:center;
        gap:10px;
        cursor:pointer;
    }
    .operatore-info img {
        width:50px; height:50px; border-radius:50%;
    }
    .operatore-popup {
        position:absolute;
        top:60px;
        left:0;
        background:#fff;
        border:1px solid #ccc;
        padding:10px;
        border-radius:5px;
        box-shadow:0 2px 10px rgba(0,0,0,0.2);
        display:none;
        z-index:1000;
        min-width:200px;
    }
    .operatore-popup button {
        width:100%;
        margin-top:8px;
    }
    .table-wrapper {
        width:100%;
        max-width:100%;
        overflow-x:auto;
        overflow-y:visible;
        margin: 0 4px;
    }
    table th, table td { white-space:nowrap; }
    td:nth-child(7){ white-space:normal; min-width:300px; }

    .btn-consegna {
        color: #fff;
        border: none;
        padding: 8px 20px;
        border-radius: 8px;
        font-weight: bold;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
    }
    .btn-consegna-green {
        background: linear-gradient(135deg, #28a745, #218838);
    }
    .btn-consegna-green:hover {
        background: linear-gradient(135deg, #218838, #1e7e34);
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(40,167,69,0.35);
    }
    .btn-consegna-red {
        background: linear-gradient(135deg, #dc3545, #c82333);
    }
    .btn-consegna-red:hover {
        background: linear-gradient(135deg, #c82333, #a71d2a);
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(220,53,69,0.35);
    }
    .btn-consegna-orange {
        background: linear-gradient(135deg, #fd7e14, #e8690b);
    }
    .btn-consegna-orange:hover {
        background: linear-gradient(135deg, #e8690b, #d35400);
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(253,126,20,0.35);
    }
    .btn-consegna:disabled {
        background: #6c757d !important;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    .kpi-box {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
        margin-bottom: 15px;
    }
    .kpi-box h3 {
        margin: 0;
        font-size: 28px;
        font-weight: bold;
    }
    .kpi-box small { color: #6c757d; }

    .progress-bar-custom {
        height: 18px;
        border-radius: 10px;
        background: #e9ecef;
        overflow: hidden;
        min-width: 80px;
    }
    .progress-bar-custom .fill {
        height: 100%;
        border-radius: 10px;
        transition: width 0.3s ease;
        text-align: center;
        font-size: 10px;
        line-height: 18px;
        color: #fff;
        font-weight: bold;
    }

    .search-box {
        max-width: 600px;
        margin: 12px 8px;
        font-size: 18px;
        padding: 12px 20px;
        border-radius: 10px;
        border: 2px solid #dee2e6;
        transition: border-color 0.2s;
    }
    .search-box:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 3px rgba(13,110,253,0.15);
    }

    .row-scaduta { background: #f8d7da !important; }
    .row-warning { background: #fff3cd !important; }

    a.commessa-link {
        color: #000;
        font-weight: bold;
        text-decoration: underline;
    }
    a.commessa-link:hover { color: #0d6efd; }

    .fasi-mancanti {
        font-size: 11px;
        color: #dc3545;
        margin-top: 4px;
    }

    .scanner-container {
        width: 100%;
        max-width: 100%;
        border-radius: 10px;
        overflow: hidden;
        margin-top: 8px;
        display: none;
    }
    .scanner-container.active { display: block; }
    .scanner-container video { border-radius: 10px; }
    .btn-scan {
        border: none;
        background: transparent;
        padding: 6px 10px;
        cursor: pointer;
        font-size: 20px;
        color: #0d6efd;
        vertical-align: middle;
    }
    .btn-scan:hover { color: #0b5ed7; }
    .segnacollo-row {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .segnacollo-row input { flex: 1; }

</style>

<div class="top-bar">
    <div class="operatore-info" id="operatoreInfo">
        <img src="{{ asset('images/icons8-utente-uomo-cerchiato-50.png') }}" alt="Operatore">
        <div class="operatore-popup" id="operatorePopup">
            <div><strong>{{ $operatore->nome }} {{ $operatore->cognome }}</strong></div>
            <div><p>Reparto: <strong>Spedizione</strong></p></div>
            <form action="{{ route('operatore.logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-secondary btn-sm mt-2">Logout</button>
            </form>
        </div>
    </div>
</div>

<h2>Dashboard Spedizione</h2>

@php
    $consegneTotali = $fasiSpediteOggi->where('tipo_consegna', 'totale')->count();
    $consegneParziali = $fasiSpediteOggi->where('tipo_consegna', 'parziale')->count();
    // Vecchie consegne senza tipo_consegna contale come totali
    $consegneSenzaTipo = $fasiSpediteOggi->whereNull('tipo_consegna')->count();
    $consegneTotali += $consegneSenzaTipo;
@endphp

<!-- KPI -->
<div class="row mx-2 mb-3">
    <div class="col-md-2">
        <div class="kpi-box" style="border-left: 4px solid #28a745;">
            <h3>{{ $fasiDaSpedire->count() }}</h3>
            <small>Da consegnare</small>
        </div>
    </div>
    <div class="col-md-2">
        <div class="kpi-box" style="border-left: 4px solid #ffc107;">
            <h3>{{ $fasiInAttesa->count() }}</h3>
            <small>In attesa</small>
        </div>
    </div>
    <div class="col-md-2">
        <a href="{{ route('spedizione.esterne') }}" style="text-decoration:none; color:inherit;">
            <div class="kpi-box" style="cursor:pointer; border-left: 4px solid #17a2b8;">
                <h3>{{ $fasiEsterne->count() }}</h3>
                <small>Lav. esterne <span style="font-size:11px">(apri)</span></small>
            </div>
        </a>
    </div>
    <div class="col-md-2">
        <div class="kpi-box" style="cursor:pointer; border-left: 4px solid #0d6efd;" data-bs-toggle="modal" data-bs-target="#modalSpediteOggi">
            <h3>{{ $fasiSpediteOggi->count() }}</h3>
            <small>Consegnate oggi</small>
        </div>
    </div>
    <div class="col-md-2">
        <div class="kpi-box" style="border-left: 4px solid #6f42c1;">
            <h3>{{ $fasiDDT->count() }}</h3>
            <small>DDT Emesse</small>
        </div>
    </div>
    <div class="col-md-2">
        <div class="kpi-box" style="border-left: 4px solid #fd7e14;">
            <h3>{{ $fasiParziali->count() }}</h3>
            <small>Parziali in attesa</small>
        </div>
    </div>
</div>
<div class="row mx-2 mb-3">
    <div class="col-md-2">
        <div class="kpi-box" style="border-left: 4px solid #198754;">
            <h3>{{ $consegneTotali }}</h3>
            <small>Totali oggi</small>
        </div>
    </div>
    <div class="col-md-2">
        <div class="kpi-box" style="border-left: 4px solid #fd7e14;">
            <h3>{{ $consegneParziali }}</h3>
            <small>Parziali oggi</small>
        </div>
    </div>
</div>

<!-- Ricerca -->
<input type="text" id="searchBox" class="form-control search-box" placeholder="Cerca commessa, cliente, descrizione...">

<!-- Tabella DDT Emesse da Onda -->
@if($fasiDDT->count() > 0)
<h4 class="mx-2 mt-2" style="color:#6f42c1;">DDT Emesse da Onda</h4>
<div class="table-wrapper">
    <table class="table table-bordered table-sm" id="tabDDT">
        <thead style="background:#6f42c1; color:#fff;">
            <tr>
                <th>Azione</th>
                <th>Commessa</th>
                <th>Cliente</th>
                <th>Cod. Articolo</th>
                <th>Descrizione</th>
                <th>Qta Ordine</th>
                <th>Qta DDT</th>
                <th>Suggerimento</th>
            </tr>
        </thead>
        <tbody>
            @foreach($fasiDDT as $fase)
                @php
                    $qtaOrdine = $fase->ordine->qta_richiesta ?? 0;
                    $qtaDDT = $fase->ordine->qta_ddt_vendita ?? 0;
                    $suggerimento = $qtaDDT >= $qtaOrdine ? 'totale' : 'parziale';
                @endphp
                <tr class="searchable">
                    <td style="white-space:nowrap;">
                        <button class="btn-consegna btn-consegna-green" onclick="apriModalConsegnaDDT({{ $fase->id }}, 'totale')">Totale</button>
                        <button class="btn-consegna btn-consegna-orange" onclick="apriModalConsegnaDDT({{ $fase->id }}, 'parziale')">Parziale</button>
                    </td>
                    <td><a href="{{ route('commesse.show', $fase->ordine->commessa ?? '-') }}" class="commessa-link">{{ $fase->ordine->commessa ?? '-' }}</a></td>
                    <td>{{ $fase->ordine->cliente_nome ?? '-' }}</td>
                    <td>{{ $fase->ordine->cod_art ?? '-' }}</td>
                    <td>{{ $fase->ordine->descrizione ?? '-' }}</td>
                    <td>{{ $qtaOrdine }}</td>
                    <td>{{ $qtaDDT }}</td>
                    <td>
                        @if($suggerimento === 'totale')
                            <span class="badge bg-success">Totale</span>
                        @else
                            <span class="badge bg-warning text-dark">Parziale</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<!-- Tabella fasi da spedire -->
<h4 class="mx-2 mt-2" style="color:#28a745;">Da consegnare</h4>
<div class="table-wrapper">
    <table class="table table-bordered table-sm table-striped" id="tabDaSpedire">
        <thead class="table-dark">
            <tr>
                <th>Azione</th>
                <th>Note</th>
                <th>Commessa</th>
                <th>Cliente</th>
                <th>Cod. Articolo</th>
                <th>Qta</th>
                <th>Descrizione</th>
                <th>Data Consegna</th>
                <th>Progresso</th>
            </tr>
        </thead>
        <tbody>
            @forelse($fasiDaSpedire as $fase)
                @php
                    $rowClass = '';
                    if ($fase->ordine && $fase->ordine->data_prevista_consegna) {
                        $oggi = \Carbon\Carbon::today();
                        $dataPrevista = \Carbon\Carbon::parse($fase->ordine->data_prevista_consegna);
                        $diff = $oggi->diffInDays($dataPrevista, false);
                        if ($diff < -5) $rowClass = 'row-scaduta';
                        elseif ($diff <= 3) $rowClass = 'row-warning';
                    }
                    $pct = $fase->percentuale ?? 0;
                    $pctColor = $pct == 100 ? '#28a745' : ($pct >= 75 ? '#17a2b8' : ($pct >= 50 ? '#ffc107' : '#dc3545'));
                @endphp
                <tr class="{{ $rowClass }} searchable">
                    <td>
                        <button class="btn-consegna btn-consegna-green" onclick="apriModalConsegna({{ $fase->id }}, false)">Consegna</button>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm" style="min-width:150px"
                               value="{{ $fase->note ?? '' }}"
                               onblur="aggiornaNota({{ $fase->id }}, this.value)">
                    </td>
                    <td><a href="{{ route('commesse.show', $fase->ordine->commessa ?? '-') }}" class="commessa-link">{{ $fase->ordine->commessa ?? '-' }}</a></td>
                    <td>{{ $fase->ordine->cliente_nome ?? '-' }}</td>
                    <td>{{ $fase->ordine->cod_art ?? '-' }}</td>
                    <td>{{ $fase->ordine->qta_richiesta ?? '-' }}</td>
                    <td>{{ $fase->ordine->descrizione ?? '-' }}</td>
                    <td>{{ $fase->ordine->data_prevista_consegna ? \Carbon\Carbon::parse($fase->ordine->data_prevista_consegna)->format('d/m/Y') : '-' }}</td>
                    <td>
                        <div class="progress-bar-custom">
                            <div class="fill" style="width:{{ $pct }}%;background:{{ $pctColor }};">{{ $pct }}%</div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-3">Nessuna consegna in coda</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Tabella Consegne Parziali -->
@if($fasiParziali->count() > 0)
<h4 class="mx-2 mt-4" style="color:#fd7e14;">Consegne Parziali</h4>
<div class="table-wrapper">
    <table class="table table-bordered table-sm" id="tabParziali">
        <thead style="background:#fd7e14; color:#fff;">
            <tr>
                <th>Azione</th>
                <th>Commessa</th>
                <th>Cliente</th>
                <th>Cod. Articolo</th>
                <th>Descrizione</th>
                <th>Segnacollo BRT</th>
                <th>Data cons. parziale</th>
            </tr>
        </thead>
        <tbody>
            @foreach($fasiParziali as $fase)
                <tr class="searchable">
                    <td>
                        <button class="btn-consegna btn-consegna-green" onclick="apriModalConsegnaDDT({{ $fase->id }}, 'totale')">Consegna Totale</button>
                    </td>
                    <td><a href="{{ route('commesse.show', $fase->ordine->commessa ?? '-') }}" class="commessa-link">{{ $fase->ordine->commessa ?? '-' }}</a></td>
                    <td>{{ $fase->ordine->cliente_nome ?? '-' }}</td>
                    <td>{{ $fase->ordine->cod_art ?? '-' }}</td>
                    <td>{{ $fase->ordine->descrizione ?? '-' }}</td>
                    <td>
                        @if($fase->segnacollo_brt)
                            <a href="#" class="fw-bold text-primary" style="text-decoration:underline;cursor:pointer;" onclick="apriTracking('{{ $fase->segnacollo_brt }}'); return false;">{{ $fase->segnacollo_brt }}</a>
                        @else
                            -
                        @endif
                    </td>
                    <td>{{ $fase->data_fine ? \Carbon\Carbon::parse($fase->data_fine)->format('d/m/Y H:i') : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<!-- Tabella fasi in attesa -->
@if($fasiInAttesa->count() > 0)
<h4 class="mx-2 mt-4" style="color:#ffc107;">In attesa (lavorazione in corso)</h4>
<div class="table-wrapper">
    <table class="table table-bordered table-sm" id="tabInAttesa">
        <thead style="background:#ffc107; color:#000;">
            <tr>
                <th>Azione</th>
                <th>Commessa</th>
                <th>Cliente</th>
                <th>Cod. Articolo</th>
                <th>Qta</th>
                <th>Descrizione</th>
                <th>Data Consegna</th>
                <th>Progresso fasi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($fasiInAttesa as $fase)
                @php
                    $pct = $fase->percentuale ?? 0;
                    $pctColor = $pct >= 75 ? '#17a2b8' : ($pct >= 50 ? '#ffc107' : '#dc3545');
                @endphp
                <tr class="searchable">
                    <td style="text-align:center; vertical-align:middle;">
                        <button class="btn-consegna btn-consegna-red" onclick="apriModalConsegna({{ $fase->id }}, true)">Consegna</button>
                        <div class="fasi-mancanti">{{ $fase->fasiNonTerminate->count() }} fase/i aperte</div>
                    </td>
                    <td><a href="{{ route('commesse.show', $fase->ordine->commessa ?? '-') }}" class="commessa-link">{{ $fase->ordine->commessa ?? '-' }}</a></td>
                    <td>{{ $fase->ordine->cliente_nome ?? '-' }}</td>
                    <td>{{ $fase->ordine->cod_art ?? '-' }}</td>
                    <td>{{ $fase->ordine->qta_richiesta ?? '-' }}</td>
                    <td>{{ $fase->ordine->descrizione ?? '-' }}</td>
                    <td>{{ $fase->ordine->data_prevista_consegna ? \Carbon\Carbon::parse($fase->ordine->data_prevista_consegna)->format('d/m/Y') : '-' }}</td>
                    <td>
                        <div class="progress-bar-custom">
                            <div class="fill" style="width:{{ $pct }}%;background:{{ $pctColor }};">{{ $pct }}%</div>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<!-- Modal Consegna -->
<div class="modal fade" id="modalConsegna" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tipo di consegna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="mc_faseId">
                <input type="hidden" id="mc_forza">
                <div class="mb-3">
                    <label for="mc_segnacollo" class="form-label fw-bold">Segnacollo BRT <small class="text-muted">(opzionale)</small></label>
                    <div class="segnacollo-row">
                        <input type="text" class="form-control form-control-lg" id="mc_segnacollo" placeholder="Es. 067138050411341" maxlength="50">
                        <button type="button" class="btn-scan" title="Scansiona codice a barre" onclick="toggleScanner('mc_scanner', 'mc_segnacollo')">&#128247;</button>
                    </div>
                    <div id="mc_scanner" class="scanner-container"></div>
                </div>
                <button type="button" class="btn btn-success btn-lg w-100 mb-3 fw-bold py-3" onclick="inviaConsegna('totale')">
                    Consegna Totale
                </button>
                <button type="button" class="btn btn-warning btn-lg w-100 fw-bold py-3 text-dark" onclick="inviaConsegna('parziale')">
                    Consegna Parziale
                </button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Spedite Oggi -->
<div class="modal fade" id="modalSpediteOggi" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Consegnate oggi ({{ $fasiSpediteOggi->count() }})</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="overflow-x:auto;">
                @if($fasiSpediteOggi->count() > 0)
                <table class="table table-bordered table-sm" style="white-space:nowrap;">
                    <thead class="table-success">
                        <tr>
                            <th>Azione</th>
                            <th>Commessa</th>
                            <th>Cliente</th>
                            <th>Cod. Articolo</th>
                            <th>Descrizione</th>
                            <th>Qta Ordinata</th>
                            <th>Tipo</th>
                            <th>Segnacollo BRT</th>
                            <th>Ora Consegna</th>
                            <th>Operatore</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($fasiSpediteOggi as $fase)
                        <tr>
                            <td>
                                <button class="btn btn-sm btn-outline-danger fw-bold" onclick="recuperaConsegna({{ $fase->id }}, this)">Recupera</button>
                            </td>
                            <td><strong>{{ $fase->ordine->commessa ?? '-' }}</strong></td>
                            <td>{{ $fase->ordine->cliente_nome ?? '-' }}</td>
                            <td>{{ $fase->ordine->cod_art ?? '-' }}</td>
                            <td>{{ $fase->ordine->descrizione ?? '-' }}</td>
                            <td>{{ $fase->ordine->qta_richiesta ?? '-' }}</td>
                            <td>
                                @if($fase->tipo_consegna === 'parziale')
                                    <span class="badge bg-warning text-dark">Parziale</span>
                                @else
                                    <span class="badge bg-success">Totale</span>
                                @endif
                            </td>
                            <td>
                                @if($fase->segnacollo_brt)
                                    <a href="#" class="fw-bold text-primary" style="text-decoration:underline;cursor:pointer;" onclick="apriTracking('{{ $fase->segnacollo_brt }}'); return false;">{{ $fase->segnacollo_brt }}</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $fase->data_fine ? \Carbon\Carbon::parse($fase->data_fine)->format('H:i:s') : '-' }}</td>
                            <td>
                                @foreach($fase->operatori as $op)
                                    {{ $op->nome }} {{ $op->cognome }}<br>
                                @endforeach
                            </td>
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

<!-- Modal Segnacollo DDT -->
<div class="modal fade" id="modalSegnacollo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:#6f42c1; color:#fff;">
                <h5 class="modal-title">Segnacollo BRT</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="msDDT_faseId">
                <input type="hidden" id="msDDT_tipo">
                <div class="mb-3">
                    <label for="msDDT_segnacollo" class="form-label fw-bold">Segnacollo BRT <small class="text-muted">(opzionale)</small></label>
                    <div class="segnacollo-row">
                        <input type="text" class="form-control form-control-lg" id="msDDT_segnacollo" placeholder="Es. 067138050411341" maxlength="50">
                        <button type="button" class="btn-scan" title="Scansiona codice a barre" onclick="toggleScanner('msDDT_scanner', 'msDDT_segnacollo')">&#128247;</button>
                    </div>
                    <div id="msDDT_scanner" class="scanner-container"></div>
                </div>
                <div id="msDDT_tipoLabel" class="mb-3 text-center">
                    <span class="badge bg-success fs-6" id="msDDT_badgeTotale" style="display:none;">Consegna Totale</span>
                    <span class="badge bg-warning text-dark fs-6" id="msDDT_badgeParziale" style="display:none;">Consegna Parziale</span>
                </div>
                <button type="button" class="btn btn-lg w-100 fw-bold py-3" id="msDDT_btnConferma" onclick="confermaDDT()">
                    Conferma
                </button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tracking BRT -->
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
                                <tr>
                                    <th>Data</th>
                                    <th>Ora</th>
                                    <th>Descrizione</th>
                                    <th>Filiale</th>
                                </tr>
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

</div>

<script>
function getHdrs() {
    return {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    };
}

function parseResponse(res) {
    if (!res.ok && res.status === 401) {
        alert('Sessione scaduta. Effettua di nuovo il login.');
        window.location.reload();
        return Promise.reject('session_expired');
    }
    const ct = res.headers.get('content-type') || '';
    if (!ct.includes('application/json')) {
        return Promise.reject('Risposta non valida dal server (status ' + res.status + ')');
    }
    return res.json();
}

function apriModalConsegna(faseId, forza) {
    document.getElementById('mc_faseId').value = faseId;
    document.getElementById('mc_forza').value = forza ? '1' : '0';
    document.getElementById('mc_segnacollo').value = '';
    new bootstrap.Modal(document.getElementById('modalConsegna')).show();
}

function inviaConsegna(tipo) {
    var faseId = document.getElementById('mc_faseId').value;
    var forza = document.getElementById('mc_forza').value === '1';

    bootstrap.Modal.getInstance(document.getElementById('modalConsegna')).hide();

    fetch('{{ route("spedizione.invio") }}', {
        method: 'POST', headers: getHdrs(),
        body: JSON.stringify({ fase_id: parseInt(faseId), tipo_consegna: tipo, forza: forza, segnacollo_brt: document.getElementById('mc_segnacollo').value.trim() || null })
    })
    .then(parseResponse)
    .then(data => {
        if (data.success) { window.location.reload(); }
        else { alert('Errore: ' + (data.messaggio || data.message || 'operazione fallita')); }
    })
    .catch(err => { if (err !== 'session_expired') { console.error('Errore:', err); alert('Errore: ' + err); } });
}

function apriModalConsegnaDDT(faseId, tipo) {
    document.getElementById('msDDT_faseId').value = faseId;
    document.getElementById('msDDT_tipo').value = tipo;
    document.getElementById('msDDT_segnacollo').value = '';

    var btnConferma = document.getElementById('msDDT_btnConferma');
    var badgeTotale = document.getElementById('msDDT_badgeTotale');
    var badgeParziale = document.getElementById('msDDT_badgeParziale');

    if (tipo === 'totale') {
        badgeTotale.style.display = 'inline-block';
        badgeParziale.style.display = 'none';
        btnConferma.className = 'btn btn-success btn-lg w-100 fw-bold py-3';
    } else {
        badgeTotale.style.display = 'none';
        badgeParziale.style.display = 'inline-block';
        btnConferma.className = 'btn btn-warning btn-lg w-100 fw-bold py-3 text-dark';
    }

    new bootstrap.Modal(document.getElementById('modalSegnacollo')).show();
}

function confermaDDT() {
    var faseId = document.getElementById('msDDT_faseId').value;
    var tipo = document.getElementById('msDDT_tipo').value;
    var segnacollo = document.getElementById('msDDT_segnacollo').value.trim() || null;

    bootstrap.Modal.getInstance(document.getElementById('modalSegnacollo')).hide();

    fetch('{{ route("spedizione.invio") }}', {
        method: 'POST', headers: getHdrs(),
        body: JSON.stringify({ fase_id: parseInt(faseId), tipo_consegna: tipo, forza: true, segnacollo_brt: segnacollo })
    })
    .then(parseResponse)
    .then(data => {
        if (data.success) { window.location.reload(); }
        else { alert('Errore: ' + (data.messaggio || data.message || 'operazione fallita')); }
    })
    .catch(err => { if (err !== 'session_expired') { console.error('Errore:', err); alert('Errore: ' + err); } });
}

function aggiornaNota(faseId, valore) {
    fetch('{{ route("produzione.aggiornaCampo") }}', {
        method: 'POST', headers: getHdrs(),
        body: JSON.stringify({ fase_id: faseId, campo: 'note', valore: valore })
    })
    .then(parseResponse)
    .then(data => { if (!data.success) alert('Errore salvataggio nota'); })
    .catch(err => { if (err !== 'session_expired') console.error('Errore:', err); });
}

function recuperaConsegna(faseId, btn) {
    if (!confirm('Annullare la consegna? La commessa tornerà in "Da consegnare".')) return;
    btn.disabled = true;
    fetch('{{ route("spedizione.recupera") }}', {
        method: 'POST', headers: getHdrs(),
        body: JSON.stringify({ fase_id: faseId })
    })
    .then(parseResponse)
    .then(data => {
        if (data.success) { window.location.reload(); }
        else { alert('Errore: ' + (data.messaggio || 'operazione fallita')); btn.disabled = false; }
    })
    .catch(err => { if (err !== 'session_expired') { console.error('Errore:', err); alert('Errore: ' + err); } btn.disabled = false; });
}

// Tracking BRT
function apriTracking(segnacollo) {
    document.getElementById('trackingSegnacollo').textContent = segnacollo;
    document.getElementById('trackingLoading').classList.remove('d-none');
    document.getElementById('trackingErrore').classList.add('d-none');
    document.getElementById('trackingContenuto').classList.add('d-none');

    var modal = new bootstrap.Modal(document.getElementById('modalTracking'));
    modal.show();

    fetch('{{ route("spedizione.tracking") }}', {
        method: 'POST', headers: getHdrs(),
        body: JSON.stringify({ segnacollo: segnacollo })
    })
    .then(parseResponse)
    .then(function(data) {
        document.getElementById('trackingLoading').classList.add('d-none');

        if (data.error) {
            document.getElementById('trackingErrore').textContent = data.message || 'Errore nel recupero tracking';
            document.getElementById('trackingErrore').classList.remove('d-none');
            return;
        }

        // Parse risposta BRT - struttura tipica con array di spedizioni
        var spedizione = null;
        if (data.parcelHistory && data.parcelHistory.length > 0) {
            spedizione = data.parcelHistory[0];
        } else if (data.length > 0) {
            spedizione = data[0];
        } else {
            spedizione = data;
        }

        // Stato
        var statoEl = document.getElementById('trackingStato');
        var statoDesc = spedizione.lastStatus || spedizione.shipmentStatusDescription || spedizione.statusDescription || '-';
        statoEl.textContent = statoDesc;
        if (statoDesc.toLowerCase().indexOf('consegnat') >= 0 || statoDesc.toLowerCase().indexOf('deliver') >= 0) {
            statoEl.className = 'badge bg-success ms-1';
        } else if (statoDesc.toLowerCase().indexOf('transit') >= 0 || statoDesc.toLowerCase().indexOf('viaggio') >= 0) {
            statoEl.className = 'badge bg-warning text-dark ms-1';
        } else {
            statoEl.className = 'badge bg-info ms-1';
        }

        // Dettagli
        document.getElementById('trackingDataConsegna').textContent = spedizione.deliveryDate || spedizione.lastEventDate || '-';
        document.getElementById('trackingDestinatario').textContent = spedizione.recipientName || spedizione.consigneeName || '-';
        document.getElementById('trackingFiliale').textContent = spedizione.deliveryBranchName || spedizione.branchName || '-';
        document.getElementById('trackingColli').textContent = spedizione.numberOfParcels || spedizione.parcels || '-';
        document.getElementById('trackingPeso').textContent = spedizione.weightKg || spedizione.weight || '-';

        // Eventi
        var eventiBody = document.getElementById('trackingEventi');
        eventiBody.innerHTML = '';
        var eventi = spedizione.events || spedizione.history || [];
        if (eventi.length === 0) {
            eventiBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Nessun evento disponibile</td></tr>';
        } else {
            eventi.forEach(function(ev) {
                var tr = document.createElement('tr');
                tr.innerHTML = '<td>' + (ev.date || ev.eventDate || '-') + '</td>' +
                               '<td>' + (ev.time || ev.eventTime || '-') + '</td>' +
                               '<td>' + (ev.description || ev.eventDescription || ev.statusDescription || '-') + '</td>' +
                               '<td>' + (ev.branchName || ev.filiale || '-') + '</td>';
                eventiBody.appendChild(tr);
            });
        }

        document.getElementById('trackingContenuto').classList.remove('d-none');
    })
    .catch(function(err) {
        document.getElementById('trackingLoading').classList.add('d-none');
        if (err !== 'session_expired') {
            document.getElementById('trackingErrore').textContent = 'Errore di connessione: ' + err;
            document.getElementById('trackingErrore').classList.remove('d-none');
        }
    });
}

// Ricerca
document.getElementById('searchBox').addEventListener('input', function() {
    const query = this.value.toLowerCase().trim();
    document.querySelectorAll('tr.searchable').forEach(function(row) {
        const text = row.innerText.toLowerCase();
        row.style.display = (!query || text.includes(query)) ? '' : 'none';
    });
});

// Popup operatore
document.getElementById('operatoreInfo').addEventListener('click', function(){
    const popup = document.getElementById('operatorePopup');
    popup.style.display = popup.style.display === 'block' ? 'none' : 'block';
});
document.addEventListener('click', function(e){
    if(!document.getElementById('operatoreInfo').contains(e.target)){
        document.getElementById('operatorePopup').style.display='none';
    }
});

// === Scanner codice a barre ===
var scannerInstances = {};

function avviaScanner(scannerId, inputId) {
    var container = document.getElementById(scannerId);
    container.classList.add('active');

    var scanner = new Html5Qrcode(scannerId, {
        formatsToSupport: [
            Html5QrcodeSupportedFormats.CODE_128,
            Html5QrcodeSupportedFormats.CODE_39,
            Html5QrcodeSupportedFormats.EAN_13,
            Html5QrcodeSupportedFormats.EAN_8,
            Html5QrcodeSupportedFormats.QR_CODE
        ]
    });
    scannerInstances[scannerId] = scanner;

    scanner.start(
        { facingMode: 'environment' },
        { fps: 10, qrbox: { width: 280, height: 120 } },
        function(decodedText) {
            document.getElementById(inputId).value = decodedText;
            fermaScanner(scannerId);
        },
        function() { /* ignore scan errors */ }
    ).catch(function(err) {
        console.error('Errore avvio scanner:', err);
        alert('Impossibile avviare la fotocamera. Verifica i permessi.');
        container.classList.remove('active');
    });
}

function fermaScanner(scannerId) {
    var scanner = scannerInstances[scannerId];
    if (scanner) {
        scanner.stop().then(function() {
            scanner.clear();
        }).catch(function() {});
        delete scannerInstances[scannerId];
    }
    var container = document.getElementById(scannerId);
    if (container) container.classList.remove('active');
}

function toggleScanner(scannerId, inputId) {
    if (scannerInstances[scannerId]) {
        fermaScanner(scannerId);
    } else {
        avviaScanner(scannerId, inputId);
    }
}

// Ferma scanner quando i modal vengono chiusi
['modalConsegna', 'modalSegnacollo'].forEach(function(modalId) {
    document.getElementById(modalId).addEventListener('hidden.bs.modal', function() {
        if (modalId === 'modalConsegna') fermaScanner('mc_scanner');
        if (modalId === 'modalSegnacollo') fermaScanner('msDDT_scanner');
    });
});
</script>
@endsection
