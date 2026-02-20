@extends('layouts.app')

@section('content')
<div class="container-fluid px-3">
    <div class="d-flex justify-content-between align-items-center mb-3 mt-2">
        <h2>Job Prinect ({{ $jobs->count() }})</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('mes.prinect') }}" class="btn btn-outline-secondary btn-sm">Dashboard</a>
            <a href="{{ route('mes.prinect.attivita') }}" class="btn btn-outline-primary btn-sm">Storico Attivita</a>
        </div>
    </div>

    <div class="card border-0 shadow-sm" style="border-radius:12px;">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0" style="font-size:13px;">
                    <thead class="table-light">
                        <tr>
                            <th>Job ID</th>
                            <th>Nome</th>
                            <th>Cliente</th>
                            <th>Commessa</th>
                            <th>Qta richiesta</th>
                            <th>Data consegna</th>
                            <th>Stato</th>
                            <th title="Progresso workflow Prinect (prepress), non stampa effettiva">Progresso</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($jobs as $job)
                        @php
                            $status = $job['jobStatus']['globalStatus'] ?? '-';
                            $milestones = $job['jobStatus']['milestones'] ?? [];
                            $maxProgress = 0;
                            foreach ($milestones as $m) {
                                $p = $m['calculatedProgress'] ?? 0;
                                if ($p > $maxProgress) $maxProgress = $p;
                            }
                            $badgeClass = match($status) {
                                'FINISHED' => 'bg-success',
                                'PROGRESS_FINISHED' => 'bg-success',
                                'NORMAL' => 'bg-primary',
                                'ERROR' => 'bg-danger',
                                default => 'bg-secondary'
                            };
                            $anno = date('y');
                            $commessa = str_pad($job['id'], 7, '0', STR_PAD_LEFT) . '-' . $anno;
                        @endphp
                        <tr>
                            <td class="fw-bold">{{ $job['id'] }}</td>
                            <td>{{ $job['name'] }}</td>
                            <td>{{ $job['jobCustomer']['name'] ?? '-' }}</td>
                            <td>
                                @if(isset($commesseConAttivita[$commessa]))
                                    <a href="{{ route('mes.prinect.report', $commessa) }}">{{ $commessa }}</a>
                                    <span class="badge bg-info" style="font-size:9px;" title="Dati stampa disponibili">STAMPA</span>
                                @else
                                    <span class="text-muted">{{ $commessa }}</span>
                                @endif
                            </td>
                            <td class="text-center">{{ number_format($job['deliveryAmount'] ?? 0) }}</td>
                            <td>{{ $job['dueDate'] ? \Carbon\Carbon::parse($job['dueDate'])->format('d/m/Y') : '-' }}</td>
                            <td><span class="badge {{ $badgeClass }}">{{ $status }}</span></td>
                            <td>
                                <div class="progress" style="height:18px; min-width:100px;">
                                    <div class="progress-bar {{ $maxProgress >= 100 ? 'bg-success' : 'bg-primary' }}"
                                         style="width:{{ min($maxProgress, 100) }}%">
                                        {{ $maxProgress }}%
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="{{ route('mes.prinect.jobDetail', $job['id']) }}" class="btn btn-outline-secondary btn-sm py-0" title="Dettaglio worksteps">Dettaglio</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
