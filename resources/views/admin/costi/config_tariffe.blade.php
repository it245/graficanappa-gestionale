@extends('layouts.app')

@section('content')
<style>
    .table-tariffe th { background: #f8f9fa; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
    .badge-non-config { background: #e9ecef; color: #6c757d; }
    .storico-row { background: #fafafa; }
    .header-line { border-bottom: 3px solid #d11317; padding-bottom: 0.5rem; margin-bottom: 1.5rem; }
</style>

<div class="container-fluid mt-3 px-4">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center header-line">
        <div>
            <h4 class="mb-0">Configurazione Tariffe Orarie</h4>
            <small class="text-muted">Costo orario per reparto/macchina</small>
        </div>
        <div>
            <a href="{{ route('admin.costi.report') }}" class="btn btn-outline-primary btn-sm me-1">Report Costi</a>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary btn-sm">Dashboard</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        {{-- Tabella reparti --}}
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h6 class="mb-3">Tariffe per Reparto</h6>
                    <table class="table table-sm table-bordered table-tariffe mb-0">
                        <thead>
                            <tr>
                                <th>Reparto</th>
                                <th class="text-end">Tariffa Corrente</th>
                                <th>Valido Dal</th>
                                <th>Note</th>
                                <th class="text-center" style="width:120px">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reparti as $rep)
                                @php
                                    $corrente = $rep->costiOrari->whereNull('valido_al')->first();
                                    $storico = $rep->costiOrari->whereNotNull('valido_al');
                                @endphp
                                <tr>
                                    <td><strong>{{ $rep->nome }}</strong></td>
                                    <td class="text-end">
                                        @if($corrente)
                                            <strong class="text-success">{{ number_format($corrente->costo_orario, 2, ',', '.') }} &euro;/h</strong>
                                        @else
                                            <span class="badge badge-non-config">Non configurato</span>
                                        @endif
                                    </td>
                                    <td>{{ $corrente ? $corrente->valido_dal->format('d/m/Y') : '-' }}</td>
                                    <td><small class="text-muted">{{ $corrente->note ?? '' }}</small></td>
                                    <td class="text-center">
                                        @if($storico->count() > 0)
                                            <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#storico-{{ $rep->id }}">
                                                Storico ({{ $storico->count() }})
                                            </button>
                                        @endif
                                        @if($corrente)
                                            <form method="POST" action="{{ route('admin.costi.eliminaTariffa', $corrente->id) }}" class="d-inline" onsubmit="return confirm('Eliminare la tariffa corrente?')">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-outline-danger btn-sm" title="Elimina corrente">X</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                                @if($storico->count() > 0)
                                    <tr class="collapse storico-row" id="storico-{{ $rep->id }}">
                                        <td colspan="5">
                                            <table class="table table-sm mb-0" style="font-size:0.8rem;">
                                                <thead><tr><th>Tariffa</th><th>Dal</th><th>Al</th><th>Note</th><th></th></tr></thead>
                                                <tbody>
                                                    @foreach($storico as $s)
                                                        <tr>
                                                            <td>{{ number_format($s->costo_orario, 2, ',', '.') }} &euro;/h</td>
                                                            <td>{{ $s->valido_dal->format('d/m/Y') }}</td>
                                                            <td>{{ $s->valido_al->format('d/m/Y') }}</td>
                                                            <td>{{ $s->note ?? '' }}</td>
                                                            <td>
                                                                <form method="POST" action="{{ route('admin.costi.eliminaTariffa', $s->id) }}" class="d-inline" onsubmit="return confirm('Eliminare?')">
                                                                    @csrf @method('DELETE')
                                                                    <button class="btn btn-outline-danger btn-sm py-0 px-1">X</button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Form nuova tariffa --}}
        <div class="col-12 col-lg-4">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">Nuova Tariffa</div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.costi.salvaTariffa') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-bold">Reparto</label>
                            <select name="reparto_id" class="form-select" required>
                                <option value="">-- Seleziona --</option>
                                @foreach($reparti as $rep)
                                    <option value="{{ $rep->id }}">{{ $rep->nome }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Costo Orario (&euro;/h)</label>
                            <input type="number" name="costo_orario" class="form-control" step="0.01" min="0" required placeholder="es. 45.00">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Valido Dal</label>
                            <input type="date" name="valido_dal" class="form-control" required value="{{ date('Y-m-d') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Valido Al <small class="text-muted">(vuoto = corrente)</small></label>
                            <input type="date" name="valido_al" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Note</label>
                            <input type="text" name="note" class="form-control" maxlength="255" placeholder="opzionale">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Salva Tariffa</button>
                    </form>
                    <small class="text-muted d-block mt-2">
                        Se lasci "Valido Al" vuoto, la tariffa diventa quella corrente e la precedente viene chiusa automaticamente.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
