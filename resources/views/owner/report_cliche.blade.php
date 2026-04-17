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
        <table class="table table-sm table-hover align-middle" style="font-size:12px;">
            <thead class="table-dark">
                <tr>
                    <th style="width:30px;"></th>
                    <th>Cliché</th>
                    <th>Scat.</th>
                    <th>Descrizione</th>
                    <th class="text-end"># Commesse</th>
                    <th class="text-end">Tiro tot. (cm)</th>
                    <th class="text-end">Tiro medio</th>
                    <th class="text-end">Qta prod. tot.</th>
                    <th class="text-end">Scarti tot.</th>
                    <th class="text-end">Scarti medi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                @php $commesseCl = $breakdown[$r->numero] ?? collect(); @endphp
                <tr class="{{ $r->n_commesse > 0 ? 'table-warning' : '' }}">
                    <td class="text-center">
                        @if($commesseCl->isNotEmpty())
                        <button class="btn btn-sm btn-link p-0" type="button" onclick="toggleRow('r{{ $r->numero }}')" title="Espandi">▸</button>
                        @endif
                    </td>
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
                @if($commesseCl->isNotEmpty())
                <tr id="r{{ $r->numero }}" style="display:none; background:#fafafa;">
                    <td></td>
                    <td colspan="9">
                        <table class="table table-sm mb-0" style="font-size:11px;">
                            <thead>
                                <tr class="table-light">
                                    <th>Commessa</th>
                                    <th>Cliente</th>
                                    <th>Descrizione</th>
                                    <th>Data consegna</th>
                                    <th class="text-end">Tiro</th>
                                    <th class="text-end">Qta prod.</th>
                                    <th class="text-end">Scarti</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($commesseCl as $cc)
                                <tr>
                                    <td><a href="{{ route('owner.dettaglioCommessa', $cc->commessa) }}">{{ $cc->commessa }}</a></td>
                                    <td>{{ $cc->cliente_nome }}</td>
                                    <td><small>{{ \Illuminate\Support\Str::limit($cc->descrizione, 60) }}</small></td>
                                    <td>{{ $cc->data_prevista_consegna ? \Carbon\Carbon::parse($cc->data_prevista_consegna)->format('d/m/Y') : '-' }}</td>
                                    <td class="text-end">{{ $cc->tiro ? number_format($cc->tiro, 0, ',', '.') : '-' }}</td>
                                    <td class="text-end">{{ $cc->qta_prod ? number_format($cc->qta_prod, 0, ',', '.') : '-' }}</td>
                                    <td class="text-end">{{ $cc->scarti ? number_format($cc->scarti, 0, ',', '.') : '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                </tr>
                @endif
                @empty
                <tr><td colspan="10" class="text-center text-muted py-3">Nessun cliché. Importa con <code>php artisan cliche:import</code>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleRow(id) {
    var r = document.getElementById(id);
    if (!r) return;
    r.style.display = r.style.display === 'none' ? 'table-row' : 'none';
}
</script>
@endsection
