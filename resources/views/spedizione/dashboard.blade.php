@extends('layouts.mes')

@section('topbar-title', 'Dashboard Spedizione')

@section('vendor-scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
@include('partials.echo-client')
@endsection

@section('topbar-actions')
<form method="POST" action="{{ route('spedizione.syncOnda') }}" style="margin:0;" onsubmit="this.querySelector('button').disabled=true; this.querySelector('button span').textContent='Sync...';">
    @csrf
    <button type="submit" style="background:none; border:1px solid var(--border-color); border-radius:6px; padding:4px 12px; cursor:pointer; display:flex; align-items:center; gap:6px; font-size:12px; color:var(--text-secondary);">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M21.5 2v6h-6"/><path d="M2.5 22v-6h6"/><path d="M2.5 11.5a10 10 0 0 1 18.8-4.3"/><path d="M21.5 12.5a10 10 0 0 1-18.8 4.2"/>
        </svg>
        <span>Sync Onda</span>
    </button>
</form>
@endsection

@section('sidebar-items')
<div class="mes-sidebar-section">
    <div class="mes-sidebar-section-label">Navigazione</div>
    <a href="{{ route('spedizione.dashboard', ['op_token' => request('op_token')]) }}" class="mes-sidebar-item active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        Dashboard
    </a>
    <a href="{{ route('spedizione.esterne', ['op_token' => request('op_token')]) }}" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
        Esterne
    </a>
    <a href="#" class="mes-sidebar-item" data-bs-toggle="modal" data-bs-target="#modalNoteConsegne" onclick="caricaNote()">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        Note Giornaliere
    </a>
    <a href="#" class="mes-sidebar-item" data-bs-toggle="modal" data-bs-target="#modalBRT">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        Notifiche BRT
    </a>
</div>
<div class="mes-sidebar-section">
    <div class="mes-sidebar-section-label">Sezioni</div>
    <a href="#sezDaConsegnare" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        Da consegnare <span class="ms-auto badge" style="background:var(--success); color:#fff; font-size:10px;">{{ $fasiDaSpedire->count() }}</span>
    </a>
    <a href="#sezInAttesa" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        In attesa <span class="ms-auto badge" style="background:var(--warning); color:#000; font-size:10px;">{{ $fasiInAttesa->count() }}</span>
    </a>
    <a href="#sezDDT" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
        DDT Emesse <span class="ms-auto badge" style="background:var(--external); color:#fff; font-size:10px;">{{ $fasiDDT->count() }}</span>
    </a>
    <a href="#sezParziali" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
        Parziali <span class="ms-auto badge" style="background:var(--warning); color:#000; font-size:10px;">{{ $fasiParziali->count() }}</span>
    </a>
    <a href="#" class="mes-sidebar-item" data-bs-toggle="modal" data-bs-target="#modalSpediteOggi">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        Consegnate oggi
    </a>
    <a href="#" class="mes-sidebar-item" data-bs-toggle="modal" data-bs-target="#modalStorico">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        Storico
    </a>
</div>
@endsection

@section('content')
<div class="container-fluid px-0">
<style>
    .table-wrapper {
        width:100%;
        max-width:100%;
        overflow-x:auto;
        overflow-y:visible;
        margin: 0 4px;
    }
    h2, h4, p { margin-left:8px; margin-right:8px; }
    table th, table td { white-space:nowrap; }
    td.desc-col, td:nth-child(7){ white-space:normal; min-width:150px; max-width:220px; overflow:hidden; text-overflow:ellipsis; }

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

    .percorso-base { background: #d4edda !important; }
    .percorso-rilievi { background: #fff3cd !important; }
    .percorso-caldo { background: #f96f2a !important; }
    .percorso-completo { background: #f8d7da !important; }

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

    /* ===== RESPONSIVE MOBILE ===== */
    @media (max-width: 768px) {
        h2, h4, p { margin-left: 4px; margin-right: 4px; }
        h2 { font-size: 16px; }
        h4 { font-size: 15px; }

        /* Search bar area */
        .search-box {
            font-size: 16px !important;
            padding: 10px 14px !important;
            min-height: 44px;
            max-width: 100% !important;
        }

        /* Notes panel: stack below search on mobile */
        #notePanel {
            flex: 1 1 100% !important;
            white-space: normal !important;
        }
        #notePanel > div {
            flex-direction: column !important;
            gap: 8px !important;
        }
        #notePanel textarea {
            width: 100% !important;
            min-height: 200px !important;
            font-size: 14px !important;
        }
        #notePanel .btn {
            min-height: 44px;
            width: 100%;
        }

        /* Table wrapper */
        .table-wrapper {
            margin: 0 2px;
            -webkit-overflow-scrolling: touch;
        }
        table { font-size: 12px; }
        table th, table td { padding: 4px 6px; }

        /* Consegna buttons */
        .btn-consegna {
            padding: 10px 14px;
            font-size: 13px;
            min-height: 44px;
            min-width: 44px;
        }

        /* Progress bars */
        .progress-bar-custom { min-width: 60px; }

        /* Description column */
        td.desc-col, td:nth-child(7) {
            max-width: 140px;
            min-width: 100px;
        }

        /* Hide less important columns on main tables */
        #tabDaSpedire th:nth-child(5), #tabDaSpedire td:nth-child(5), /* Cod. Articolo */
        #tabDDT th:nth-child(4), #tabDDT td:nth-child(4),             /* Cod. Articolo */
        #tabInAttesa th:nth-child(5), #tabInAttesa td:nth-child(5),   /* Qta */
        #tabParziali th:nth-child(4), #tabParziali td:nth-child(4)     /* Cod. Articolo */
        {
            display: none;
        }

        /* Notes input in table */
        .table-wrapper input.form-control {
            min-height: 44px;
            font-size: 14px;
            min-width: 120px;
        }

    }

    @media (max-width: 480px) {
        h2 { font-size: 14px; }
        table { font-size: 11px; }
        .btn-consegna {
            padding: 8px 10px;
            font-size: 12px;
        }

        /* Hide even more columns */
        #tabDaSpedire th:nth-child(6), #tabDaSpedire td:nth-child(6),  /* Qta */
        #tabDDT th:nth-child(6), #tabDDT td:nth-child(6),              /* Qta Ordine */
        #tabDDT th:nth-child(7), #tabDDT td:nth-child(7),              /* Qta DDT */
        #tabInAttesa th:nth-child(4), #tabInAttesa td:nth-child(4)      /* Cod. Articolo */
        {
            display: none;
        }
    }

</style>

@php
    $consegneTotali = $fasiSpediteOggi->where('tipo_consegna', 'totale')->count();
    $consegneParziali = $fasiSpediteOggi->where('tipo_consegna', 'parziale')->count();
    $consegneSenzaTipo = $fasiSpediteOggi->whereNull('tipo_consegna')->count();
    $consegneTotali += $consegneSenzaTipo;
@endphp

<!-- Ricerca + matita note -->
<div style="display:flex; align-items:center; gap:8px; margin:12px 8px; flex-wrap:nowrap;">
    <input type="text" id="searchBox" class="form-control search-box" placeholder="Cerca commessa, cliente, descrizione..." style="margin:0; flex:1; min-width:200px;">
    <button data-bs-toggle="modal" data-bs-target="#modalNoteConsegne" onclick="caricaNote()" title="Note consegne" style="background:none; border:none; cursor:pointer; font-size:24px; color:#0d6efd; padding:4px 8px; position:relative;">&#9998;<span id="noteConsegneBadge" style="display:none; position:absolute; top:-2px; right:-2px; background:#dc3545; color:#fff; border-radius:50%; width:16px; height:16px; font-size:10px; font-weight:bold; text-align:center; line-height:16px;">!</span></button>
</div>

<!-- Modale Note Consegne -->
<div class="modal fade" id="modalNoteConsegne" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background:#0d6efd; color:#fff; padding:12px 20px;">
                <h5 class="modal-title" style="font-weight:700;">Note Consegne</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="padding:20px;">
                <textarea id="notaContenuto" rows="18" class="form-control" style="border-color:#0d6efd; font-size:14px; min-height:400px; resize:vertical;" placeholder="Note consegne..."></textarea>
                <div style="display:flex; align-items:center; justify-content:space-between; margin-top:12px;">
                    <span id="noteSaveStatus" style="font-size:12px; color:#6c757d;"></span>
                    <button onclick="salvaNote()" class="btn btn-primary" style="padding:8px 28px;">Salva</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabella DDT Emesse da Onda -->
<div id="sezDDT"></div>
@if($fasiDDT->count() > 0)
<h4 class="mx-2 mt-2" style="color:#6f42c1;">DDT Emesse da Onda</h4>
<div class="table-wrapper">
    <table class="table table-bordered table-sm sortable" id="tabDDT">
        <thead style="background:#6f42c1; color:#fff;">
            <tr>
                <th>Azione</th>
                <th data-sort="text" style="cursor:pointer;">Commessa <span class="sort-arrow">▼</span></th>
                <th data-sort="text" style="cursor:pointer;">Cliente <span class="sort-arrow">▼</span></th>
                <th>Cod. Articolo</th>
                <th>Descrizione</th>
                <th>Qta Ordine</th>
                <th>Qta DDT</th>
                <th>Suggerimento</th>
                <th>Vettore</th>
            </tr>
        </thead>
        <tbody>
            @foreach($fasiDDT as $fase)
                @php
                    $qtaOrdine = $fase->ordine->qta_richiesta ?? 0;
                    $qtaDDT = $fase->ordine->qta_ddt_vendita ?? 0;
                    $suggerimento = $qtaDDT >= $qtaOrdine ? 'totale' : 'parziale';
                    $numDDT = $fase->ordine->numero_ddt_vendita ?? '';
                    $vettore = $fase->ordine->vettore_ddt ?? '';
                    $isBRT = stripos($vettore, 'BRT') !== false;
                @endphp
                <tr class="searchable">
                    <td style="white-space:nowrap;">
                        <button class="btn-consegna btn-consegna-green" onclick="apriModalConsegnaDDT({{ $fase->id }}, 'totale')">Totale</button>
                        <button class="btn-consegna btn-consegna-orange" onclick="apriModalConsegnaDDT({{ $fase->id }}, 'parziale')">Parziale</button>
                        @if($numDDT)
                            <a href="{{ route('ddt.pdf', ltrim($numDDT, '0')) }}" target="_blank" class="btn btn-sm btn-outline-primary fw-bold" title="Stampa PDF DDT">PDF</a>
                        @endif
                    </td>
                    <td><a href="{{ route('commesse.show', $fase->ordine->commessa ?? '-') }}" class="commessa-link">{{ $fase->ordine->commessa ?? '-' }}</a></td>
                    <td>{{ $fase->ordine->cliente_nome ?? '-' }}</td>
                    <td>{{ $fase->ordine->cod_art ?? '-' }}</td>
                    <td class="desc-col">{{ $fase->ordine->descrizione ?? '-' }}</td>
                    <td>{{ $qtaOrdine }}</td>
                    <td>{{ $qtaDDT }}</td>
                    <td>
                        @if($suggerimento === 'totale')
                            <span class="badge bg-success">Totale</span>
                        @else
                            <span class="badge bg-warning text-dark">Parziale</span>
                        @endif
                    </td>
                    <td style="white-space:nowrap;">
                        @if($isBRT && $numDDT)
                            <button class="btn btn-sm btn-outline-danger fw-bold" onclick="apriTrackingDDT('{{ $numDDT }}', this)">
                                <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                                BRT Tracking
                            </button>
                        @elseif($vettore)
                            <span class="badge bg-secondary">{{ $vettore }}</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<!-- Tabella fasi da spedire -->
<h4 class="mx-2 mt-2" id="sezDaConsegnare" style="color:#28a745;">Da consegnare</h4>
<div class="table-wrapper">
    <table class="table table-bordered table-sm table-striped sortable" id="tabDaSpedire">
        <thead class="table-dark">
            <tr>
                <th>Azione</th>
                <th>Note</th>
                <th data-sort="text" style="cursor:pointer;">Commessa <span class="sort-arrow">▼</span></th>
                <th data-sort="text" style="cursor:pointer;">Cliente <span class="sort-arrow">▼</span></th>
                <th>Cod. Articolo</th>
                <th>Qta</th>
                <th>Descrizione</th>
                <th data-sort="date" style="cursor:pointer;">Data Consegna <span class="sort-arrow">▼</span></th>
                <th>Progresso</th>
            </tr>
        </thead>
        <tbody>
            @forelse($fasiDaSpedire as $fase)
                @php
                    $rowClass = $fase->ordine ? $fase->ordine->getPercorsoClass() : '';
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
                    <td class="desc-col">{{ $fase->ordine->descrizione ?? '-' }}</td>
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
<div id="sezParziali"></div>
@if($fasiParziali->count() > 0)
<h4 class="mx-2 mt-4" style="color:#fd7e14;">Consegne Parziali</h4>
<div class="table-wrapper">
    <table class="table table-bordered table-sm sortable" id="tabParziali">
        <thead style="background:#fd7e14; color:#fff;">
            <tr>
                <th>Azione</th>
                <th data-sort="text" style="cursor:pointer;">Commessa <span class="sort-arrow">▼</span></th>
                <th data-sort="text" style="cursor:pointer;">Cliente <span class="sort-arrow">▼</span></th>
                <th>Cod. Articolo</th>
                <th>Descrizione</th>
                <th data-sort="date" style="cursor:pointer;">Data cons. parziale <span class="sort-arrow">▼</span></th>
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
                    <td class="desc-col">{{ $fase->ordine->descrizione ?? '-' }}</td>
                    <td>{{ $fase->data_fine ? \Carbon\Carbon::parse($fase->data_fine)->format('d/m/Y H:i') : '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<!-- Tabella fasi in attesa -->
<div id="sezInAttesa"></div>
@if($fasiInAttesa->count() > 0)
<h4 class="mx-2 mt-4" style="color:#ffc107;">In attesa (lavorazione in corso)</h4>
<div class="table-wrapper">
    <table class="table table-bordered table-sm sortable" id="tabInAttesa">
        <thead style="background:#ffc107; color:#000;">
            <tr>
                <th>Azione</th>
                <th data-sort="text" style="cursor:pointer;">Commessa <span class="sort-arrow">▼</span></th>
                <th data-sort="text" style="cursor:pointer;">Cliente <span class="sort-arrow">▼</span></th>
                <th>Cod. Articolo</th>
                <th>Qta</th>
                <th>Descrizione</th>
                <th data-sort="date" style="cursor:pointer;">Data Consegna <span class="sort-arrow">▼</span></th>
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
                    <td class="desc-col">{{ $fase->ordine->descrizione ?? '-' }}</td>
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
                            <th>DDT</th>
                            <th>Tipo</th>
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
                            <td class="desc-col">{{ $fase->ordine->descrizione ?? '-' }}</td>
                            <td>{{ $fase->ordine->qta_richiesta ?? '-' }}</td>
                            <td>{{ $fase->ordine->numero_ddt_vendita ? ltrim($fase->ordine->numero_ddt_vendita, '0') : '-' }}</td>
                            <td>
                                @if($fase->tipo_consegna === 'parziale')
                                    <span class="badge bg-warning text-dark">Parziale</span>
                                @else
                                    <span class="badge bg-success">Totale</span>
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

<!-- Modal Storico Consegne -->
<div class="modal fade" id="modalStorico" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header" style="background:#6c757d; color:#fff;">
                <h5 class="modal-title">Storico consegne (ultimi 30 giorni)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="overflow-x:auto;">
                @if($storicoConsegne->count() > 0)
                @foreach($storicoConsegne->groupBy(fn($f) => \Carbon\Carbon::parse($f->data_fine)->format('Y-m-d')) as $data => $fasiGiorno)
                <h6 class="mt-3 mb-2 fw-bold" style="color:#333;">
                    {{ \Carbon\Carbon::parse($data)->format('d/m/Y') }}
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
                        @foreach($fasiGiorno as $fase)
                        <tr>
                            <td><strong>{{ $fase->ordine->commessa ?? '-' }}</strong></td>
                            <td>{{ $fase->ordine->cliente_nome ?? '-' }}</td>
                            <td>{{ $fase->ordine->cod_art ?? '-' }}</td>
                            <td class="desc-col">{{ $fase->ordine->descrizione ?? '-' }}</td>
                            <td>{{ $fase->ordine->qta_richiesta ?? '-' }}</td>
                            <td>
                                @if($fase->tipo_consegna === 'parziale')
                                    <span class="badge bg-warning text-dark">Parziale</span>
                                @else
                                    <span class="badge bg-success">Totale</span>
                                @endif
                            </td>
                            <td>{{ $fase->data_fine ? \Carbon\Carbon::parse($fase->data_fine)->format('H:i') : '-' }}</td>
                            <td>
                                @foreach($fase->operatori as $op)
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
    new bootstrap.Modal(document.getElementById('modalConsegna')).show();
}

function inviaConsegna(tipo) {
    var faseId = document.getElementById('mc_faseId').value;
    var forza = document.getElementById('mc_forza').value === '1';

    bootstrap.Modal.getInstance(document.getElementById('modalConsegna')).hide();

    fetch('{{ route("spedizione.invio") }}', {
        method: 'POST', headers: getHdrs(),
        body: JSON.stringify({ fase_id: parseInt(faseId), tipo_consegna: tipo, forza: forza })
    })
    .then(parseResponse)
    .then(data => {
        if (data.success) { window.location.reload(); }
        else { alert('Errore: ' + (data.messaggio || data.message || 'operazione fallita')); }
    })
    .catch(err => { if (err !== 'session_expired') { console.error('Errore:', err); alert('Errore: ' + err); } });
}

function apriModalConsegnaDDT(faseId, tipo) {
    if (!confirm('Confermi consegna ' + tipo + '?')) return;

    fetch('{{ route("spedizione.invio") }}', {
        method: 'POST', headers: getHdrs(),
        body: JSON.stringify({ fase_id: parseInt(faseId), tipo_consegna: tipo, forza: true })
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
// Tracking BRT via numero DDT (SOAP)
function apriTrackingDDT(numeroDDT, btn) {
    var ddtLabel = numeroDDT.replace(/^0+/, '') || numeroDDT;

    // Apri modal subito con loading
    document.getElementById('trackingSegnacollo').textContent = 'DDT ' + ddtLabel;
    document.getElementById('trackingLoading').classList.remove('d-none');
    document.getElementById('trackingErrore').classList.add('d-none');
    document.getElementById('trackingContenuto').classList.add('d-none');
    new bootstrap.Modal(document.getElementById('modalTracking')).show();

    fetch('{{ route("spedizione.trackingByDDT") }}', {
        method: 'POST', headers: getHdrs(),
        body: JSON.stringify({ numero_ddt: numeroDDT })
    })
    .then(parseResponse)
    .then(function(data) {
        document.getElementById('trackingLoading').classList.add('d-none');

        if (data.error) {
            document.getElementById('trackingErrore').textContent = data.message || 'Nessuna spedizione BRT trovata per DDT ' + ddtLabel;
            document.getElementById('trackingErrore').classList.remove('d-none');
            return;
        }

        // Se non c'è bolla, la spedizione esiste ma non è ancora elaborata
        if (!data.bolla || !data.bolla.spedizione_id) {
            document.getElementById('trackingErrore').innerHTML = '<strong>Spedizione trovata</strong> (ID: ' + (data.spedizione_id || '?') + ')<br>In attesa di elaborazione da BRT. Il tracking sarà disponibile dopo il ritiro del corriere.';
            document.getElementById('trackingErrore').className = 'alert alert-warning';
            document.getElementById('trackingErrore').classList.remove('d-none');
            return;
        }

        var bolla = data.bolla;

        // Titolo con DDT senza zeri
        document.getElementById('trackingSegnacollo').textContent = 'DDT ' + (bolla.rif_mittente_alfa || ddtLabel) + ' (Sped. ' + bolla.spedizione_id + ')';

        // Stato
        var statoEl = document.getElementById('trackingStato');
        statoEl.textContent = data.stato || 'IN ELABORAZIONE';
        if ((data.stato || '').indexOf('CONSEGNATA') >= 0) {
            statoEl.className = 'badge bg-success ms-1';
        } else if ((data.stato || '').indexOf('CONSEGNA') >= 0 || (data.stato || '').indexOf('PARTITA') >= 0) {
            statoEl.className = 'badge bg-warning text-dark ms-1';
        } else {
            statoEl.className = 'badge bg-info ms-1';
        }

        // Dettagli bolla
        document.getElementById('trackingDataConsegna').textContent = bolla.data_consegna ? (bolla.data_consegna + ' ' + (bolla.ora_consegna || '')) : '-';
        var destLabel = [bolla.destinatario_ragione_sociale, bolla.destinatario_localita, bolla.destinatario_provincia].filter(Boolean).join(' - ') || '-';
        document.getElementById('trackingDestinatario').textContent = destLabel;
        document.getElementById('trackingFiliale').textContent = bolla.filiale_arrivo || '-';
        document.getElementById('trackingColli').textContent = bolla.colli || '-';
        document.getElementById('trackingPeso').textContent = bolla.peso_kg || '-';

        // Eventi
        var eventiBody = document.getElementById('trackingEventi');
        eventiBody.innerHTML = '';
        var eventi = data.eventi || [];
        if (eventi.length === 0) {
            eventiBody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">Nessun evento disponibile</td></tr>';
        } else {
            eventi.forEach(function(ev) {
                var tr = document.createElement('tr');
                [ev.data || '-', ev.ora || '-', ev.descrizione || '-', ev.filiale || '-'].forEach(function(val) {
                    var td = document.createElement('td');
                    td.textContent = val;
                    tr.appendChild(td);
                });
                eventiBody.appendChild(tr);
            });
        }

        document.getElementById('trackingContenuto').classList.remove('d-none');
    })
    .catch(function(err) {
        document.getElementById('trackingLoading').classList.add('d-none');
        if (err !== 'session_expired') {
            document.getElementById('trackingErrore').textContent = 'Errore connessione BRT: ' + err;
            document.getElementById('trackingErrore').className = 'alert alert-danger';
            document.getElementById('trackingErrore').classList.remove('d-none');
        }
    });
}

// === Tracking BRT KPI ===
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
        var item = brtDDTList[i];
        labelProgress.textContent = (i + 1) + '/' + total + '...';
        caricaStatoBRT(item.ddt, item.hash, function() {
            i++;
            setTimeout(next, 300);
        });
    }
    next();
}

function caricaStatoBRT(numeroDDT, hash, callback) {
    fetch('{{ route("spedizione.trackingByDDT") }}', {
        method: 'POST', headers: getHdrs(),
        body: JSON.stringify({ numero_ddt: numeroDDT })
    })
    .then(parseResponse)
    .then(function(data) {
        var statoEl = document.getElementById('brt_stato_' + hash);
        var dataEl = document.getElementById('brt_data_' + hash);
        var destEl = document.getElementById('brt_dest_' + hash);
        var colliEl = document.getElementById('brt_colli_' + hash);

        if (data.multi_spedizione) {
            statoEl.innerHTML = '<span class="badge bg-purple" style="background:#7c3aed!important;">Multi-spedizione</span>';
            callback();
            return;
        }

        if (data.error) {
            statoEl.innerHTML = '<span class="badge bg-warning text-dark">In attesa</span>';
            callback();
            return;
        }

        if (!data.bolla || !data.bolla.spedizione_id) {
            statoEl.innerHTML = '<span class="badge bg-info">In elaborazione</span>';
            callback();
            return;
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
    .catch(function(err) {
        var statoEl = document.getElementById('brt_stato_' + hash);
        if (statoEl) statoEl.innerHTML = '<span class="badge bg-danger">Errore</span>';
        callback();
    });
}

// Ricerca con persistenza
var searchBox = document.getElementById('searchBox');
var savedSearch = localStorage.getItem('spedizione_search') || '';
if (savedSearch) {
    searchBox.value = savedSearch;
    filtraRicerca(savedSearch);
}
searchBox.addEventListener('input', function() {
    var query = this.value.trim();
    localStorage.setItem('spedizione_search', query);
    filtraRicerca(query);
});
function filtraRicerca(query) {
    var q = query.toLowerCase();
    document.querySelectorAll('tr.searchable').forEach(function(row) {
        var text = row.innerText.toLowerCase();
        row.style.display = (!q || text.includes(q)) ? '' : 'none';
    });
}

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
    var el = document.getElementById(modalId);
    if (el) el.addEventListener('hidden.bs.modal', function() {
        if (modalId === 'modalConsegna') fermaScanner('mc_scanner');
        if (modalId === 'modalSegnacollo') fermaScanner('msDDT_scanner');
    });
});

// Auto-carica tracking BRT quando il modal viene aperto
var brtModalCaricato = false;
document.getElementById('modalBRT').addEventListener('shown.bs.modal', function() {
    if (!brtModalCaricato && brtDDTList.length > 0) {
        brtModalCaricato = true;
        caricaTuttiTrackingBRT();
    }
});

// === Token e CSRF per fetch autenticate ===
var _opToken = '{{ request()->query("op_token", "") }}';
function urlToken(url) {
    if (!_opToken) return url;
    return url + (url.indexOf('?') >= 0 ? '&' : '?') + 'op_token=' + encodeURIComponent(_opToken);
}
function csrfToken() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

// === Note giornaliere ===

function caricaNote() {
    document.getElementById('noteConsegneBadge').style.display = 'none';
    fetch(urlToken('{{ route("spedizione.noteGiornaliere") }}?data={{ now()->toDateString() }}'), {
        headers: {'Accept': 'application/json'}
    })
    .then(r => r.json())
    .then(d => {
        document.getElementById('notaContenuto').value = d.contenuto || '';
        if (d.da_data) {
            var parts = d.da_data.split('-');
            document.getElementById('noteSaveStatus').textContent = '(dal ' + parts[2] + '/' + parts[1] + ')';
        } else {
            document.getElementById('noteSaveStatus').textContent = '';
        }
    })
    .catch(() => {});
}

function salvaNote() {
    var btn = event.target;
    btn.disabled = true;
    fetch(urlToken('{{ route("spedizione.salvaNotaGiornaliera") }}'), {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': csrfToken(),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            data: '{{ now()->toDateString() }}',
            contenuto: document.getElementById('notaContenuto').value
        })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            // Aggiorna timestamp per non auto-notificarsi
            fetch(urlToken('{{ route("spedizione.noteCheck") }}'), {headers:{'Accept':'application/json'}})
                .then(function(r){return r.json();})
                .then(function(dd){ if(dd.updated_at){ _noteLastUpdate=dd.updated_at; localStorage.setItem('noteConsegne_lastUpdate_sped',_noteLastUpdate); } });
            // Chiudi modale
            bootstrap.Modal.getInstance(document.getElementById('modalNoteConsegne')).hide();
        }
    })
    .catch(() => {
        document.getElementById('noteSaveStatus').textContent = 'Errore salvataggio';
        document.getElementById('noteSaveStatus').style.color = '#dc3545';
    })
    .finally(() => { btn.disabled = false; });
}

// === Notifiche Note Consegne (polling) ===
var _noteLastUpdate = localStorage.getItem('noteConsegne_lastUpdate_sped') || '';

// Sblocca AudioContext al primo click (richiesto dai browser)
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
    fetch(urlToken('{{ route("spedizione.noteCheck") }}'), {
        headers: {'Accept': 'application/json'}
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.updated_at && d.updated_at !== _noteLastUpdate) {
            if (_noteLastUpdate !== '') {
                document.getElementById('noteConsegneBadge').style.display = 'inline-block';
                try {
                    if (!_audioCtx) _audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                    if (_audioCtx.state === 'suspended') _audioCtx.resume();
                    var osc = _audioCtx.createOscillator();
                    var gain = _audioCtx.createGain();
                    osc.connect(gain); gain.connect(_audioCtx.destination);
                    osc.frequency.value = 800; gain.gain.value = 0.3;
                    osc.start(); osc.stop(_audioCtx.currentTime + 0.3);
                } catch(e) {}
                if ('Notification' in window && Notification.permission === 'granted') {
                    new Notification('Note Consegne aggiornate', {
                        body: (d.contenuto || '').substring(0, 100) || 'Le note consegne sono state aggiornate',
                        icon: '/favicon.ico'
                    });
                }
                showNoteToast('Le note consegne sono state aggiornate');
                document.getElementById('notaContenuto').value = d.contenuto || '';
            }
            _noteLastUpdate = d.updated_at;
            localStorage.setItem('noteConsegne_lastUpdate_sped', _noteLastUpdate);
        }
    })
    .catch(function(e) { console.error('noteCheck error:', e); });
}

