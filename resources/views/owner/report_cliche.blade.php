@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">📋 Report Cliché</h4>
        <a href="{{ route('owner.dashboard') }}" class="btn btn-outline-secondary btn-sm">← Dashboard</a>
    </div>

    <div class="card mb-3">
        <div class="card-body py-2">
            <small class="text-muted">
                Totale cliché in anagrafica: <strong>{{ count($rows) }}</strong> —
                Utilizzati almeno una volta: <strong>{{ $rows->where('n_commesse', '>', 0)->count() }}</strong>
            </small>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-sm table-hover table-striped align-middle" style="font-size:12px;">
            <thead class="table-dark">
                <tr>
                    <th>Cliché</th>
                    <th>Scat.</th>
                    <th>Descrizione</th>
                    <th class="text-end"># Commesse</th>
                    <th class="text-end">Tiro tot. (cm foil)</th>
                    <th class="text-end">Tiro medio</th>
                    <th class="text-end">Qta prod. tot.</th>
                    <th class="text-end">Scarti tot.</th>
                    <th class="text-end">Scarti medi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                <tr>
                    <td><span class="badge" style="background:#f57f17; color:white;">C{{ $r->numero }}{{ $r->scatola ? '-S'.$r->scatola : '' }}</span></td>
                    <td>{{ $r->scatola ?? '-' }}</td>
                    <td><small>{{ $r->descrizione_raw }}</small></td>
                    <td class="text-end">{{ $r->n_commesse }}</td>
                    <td class="text-end fw-bold">{{ $r->tiro_totale ? number_format($r->tiro_totale, 0, ',', '.') : '-' }}</td>
                    <td class="text-end">{{ $r->tiro_medio ? number_format($r->tiro_medio, 1, ',', '.') : '-' }}</td>
                    <td class="text-end">{{ $r->qta_prod_totale ? number_format($r->qta_prod_totale, 0, ',', '.') : '-' }}</td>
                    <td class="text-end">{{ $r->scarti_totali ? number_format($r->scarti_totali, 0, ',', '.') : '-' }}</td>
                    <td class="text-end">{{ $r->scarti_medi ? number_format($r->scarti_medi, 1, ',', '.') : '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="9" class="text-center text-muted py-3">Nessun cliché. Importa con <code>php artisan cliche:import</code>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
