@extends('layouts.app')

@section('content')
<div class="container-fluid mt-3 px-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Storico Commesse</h2>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-secondary">Dashboard Admin</a>
    </div>

    {{-- Filtri --}}
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('admin.commesse') }}" class="row g-2 align-items-end">
                {{-- Filtro stato --}}
                <div class="col-auto">
                    <label class="form-label mb-0" style="font-size:12px;">Stato</label>
                    <select name="filtro" class="form-select form-select-sm" style="min-width:130px;">
                        <option value="tutte" {{ $filtro === 'tutte' ? 'selected' : '' }}>Tutte</option>
                        <option value="in_corso" {{ $filtro === 'in_corso' ? 'selected' : '' }}>In corso</option>
                        <option value="completate" {{ $filtro === 'completate' ? 'selected' : '' }}>Completate</option>
                        <option value="consegnate" {{ $filtro === 'consegnate' ? 'selected' : '' }}>Consegnate</option>
                    </select>
                </div>

                {{-- Ricerca cliente --}}
                <div class="col-auto">
                    <label class="form-label mb-0" style="font-size:12px;">Cliente</label>
                    <input type="text" name="cliente" class="form-control form-control-sm" placeholder="Cerca cliente..." value="{{ $ricercaCliente }}" style="min-width:180px;">
                </div>

                {{-- Data da --}}
                <div class="col-auto">
                    <label class="form-label mb-0" style="font-size:12px;">Consegna da</label>
                    <input type="date" name="data_da" class="form-control form-control-sm" value="{{ $dataDa }}">
                </div>

                {{-- Data a --}}
                <div class="col-auto">
                    <label class="form-label mb-0" style="font-size:12px;">Consegna a</label>
                    <input type="date" name="data_a" class="form-control form-control-sm" value="{{ $dataA }}">
                </div>

                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-dark">Filtra</button>
                    <a href="{{ route('admin.commesse') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
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
                            <th>Descrizione</th>
                            <th>Consegna</th>
                            <th class="text-center">Fasi tot.</th>
                            <th class="text-center">Completate</th>
                            <th class="text-center" style="min-width:120px;">Avanzamento</th>
                            <th class="text-center">Stato</th>
                            <th class="text-center">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($commesse as $c)
                        <tr>
                            <td><strong>{{ $c->commessa }}</strong></td>
                            <td>{{ $c->cliente_nome ?: '-' }}</td>
                            <td style="max-width:300px; white-space:normal;">{{ $c->descrizione ?: '-' }}</td>
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
                                    <div class="progress-bar {{ $c->consegnata ? 'bg-secondary' : ($c->completata ? 'bg-success' : 'bg-primary') }}"
                                         style="width:{{ $c->percentuale }}%">
                                        {{ $c->percentuale }}%
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                @if($c->consegnata)
                                    <span class="badge bg-secondary">Consegnata</span>
                                @elseif($c->completata)
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
                            <td colspan="9" class="text-center text-muted py-3">Nessuna commessa trovata</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