function showNoteToast(msg) {
    var toast = document.createElement('div');
    toast.innerHTML = '<strong>Note Consegne</strong><br>' + msg;
    toast.style.cssText = 'position:fixed; top:20px; right:20px; z-index:9999; background:#0d6efd; color:#fff; padding:15px 20px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.3); font-size:14px; cursor:pointer; max-width:350px;';
    toast.onclick = function() {
        toast.remove();
        document.getElementById('noteConsegneBadge').style.display = 'none';
        var modal = new bootstrap.Modal(document.getElementById('modalNoteConsegne'));
        modal.show();
        caricaNote();
    };
    document.body.appendChild(toast);
    setTimeout(function() { if (toast.parentNode) toast.remove(); }, 30000);
}

// WebSocket con fallback per Note Consegne
if (window.listenOrPoll) {
    window.listenOrPoll('note-consegne', 'aggiornate', function(data) {
        var lastKnown = localStorage.getItem('noteConsegne_lastUpdate_sped') || '';
        if (data.updated_at && data.updated_at !== lastKnown) {
            localStorage.setItem('noteConsegne_lastUpdate_sped', data.updated_at);
            _beepNote();
            mostraToastNote('Note aggiornate da ' + (data.aggiornato_da || 'Owner'));
        }
    }, checkNoteConsegne, 15000);
} else {
    checkNoteConsegne();
    setInterval(checkNoteConsegne, 15000);
}

