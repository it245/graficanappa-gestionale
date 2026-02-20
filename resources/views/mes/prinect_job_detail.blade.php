@extends('layouts.app')

@section('content')
<style>
.ms-pipeline { display:flex; gap:3px; align-items:center; flex-wrap:wrap; }
.ms-step { font-size:10px; padding:2px 6px; border-radius:4px; white-space:nowrap; }
.ms-done { background:#198754; color:#fff; }
.ms-partial { background:#ffc107; color:#000; }
.ms-todo { background:#e9ecef; color:#999; }
.info-label { font-size:11px; text-transform:uppercase; letter-spacing:0.5px; color:#6c757d; margin-bottom:2px; }
.info-value { font-size:14px; font-weight:600; }
.plate-badge { font-size:10px; padding:2px 6px; border-radius:4px; margin:1px; display:inline-block; }
.plate-AVAILABLE, .plate-IMAGED { background:#198754; color:#fff; }
.plate-PENDING { background:#ffc107; color:#000; }
.plate-UNAVAILABLE { background:#e9ecef; color:#999; }
.plate-TO_BE_APPROVED { background:#0d6efd; color:#fff; }
.plate-APPROVED { background:#198754; color:#fff; }
.plate-REJECTED { background:#dc3545; color:#fff; }
</style>

<div class="container-fluid px-3">
    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-3 mt-2">
        <div>
            <h2 class="mb-0">Job {{ $jobId }} @if($job) - {{ $job['name'] }} @endif</h2>
            <small class="text-muted">Commessa: <strong>{{ $commessa }}</strong></small>
        </div>
        <div class="d-flex gap-2">
            @if($attivitaDB->isNotEmpty())
                <a href="{{ route('mes.prinect.report', $commessa) }}" class="btn btn-outline-success btn-sm">Report Stampa</a>
            @endif
            <a href="{{ route('mes.prinect.jobs') }}" class="btn btn-outline-secondary btn-sm">Lista Job</a>
            <a href="{{ route('mes.prinect') }}" class="btn btn-outline-secondary btn-sm">Dashboard</a>
        </div>
    </div>

    @if($job)
    {{-- INFO JOB + PIPELINE --}}
    <div class="row g-3 mb-3">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
                <div class="card-body py-3">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="info-label">Creazione</div>
                            <div class="info-value">{{ isset($job['creationDate']) ? \Carbon\Carbon::parse($job['creationDate'])->format('d/m/Y H:i') : '-' }}</div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-label">Ultima modifica</div>
                            <div class="info-value">{{ isset($job['lastModified']) ? \Carbon\Carbon::parse($job['lastModified'])->format('d/m/Y H:i') : '-' }}</div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-label">Stato</div>
                            <div class="info-value">
                                @php
                                    $gs = $job['jobStatus']['globalStatus'] ?? '-';
                                    $gsClass = match($gs) { 'ACTIVE'=>'bg-primary', 'RUNNING'=>'bg-info', 'FINISHED'=>'bg-success', 'SETUP'=>'bg-warning text-dark', default=>'bg-secondary' };
                                @endphp
                                <span class="badge {{ $gsClass }}">{{ $gs }}</span>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-label">Pagine</div>
                            <div class="info-value">{{ $job['numberPlannedPages'] ?? '-' }}</div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-label">Autore</div>
                            <div class="info-value" style="font-size:12px;">{{ $job['author'] ?? '-' }}</div>
                        </div>
                    </div>
                    @if($job['description'])
                    <div class="mt-2">
                        <div class="info-label">Descrizione</div>
                        <div>{{ $job['description'] }}</div>
                    </div>
                    @endif
                    {{-- Pipeline milestones --}}
                    <div class="mt-3">
                        <div class="info-label mb-1">Pipeline produzione</div>
                        <div class="ms-pipeline">
                            @foreach($job['jobStatus']['milestones'] ?? [] as $m)
                                @php
                                    $mName = $milestoneMap[$m['milestoneDefId']] ?? '?';
                                    $mProgress = $m['calculatedProgress'] ?? 0;
                                    $mStatus = $m['status'] ?? 'NORMAL';
                                    $mClass = ($mStatus === 'PROGRESS_FINISHED' || $mStatus === 'USER_FINISHED') ? 'ms-done'
                                        : ($mProgress > 0 ? 'ms-partial' : 'ms-todo');
                                @endphp
                                <span class="ms-step {{ $mClass }}">{{ $mName }} {{ $mProgress > 0 ? $mProgress.'%' : '' }}</span>
                                @if(!$loop->last)<span style="color:#ccc;">&#9654;</span>@endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- PREVIEW --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
                <div class="card-header bg-white border-0"><strong>Anteprima foglio</strong></div>
                <div class="card-body text-center p-2">
                    @if($preview)
                        <img src="data:{{ $preview['mimeType'] }};base64,{{ $preview['data'] }}"
                             alt="Preview" style="max-width:100%; max-height:250px; border-radius:8px; border:1px solid #dee2e6;">
                    @else
                        <div class="text-muted py-4">Nessuna anteprima disponibile</div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- FOGLI DI STAMPA + LASTRE --}}
    @if(!empty($elements['pressSheets']))
    <div class="row g-3 mb-3">
        @foreach($elements['pressSheets'] as $sheet)
        <div class="col-md-6">
            <div class="card border-0 shadow-sm" style="border-radius:12px;">
                <div class="card-header bg-white border-0">
                    <strong>{{ $sheet['sheetName'] ?? '-' }}</strong>
                    <span class="badge bg-secondary ms-1">Foglio stampa</span>
                </div>
                <div class="card-body py-2">
                    <div class="row">
                        <div class="col-4">
                            <div class="info-label">Carta</div>
                            <div class="info-value" style="font-size:13px;">{{ $sheet['brand'] ?? 'N/D' }}</div>
                        </div>
                        <div class="col-4">
                            <div class="info-label">Grammatura</div>
                            <div class="info-value" style="font-size:13px;">{{ $sheet['weight'] ?? '-' }} g/m&sup2;</div>
                        </div>
                        <div class="col-4">
                            <div class="info-label">Formato (cm)</div>
                            @php
                                $wCm = isset($sheet['width']) ? round($sheet['width'] / 72 * 2.54, 1) : '-';
                                $hCm = isset($sheet['height']) ? round($sheet['height'] / 72 * 2.54, 1) : '-';
                            @endphp
                            <div class="info-value" style="font-size:13px;">{{ $wCm }} x {{ $hCm }}</div>
                        </div>
                    </div>
                    @if(!empty($sheet['surfaces']))
                    <div class="mt-2">
                        @foreach($sheet['surfaces'] as $surf)
                        <div>
                            <span class="info-label">{{ $surf['name'] }}:</span>
                            @foreach($surf['colors'] ?? [] as $color)
                                @php
                                    $cn = strtolower($color);
                                    $cc = match(true) {
                                        str_contains($cn, 'cyan') => '#00bcd4',
                                        str_contains($cn, 'magenta') => '#e91e63',
                                        str_contains($cn, 'yellow') => '#ffc107',
                                        str_contains($cn, 'black') => '#333',
                                        default => '#9e9e9e'
                                    };
                                @endphp
                                <span class="badge" style="background:{{ $cc }}; font-size:9px;">{{ $color }}</span>
                            @endforeach
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- LASTRE --}}
    @if(!empty($elements['plates']))
    <div class="card border-0 shadow-sm mb-3" style="border-radius:12px;">
        <div class="card-header bg-white border-0">
            <strong>Lastre ({{ count($elements['plates']) }})</strong>
        </div>
        <div class="card-body py-2">
            @foreach($elements['plates'] as $plate)
                @php
                    $ps = $plate['plateStatus'] ?? 'UNAVAILABLE';
                @endphp
                <span class="plate-badge plate-{{ $ps }}" title="{{ $plate['sheetName'] ?? '' }} - {{ $plate['separationName'] ?? '' }} - {{ $ps }}">
                    {{ $plate['color'] ?? '?' }} / {{ $plate['surfaceName'] ?? '?' }}
                    @if($ps !== 'UNAVAILABLE') &#10003; @endif
                </span>
            @endforeach
        </div>
    </div>
    @endif
    @endif

    {{-- WORKSTEP STAMPA CON INK --}}
    @php $wsConInk = collect($worksteps)->filter(fn($ws) => !empty($ws['ink'])); @endphp

    @if($wsConInk->isNotEmpty())
    <div class="row g-3 mb-3">
        @foreach($wsConInk as $ws)
        <div class="col-md-6">
            <div class="card border-0 shadow-sm" style="border-radius:12px;">
                <div class="card-header bg-white border-0">
                    <strong>{{ $ws['name'] ?? '-' }}</strong>
                    <span class="badge {{ ($ws['status'] ?? '') === 'RUNNING' ? 'bg-primary' : (($ws['status'] ?? '') === 'COMPLETED' ? 'bg-success' : 'bg-warning text-dark') }} ms-1">{{ $ws['status'] ?? '-' }}</span>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-3 text-center">
                            <div class="info-label">Prodotti</div>
                            <div class="fw-bold text-success" style="font-size:18px;">{{ number_format($ws['amountProduced'] ?? 0) }}</div>
                        </div>
                        <div class="col-3 text-center">
                            <div class="info-label">Scarto</div>
                            <div class="fw-bold text-danger" style="font-size:18px;">{{ number_format($ws['wasteProduced'] ?? 0) }}</div>
                        </div>
                        <div class="col-3 text-center">
                            <div class="info-label">Avviamento</div>
                            @php $at = collect($ws['actualTimes'] ?? [])->firstWhere('timeTypeName', 'Tempo di avviamento'); @endphp
                            <div class="fw-bold" style="font-size:14px;">{{ $at ? floor($at['duration']/3600).'h'.floor(($at['duration']%3600)/60).'m' : '-' }}</div>
                        </div>
                        <div class="col-3 text-center">
                            <div class="info-label">Produzione</div>
                            @php $pt = collect($ws['actualTimes'] ?? [])->firstWhere('timeTypeName', 'Tempo di esecuzione'); @endphp
                            <div class="fw-bold" style="font-size:14px;">{{ $pt ? floor($pt['duration']/3600).'h'.floor(($pt['duration']%3600)/60).'m' : '-' }}</div>
                        </div>
                    </div>
                    @if(!empty($ws['ink']['inkConsumptions']))
                    <h6 class="mb-2" style="font-size:12px;">Consumo inchiostro (kg/1000 fogli)</h6>
                    <div style="position:relative; height:120px;">
                        <canvas id="inkChart_{{ $loop->index }}"></canvas>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- TABELLA TUTTI I WORKSTEP --}}
    <div class="card border-0 shadow-sm mb-3" style="border-radius:12px;">
        <div class="card-header bg-white border-0"><strong>Tutti i workstep ({{ count($worksteps) }})</strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0" style="font-size:13px;">
                    <thead class="table-light">
                        <tr>
                            <th>Nome</th>
                            <th>Tipo</th>
                            <th>Stato</th>
                            <th class="text-center">Pianificati</th>
                            <th class="text-center">Prodotti</th>
                            <th class="text-center">Scarto</th>
                            <th>Inizio</th>
                            <th>Fine</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($worksteps as $ws)
                        @php
                            $statusClass = match($ws['status'] ?? '') {
                                'COMPLETED' => 'bg-success',
                                'WAITING' => 'bg-warning text-dark',
                                'RUNNING' => 'bg-primary',
                                'SUSPENDED' => 'bg-secondary',
                                'ABORTED' => 'bg-danger',
                                default => 'bg-secondary'
                            };
                        @endphp
                        <tr>
                            <td class="fw-bold">{{ $ws['name'] ?? '-' }}</td>
                            <td><small>{{ implode(', ', array_slice($ws['types'] ?? [], 0, 2)) }}</small></td>
                            <td><span class="badge {{ $statusClass }}">{{ $ws['status'] ?? '-' }}</span></td>
                            <td class="text-center">{{ number_format($ws['amountPlanned'] ?? 0) }}</td>
                            <td class="text-center text-success fw-bold">{{ number_format($ws['amountProduced'] ?? 0) }}</td>
                            <td class="text-center text-danger">{{ number_format($ws['wasteProduced'] ?? 0) }}</td>
                            <td><small>{{ isset($ws['start']) ? \Carbon\Carbon::parse($ws['start'])->format('d/m H:i') : '-' }}</small></td>
                            <td><small>{{ isset($ws['end']) ? \Carbon\Carbon::parse($ws['end'])->format('d/m H:i') : '-' }}</small></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ATTIVITA STAMPA DA DB --}}
    @if($attivitaDB->isNotEmpty())
    <div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
        <div class="card-header bg-white border-0">
            <strong>Attivita stampa registrate ({{ $attivitaDB->count() }})</strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0" style="font-size:13px;">
                    <thead class="table-light">
                        <tr><th>Data</th><th>Tipo</th><th>Workstep</th><th class="text-center">Buoni</th><th class="text-center">Scarto</th><th>Durata</th><th>Operatore</th></tr>
                    </thead>
                    <tbody>
                        @foreach($attivitaDB->take(30) as $att)
                        <tr class="{{ $att->activity_name === 'Avviamento' ? 'table-warning' : 'table-success' }}">
                            <td>{{ $att->start_time ? $att->start_time->format('d/m H:i') : '-' }}</td>
                            <td><span class="badge {{ $att->activity_name === 'Avviamento' ? 'bg-warning text-dark' : 'bg-success' }}">{{ $att->activity_name === 'Avviamento' ? 'Avv' : 'Prod' }}</span></td>
                            <td class="small">{{ $att->workstep_name ?? '-' }}</td>
                            <td class="text-center text-success fw-bold">{{ $att->good_cycles > 0 ? number_format($att->good_cycles) : '-' }}</td>
                            <td class="text-center text-danger">{{ $att->waste_cycles > 0 ? number_format($att->waste_cycles) : '-' }}</td>
                            <td>
                                @if($att->start_time && $att->end_time)
                                    @php $d=$att->start_time->diffInSeconds($att->end_time); @endphp
                                    {{ floor($d/60) }}m {{ $d%60 }}s
                                @else - @endif
                            </td>
                            <td class="small">{{ $att->operatore_prinect ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
@foreach($wsConInk ?? collect() as $ws)
@if(!empty($ws['ink']['inkConsumptions']))
(function(){
    const inks = @json($ws['ink']['inkConsumptions']);
    const labels = inks.map(i => i.color || '?');
    const values = inks.map(i => i.estimatedConsumption || 0);
    const colors = labels.map(l => {
        const n = l.toLowerCase();
        if (n.includes('cyan')) return '#00bcd4';
        if (n.includes('magenta')) return '#e91e63';
        if (n.includes('yellow')) return '#ffc107';
        if (n.includes('black') || n.includes('nero')) return '#333';
        return '#9e9e9e';
    });
    new Chart(document.getElementById('inkChart_{{ $loop->index }}'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'kg/1000',
                data: values,
                backgroundColor: colors,
                borderRadius: 6
            }]
        },
        options: {
            responsive:true, maintainAspectRatio:false, resizeDelay:0,
            animation:false,
            scales:{ y:{ beginAtZero:true, title:{ display:true, text:'kg/1000 fogli' } } },
            plugins:{ legend:{ display:false } }
        }
    });
})();
@endif
@endforeach
</script>
@endsection
