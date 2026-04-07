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
    .percorso-base { background: #d4edda !important; }
    .percorso-rilievi { background: #fff3cd !important; }
    .percorso-caldo { background: #f96f2a !important; }
    .percorso-completo { background: #f8d7da !important; }
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
                <th>Commessa</th>
                <th>Cliente</th>
                <th>Fornitore</th>
                <th>Fasi ({{ $commesseEsterne->sum('num_fasi') }})</th>
                <th>Qta</th>
                <th>Cod. Articolo</th>
                <th>Descrizione</th>
                <th>Data Consegna</th>
                <th>Data Invio</th>
            </tr>
        </thead>
        <tbody>
            @forelse($commesseEsterne as $riga)
                @php
                    $rowClass = $riga->ordine ? $riga->ordine->getPercorsoClass() : '';
                    $statoFase = $riga->stato;
                    $inPausa = is_string($statoFase) && !is_numeric($statoFase);
                @endphp
                <tr class="{{ $rowClass }} searchable">
                    <td style="white-space:nowrap;">
                        @if($statoFase == 0)
                            <span class="badge bg-secondary badge-stato">Da fare</span>
                        @elseif($statoFase == 1)
                            <span class="badge bg-info badge-stato">Pronto</span>
                        @elseif($statoFase == 2)
                            <span class="badge bg-primary badge-stato">In corso</span>
                            <br>
                            <button class="btn btn-sm btn-success fw-bold mt-1" style="font-size:11px;"
                                    onclick="esternoTerminaOwner({{ json_encode($riga->fasi_ids) }})">Rientro</button>
                        @elseif($inPausa)
                            <span class="badge bg-warning text-dark badge-stato">Pausa: {{ $statoFase }}</span>
                        @endif
                    </td>
                    <td><a href="{{ route('owner.dettaglioCommessa', $riga->ordine->commessa ?? '-') }}" class="commessa-link">{{ $riga->ordine->commessa ?? '-' }}</a></td>
                    <td>{{ $riga->ordine->cliente_nome ?? '-' }}</td>
                    <td><strong>{{ $riga->fornitore }}</strong></td>
                    <td class="fasi-col">{{ $riga->fasi }} @if($riga->num_fasi > 1)<span class="badge bg-secondary">{{ $riga->num_fasi }}</span>@endif</td>
                    <td style="font-size:12px; width:60px; text-align:center;">
                        @foreach($riga->fasi_dettaglio as $fd)
                            <div contenteditable
                                 style="font-weight:bold; padding:1px 2px; border:1px solid transparent; cursor:text; {{ !$loop->last ? 'border-bottom:1px solid #eee;' : '' }}"
                                 onfocus="this.style.borderColor='#17a2b8'"
                                 onblur="this.style.borderColor='transparent'; aggiornaQtaEsterna({{ $fd['id'] }}, this.innerText)"
                            >{{ $fd['qta'] ? number_format($fd['qta'], 0, ',', '.') : '-' }}</div>
                        @endforeach
                    </td>
                    <td>{{ $riga->ordine->cod_art ?? '-' }}</td>
                    <td class="desc-col">{{ $riga->ordine->descrizione ?? '-' }}</td>
                    <td>{{ $riga->ordine->data_prevista_consegna ? \Carbon\Carbon::parse($riga->ordine->data_prevista_consegna)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $riga->data_invio ? \Carbon\Carbon::parse($riga->data_invio)->format('d/m/Y') : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center text-muted py-3">Nessuna lavorazione esterna</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Modal Termina Esterna (stessa logica della spedizione) -->
<div class="modal fade" id="modalTerminaEsterno" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Termina Fase Esterna</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="terminaEsternoFasiIds">
                <p class="fw-bold mb-3">La lavorazione esterna e completata?</p>
                <button type="button" class="btn btn-success btn-lg w-100 mb-3" onclick="confermaTerminaEsterno('terminata')">
                    <strong>Terminata</strong><br>
                    <small>La lavorazione e completata</small>
                </button>
                <button type="button" class="btn btn-warning btn-lg w-100 text-dark mb-3" onclick="confermaTerminaEsterno('rientro')">
                    <strong>Rientrata - servono altre lavorazioni</strong><br>
                    <small>Il materiale e rientrato ma servono lavorazioni aggiuntive</small>
                </button>
                <button type="button" class="btn btn-secondary btn-lg w-100" onclick="confermaTerminaEsterno('nessuna')">
                    <strong>Rientrata senza lavorazione</strong><br>
                    <small>Nessuna lavorazione effettuata, torna in attesa</small>
                </button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
            </div>
        </div>
    </div>
</div>

</div>

<script>
function esternoTerminaOwner(fasiIds) {
    document.getElementById('terminaEsternoFasiIds').value = JSON.stringify(fasiIds);
    new bootstrap.Modal(document.getElementById('modalTerminaEsterno')).show();
}

function confermaTerminaEsterno(tipo) {
    var fasiIds = JSON.parse(document.getElementById('terminaEsternoFasiIds').value);
    bootstrap.Modal.getInstance(document.getElementById('modalTerminaEsterno')).hide();

    var promises = fasiIds.map(function(faseId) {
        var body = { fase_id: faseId, qta_prodotta: 0, scarti: 0 };
        if (tipo === 'rientro' || tipo === 'nessuna') body.rientro = true;
        return fetch('{{ route("produzione.termina") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(body)
        }).then(function(r) { return r.json(); });
    });

    Promise.all(promises).then(function(results) {
        var errori = results.filter(function(d) { return !d.success; });
        if (errori.length > 0) {
            alert('Errore su ' + errori.length + ' fase/i');
        }
        window.location.reload();
    }).catch(function() { alert('Errore di connessione'); });
}

function aggiornaQtaEsterna(faseId, valore) {
    valore = valore.trim().replace(/\./g, '').replace(',', '.');
    if (valore === '-' || valore === '') return;
    fetch('{{ route("owner.aggiornaCampo") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ fase_id: faseId, campo: 'qta_fase', valore: valore })
    })
    .then(r => r.json())
    .then(d => {
        if (!d.success) alert('Errore: ' + (d.messaggio || ''));
    })
    .catch(() => alert('Errore di connessione'));
}

document.getElementById('searchBox').addEventListener('input', function() {
    const query = this.value.toLowerCase().trim();
    document.querySelectorAll('tr.searchable').forEach(function(row) {
        const text = row.innerText.toLowerCase();
        row.style.display = (!query || text.includes(query)) ? '' : 'none';
    });
});
</script>
@endsection
