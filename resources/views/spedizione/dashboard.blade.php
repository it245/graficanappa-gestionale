@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
<style>
    html, body {
        margin:0; padding:0; overflow-x:hidden; width:100%;
    }
    h2, p { margin-left:8px; margin-right:8px; }
    .top-bar {
        display:flex;
        align-items:center;
        justify-content:space-between;
        margin-bottom:10px;
    }
    .operatore-info {
        position:relative;
        display:flex;
        align-items:center;
        gap:10px;
        cursor:pointer;
    }
    .operatore-info img {
        width:50px; height:50px; border-radius:50%;
    }
    .operatore-popup {
        position:absolute;
        top:60px;
        left:0;
        background:#fff;
        border:1px solid #ccc;
        padding:10px;
        border-radius:5px;
        box-shadow:0 2px 10px rgba(0,0,0,0.2);
        display:none;
        z-index:1000;
        min-width:200px;
    }
    .operatore-popup button {
        width:100%;
        margin-top:8px;
    }
    .table-wrapper {
        width:100%;
        max-width:100%;
        overflow-x:auto;
        overflow-y:visible;
        margin: 0 4px;
    }
    table th, table td { white-space:nowrap; }

    th:nth-child(5),
    table td:nth-child(5){
        min-width: 400px;
    }
    td:nth-child(5){ white-space:normal; }

    .btn-invia {
        background-color: #28a745;
        color: #fff;
        border: none;
        padding: 8px 20px;
        border-radius: 5px;
        font-weight: bold;
        font-size: 14px;
        cursor: pointer;
        transition: background-color 0.2s;
    }
    .btn-invia:hover {
        background-color: #218838;
    }
    .btn-invia:disabled {
        background-color: #6c757d;
        cursor: not-allowed;
    }

    .badge-spedito {
        background-color: #28a745;
        color: #fff;
        padding: 4px 12px;
        border-radius: 10px;
        font-size: 13px;
    }

    .kpi-box {
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
        margin-bottom: 15px;
    }
    .kpi-box h3 {
        margin: 0;
        font-size: 28px;
        font-weight: bold;
    }
    .kpi-box small {
        color: #6c757d;
    }
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

<h2>Dashboard Spedizione</h2>

<!-- KPI -->
<div class="row mx-2 mb-3">
    <div class="col-md-6">
        <div class="kpi-box">
            <h3>{{ $fasiDaSpedire->count() }}</h3>
            <small>Da consegnare</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="kpi-box" style="cursor:pointer" data-bs-toggle="modal" data-bs-target="#modalSpediteOggi">
            <h3>{{ $fasiSpediteOggi->count() }}</h3>
            <small>Consegnate oggi <span style="font-size:11px">(clicca per vedere)</span></small>
        </div>
    </div>
</div>

<!-- Tabella fasi da spedire -->
<h4 class="mx-2">Da consegnare</h4>
<div class="table-wrapper">
    <table class="table table-bordered table-sm table-striped">
        <thead class="table-dark">
            <tr>
                <th>Azione</th>
                <th>Commessa</th>
                <th>Cliente</th>
                <th>Cod. Articolo</th>
                <th>Descrizione</th>
                <th>Quantita</th>
                <th>UM</th>
                <th>Data Consegna</th>
                <th>Note</th>
            </tr>
        </thead>
        <tbody>
            @forelse($fasiDaSpedire as $fase)
                @php
                    $rowClass = '';
                    if ($fase->ordine && $fase->ordine->data_prevista_consegna) {
                        $oggi = \Carbon\Carbon::today();
                        $dataPrevista = \Carbon\Carbon::parse($fase->ordine->data_prevista_consegna);
                        $diff = $oggi->diffInDays($dataPrevista, false);
                        if ($diff < -5) $rowClass = 'scaduta';
                        elseif ($diff <= 3) $rowClass = 'warning-strong';
                        elseif ($diff <= 5) $rowClass = 'warning-light';
                    }
                @endphp
                <tr id="fase-{{ $fase->id }}" class="{{ $rowClass }}">
                    <td>
                        <button class="btn-invia" onclick="inviaAutomatico({{ $fase->id }}, this)">
                            Consegnato
                        </button>
                    </td>
                    <td><strong>{{ $fase->ordine->commessa ?? '-' }}</strong></td>
                    <td>{{ $fase->ordine->cliente_nome ?? '-' }}</td>
                    <td>{{ $fase->ordine->cod_art ?? '-' }}</td>
                    <td>{{ $fase->ordine->descrizione ?? '-' }}</td>
                    <td>{{ $fase->ordine->qta_richiesta ?? '-' }}</td>
                    <td>{{ $fase->ordine->um ?? '-' }}</td>
                    <td>{{ $fase->ordine->data_prevista_consegna ? \Carbon\Carbon::parse($fase->ordine->data_prevista_consegna)->format('d/m/Y') : '-' }}</td>
                    <td>
                        <input type="text" class="form-control form-control-sm" style="min-width:150px"
                               value="{{ $fase->note ?? '' }}"
                               onblur="aggiornaNota({{ $fase->id }}, this.value)">
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center text-muted py-3">Nessuna consegna in coda</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Modal Spedite Oggi -->
<div class="modal fade" id="modalSpediteOggi" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">Consegnate oggi ({{ $fasiSpediteOggi->count() }})</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="overflow-x:auto;">
                @if($fasiSpediteOggi->count() > 0)
                <table class="table table-bordered table-sm" style="white-space:nowrap;">
                    <thead class="table-success">
                        <tr>
                            <th>Commessa</th>
                            <th>Cliente</th>
                            <th>Cod. Articolo</th>
                            <th>Descrizione</th>
                            <th>Quantita</th>
                            <th>UM</th>
                            <th>Ora Consegna</th>
                            <th>Operatore</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($fasiSpediteOggi as $fase)
                        <tr>
                            <td><strong>{{ $fase->ordine->commessa ?? '-' }}</strong></td>
                            <td>{{ $fase->ordine->cliente_nome ?? '-' }}</td>
                            <td>{{ $fase->ordine->cod_art ?? '-' }}</td>
                            <td>{{ $fase->ordine->descrizione ?? '-' }}</td>
                            <td>{{ $fase->ordine->qta_richiesta ?? '-' }}</td>
                            <td>{{ $fase->ordine->um ?? '-' }}</td>
                            <td>{{ $fase->data_fine ? \Carbon\Carbon::parse($fase->data_fine)->format('H:i:s') : '-' }}</td>
                            <td>
                                @foreach($fase->operatori as $op)
                                    {{ $op->nome }} {{ $op->cognome }}<br>
                                @endforeach
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <p class="text-muted text-center py-3">Nessuna consegna effettuata oggi</p>
                @endif
            </div>
        </div>
    </div>
</div>

</div>

<script>
function inviaAutomatico(faseId, btn) {
    if (!confirm('Confermi la consegna?')) return;

    btn.disabled = true;
    btn.textContent = 'Consegna...';

    fetch('{{ route("spedizione.invio") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ fase_id: faseId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        } else {
            alert('Errore: ' + (data.messaggio || 'operazione fallita'));
            btn.disabled = false;
            btn.textContent = 'Consegnato';
        }
    })
    .catch(err => {
        console.error('Errore:', err);
        btn.disabled = false;
        btn.textContent = 'Invia';
    });
}

function aggiornaNota(faseId, valore) {
    fetch('{{ route("produzione.aggiornaCampo") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ fase_id: faseId, campo: 'note', valore: valore })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) alert('Errore salvataggio nota');
    })
    .catch(err => console.error('Errore:', err));
}

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
