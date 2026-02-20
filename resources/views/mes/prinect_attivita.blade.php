@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Storico Attivita Prinect</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('mes.prinect') }}" class="btn btn-outline-secondary btn-sm">Prinect</a>
            <a href="{{ route('mes.prinect.jobs') }}" class="btn btn-outline-secondary btn-sm">Lista Job</a>
            <a href="{{ route('owner.dashboard') }}" class="btn btn-dark btn-sm">Dashboard</a>
        </div>
    </div>

    {{-- Filtri --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label mb-0 small">Job ID</label>
                    <input type="text" name="job" class="form-control form-control-sm" value="{{ request('job') }}" placeholder="es. 66455">
                </div>
                <div class="col-auto">
                    <label class="form-label mb-0 small">Tipo</label>
                    <select name="tipo" class="form-select form-select-sm">
                        <option value="">Tutti</option>
                        <option value="Avviamento" @selected(request('tipo') === 'Avviamento')>Avviamento</option>
                        <option value="Produzione fogli buoni" @selected(request('tipo') === 'Produzione fogli buoni')>Produzione</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label mb-0 small">Data da</label>
                    <input type="date" name="da" class="form-control form-control-sm" value="{{ request('da') }}">
                </div>
                <div class="col-auto">
                    <label class="form-label mb-0 small">Data a</label>
                    <input type="date" name="a" class="form-control form-control-sm" value="{{ request('a') }}">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">Filtra</button>
                    <a href="{{ route('mes.prinect.attivita') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Riepilogo per job --}}
    @if($riepilogoJobs->isNotEmpty())
    <div class="card mb-3">
        <div class="card-header">
            <strong>Riepilogo per Job</strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Job ID</th>
                            <th>Descrizione</th>
                            <th>Commessa</th>
                            <th>Fogli buoni</th>
                            <th>Fogli scarto</th>
                            <th>% Scarto</th>
                            <th>N. Attivita</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($riepilogoJobs as $job)
                            <tr>
                                <td>{{ $job->prinect_job_id }}</td>
                                <td>{{ $job->prinect_job_name }}</td>
                                <td>
                                    @if($job->commessa_gestionale)
                                        <a href="{{ route('mes.prinect.report', $job->commessa_gestionale) }}" class="fw-bold">{{ $job->commessa_gestionale }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="text-success fw-bold">{{ number_format($job->total_good) }}</td>
                                <td class="text-danger">{{ number_format($job->total_waste) }}</td>
                                <td>
                                    @php
                                        $totale = $job->total_good + $job->total_waste;
                                        $percentuale = $totale > 0 ? round(($job->total_waste / $totale) * 100, 1) : 0;
                                    @endphp
                                    {{ $percentuale }}%
                                </td>
                                <td>{{ $job->count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- Dettaglio attivita --}}
    <div class="card">
        <div class="card-header">
            <strong>Dettaglio attivita ({{ $attivita->total() }})</strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Inizio</th>
                            <th>Fine</th>
                            <th>Durata</th>
                            <th>Tipo</th>
                            <th>Job</th>
                            <th>Commessa</th>
                            <th>Workstep</th>
                            <th>Buoni</th>
                            <th>Scarto</th>
                            <th>Operatore</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attivita as $att)
                            <tr class="@if($att->activity_name === 'Avviamento') table-warning @elseif($att->activity_name === 'Produzione fogli buoni') table-success @endif">
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
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($att->activity_name === 'Avviamento')
                                        <span class="badge bg-warning text-dark">Avviamento</span>
                                    @elseif($att->activity_name === 'Produzione fogli buoni')
                                        <span class="badge bg-success">Produzione</span>
                                    @else
                                        {{ $att->activity_name ?? '-' }}
                                    @endif
                                </td>
                                <td>{{ $att->prinect_job_name ?? '-' }}</td>
                                <td>
                                    @if($att->commessa_gestionale)
                                        <a href="{{ route('mes.prinect.report', $att->commessa_gestionale) }}">{{ $att->commessa_gestionale }}</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td><small>{{ $att->workstep_name ?? '-' }}</small></td>
                                <td>
                                    @if($att->good_cycles > 0)
                                        <span class="text-success fw-bold">{{ number_format($att->good_cycles) }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($att->waste_cycles > 0)
                                        <span class="text-danger">{{ number_format($att->waste_cycles) }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $att->operatore_prinect ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="text-center">Nessuna attivita trovata. Esegui <code>php artisan prinect:sync-attivita</code> per importare.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-center">
        {{ $attivita->withQueryString()->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
