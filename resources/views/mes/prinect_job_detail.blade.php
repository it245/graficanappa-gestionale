@extends('layouts.app')

@section('content')
<div class="container-fluid px-3">
    <div class="d-flex justify-content-between align-items-center mb-3 mt-2">
        <div>
            <h2>Job {{ $jobId }} - Worksteps</h2>
            <small class="text-muted">{{ count($worksteps) }} workstep trovati</small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('mes.prinect.jobs') }}" class="btn btn-outline-secondary btn-sm">Lista Job</a>
            <a href="{{ route('mes.prinect') }}" class="btn btn-outline-secondary btn-sm">Dashboard</a>
        </div>
    </div>

    {{-- Worksteps con ink --}}
    @php
        $wsConInk = collect($worksteps)->filter(fn($ws) => !empty($ws['ink']));
    @endphp

    @if($wsConInk->isNotEmpty())
    <div class="row g-3 mb-4">
        @foreach($wsConInk as $ws)
        <div class="col-md-6">
            <div class="card border-0 shadow-sm" style="border-radius:12px;">
                <div class="card-header bg-white border-0">
                    <strong>{{ $ws['name'] ?? '-' }}</strong>
                    <span class="badge bg-secondary ms-2">{{ $ws['sequenceType'] ?? '-' }}</span>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-6">
                            <small class="text-muted">Prodotti</small>
                            <div class="fw-bold text-success">{{ number_format($ws['amountProduced'] ?? 0) }}</div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Scarto</small>
                            <div class="fw-bold text-danger">{{ number_format($ws['wasteProduced'] ?? 0) }}</div>
                        </div>
                    </div>
                    @if(!empty($ws['ink']['inkConsumption']))
                    <h6 class="mb-2">Consumo inchiostro (kg/1000 fogli)</h6>
                    <canvas id="inkChart_{{ $loop->index }}" height="150"></canvas>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Tabella tutti i workstep --}}
    <div class="card border-0 shadow-sm" style="border-radius:12px;">
        <div class="card-header bg-white border-0"><strong>Tutti i workstep</strong></div>
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
                            <th class="text-center">% Scarto</th>
                            <th>Inizio</th>
                            <th>Fine</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($worksteps as $ws)
                        @php
                            $prod = $ws['amountProduced'] ?? 0;
                            $waste = $ws['wasteProduced'] ?? 0;
                            $tot = $prod + $waste;
                            $perc = $tot > 0 ? round(($waste/$tot)*100,1) : 0;
                            $statusClass = match($ws['status'] ?? '') {
                                'FINISHED' => 'bg-success',
                                'WAITING' => 'bg-warning text-dark',
                                'RUNNING' => 'bg-primary',
                                default => 'bg-secondary'
                            };
                        @endphp
                        <tr>
                            <td class="fw-bold">{{ $ws['name'] ?? '-' }}</td>
                            <td><small>{{ $ws['sequenceType'] ?? '-' }}</small></td>
                            <td><span class="badge {{ $statusClass }}">{{ $ws['status'] ?? '-' }}</span></td>
                            <td class="text-center">{{ number_format($ws['amountPlanned'] ?? 0) }}</td>
                            <td class="text-center text-success fw-bold">{{ number_format($prod) }}</td>
                            <td class="text-center text-danger">{{ number_format($waste) }}</td>
                            <td class="text-center">{{ $perc }}%</td>
                            <td><small>{{ isset($ws['start']) ? \Carbon\Carbon::parse($ws['start'])->format('d/m H:i') : '-' }}</small></td>
                            <td><small>{{ isset($ws['end']) ? \Carbon\Carbon::parse($ws['end'])->format('d/m H:i') : '-' }}</small></td>
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
@foreach($wsConInk ?? collect() as $ws)
@if(!empty($ws['ink']['inkConsumption']))
(function(){
    const inks = @json($ws['ink']['inkConsumption']);
    const labels = inks.map(i => i.inkName || i.inkId || '?');
    const values = inks.map(i => i.consumptionPerThousand || 0);
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
            responsive:true, maintainAspectRatio:false,
            scales:{ y:{ beginAtZero:true, title:{ display:true, text:'kg/1000 fogli' } } },
            plugins:{ legend:{ display:false } }
        }
    });
})();
@endif
@endforeach
</script>
@endsection
