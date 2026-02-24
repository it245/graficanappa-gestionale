@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
<style>
    html, body { margin:0; padding:0; overflow-x:hidden; width:100%; }
    h2, h4, p { margin-left:8px; margin-right:8px; }
    .top-bar {
        display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;
    }
    .operatore-info {
        position:relative; display:flex; align-items:center; gap:10px; cursor:pointer;
    }
    .operatore-info img { width:50px; height:50px; border-radius:50%; }
    .operatore-popup {
        position:absolute; top:60px; left:0; background:#fff; border:1px solid #ccc;
        padding:10px; border-radius:5px; box-shadow:0 2px 10px rgba(0,0,0,0.2);
        display:none; z-index:1000; min-width:200px;
    }
    .operatore-popup button { width:100%; margin-top:8px; }
    .table-wrapper {
        width:100%; max-width:100%; overflow-x:auto; overflow-y:visible; margin: 0 4px;
    }
    table th, table td { white-space:nowrap; }
    td:nth-child(8){ white-space:normal; min-width:300px; }
    .search-box {
        max-width:600px; margin:12px 8px; font-size:18px; padding:12px 20px;
        border-radius:10px; border:2px solid #dee2e6; transition:border-color 0.2s;
    }
    .search-box:focus { border-color:#0d6efd; box-shadow:0 0 0 3px rgba(13,110,253,0.15); }
    .row-scaduta { background: #f8d7da !important; }
    .row-warning { background: #fff3cd !important; }
    a.commessa-link { color:#000; font-weight:bold; text-decoration:underline; }
    a.commessa-link:hover { color:#0d6efd; }
</style>

<div class="top-bar">
    <div class="operatore-info" id="operatoreInfo">
        <img src="{{ asset('images/icons8-utente-uomo-cerchiato-50.png') }}" alt="Operatore">
        <div class="operatore-popup" id="operatorePopup">
            <div><strong>{{ $operatore->nome }} {{ $operatore->cognome }}</strong></div>
            <div><p>Reparto: <strong>Spedizione</strong></p></div>
            <form action="{{ route('operatore.logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-secondary btn-sm mt-2">Logout</button>
            </form>
        </div>
    </div>
</div>

<div class="d-flex align-items-center mx-2 mb-2">
    <a href="{{ route('spedizione.dashboard') }}" class="btn btn-outline-secondary btn-sm me-3">&larr; Dashboard</a>
    <h2 class="mb-0" style="color:#17a2b8;">Lavorazioni Esterne ({{ $fasiEsterne->count() }})</h2>
</div>

<input type="text" id="searchBox" class="form-control search-box" placeholder="Cerca commessa, cliente, descrizione...">

<div class="table-wrapper">
    <table class="table table-bordered table-sm table-striped" id="tabEsterne">
        <thead style="background:#17a2b8; color:#fff;">
            <tr>
                <th>Azione</th>
                <th>Stato</th>
                <th>Note</th>
                <th>Commessa</th>
                <th>Cliente</th>
                <th>Fase</th>
                <th>Cod. Articolo</th>
                <th>Descrizione</th>
                <th>Data Consegna</th>
            </tr>
        </thead>
        <tbody>
            @forelse($fasiEsterne as $fase)
                @php
                    $rowClass = '';
                    if ($fase->ordine && $fase->ordine->data_prevista_consegna) {
                        $oggi = \Carbon\Carbon::today();
                        $dataPrevista = \Carbon\Carbon::parse($fase->ordine->data_prevista_consegna);
                        $diff = $oggi->diffInDays($dataPrevista, false);
                        if ($diff < -5) $rowClass = 'row-scaduta';
                        elseif ($diff <= 3) $rowClass = 'row-warning';
                    }
                    $statoFase = $fase->stato;
                    $inPausa = is_string($statoFase) && !is_numeric($statoFase);
                @endphp
                <tr class="{{ $rowClass }} searchable">
                    <td style="white-space:nowrap;">
                        @if($statoFase == 0 || $statoFase == 1)
                            <button class="btn btn-sm btn-success fw-bold" onclick="esternoAvvia({{ $fase->id }}, this)">Avvia</button>
                        @elseif($statoFase == 2)
                            <button class="btn btn-sm btn-warning fw-bold" onclick="esternoPausa({{ $fase->id }}, this)">Pausa</button>
                            <button class="btn btn-sm btn-danger fw-bold" onclick="esternoTermina({{ $fase->id }}, this)">Termina</button>
                        @elseif($inPausa)
                            <button class="btn btn-sm btn-success fw-bold" onclick="esternoRiprendi({{ $fase->id }}, this)">Riprendi</button>
                            <button class="btn btn-sm btn-danger fw-bold" onclick="esternoTermina({{ $fase->id }}, this)">Termina</button>
                        @endif
                    </td>
                    <td>
                        @if($statoFase == 0)
                            <span class="badge bg-secondary">Da fare</span>
                        @elseif($statoFase == 1)
                            <span class="badge bg-info">Pronto</span>
                        @elseif($statoFase == 2)
                            <span class="badge bg-primary">In corso</span>
                        @elseif($inPausa)
                            <span class="badge bg-warning text-dark">Pausa: {{ $statoFase }}</span>
                        @endif
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm" style="min-width:150px"
                               value="{{ $fase->note ?? '' }}"
                               onblur="aggiornaNota({{ $fase->id }}, this.value)">
                    </td>
                    <td><a href="{{ route('commesse.show', $fase->ordine->commessa ?? '-') }}" class="commessa-link">{{ $fase->ordine->commessa ?? '-' }}</a></td>
                    <td>{{ $fase->ordine->cliente_nome ?? '-' }}</td>
                    <td>{{ $fase->faseCatalogo->nome ?? '-' }}</td>
                    <td>{{ $fase->ordine->cod_art ?? '-' }}</td>
                    <td>{{ $fase->ordine->descrizione ?? '-' }}</td>
                    <td>{{ $fase->ordine->data_prevista_consegna ? \Carbon\Carbon::parse($fase->ordine->data_prevista_consegna)->format('d/m/Y') : '-' }}</td>
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
const hdrs = {
    'X-CSRF-TOKEN': '{{ csrf_token() }}',
    'Content-Type': 'application/json',
    'Accept': 'application/json'
};

function parseResponse(res) {
    if (!res.ok && res.status === 401) {
        alert('Sessione scaduta. Effettua di nuovo il login.');
        window.location.reload();
        return Promise.reject('session_expired');
    }
    const ct = res.headers.get('content-type') || '';
    if (!ct.includes('application/json')) {
        return Promise.reject('Risposta non valida dal server (status ' + res.status + ')');
    }
    return res.json();
}

function aggiornaNota(faseId, valore) {
    fetch('{{ route("produzione.aggiornaCampo") }}', {
        method: 'POST', headers: hdrs,
        body: JSON.stringify({ fase_id: faseId, campo: 'note', valore: valore })
    })
    .then(parseResponse)
    .then(data => { if (!data.success) alert('Errore salvataggio nota'); })
    .catch(err => { if (err !== 'session_expired') console.error('Errore:', err); });
}

const motiviPausaEsterno = ["Attesa materiale", "Problema macchina", "Pranzo", "Altro"];

function esternoAvvia(faseId, btn) {
    var terzista = prompt("A chi viene inviato il prodotto (terzista)?");
    if (terzista === null) return;
    btn.disabled = true;
    fetch('{{ route("produzione.avvia") }}', {
        method: 'POST', headers: hdrs,
        body: JSON.stringify({ fase_id: faseId, terzista: terzista })
    })
    .then(parseResponse)
    .then(data => {
        if (data.success) { window.location.reload(); }
        else { alert('Errore: ' + (data.messaggio || data.message || 'operazione fallita')); btn.disabled = false; }
    })
    .catch(err => { if (err !== 'session_expired') { console.error('Errore:', err); alert('Errore: ' + err); } btn.disabled = false; });
}

function esternoPausa(faseId, btn) {
    let scelta = prompt("Seleziona motivo pausa:\n1) Attesa materiale\n2) Problema macchina\n3) Pranzo\n4) Altro");
    if (!scelta || !["1","2","3","4"].includes(scelta)) return;
    let motivo = motiviPausaEsterno[parseInt(scelta) - 1];
    btn.disabled = true;
    fetch('{{ route("produzione.pausa") }}', {
        method: 'POST', headers: hdrs,
        body: JSON.stringify({ fase_id: faseId, motivo: motivo })
    })
    .then(parseResponse)
    .then(data => {
        if (data.success) { window.location.reload(); }
        else { alert('Errore: ' + (data.messaggio || data.message || 'operazione fallita')); btn.disabled = false; }
    })
    .catch(err => { if (err !== 'session_expired') { console.error('Errore:', err); alert('Errore: ' + err); } btn.disabled = false; });
}

function esternoTermina(faseId, btn) {
    if (!confirm("Sei sicuro di voler terminare questa fase esterna?")) return;
    btn.disabled = true;
    fetch('{{ route("produzione.termina") }}', {
        method: 'POST', headers: hdrs,
        body: JSON.stringify({ fase_id: faseId })
    })
    .then(parseResponse)
    .then(data => {
        if (data.success) { window.location.reload(); }
        else { alert('Errore: ' + (data.messaggio || data.message || 'operazione fallita')); btn.disabled = false; }
    })
    .catch(err => { if (err !== 'session_expired') { console.error('Errore:', err); alert('Errore: ' + err); } btn.disabled = false; });
}

function esternoRiprendi(faseId, btn) {
    btn.disabled = true;
    fetch('{{ route("produzione.riprendi") }}', {
        method: 'POST', headers: hdrs,
        body: JSON.stringify({ fase_id: faseId })
    })
    .then(parseResponse)
    .then(data => {
        if (data.success) { window.location.reload(); }
        else { alert('Errore: ' + (data.messaggio || data.message || 'operazione fallita')); btn.disabled = false; }
    })
    .catch(err => { if (err !== 'session_expired') { console.error('Errore:', err); alert('Errore: ' + err); } btn.disabled = false; });
}

// Ricerca
document.getElementById('searchBox').addEventListener('input', function() {
    const query = this.value.toLowerCase().trim();
    document.querySelectorAll('tr.searchable').forEach(function(row) {
        const text = row.innerText.toLowerCase();
        row.style.display = (!query || text.includes(query)) ? '' : 'none';
    });
});

// Popup operatore
document.getElementById('operatoreInfo').addEventListener('click', function(){
    const popup = document.getElementById('operatorePopup');
    popup.style.display = popup.style.display === 'block' ? 'none' : 'block';
});
document.addEventListener('click', function(e){
    if(!document.getElementById('operatoreInfo').contains(e.target)){
        document.getElementById('operatorePopup').style.display='none';
    }
});
</script>
@endsection
