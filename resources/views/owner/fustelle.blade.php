@extends('layouts.app')

@section('content')
<style>
    .top-bar {
        display: flex; justify-content: space-between; align-items: center;
        padding: 8px 12px; background: #1a1a2e; color: #fff;
    }
    .top-bar a { color: #fff; text-decoration: none; font-size: 14px; font-weight: 600; background: rgba(255,255,255,.15); padding: 6px 14px; border-radius: 6px; }
    .top-bar a:hover { background: rgba(255,255,255,.25); }

    .btn-stampa {
        background: rgba(255,255,255,.15); color: #fff; border: none; padding: 6px 14px;
        border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600;
    }
    .btn-stampa:hover { background: rgba(255,255,255,.25); }

    .fustelle-layout { display: flex; gap: 0; min-height: calc(100vh - 56px); }

    .fustelle-sidebar {
        width: 320px; min-width: 320px; background: #f8f9fa; border-right: 1px solid #dee2e6;
        padding: 16px 0; overflow-y: auto; max-height: calc(100vh - 56px); position: sticky; top: 0;
    }
    .sidebar-title {
        font-size: 1.2rem; font-weight: 700; color: #6c757d; text-transform: uppercase;
        letter-spacing: .5px; padding: 0 16px 12px; border-bottom: 1px solid #dee2e6; margin-bottom: 4px;
    }
    .sidebar-item {
        display: flex; justify-content: space-between; align-items: center;
        padding: 14px 16px; cursor: pointer; font-size: 1.25rem; border-left: 4px solid transparent;
        transition: background .15s;
    }
    .sidebar-item:hover { background: #e9ecef; }
    .sidebar-item.active { background: #e2e6ea; border-left-color: #0d6efd; font-weight: 700; }
    .sidebar-item .fs-name { font-family: 'Courier New', monospace; font-weight: 700; color: #1a1a2e; font-size: 1.35rem; }
    .sidebar-item .fs-count {
        background: #0d6efd; color: #fff; font-size: 1rem; font-weight: 700;
        padding: 3px 10px; border-radius: 10px; min-width: 28px; text-align: center;
    }
    .sidebar-item.scaduta-item .fs-count { background: #dc3545; }

    .fustelle-container { padding: 16px; flex: 1; min-width: 0; }

    .fs-card {
        background: #fff; border: 1px solid #dee2e6; border-radius: 8px;
        margin-bottom: 16px; overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,.08);
    }
    .fs-header {
        display: flex; justify-content: space-between; align-items: center;
        padding: 12px 16px; background: #f8f9fa; border-bottom: 1px solid #dee2e6;
        cursor: pointer;
    }
    .fs-header:hover { background: #e9ecef; }
    .fs-code {
        font-size: 1.3rem; font-weight: 700; color: #1a1a2e;
        font-family: 'Courier New', monospace;
    }
    .fs-badge {
        display: inline-block; background: #0d6efd; color: #fff;
        padding: 2px 10px; border-radius: 12px; font-size: 0.85rem; font-weight: 600;
    }
    .fs-body { padding: 0; }
    .fs-body table { margin: 0; }
    .fs-body th { background: #f1f3f5; font-size: 0.85rem; padding: 8px 12px !important; }
    .fs-body td { font-size: 0.85rem; padding: 6px 12px !important; vertical-align: middle; }

    .badge-stato {
        display: inline-block; padding: 2px 8px; border-radius: 4px;
        font-size: 0.8rem; font-weight: 600; color: #fff;
    }
    .badge-stato-0 { background: #6c757d; }
    .badge-stato-1 { background: #ffc107; color: #333; }
    .badge-stato-2 { background: #0d6efd; }

    .badge-consegna-scaduta { background: #dc3545; color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem; }
    .badge-consegna-oggi { background: #fd7e14; color: #fff; padding: 2px 6px; border-radius: 4px; font-size: 0.8rem; }

    .kpi-row {
        display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap;
    }
    .kpi-card {
        flex: 1; min-width: 140px; background: #fff; border: 1px solid #dee2e6;
        border-radius: 8px; padding: 16px; text-align: center;
        border-top: 3px solid #0d6efd;
    }
    .kpi-card .kpi-value { font-size: 2rem; font-weight: 700; color: #1a1a2e; }
    .kpi-card .kpi-label { font-size: 0.85rem; color: #6c757d; margin-top: 4px; }

    .desc-cell { max-width: 280px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    .empty-state {
        text-align: center; padding: 60px 20px; color: #6c757d;
    }
    .empty-state .icon { font-size: 3rem; margin-bottom: 12px; }

    @media print {
        .top-bar, .fustelle-sidebar, .kpi-row, .btn-stampa,
        .filtri-bar-fustelle, .hide-print { display: none !important; }
        .fustelle-layout { display: block !important; }
        .fustelle-container { padding: 0 !important; }
        .fs-card { break-inside: avoid; margin-bottom: 12px; box-shadow: none; border: 1px solid #aaa; }
        .fs-header { background: #eee !important; }
        .fs-body { display: block !important; }
        body { font-size: 12px; }
        h2.print-title { display: block !important; }
    }
    h2.print-title { display: none; }
</style>

<div class="top-bar">
    <div style="display:flex; align-items:center; gap:10px;">
        <img src="{{ asset('images/logo_gn.png') }}" alt="Logo" style="height:40px;">
        <span style="font-size:16px; font-weight:700;">Fustelle</span>
    </div>
    <div style="display:flex; align-items:center; gap:15px;">
        <a href="{{ route('owner.dashboard', ['op_token' => request('op_token')]) }}">Dashboard</a>
        <button class="btn-stampa" onclick="window.print()">Stampa</button>
    </div>
</div>

<h2 class="print-title" style="padding:16px 0 0 16px;">Fustelle — prossimi 30 giorni</h2>

<div class="fustelle-layout">

{{-- SIDEBAR --}}
<div class="fustelle-sidebar">
    <div class="sidebar-title">Fustelle ({{ count($fustelleMap) }})</div>
    <div class="sidebar-item" onclick="filtroSidebar('')" data-sidebar-codice="">
        <span style="font-weight:600; color:#333;">Tutte</span>
        <span class="fs-count" style="background:#6c757d;">{{ count($fustelleMap) }}</span>
    </div>
    @foreach($fustelleMap as $codice => $commesse)
        @php
            $haScadute = collect($commesse)->contains(fn($c) => $c['data_consegna'] && \Carbon\Carbon::parse($c['data_consegna'])->lt(\Carbon\Carbon::today()));
        @endphp
        <div class="sidebar-item {{ $haScadute ? 'scaduta-item' : '' }}"
             onclick="filtroSidebar('{{ $codice }}')" data-sidebar-codice="{{ $codice }}">
            <span class="fs-name">{{ $codice }}</span>
            <span class="fs-count">{{ count($commesse) }}</span>
        </div>
    @endforeach
</div>

{{-- CONTENUTO --}}
<div class="fustelle-container">
    <h2 style="margin-bottom: 4px;">Fustelle</h2>
    <p style="color: #6c757d; margin-bottom: 16px;">Fustelle da utilizzare nei prossimi 30 giorni</p>

    @php
        $totFustelle = count($fustelleMap);
        $totCommesse = collect($fustelleMap)->flatten(1)->count();
        $scadute = collect($fustelleMap)->flatten(1)->filter(fn($c) => $c['data_consegna'] && \Carbon\Carbon::parse($c['data_consegna'])->lt(\Carbon\Carbon::today()))->count();
    @endphp

    <div class="kpi-row">
        <div class="kpi-card">
            <div class="kpi-value">{{ $totFustelle }}</div>
            <div class="kpi-label">Fustelle</div>
        </div>
        <div class="kpi-card" style="border-top-color: #198754;">
            <div class="kpi-value">{{ $totCommesse }}</div>
            <div class="kpi-label">Commesse associate</div>
        </div>
        @if($scadute > 0)
        <div class="kpi-card" style="border-top-color: #dc3545;">
            <div class="kpi-value" style="color: #dc3545;">{{ $scadute }}</div>
            <div class="kpi-label">Scadute</div>
        </div>
        @endif
    </div>

    <div class="filtri-bar-fustelle" style="margin-bottom: 16px; display:flex; align-items:center; gap:10px;">
        <label for="filtroFustella" style="font-weight:600; font-size:0.9rem;">Filtra fustella:</label>
        <select id="filtroFustella" onchange="filtraFustelle(this.value)" style="padding:6px 12px; border:1px solid #ced4da; border-radius:6px; font-size:0.9rem; min-width:180px;">
            <option value="">Tutte ({{ $totFustelle }})</option>
            @foreach($fustelleMap as $cod => $comm)
                <option value="{{ $cod }}">{{ $cod }} ({{ count($comm) }})</option>
            @endforeach
        </select>
        <input type="text" id="filtroCliente" oninput="filtraFustelle(document.getElementById('filtroFustella').value)" placeholder="Cerca cliente..." style="padding:6px 12px; border:1px solid #ced4da; border-radius:6px; font-size:0.9rem; width:200px;">
        <button onclick="document.getElementById('filtroFustella').value=''; document.getElementById('filtroCliente').value=''; filtraFustelle('');" style="padding:6px 12px; border:1px solid #adb5bd; background:#fff; border-radius:6px; cursor:pointer; font-size:0.9rem;">Reset</button>
    </div>

    @forelse($fustelleMap as $codice => $commesse)
        <div class="fs-card" data-fustella="{{ $codice }}">
            <div class="fs-header" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'none' ? '' : 'none'">
                <span class="fs-code">{{ $codice }}</span>
                <span class="fs-badge">{{ count($commesse) }} commess{{ count($commesse) === 1 ? 'a' : 'e' }}</span>
            </div>
            <div class="fs-body">
                <table class="table table-bordered table-sm table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Commessa</th>
                            <th>Cliente</th>
                            <th>Descrizione</th>
                            <th>Consegna</th>
                            <th>Stato</th>
                            <th class="hide-print">Reparto</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($commesse as $c)
                            @php
                                $consegna = $c['data_consegna'] ? \Carbon\Carbon::parse($c['data_consegna']) : null;
                                $oggi = \Carbon\Carbon::today();
                                $scaduta = $consegna && $consegna->lt($oggi);
                                $isOggi = $consegna && $consegna->isSameDay($oggi);
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('owner.dettaglioCommessa', $c['commessa']) }}" target="_blank" style="font-weight:600;">
                                        {{ $c['commessa'] }}
                                    </a>
                                </td>
                                <td>{{ $c['cliente'] }}</td>
                                <td class="desc-cell" title="{{ $c['descrizione'] }}">{{ Str::limit($c['descrizione'], 60) }}</td>
                                <td>
                                    @if($consegna)
                                        @if($scaduta)
                                            <span class="badge-consegna-scaduta">{{ $consegna->format('d/m/Y') }}</span>
                                        @elseif($isOggi)
                                            <span class="badge-consegna-oggi">OGGI</span>
                                        @else
                                            {{ $consegna->format('d/m/Y') }}
                                        @endif
                                    @else
                                        -
                                    @endif
                                </td>
                                <td><span class="badge-stato badge-stato-{{ $c['stato'] }}">{{ $c['stato'] }}</span></td>
                                <td class="hide-print">{{ ucfirst($c['fase']) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="empty-state">
            <div class="icon">&#9881;</div>
            <h4>Nessuna fustella prevista</h4>
            <p>Non ci sono fustelle da utilizzare nei prossimi 30 giorni.</p>
        </div>
    @endforelse
</div>
</div>

<script>
function filtroSidebar(codice) {
    document.querySelectorAll('.sidebar-item').forEach(function(item) {
        item.classList.toggle('active', item.dataset.sidebarCodice === codice);
    });
    document.getElementById('filtroFustella').value = codice;
    filtraFustelle(codice);
    if (codice) {
        var card = document.querySelector('.fs-card[data-fustella="' + codice + '"]');
        if (card) card.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function filtraFustelle(codice) {
    var clienteFiltro = (document.getElementById('filtroCliente').value || '').toLowerCase();
    document.querySelectorAll('.sidebar-item').forEach(function(item) {
        item.classList.toggle('active', item.dataset.sidebarCodice === codice);
    });
    document.querySelectorAll('.fs-card').forEach(function(card) {
        var matchCodice = !codice || card.dataset.fustella === codice;
        var matchCliente = true;
        if (clienteFiltro) {
            matchCliente = false;
            card.querySelectorAll('tbody tr').forEach(function(row) {
                if (row.cells[1] && row.cells[1].textContent.toLowerCase().indexOf(clienteFiltro) !== -1) {
                    matchCliente = true;
                }
            });
        }
        card.style.display = (matchCodice && matchCliente) ? '' : 'none';
    });
}
</script>
@endsection
