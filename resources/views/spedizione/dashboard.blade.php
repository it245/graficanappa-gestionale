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

    /* Hamburger */
    .hamburger-btn {
        background: none; border: none; cursor: pointer; padding: 4px;
        display: flex; flex-direction: column; gap: 5px; transition: transform 0.15s ease;
    }
    .hamburger-btn:hover { transform: scale(1.1); }
    .hamburger-btn span {
        display: block; width: 28px; height: 3px; background: #333; border-radius: 2px;
    }
    /* Sidebar */
    .sidebar-overlay {
        display: none; position: fixed; top:0; left:0; right:0; bottom:0;
        background: rgba(0,0,0,0.4); z-index: 9998;
    }
    .sidebar-overlay.open { display: block; }
    .sidebar-menu {
        position: fixed; top:0; left:-300px; width: 280px; height: 100%;
        background: #fff; z-index: 9999; box-shadow: 2px 0 12px rgba(0,0,0,0.2);
        transition: left 0.25s ease; overflow-y: auto; padding-top: 15px;
    }
    .sidebar-menu.open { left: 0; }
    .sidebar-menu .sidebar-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 10px 18px 15px; border-bottom: 1px solid #dee2e6; margin-bottom: 5px;
    }
    .sidebar-menu .sidebar-header h5 { margin: 0; font-size: 16px; font-weight: 700; }
    .sidebar-close { background: none; border: none; font-size: 22px; cursor: pointer; color: #666; }
    .sidebar-close:hover { color: #000; }
    .sidebar-menu .sidebar-item {
        display: flex; align-items: center; gap: 12px; padding: 12px 18px;
        text-decoration: none; color: #333; font-size: 14px; font-weight: 500;
        border-bottom: 1px solid #f0f0f0; cursor: pointer; transition: background 0.15s;
    }
    .sidebar-menu .sidebar-item:hover { background: #f5f5f5; color: #000; }
    .sidebar-menu .sidebar-item .kpi-inline {
        font-size: 20px; font-weight: 700; min-width: 28px; text-align: center;
    }

</style>

@php
    $consegneTotali = $fasiSpediteOggi->where('tipo_consegna', 'totale')->count();
    $consegneParziali = $fasiSpediteOggi->where('tipo_consegna', 'parziale')->count();
    $consegneSenzaTipo = $fasiSpediteOggi->whereNull('tipo_consegna')->count();
    $consegneTotali += $consegneSenzaTipo;
@endphp

<div class="top-bar">
    <div style="display:flex; align-items:center; gap:12px;">
        <img src="{{ asset('images/logo_gn.png') }}" alt="Logo" style="height:40px;">
        <button class="hamburger-btn" id="hamburgerBtn" title="Menu">
            <span></span><span></span><span></span>
        </button>
        <h2 class="mb-0">Dashboard Spedizione</h2>
    </div>
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

<!-- Sidebar overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar menu -->
<div class="sidebar-menu" id="sidebarMenu">
    <div class="sidebar-header">
        <h5>Riepilogo</h5>
        <button class="sidebar-close" id="sidebarClose">&times;</button>
    </div>
    <div class="sidebar-item">
        <span class="kpi-inline" style="color:#28a745;">{{ $fasiDaSpedire->count() }}</span>
        <span>Da consegnare</span>
    </div>
    <div class="sidebar-item">
        <span class="kpi-inline" style="color:#ffc107;">{{ $fasiInAttesa->count() }}</span>
        <span>In attesa</span>
    </div>
    <div class="sidebar-item">
        <span class="kpi-inline" style="color:#6f42c1;">{{ $fasiDDT->count() }}</span>
        <span>DDT Emesse</span>
    </div>
    <div class="sidebar-item">
        <span class="kpi-inline" style="color:#fd7e14;">{{ $fasiParziali->count() }}</span>
        <span>Parziali in attesa</span>
    </div>
    <div class="sidebar-item">
        <span class="kpi-inline" style="color:#198754;">{{ $consegneTotali }}</span>
        <span>Consegne totali oggi</span>
    </div>
    <div class="sidebar-item">
        <span class="kpi-inline" style="color:#fd7e14;">{{ $consegneParziali }}</span>
        <span>Consegne parziali oggi</span>
    </div>
    <hr style="margin:4px 18px;">
    <a href="{{ route('spedizione.esterne') }}" class="sidebar-item">
        <span class="kpi-inline" style="color:#17a2b8;">{{ $fasiEsterne->count() }}</span>
        <span>Lav. esterne</span>
    </a>
    <a href="#" class="sidebar-item" data-bs-toggle="modal" data-bs-target="#modalSpediteOggi" onclick="closeSidebar()">
        <span class="kpi-inline" style="color:#0d6efd;">{{ $fasiSpediteOggi->count() }}</span>
        <span>Consegnate oggi</span>
    </a>
    <a href="#" class="sidebar-item" data-bs-toggle="modal" data-bs-target="#modalBRT" onclick="closeSidebar()">
        <span class="kpi-inline" style="color:#d4380d;">{{ $spedizioniBRT->count() }}</span>
        <span>Spedizioni BRT</span>
    </a>
    <hr style="margin:4px 18px;">
    <a href="#" class="sidebar-item" data-bs-toggle="modal" data-bs-target="#modalStorico" onclick="closeSidebar()">
        <span class="kpi-inline" style="color:#6c757d;">{{ $storicoConsegne->count() }}</span>
        <span>Storico consegne</span>
    </a>
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
                            <td>{{ $fase->ordine->descrizione ?? '-' }}</td>
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
                tr.innerHTML = '<td>' + (ev.data || '-') + '</td><td>' + (ev.ora || '-') + '</td><td>' + (ev.descrizione || '-') + '</td><td>' + (ev.filiale || '-') + '</td>';
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

// Ricerca
document.getElementById('searchBox').addEventListener('input', function() {
    const query = this.value.toLowerCase().trim();
    document.querySelectorAll('tr.searchable').forEach(function(row) {
        const text = row.innerText.toLowerCase();
        row.style.display = (!query || text.includes(query)) ? '' : 'none';
    });
});

// Sidebar
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

// Auto-carica tracking BRT quando il modal viene aperto
var brtModalCaricato = false;
document.getElementById('modalBRT').addEventListener('shown.bs.modal', function() {
    if (!brtModalCaricato && brtDDTList.length > 0) {
        brtModalCaricato = true;
        caricaTuttiTrackingBRT();
    }
});
</script>
@endsection
