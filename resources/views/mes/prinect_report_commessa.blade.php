@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2>Report Commessa {{ $commessa }}</h2>
            <p class="text-muted mb-0">{{ $jobName }} (Job ID: {{ $jobId }})</p>
        </div>
        <div>
            <a href="{{ route('mes.prinect.attivita') }}" class="btn btn-outline-secondary btn-sm">Torna allo Storico</a>
            <a href="{{ route('mes.prinect') }}" class="btn btn-outline-secondary btn-sm">Dashboard</a>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-2">
            <div class="card border-success h-100">
                <div class="card-body text-center">
                    <div class="text-muted small">Fogli buoni</div>
                    <div class="fs-3 fw-bold text-success">{{ number_format($totBuoni) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-danger h-100">
                <div class="card-body text-center">
                    <div class="text-muted small">Fogli scarto</div>
                    <div class="fs-3 fw-bold text-danger">{{ number_format($totScarto) }}</div>
                    <div class="small text-muted">{{ $percScarto }}%</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="text-muted small">Fogli totali</div>
                    <div class="fs-3 fw-bold">{{ number_format($totFogli) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-warning h-100">
                <div class="card-body text-center">
                    <div class="text-muted small">Tempo avviamento</div>
                    <div class="fs-3 fw-bold text-warning">{{ floor($tempoAvviamentoSec/3600) }}h {{ floor(($tempoAvviamentoSec%3600)/60) }}m</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-primary h-100">
                <div class="card-body text-center">
                    <div class="text-muted small">Tempo produzione</div>
                    <div class="fs-3 fw-bold text-primary">{{ floor($tempoProduzioneSec/3600) }}h {{ floor(($tempoProduzioneSec%3600)/60) }}m</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-dark h-100">
                <div class="card-body text-center">
                    <div class="text-muted small">Tempo totale</div>
                    <div class="fs-3 fw-bold">{{ floor($tempoTotaleSec/3600) }}h {{ floor(($tempoTotaleSec%3600)/60) }}m</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Grafici --}}
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header"><strong>Ripartizione tempo</strong></div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <canvas id="chartTempo" style="max-height:250px"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header"><strong>Fogli buoni vs scarto</strong></div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <canvas id="chartFogli" style="max-height:250px"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header"><strong>Produzione per workstep</strong></div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <canvas id="chartWorkstep" style="max-height:250px"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- Grafico timeline --}}
    <div class="card mb-4">
        <div class="card-header"><strong>Timeline attivita</strong></div>
        <div class="card-body">
            <canvas id="chartTimeline" style="height:200px"></canvas>
        </div>
    </div>

    {{-- Tabella per workstep --}}
    <div class="card mb-4">
        <div class="card-header"><strong>Dettaglio per workstep</strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Workstep</th>
                            <th>Fogli buoni</th>
                            <th>Fogli scarto</th>
                            <th>% Scarto</th>
                            <th>Tempo avviamento</th>
                            <th>Tempo produzione</th>
                            <th>N. Attivita</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($perWorkstep as $ws => $dati)
                            @php
                                $totWs = $dati->buoni + $dati->scarto;
                                $percWs = $totWs > 0 ? round(($dati->scarto / $totWs) * 100, 1) : 0;
                            @endphp
                            <tr>
                                <td><strong>{{ $ws }}</strong></td>
                                <td class="text-success fw-bold">{{ number_format($dati->buoni) }}</td>
                                <td class="text-danger">{{ number_format($dati->scarto) }}</td>
                                <td>{{ $percWs }}%</td>
                                <td>{{ floor($dati->sec_avviamento/3600) }}h {{ floor(($dati->sec_avviamento%3600)/60) }}m</td>
                                <td>{{ floor($dati->sec_produzione/3600) }}h {{ floor(($dati->sec_produzione%3600)/60) }}m</td>
                                <td>{{ $dati->n_attivita }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Tabella per operatore --}}
    <div class="card mb-4">
        <div class="card-header"><strong>Dettaglio per operatore</strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Operatore</th>
                            <th>Fogli buoni</th>
                            <th>Fogli scarto</th>
                            <th>Tempo totale</th>
                            <th>N. Attivita</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($perOperatore as $nome => $dati)
                            <tr>
                                <td><strong>{{ $nome ?? '-' }}</strong></td>
                                <td class="text-success fw-bold">{{ number_format($dati->buoni) }}</td>
                                <td class="text-danger">{{ number_format($dati->scarto) }}</td>
                                <td>{{ floor($dati->sec_totali/3600) }}h {{ floor(($dati->sec_totali%3600)/60) }}m</td>
                                <td>{{ $dati->n_attivita }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Dettaglio attivita --}}
    <div class="card mb-4">
        <div class="card-header"><strong>Tutte le attivita ({{ $attivita->count() }})</strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Inizio</th>
                            <th>Fine</th>
                            <th>Durata</th>
                            <th>Tipo</th>
                            <th>Workstep</th>
                            <th>Buoni</th>
                            <th>Scarto</th>
                            <th>Operatore</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($attivita->sortByDesc('start_time') as $att)
                            <tr class="@if($att->activity_name === 'Avviamento') table-warning @else table-success @endif">
                                <td>{{ $att->start_time ? $att->start_time->format('d/m H:i:s') : '-' }}</td>
                                <td>{{ $att->end_time ? $att->end_time->format('d/m H:i:s') : '-' }}</td>
                                <td>
                                    @if($att->start_time && $att->end_time)
                                        @php
                                            $diff = $att->start_time->diffInSeconds($att->end_time);
                                            $min = floor($diff / 60);
                                            $sec = $diff % 60;
                                        @endphp
                                        {{ $min }}m {{ $sec }}s
                                    @else - @endif
                                </td>
                                <td>
                                    @if($att->activity_name === 'Avviamento')
                                        <span class="badge bg-warning text-dark">Avviamento</span>
                                    @else
                                        <span class="badge bg-success">Produzione</span>
                                    @endif
                                </td>
                                <td><small>{{ $att->workstep_name ?? '-' }}</small></td>
                                <td>
                                    @if($att->good_cycles > 0)
                                        <span class="text-success fw-bold">{{ number_format($att->good_cycles) }}</span>
                                    @else - @endif
                                </td>
                                <td>
                                    @if($att->waste_cycles > 0)
                                        <span class="text-danger">{{ number_format($att->waste_cycles) }}</span>
                                    @else - @endif
                                </td>
                                <td>{{ $att->operatore_prinect ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
// Helpers
function formatMin(sec){ const h=Math.floor(sec/3600); const m=Math.floor((sec%3600)/60); return h>0 ? h+'h '+m+'m' : m+'m'; }

const avvSec = {{ $tempoAvviamentoSec }};
const prodSec = {{ $tempoProduzioneSec }};
const buoni = {{ $totBuoni }};
const scarto = {{ $totScarto }};

// 1. Pie - Tempo
new Chart(document.getElementById('chartTempo'), {
    type: 'doughnut',
    data: {
        labels: ['Avviamento ('+formatMin(avvSec)+')', 'Produzione ('+formatMin(prodSec)+')'],
        datasets: [{
            data: [Math.round(avvSec/60), Math.round(prodSec/60)],
            backgroundColor: ['#ffc107', '#0d6efd']
        }]
    },
    options: { responsive:true, plugins:{ legend:{ position:'bottom' } } }
});

// 2. Pie - Fogli
new Chart(document.getElementById('chartFogli'), {
    type: 'doughnut',
    data: {
        labels: ['Buoni ('+buoni.toLocaleString()+')', 'Scarto ('+scarto.toLocaleString()+')'],
        datasets: [{
            data: [buoni, scarto],
            backgroundColor: ['#198754', '#dc3545']
        }]
    },
    options: { responsive:true, plugins:{ legend:{ position:'bottom' } } }
});

// 3. Bar - Workstep
const wsData = @json($perWorkstep);
const wsLabels = Object.keys(wsData);
const wsBuoni = wsLabels.map(k => wsData[k].buoni);
const wsScarto = wsLabels.map(k => wsData[k].scarto);

new Chart(document.getElementById('chartWorkstep'), {
    type: 'bar',
    data: {
        labels: wsLabels.map(l => l.length > 20 ? l.substring(0,20)+'...' : l),
        datasets: [
            { label:'Buoni', data:wsBuoni, backgroundColor:'#198754' },
            { label:'Scarto', data:wsScarto, backgroundColor:'#dc3545' }
        ]
    },
    options: {
        responsive:true,
        indexAxis: wsLabels.length > 5 ? 'y' : 'x',
        scales:{ x:{ stacked:true }, y:{ stacked:true } },
        plugins:{ legend:{ position:'bottom' } }
    }
});

// 4. Timeline
const timeline = @json($chartData);
const tlLabels = timeline.map((t,i) => {
    const d = new Date(t.start);
    return (d.getDate()+'/'+(d.getMonth()+1)+' '+d.getHours()+':'+String(d.getMinutes()).padStart(2,'0'));
});
const tlColors = timeline.map(t => t.tipo === 'Avviamento' ? '#ffc107' : '#198754');
const tlDurate = timeline.map(t => t.durata_min);

new Chart(document.getElementById('chartTimeline'), {
    type: 'bar',
    data: {
        labels: tlLabels,
        datasets: [{
            label: 'Durata (min)',
            data: tlDurate,
            backgroundColor: tlColors,
            borderWidth: 0
        }]
    },
    options: {
        responsive:true,
        maintainAspectRatio:false,
        scales:{
            x:{ ticks:{ maxRotation:90, font:{size:9} } },
            y:{ beginAtZero:true, title:{ display:true, text:'Minuti' } }
        },
        plugins:{
            legend:{ display:false },
            tooltip:{
                callbacks:{
                    afterLabel: function(ctx){
                        const t = timeline[ctx.dataIndex];
                        return t.tipo+' | '+t.workstep+'\nBuoni: '+t.buoni+' | Scarto: '+t.scarto+'\n'+t.operatore;
                    }
                }
            }
        }
    }
});
</script>
@endsection
