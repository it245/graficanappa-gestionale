@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
<style>
    html, body { margin:0; padding:0; overflow-x:hidden; width:100%; }
    h2, h4, p { margin-left:8px; margin-right:8px; }
    .table-wrapper {
        width:100%; max-width:100%; overflow-x:auto; overflow-y:visible; margin: 0 4px;
    }
    table th, table td { white-space:nowrap; font-size:13px; }
    td.desc-col { white-space:normal; min-width:280px; max-width:400px; }
    td.fasi-col { white-space:normal; min-width:180px; max-width:300px; font-size:12px; }
    .search-box {
        max-width:600px; margin:12px 8px; font-size:18px; padding:12px 20px;
        border-radius:10px; border:2px solid #dee2e6; transition:border-color 0.2s;
    }
    .search-box:focus { border-color:#17a2b8; box-shadow:0 0 0 3px rgba(23,162,184,0.15); }
    .row-scaduta { background: #f8d7da !important; }
    .row-warning { background: #fff3cd !important; }
    a.commessa-link { color:#000; font-weight:bold; text-decoration:underline; }
    a.commessa-link:hover { color:#0d6efd; }
    .badge-stato { font-size:11px; }
</style>

<div class="d-flex align-items-center mx-2 mb-2 mt-2">
    <a href="{{ route('owner.dashboard') }}" class="btn btn-outline-secondary btn-sm me-3">&larr; Dashboard</a>
    <h2 class="mb-0" style="color:#17a2b8;">Lavorazioni Esterne ({{ $commesseEsterne->count() }})</h2>
</div>

<input type="text" id="searchBox" class="form-control search-box" placeholder="Cerca commessa, cliente, fornitore, fase...">

<div class="table-wrapper">
    <table class="table table-bordered table-sm table-striped" id="tabEsterne">
        <thead style="background:#17a2b8; color:#fff;">
            <tr>
                <th>Stato</th>
                <th>Fornitore</th>
                <th>Commessa</th>
                <th>Cliente</th>
                <th>Fasi ({{ $commesseEsterne->sum('num_fasi') }})</th>
                <th>Cod. Articolo</th>
                <th>Descrizione</th>
                <th>Data Consegna</th>
                <th>Data Invio</th>
            </tr>
        </thead>
        <tbody>
            @forelse($commesseEsterne as $riga)
                @php
                    $rowClass = '';
                    if ($riga->ordine && $riga->ordine->data_prevista_consegna) {
                        $oggi = \Carbon\Carbon::today();
                        $dataPrevista = \Carbon\Carbon::parse($riga->ordine->data_prevista_consegna);
                        $diff = $oggi->diffInDays($dataPrevista, false);
                        if ($diff < -5) $rowClass = 'row-scaduta';
                        elseif ($diff <= 3) $rowClass = 'row-warning';
                    }
                    $statoFase = $riga->stato;
                    $inPausa = is_string($statoFase) && !is_numeric($statoFase);
                @endphp
                <tr class="{{ $rowClass }} searchable">
                    <td>
                        @if($statoFase == 0)
                            <span class="badge bg-secondary badge-stato">Da fare</span>
                        @elseif($statoFase == 1)
                            <span class="badge bg-info badge-stato">Pronto</span>
                        @elseif($statoFase == 2)
                            <span class="badge bg-primary badge-stato">In corso</span>
                        @elseif($inPausa)
                            <span class="badge bg-warning text-dark badge-stato">Pausa: {{ $statoFase }}</span>
                        @endif
                    </td>
                    <td><strong>{{ $riga->fornitore }}</strong></td>
                    <td><a href="{{ route('owner.dettaglioCommessa', $riga->ordine->commessa ?? '-') }}" class="commessa-link">{{ $riga->ordine->commessa ?? '-' }}</a></td>
                    <td>{{ $riga->ordine->cliente_nome ?? '-' }}</td>
                    <td class="fasi-col">{{ $riga->fasi }} @if($riga->num_fasi > 1)<span class="badge bg-secondary">{{ $riga->num_fasi }}</span>@endif</td>
                    <td>{{ $riga->ordine->cod_art ?? '-' }}</td>
                    <td class="desc-col">{{ $riga->ordine->descrizione ?? '-' }}</td>
                    <td>{{ $riga->ordine->data_prevista_consegna ? \Carbon\Carbon::parse($riga->ordine->data_prevista_consegna)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $riga->data_invio ? \Carbon\Carbon::parse($riga->data_invio)->format('d/m/Y') : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-3">Nessuna lavorazione esterna</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

</div>

<script>
document.getElementById('searchBox').addEventListener('input', function() {
    const query = this.value.toLowerCase().trim();
    document.querySelectorAll('tr.searchable').forEach(function(row) {
        const text = row.innerText.toLowerCase();
        row.style.display = (!query || text.includes(query)) ? '' : 'none';
    });
});
</script>
@endsection
