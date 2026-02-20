@extends('layouts.app')

@section('content')
<style>
.ms-pipeline { display:flex; gap:2px; align-items:center; }
.ms-step { font-size:9px; padding:1px 4px; border-radius:3px; white-space:nowrap; }
.ms-done { background:#198754; color:#fff; }
.ms-partial { background:#ffc107; color:#000; }
.ms-todo { background:#e9ecef; color:#999; }
</style>

<div class="container-fluid px-3">
    <div class="d-flex justify-content-between align-items-center mb-3 mt-2">
        <h2>Job Prinect ({{ $jobs->count() }})</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('mes.prinect') }}" class="btn btn-outline-secondary btn-sm">Prinect</a>
            <a href="{{ route('mes.prinect.attivita') }}" class="btn btn-outline-primary btn-sm">Storico Attivita</a>
            <a href="{{ route('owner.dashboard') }}" class="btn btn-dark btn-sm">Dashboard</a>
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
                            <th>Commessa</th>
                            <th>Stato</th>
                            <th>Pipeline Produzione</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($jobs as $job)
                        @php
                            $status = $job['jobStatus']['globalStatus'] ?? '-';
                            $milestones = $job['jobStatus']['milestones'] ?? [];
                            $badgeClass = match($status) {
                                'FINISHED' => 'bg-success',
                                'ACTIVE' => 'bg-primary',
                                'RUNNING' => 'bg-info',
                                'SETUP' => 'bg-warning text-dark',
                                'ERROR' => 'bg-danger',
                                'CANCELLED' => 'bg-dark',
                                default => 'bg-secondary'
                            };
                            $anno = date('y');
                            $commessa = str_pad($job['id'], 7, '0', STR_PAD_LEFT) . '-' . $anno;
                        @endphp
                        <tr>
                            <td class="fw-bold">{{ $job['id'] }}</td>
                            <td>{{ $job['name'] }}</td>
                            <td>
                                @if(isset($commesseConAttivita[$commessa]))
                                    <a href="{{ route('mes.prinect.report', $commessa) }}">{{ $commessa }}</a>
                                    <span class="badge bg-info" style="font-size:9px;">STAMPA</span>
                                @else
                                    <span class="text-muted">{{ $commessa }}</span>
                                @endif
                            </td>
                            <td><span class="badge {{ $badgeClass }}">{{ $status }}</span></td>
                            <td>
                                <div class="ms-pipeline">
                                    @foreach($milestones as $m)
                                        @php
                                            $mName = $milestoneMap[$m['milestoneDefId']] ?? '?';
                                            $mProgress = $m['calculatedProgress'] ?? 0;
                                            $mStatus = $m['status'] ?? 'NORMAL';
                                            $mClass = ($mStatus === 'PROGRESS_FINISHED' || $mStatus === 'USER_FINISHED') ? 'ms-done'
                                                : ($mProgress > 0 ? 'ms-partial' : 'ms-todo');
                                        @endphp
                                        <span class="ms-step {{ $mClass }}" title="{{ $mName }}: {{ $mProgress }}%">{{ $mName }}</span>
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                <a href="{{ route('mes.prinect.jobDetail', $job['id']) }}" class="btn btn-outline-secondary btn-sm py-0">Dettaglio</a>
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
