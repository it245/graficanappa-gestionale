@extends('layouts.app')

@section('content')
<style>
    .kpi-card {
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: transform 0.15s;
        overflow: hidden;
    }
    .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.12); }
    .kpi-card .card-body { padding: 1rem 1.2rem; }
    .kpi-card .kpi-value { font-size: 1.8rem; font-weight: 700; line-height: 1.1; }
    .kpi-card .kpi-label { font-size: 0.78rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; }
    .kpi-card .kpi-delta { font-size: 0.82rem; font-weight: 600; margin-top: 0.3rem; }
    .kpi-accent-blue   { border-left: 4px solid #0d6efd; }
    .kpi-accent-green  { border-left: 4px solid #198754; }
    .kpi-accent-red    { border-left: 4px solid #dc3545; }
    .kpi-accent-orange { border-left: 4px solid #fd7e14; }
    .kpi-accent-purple { border-left: 4px solid #6f42c1; }
    .kpi-accent-teal   { border-left: 4px solid #20c997; }
    .section-title { font-size: 1.05rem; font-weight: 600; margin-bottom: 0.75rem; }
    .table-report { font-size: 13px; }
    .table-report th { white-space: nowrap; }
    .header-line { border-bottom: 3px solid #d11317; margin-bottom: 1.5rem; padding-bottom: 0.75rem; }
    .delta-up { color: #198754; }
    .delta-down { color: #dc3545; }
    .btn-periodo { min-width: 100px; }
    .btn-periodo.active { font-weight: 700; }
    .bottleneck-danger { background-color: #f8d7da !important; }

    @media print {
        .btn, .no-print { display: none !important; }
        .kpi-card { box-shadow: none !important; border: 1px solid #dee2e6; }
        .card { break-inside: avoid; }
        canvas { max-height: 250px !important; }
    }
</style>

<div class="container-fluid mt-3 px-4">
    {{-- Header --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center header-line">
        <div>
            <h3 class="mb-0">Report Direzione</h3>
            <small class="text-muted">{{ $labelPeriodo }}</small>
        </div>
        <div class="no-print d-flex flex-wrap align-items-center gap-2 mt-2 mt-md-0">
            {{-- Selettore periodo --}}
            @php
                $periodi = ['settimana' => 'Settimana', 'mese' => 'Mese', 'trimestre' => 'Trimestre', 'semestre' => 'Semestre', 'anno' => 'Anno'];
            @endphp
            @foreach($periodi as $key => $label)
                <a href="{{ route('admin.reportDirezione', ['periodo' => $key]) }}"
                   class="btn btn-sm btn-periodo {{ $periodo === $key ? 'btn-dark active' : 'btn-outline-dark' }}">
                    {{ $label }}
                </a>
            @endforeach
            <span class="mx-1"></span>
            <a href="{{ route('admin.reportDirezioneExcel', ['periodo' => $periodo]) }}" class="btn btn-sm btn-success">Export Excel</a>
            <button onclick="window.print()" class="btn btn-sm btn-outline-dark">Stampa</button>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-secondary">Dashboard</a>
        </div>
    </div>

    {{-- Riga 1: 6 KPI Cards --}}
    <div class="row g-3 mb-4">
        {{-- Fasi Completate --}}
        <div class="col-6 col-md-2">
            <div class="card kpi-card kpi-accent-blue">
                <div class="card-body">
                    <div class="kpi-value text-primary">{{ number_format($kpi->fasiCompletate) }}</div>
                    <div class="kpi-label">Fasi Completate</div>
                    @if($delta->fasiCompletate !== null)
                        <div class="kpi-delta {{ $delta->fasiCompletate >= 0 ? 'delta-up' : 'delta-down' }}">
                            {!! $delta->fasiCompletate >= 0 ? '&#9650;' : '&#9660;' !!} {{ abs($delta->fasiCompletate) }}%
                        </div>
                    @endif
                </div>
            </div>
        </div>
        {{-- Ore Lavorate --}}
        <div class="col-6 col-md-2">
            <div class="card kpi-card kpi-accent-purple">
                <div class="card-body">
                    <div class="kpi-value" style="color:#6f42c1">{{ $kpi->oreLavorate }}h</div>
                    <div class="kpi-label">Ore Lavorate</div>
                    @if($delta->oreLavorate !== null)
                        <div class="kpi-delta {{ $delta->oreLavorate >= 0 ? 'delta-up' : 'delta-down' }}">
                            {!! $delta->oreLavorate >= 0 ? '&#9650;' : '&#9660;' !!} {{ abs($delta->oreLavorate) }}%
                        </div>
                    @endif
                </div>
            </div>
        </div>
        {{-- Commesse Completate --}}
        <div class="col-6 col-md-2">
            <div class="card kpi-card kpi-accent-green">
                <div class="card-body">
                    <div class="kpi-value text-success">{{ $kpi->numCommesseCompletate }}</div>
                    <div class="kpi-label">Commesse Completate</div>
                    @if($delta->commesseCompletate !== null)
                        <div class="kpi-delta {{ $delta->commesseCompletate >= 0 ? 'delta-up' : 'delta-down' }}">
                            {!! $delta->commesseCompletate >= 0 ? '&#9650;' : '&#9660;' !!} {{ abs($delta->commesseCompletate) }}%
                        </div>
                    @endif
                </div>
            </div>
        </div>
        {{-- Commesse in Ritardo --}}
        <div class="col-6 col-md-2">
            <div class="card kpi-card kpi-accent-red">
                <div class="card-body">
                    <div class="kpi-value text-danger">{{ $kpi->numCommesseInRitardo }}</div>
                    <div class="kpi-label">In Ritardo</div>
                    @if($delta->commesseInRitardo !== null)
                        {{-- Per ritardi, meno e meglio: invertire colore --}}
                        <div class="kpi-delta {{ $delta->commesseInRitardo <= 0 ? 'delta-up' : 'delta-down' }}">
                            {!! $delta->commesseInRitardo >= 0 ? '&#9650;' : '&#9660;' !!} {{ abs($delta->commesseInRitardo) }}%
                        </div>
                    @endif
                </div>
            </div>
        </div>
        {{-- Tasso Puntualita --}}
        <div class="col-6 col-md-2">
            <div class="card kpi-card kpi-accent-orange">
                <div class="card-body">
                    <div class="kpi-value" style="color:#fd7e14">{{ $kpi->tassoPuntualita }}%</div>
                    <div class="kpi-label">Tasso Puntualita</div>
                    <div class="kpi-delta {{ $delta->tassoPuntualita >= 0 ? 'delta-up' : 'delta-down' }}">
                        {!! $delta->tassoPuntualita >= 0 ? '&#9650;' : '&#9660;' !!} {{ abs($delta->tassoPuntualita) }} pp
                    </div>
                </div>
            </div>
        </div>
        {{-- Scarto Prinect --}}
        <div class="col-6 col-md-2">
            <div class="card kpi-card kpi-accent-teal">
                <div class="card-body">
                    <div class="kpi-value" style="color:#20c997">{{ $kpi->scartoPercentuale }}%</div>
                    <div class="kpi-label">Scarto Prinect</div>
                    {{-- Per scarto, meno e meglio: invertire colore --}}
                    <div class="kpi-delta {{ $delta->scartoPercentuale <= 0 ? 'delta-up' : 'delta-down' }}">
                        {!! $delta->scartoPercentuale >= 0 ? '&#9650;' : '&#9660;' !!} {{ abs($delta->scartoPercentuale) }} pp
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Riga 2: Colli di Bottiglia Reparti --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-7">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Colli di Bottiglia Reparti</div>
                    @if($kpi->colliBottiglia->isEmpty())
                        <p class="text-muted mb-0">Nessun dato disponibile.</p>
                    @else
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-report mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Reparto</th>
                                    <th class="text-end">Coda</th>
                                    <th class="text-end">In Corso</th>
                                    <th class="text-end">Completate</th>
                                    <th class="text-end">T. Medio</th>
                                    <th class="text-end">Indice BN</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($kpi->colliBottiglia as $r)
                                <tr class="{{ $r->indice_bottleneck > 2 ? 'bottleneck-danger' : '' }}">
                                    <td><strong>{{ $r->nome }}</strong></td>
                                    <td class="text-end">{{ $r->coda }}</td>
                                    <td class="text-end">{{ $r->in_corso }}</td>
                                    <td class="text-end">{{ $r->completate_periodo }}</td>
                                    <td class="text-end">{{ $r->tempo_medio_sec > 0 ? round($r->tempo_medio_sec / 60, 1) . ' min' : '-' }}</td>
                                    <td class="text-end">
                                        <strong class="{{ $r->indice_bottleneck > 2 ? 'text-danger' : '' }}">{{ $r->indice_bottleneck }}</strong>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12 col-md-5">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Distribuzione Reparti</div>
                    <canvas id="chartReparti" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Riga 3: Trend Produzione + Confronto Periodi --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-8">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Trend Produzione</div>
                    <canvas id="chartTrend" height="110"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Confronto Periodi</div>
                    <table class="table table-sm table-report mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>KPI</th>
                                <th class="text-end">Attuale</th>
                                <th class="text-end">Precedente</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Fasi Completate</td>
                                <td class="text-end"><strong>{{ number_format($kpi->fasiCompletate) }}</strong></td>
                                <td class="text-end">{{ number_format($kpiPrev->fasiCompletate) }}</td>
                            </tr>
                            <tr>
                                <td>Ore Lavorate</td>
                                <td class="text-end"><strong>{{ $kpi->oreLavorate }}h</strong></td>
                                <td class="text-end">{{ $kpiPrev->oreLavorate }}h</td>
                            </tr>
                            <tr>
                                <td>Commesse Completate</td>
                                <td class="text-end"><strong>{{ $kpi->numCommesseCompletate }}</strong></td>
                                <td class="text-end">{{ $kpiPrev->numCommesseCompletate }}</td>
                            </tr>
                            <tr>
                                <td>In Ritardo</td>
                                <td class="text-end"><strong>{{ $kpi->numCommesseInRitardo }}</strong></td>
                                <td class="text-end">{{ $kpiPrev->numCommesseInRitardo }}</td>
                            </tr>
                            <tr>
                                <td>Puntualita</td>
                                <td class="text-end"><strong>{{ $kpi->tassoPuntualita }}%</strong></td>
                                <td class="text-end">{{ $kpiPrev->tassoPuntualita }}%</td>
                            </tr>
                            <tr>
                                <td>Scarto Prinect</td>
                                <td class="text-end"><strong>{{ $kpi->scartoPercentuale }}%</strong></td>
                                <td class="text-end">{{ $kpiPrev->scartoPercentuale }}%</td>
                            </tr>
                        </tbody>
                    </table>
                    <small class="text-muted d-block mt-2">Precedente: {{ $labelPrecedente }}</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Riga 4: Performance Operatori --}}
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Performance Operatori</div>
                    @if($kpi->operatoriPerf->isEmpty())
                        <p class="text-muted mb-0">Nessun dato disponibile.</p>
                    @else
                    <div class="row">
                        <div class="col-12 col-lg-7">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped table-report mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>#</th>
                                            <th>Operatore</th>
                                            <th>Reparti</th>
                                            <th class="text-end">Fasi</th>
                                            <th class="text-end">Ore</th>
                                            <th class="text-end">Qta</th>
                                            <th class="text-end">T.Medio</th>
                                            <th class="text-end">Fasi/gg</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($kpi->operatoriPerf as $i => $op)
                                        <tr>
                                            <td>{{ $i + 1 }}</td>
                                            <td><strong>{{ $op->nome }} {{ $op->cognome }}</strong></td>
                                            <td>{{ $op->reparti ?: '-' }}</td>
                                            <td class="text-end">{{ $op->fasi_completate }}</td>
                                            <td class="text-end">{{ $op->ore_lavorate }}h</td>
                                            <td class="text-end">{{ number_format($op->qta_prodotta ?? 0) }}</td>
                                            <td class="text-end">{{ $op->tempo_medio_sec > 0 ? round($op->tempo_medio_sec / 60, 1) . 'm' : '-' }}</td>
                                            <td class="text-end">{{ $op->fasi_giorno }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-12 col-lg-5">
                            <div class="section-title">Top 10 Operatori</div>
                            <canvas id="chartOperatori" height="220"></canvas>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Riga 5: Analisi Pause + Scarto Prinect --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Analisi Pause</div>
                    @if($kpi->motiviPausa->isEmpty())
                        <p class="text-muted mb-0">Nessuna pausa nel periodo.</p>
                    @else
                    <div class="row">
                        <div class="col-7">
                            <table class="table table-sm table-striped table-report mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Motivo</th>
                                        <th class="text-end">N.</th>
                                        <th class="text-end">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($kpi->motiviPausa as $m)
                                    <tr>
                                        <td>{{ $m->motivo }}</td>
                                        <td class="text-end">{{ $m->totale }}</td>
                                        <td class="text-end">{{ $m->percentuale }}%</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="col-5">
                            <canvas id="chartPause" height="180"></canvas>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Scarto Prinect</div>
                    <canvas id="chartScarto" height="120"></canvas>
                    @if($kpi->topScartoCommesse->isNotEmpty())
                    <div class="mt-3">
                        <small class="text-muted fw-bold">Top 5 Commesse per Scarto</small>
                        <table class="table table-sm table-striped table-report mb-0 mt-1">
                            <thead class="table-dark">
                                <tr>
                                    <th>Commessa</th>
                                    <th class="text-end">Buoni</th>
                                    <th class="text-end">Scarto</th>
                                    <th class="text-end">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($kpi->topScartoCommesse as $s)
                                <tr>
                                    <td>{{ $s->commessa_gestionale }}</td>
                                    <td class="text-end">{{ number_format($s->good) }}</td>
                                    <td class="text-end">{{ number_format($s->waste) }}</td>
                                    <td class="text-end"><strong>{{ $s->scarto_pct }}%</strong></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Riga 6: Analisi Clienti + Commesse Completate --}}
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Top Clienti</div>
                    @if($kpi->topClienti->isEmpty())
                        <p class="text-muted mb-0">Nessun dato disponibile.</p>
                    @else
                    <table class="table table-sm table-striped table-report mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Cliente</th>
                                <th class="text-end">Commesse</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($kpi->topClienti as $i => $c)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $c->cliente_nome }}</td>
                                <td class="text-end"><strong>{{ $c->commesse }}</strong></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Commesse Completate ({{ $kpi->numCommesseCompletate }})</div>
                    @if($kpi->dettaglioCompletate->isEmpty())
                        <p class="text-muted mb-0">Nessuna commessa completata nel periodo.</p>
                    @else
                    <div class="table-responsive" style="max-height:350px; overflow-y:auto;">
                        <table class="table table-sm table-striped table-report mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Commessa</th>
                                    <th>Cliente</th>
                                    <th class="text-end">Fasi</th>
                                    <th class="text-end">Ore</th>
                                    <th>Consegna</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($kpi->dettaglioCompletate as $c)
                                <tr>
                                    <td><strong>{{ $c->commessa }}</strong></td>
                                    <td>{{ $c->cliente }}</td>
                                    <td class="text-end">{{ $c->fasi_totali }}</td>
                                    <td class="text-end">{{ $c->ore_totali }}h</td>
                                    <td>{{ $c->data_consegna ? \Carbon\Carbon::parse($c->data_consegna)->format('d/m/Y') : '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Riga 7: Commesse in Ritardo --}}
    @if($kpi->commesseInRitardo->count() > 0)
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card border-danger">
                <div class="card-body">
                    <div class="section-title text-danger">Commesse in Ritardo ({{ $kpi->commesseInRitardo->count() }})</div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-report mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Commessa</th>
                                    <th>Cliente</th>
                                    <th>Data Consegna</th>
                                    <th class="text-end">Ritardo</th>
                                    <th>Avanzamento</th>
                                    <th>Fasi Mancanti</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($kpi->commesseInRitardo as $r)
                                <tr>
                                    <td><strong>{{ $r->commessa }}</strong></td>
                                    <td>{{ $r->cliente_nome ?? '-' }}</td>
                                    <td>{{ $r->data_prevista_consegna ? \Carbon\Carbon::parse($r->data_prevista_consegna)->format('d/m/Y') : '-' }}</td>
                                    <td class="text-end"><span class="badge bg-danger">{{ $r->giorni_ritardo }}gg</span></td>
                                    <td style="min-width:120px">
                                        <div class="progress" style="height:18px">
                                            <div class="progress-bar {{ $r->avanzamento >= 80 ? 'bg-warning' : 'bg-danger' }}"
                                                 style="width:{{ $r->avanzamento }}%">{{ $r->avanzamento }}%</div>
                                        </div>
                                    </td>
                                    <td><small>{{ $r->fasi_mancanti }}</small></td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const colori = ['#0d6efd','#198754','#dc3545','#fd7e14','#6f42c1','#20c997','#0dcaf0','#ffc107','#6610f2','#d63384'];

    // Granularita adattiva per etichette
    const granularita = @json($kpi->granularita);
    const mesiIt = ['','Gen','Feb','Mar','Apr','Mag','Giu','Lug','Ago','Set','Ott','Nov','Dic'];
    function formatLabel(val) {
        if (granularita === 'mese') {
            const p = val.split('-');
            return mesiIt[parseInt(p[1])] + ' ' + p[0];
        } else if (granularita === 'settimana') {
            return 'S' + val.split('-W')[1] + ' ' + val.split('-')[0];
        } else {
            const p = val.split('-');
            return p[2] + '/' + p[1];
        }
    }

    // --- 1. Trend Produzione (linea + barre) ---
    const trendLabels = @json(array_keys($kpi->trendGiornaliero));
    const trendFasi = @json(array_values($kpi->trendGiornaliero));
    const trendOre = @json(array_values($kpi->oreTrendGiornaliero));

    new Chart(document.getElementById('chartTrend'), {
        data: {
            labels: trendLabels.map(d => formatLabel(d)),
            datasets: [
                {
                    type: 'bar',
                    label: 'Fasi completate',
                    data: trendFasi,
                    backgroundColor: 'rgba(13,110,253,0.6)',
                    borderRadius: 3,
                    yAxisID: 'y',
                    order: 2
                },
                {
                    type: 'line',
                    label: 'Ore lavorate',
                    data: trendOre,
                    borderColor: '#fd7e14',
                    backgroundColor: 'rgba(253,126,20,0.1)',
                    tension: 0.3,
                    pointRadius: 3,
                    fill: true,
                    yAxisID: 'y1',
                    order: 1
                }
            ]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            scales: {
                y:  { beginAtZero: true, position: 'left',  title: { display: true, text: 'Fasi' } },
                y1: { beginAtZero: true, position: 'right', title: { display: true, text: 'Ore' }, grid: { drawOnChartArea: false } }
            },
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // --- 2. Reparti (barre orizzontali impilate) ---
    const repLabels = @json($kpi->colliBottiglia->pluck('nome'));
    const repCoda = @json($kpi->colliBottiglia->pluck('coda'));
    const repCorso = @json($kpi->colliBottiglia->pluck('in_corso'));
    const repDone = @json($kpi->colliBottiglia->pluck('completate_periodo'));

    new Chart(document.getElementById('chartReparti'), {
        type: 'bar',
        data: {
            labels: repLabels,
            datasets: [
                { label: 'In coda',     data: repCoda,  backgroundColor: '#ffc107' },
                { label: 'In corso',    data: repCorso, backgroundColor: '#0d6efd' },
                { label: 'Completate',  data: repDone,  backgroundColor: '#198754' }
            ]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            scales: {
                x: { stacked: true, beginAtZero: true },
                y: { stacked: true }
            },
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // --- 3. Top 10 Operatori (barre orizzontali) ---
    const opData = @json($kpi->operatoriPerf->take(10)->values());
    if (opData.length > 0) {
        new Chart(document.getElementById('chartOperatori'), {
            type: 'bar',
            data: {
                labels: opData.map(o => o.nome + ' ' + o.cognome),
                datasets: [{
                    label: 'Fasi completate',
                    data: opData.map(o => o.fasi_completate),
                    backgroundColor: colori.slice(0, opData.length),
                    borderRadius: 3
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                scales: { x: { beginAtZero: true } },
                plugins: { legend: { display: false } }
            }
        });
    }

    // --- 4. Pause (ciambella) ---
    const pauseLabels = @json($kpi->motiviPausa->pluck('motivo'));
    const pauseData = @json($kpi->motiviPausa->pluck('totale'));

    if (pauseLabels.length > 0) {
        new Chart(document.getElementById('chartPause'), {
            type: 'doughnut',
            data: {
                labels: pauseLabels,
                datasets: [{
                    data: pauseData,
                    backgroundColor: colori.slice(0, pauseLabels.length),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                cutout: '55%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } }
                }
            }
        });
    }

    // --- 5. Scarto Prinect trend (linea) ---
    const scartoTrend = @json($kpi->prinectTrend);
    if (scartoTrend.length > 0) {
        new Chart(document.getElementById('chartScarto'), {
            type: 'line',
            data: {
                labels: scartoTrend.map(r => formatLabel(r.periodo)),
                datasets: [{
                    label: 'Scarto %',
                    data: scartoTrend.map(r => r.scarto_pct),
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220,53,69,0.1)',
                    tension: 0.3,
                    pointRadius: 3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Scarto %' } }
                },
                plugins: { legend: { display: false } }
            }
        });
    }

});
</script>
@endsection