// === NOTIFICHE INVII ESTERNI ===
var _notifIdsViste = JSON.parse(localStorage.getItem('notifiche_sped_viste') || '[]');

function checkNotificheEsterne() {
    fetch('{{ route("spedizione.notifiche") }}')
    .then(r => r.json())
    .then(function(d) {
        if (!d.notifiche || d.notifiche.length === 0) return;
        d.notifiche.forEach(function(n) {
            if (_notifIdsViste.indexOf(n.id) === -1) {
                _notifIdsViste.push(n.id);
                localStorage.setItem('notifiche_sped_viste', JSON.stringify(_notifIdsViste));
                showNotificaEsterna(n);
                // Beep
                try {
                    var ctx = new (window.AudioContext || window.webkitAudioContext)();
                    var osc = ctx.createOscillator();
                    osc.type = 'sine'; osc.frequency.value = 880;
                    osc.connect(ctx.destination);
                    osc.start(); osc.stop(ctx.currentTime + 0.15);
                    setTimeout(function() {
                        var osc2 = ctx.createOscillator();
                        osc2.type = 'sine'; osc2.frequency.value = 1100;
                        osc2.connect(ctx.destination);
                        osc2.start(); osc2.stop(ctx.currentTime + 0.15);
                    }, 200);
                } catch(e) {}
            }
        });
    })
    .catch(function(e) { console.error('notifiche error:', e); });
}

