@extends('layouts.app')

@section('content')
<div class="container-fluid mt-3 px-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Report Commesse</h2>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-secondary">Dashboard Admin</a>
    </div>

    {{-- Filtri --}}
    <div class="btn-group mb-3" role="group">
        <a href="{{ route('admin.commesse', ['filtro' => 'tutte']) }}"
           class="btn btn-sm {{ $filtro === 'tutte' ? 'btn-dark' : 'btn-outline-dark' }}">Tutte</a>
        <a href="{{ route('admin.commesse', ['filtro' => 'completate']) }}"
           class="btn btn-sm {{ $filtro === 'completate' ? 'btn-success' : 'btn-outline-success' }}">Completate</a>
        <a href="{{ route('admin.commesse', ['filtro' => 'in_corso']) }}"
           class="btn btn-sm {{ $filtro === 'in_corso' ? 'btn-warning' : 'btn-outline-warning' }}">In corso</a>
    </div>

    <div class="card">
        <div class="card-header bg-dark text-white">
            <strong>Commesse ({{ $commesse->count() }})</strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm table-striped mb-0" style="font-size:13px;">
                    <thead class="table-dark">
                        <tr>
                            <th>Commessa</th>
                            <th>Cliente</th>
                            <th>Consegna</th>
                            <th class="text-center">Fasi totali</th>
                            <th class="text-center">Fasi completate</th>
                            <th class="text-center">Avanzamento</th>
                            <th class="text-center">Stato</th>
                            <th class="text-center">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($commesse as $c)
                        <tr>
                            <td><strong>{{ $c->commessa }}</strong></td>
                            <td>{{ $c->cliente_nome ?: '-' }}</td>
                            <td>
                                @if($c->data_prevista_consegna)
                                    {{ \Carbon\Carbon::parse($c->data_prevista_consegna)->format('d/m/Y') }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-center">{{ $c->fasi_totali }}</td>
                            <td class="text-center">{{ $c->fasi_completate }}</td>
                            <td class="text-center">
                                <div class="progress" style="height:18px; min-width:80px;">
                                    <div class="progress-bar {{ $c->completata ? 'bg-success' : 'bg-primary' }}"
                                         style="width:{{ $c->percentuale }}%">
                                        {{ $c->percentuale }}%
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                @if($c->completata)
                                    <span class="badge bg-success">Completata</span>
                                @else
                                    <span class="badge bg-warning text-dark">In corso</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('admin.reportCommessa', $c->commessa) }}" class="btn btn-sm btn-outline-primary">Report</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-3">Nessuna commessa trovata</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
