@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">📋 Report Cliché</h4>
        <a href="{{ route('owner.dashboard') }}" class="btn btn-outline-secondary btn-sm">← Dashboard</a>
    </div>

    <div class="d-flex gap-2 mb-3 align-items-center flex-wrap">
        <div class="btn-group" role="group">
            <button type="button" class="btn btn-warning btn-sm active" onclick="showTab('tabCliche')" id="btnCliche">Per Cliché</button>
            <button type="button" class="btn btn-outline-warning btn-sm" onclick="showTab('tabCommessa')" id="btnCommessa">Per Commessa</button>
        </div>
        <input type="text" id="searchInput" class="form-control form-control-sm" style="max-width:300px;" placeholder="🔍 Cerca commessa, cliente, cliché..." oninput="filterRows()">
        <small class="text-muted ms-auto">
            Cliché: <strong>{{ count($rows) }}</strong> — Usati: <strong>{{ $rows->where('n_commesse', '>', 0)->count() }}</strong>
            | Commesse: <strong>{{ count($perCommessa) }}</strong>
        </small>
    </div>

    {{-- TAB PER CLICHÉ --}}
    <div id="tabCliche" class="table-responsive">
        <table class="table table-sm table-hover align-middle" style="font-size:12px;">
            <thead class="table-dark">
                <tr>
                    <th style="width:30px;"></th>
                    <th>Cliché</th>
                    <th>Scat.</th>
                    <th>Descrizione</th>
                    <th class="text-end"># Commesse</th>
                    <th class="text-end">Tiro tot. (cm)</th>
                    <th class="text-end">Tiro medio (cm)</th>
                    <th class="text-end">Qta prod. tot.</th>
                    <th class="text-end">Scarti tot.</th>
                    <th class="text-end">Scarti medi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                @php $commesseCl = $breakdown[$r->numero] ?? collect(); @endphp
                <tr class="cliche-row {{ $r->n_commesse > 0 ? 'table-warning' : '' }}" data-search="{{ strtolower('C' . $r->numero . ' ' . ($r->descrizione_raw ?? '') . ' ' . $commesseCl->pluck('commessa')->implode(' ') . ' ' . $commesseCl->pluck('cliente_nome')->implode(' ')) }}">
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
                <tr id="r{{ $r->numero }}" class="cliche-detail" style="display:none; background:#fafafa;">
                    <td></td>
                    <td colspan="9">
                        <table class="table table-sm mb-0" style="font-size:11px;">
                            <thead>
                                <tr class="table-light">
                                    <th>Commessa</th>
                                    <th>Cliente</th>
                                    <th>Descrizione</th>
                                    <th>Data consegna</th>
                                    <th class="text-end">Tiro (cm)</th>
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

    {{-- TAB PER COMMESSA --}}
    <div id="tabCommessa" class="table-responsive" style="display:none;">
        <table class="table table-sm table-hover align-middle" style="font-size:12px;">
            <thead class="table-dark">
                <tr>
                    <th>Commessa</th>
                    <th>Cliente</th>
                    <th>Descrizione</th>
                    <th>Data consegna</th>
                    <th class="text-end">Qta rich.</th>
                    <th>Cliché</th>
                    <th>Scat.</th>
                    <th class="text-end">Tiro (cm)</th>
                    <th class="text-end">Qta prod.</th>
                    <th class="text-end">Scarti</th>
                </tr>
            </thead>
            <tbody>
                @forelse($perCommessa as $cc)
                <tr class="commessa-row" data-search="{{ strtolower($cc->commessa . ' ' . ($cc->cliente_nome ?? '') . ' ' . ($cc->descrizione ?? '') . ' C' . $cc->cliche_numero . ' ' . ($cc->cliche_desc ?? '')) }}">
                    <td><a href="{{ route('owner.dettaglioCommessa', $cc->commessa) }}">{{ $cc->commessa }}</a></td>
                    <td>{{ $cc->cliente_nome }}</td>
                    <td><small>{{ \Illuminate\Support\Str::limit($cc->descrizione, 60) }}</small></td>
                    <td>{{ $cc->data_prevista_consegna ? \Carbon\Carbon::parse($cc->data_prevista_consegna)->format('d/m/Y') : '-' }}</td>
                    <td class="text-end">{{ $cc->qta_richiesta ? number_format($cc->qta_richiesta, 0, ',', '.') : '-' }}</td>
                    <td><span class="badge" style="background:#f57f17; color:white;" title="{{ $cc->cliche_desc }}">C{{ $cc->cliche_numero }}</span></td>
                    <td>{{ $cc->scatola ?? '-' }}</td>
                    <td class="text-end fw-bold">{{ $cc->tiro ? number_format($cc->tiro, 0, ',', '.') : '-' }}</td>
                    <td class="text-end">{{ $cc->qta_prod ? number_format($cc->qta_prod, 0, ',', '.') : '-' }}</td>
                    <td class="text-end">{{ $cc->scarti ? number_format($cc->scarti, 0, ',', '.') : '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="10" class="text-center text-muted py-3">Nessuna commessa con cliché collegato.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
function showTab(id) {
    document.getElementById('tabCliche').style.display = id === 'tabCliche' ? '' : 'none';
    document.getElementById('tabCommessa').style.display = id === 'tabCommessa' ? '' : 'none';
    document.getElementById('btnCliche').classList.toggle('active', id === 'tabCliche');
    document.getElementById('btnCliche').classList.toggle('btn-warning', id === 'tabCliche');
    document.getElementById('btnCliche').classList.toggle('btn-outline-warning', id !== 'tabCliche');
    document.getElementById('btnCommessa').classList.toggle('active', id === 'tabCommessa');
    document.getElementById('btnCommessa').classList.toggle('btn-warning', id === 'tabCommessa');
    document.getElementById('btnCommessa').classList.toggle('btn-outline-warning', id !== 'tabCommessa');
    filterRows();
}

function toggleRow(id) {
    var r = document.getElementById(id);
    if (!r) return;
    r.style.display = r.style.display === 'none' ? 'table-row' : 'none';
}

function filterRows() {
    var q = document.getElementById('searchInput').value.toLowerCase().trim();
    document.querySelectorAll('.cliche-row').forEach(function(r) {
        var match = !q || r.dataset.search.includes(q);
        r.style.display = match ? '' : 'none';
    });
    document.querySelectorAll('.commessa-row').forEach(function(r) {
        var match = !q || r.dataset.search.includes(q);
        r.style.display = match ? '' : 'none';
    });
}
</script>
@endsection