function showNotificaEsterna(n) {
    var toast = document.createElement('div');
    toast.innerHTML = '<strong>📦 Invio Esterno</strong><br>' + n.messaggio;
    toast.style.cssText = 'position:fixed; top:20px; left:50%; transform:translateX(-50%); z-index:9999; background:#f59e0b; color:#000; padding:15px 20px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.3); font-size:14px; cursor:pointer; max-width:450px; text-align:center;';
    toast.onclick = function() {
        toast.remove();
        fetch('{{ url("/spedizione/notifiche") }}/' + n.id + '/letta', {method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'}});
    };
    document.body.appendChild(toast);
    setTimeout(function() { if (toast.parentNode) toast.remove(); }, 30000);
}

// WebSocket con fallback per Notifiche Esterne
if (window.listenOrPoll) {
    window.listenOrPoll('notifiche-esterne', 'nuova', function(data) {
        mostraToastEsterna({
            id: Date.now(),
            commessa: data.commessa,
            fase: data.fase,
            fornitore: data.fornitore
        });
    }, checkNotificheEsterne, 15000);
} else {
    checkNotificheEsterne();
    setInterval(checkNotificheEsterne, 15000);
}

// ===== Sort tabelle cliccando intestazione =====
document.querySelectorAll('table.sortable th[data-sort]').forEach(function(th) {
    th.addEventListener('click', function() {
        var table = th.closest('table');
        var tbody = table.querySelector('tbody');
        var rows = Array.from(tbody.querySelectorAll('tr'));
        var colIdx = Array.from(th.parentNode.children).indexOf(th);
        var sortType = th.getAttribute('data-sort');
        var asc = th.getAttribute('data-dir') !== 'asc';
        th.setAttribute('data-dir', asc ? 'asc' : 'desc');

        // Reset frecce
        th.closest('tr').querySelectorAll('.sort-arrow').forEach(function(s) { s.textContent = ''; });
        th.querySelector('.sort-arrow').textContent = asc ? ' \u25B2' : ' \u25BC';

        rows.sort(function(a, b) {
            var aVal = a.cells[colIdx] ? a.cells[colIdx].textContent.trim() : '';
            var bVal = b.cells[colIdx] ? b.cells[colIdx].textContent.trim() : '';

            if (sortType === 'date') {
                // Converte dd/mm/yyyy in Date
                var parseDate = function(s) {
                    var p = s.split('/');
                    return p.length === 3 ? new Date(p[2], p[1]-1, p[0]) : new Date(0);
                };
                aVal = parseDate(aVal);
                bVal = parseDate(bVal);
                return asc ? aVal - bVal : bVal - aVal;
            } else {
                return asc ? aVal.localeCompare(bVal, 'it') : bVal.localeCompare(aVal, 'it');
            }
        });

        rows.forEach(function(row) { tbody.appendChild(row); });
    });
});
</script>
@endsection
