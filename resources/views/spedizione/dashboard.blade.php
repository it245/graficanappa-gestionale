@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
<style>
    html, body {
        margin:0; padding:0; overflow-x:hidden; width:100%;
    }
    h2, h4, p { margin-left:8px; margin-right:8px; }
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
    td:nth-child(7){ white-space:normal; min-width:300px; }

    .btn-consegna {
        color: #fff;
        border: none;
        padding: 8px 20px;
        border-radius: 8px;
        font-weight: bold;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s;
        white-space: nowrap;
    }
    .btn-consegna-green {
        background: linear-gradient(135deg, #28a745, #218838);
    }
    .btn-consegna-green:hover {
        background: linear-gradient(135deg, #218838, #1e7e34);
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(40,167,69,0.35);
    }
    .btn-consegna-red {
        background: linear-gradient(135deg, #dc3545, #c82333);
    }
    .btn-consegna-red:hover {
        background: linear-gradient(135deg, #c82333, #a71d2a);
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(220,53,69,0.35);
    }
    .btn-consegna-orange {
        background: linear-gradient(135deg, #fd7e14, #e8690b);
    }
    .btn-consegna-orange:hover {
        background: linear-gradient(135deg, #e8690b, #d35400);
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(253,126,20,0.35);
    }
    .btn-consegna:disabled {
        background: #6c757d !important;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
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
    .kpi-box small { color: #6c757d; }

    .progress-bar-custom {
        height: 18px;
        border-radius: 10px;
        background: #e9ecef;
        overflow: hidden;
        min-width: 80px;
    }
    .progress-bar-custom .fill {
        height: 100%;
        border-radius: 10px;
        transition: width 0.3s ease;
        text-align: center;
        font-size: 10px;
        line-height: 18px;
        color: #fff;
        font-weight: bold;
    }

    .search-box {
        max-width: 600px;
        margin: 12px 8px;
        font-size: 18px;
        padding: 12px 20px;
        border-radius: 10px;
        border: 2px solid #dee2e6;
        transition: border-color 0.2s;
    }
    .search-box:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 3px rgba(13,110,253,0.15);
    }

    .row-scaduta { background: #f8d7da !important; }
    .row-warning { background: #fff3cd !important; }

    a.commessa-link {
        color: #000;
        font-weight: bold;
        text-decoration: underline;
    }
    a.commessa-link:hover { color: #0d6efd; }

    .fasi-mancanti {
        font-size: 11px;
        color: #dc3545;
        margin-top: 4px;
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

@php
    $consegneTotali = $fasiSpediteOggi->where('tipo_consegna', 'totale')->count();
    $consegneParziali = $fasiSpediteOggi->where('tipo_consegna', 'parziale')->count();
    // Vecchie consegne senza tipo_consegna contale come totali
    $consegneSenzaTipo = $fasiSpediteOggi->whereNull('tipo_consegna')->count();
    $consegneTotali += $consegneSenzaTipo;
@endphp

<!-- KPI -->
<div class="row mx-2 mb-3">
    <div class="col-md-2">
        <div class="kpi-box" style="border-left: 4px solid #28a745;">
            <h3>{{ $fasiDaSpedire->count() }}</h3>
            <small>Da consegnare</small>
        </div>
    </div>
    <div class="col-md-2">
        <div class="kpi-box" style="border-left: 4px solid #ffc107;">
            <h3>{{ $fasiInAttesa->count() }}</h3>
            <small>In attesa</small>
        </div>
    </div>
    <div class="col-md-2">
        <a href="{{ route('spedizione.esterne') }}" style="text-decoration:none; color:inherit;">
            <div class="kpi-box" style="cursor:pointer; border-left: 4px solid #17a2b8;">
                <h3>{{ $fasiEsterne->count() }}</h3>
                <small>Lav. esterne <span style="font-size:11px">(apri)</span></small>
            </div>
        </a>
    </div>
    <div class="col-md-2">
        <div class="kpi-box" style="cursor:pointer; border-left: 4px solid #0d6efd;" data-bs-toggle="modal" data-bs-target="#modalSpediteOggi">
            <h3>{{ $fasiSpediteOggi->count() }}</h3>
            <small>Consegnate oggi</small>
        </div>
    </div>
    <div class="col-md-2">
        <div class="kpi-box" style="border-left: 4px solid #198754;">
            <h3>{{ $consegneTotali }}</h3>
            <small>Totali oggi</small>
        </div>
    </div>
    <div class="col-md-2">
        <div class="kpi-box" style="border-left: 4px solid #fd7e14;">
            <h3>{{ $consegneParziali }}</h3>
            <small>Parziali oggi</small>
        </div>
    </div>
</div>

<!-- Ricerca -->
<input type="text" id="searchBox" class="form-control search-box" placeholder="Cerca commessa, cliente, descrizione...">

<!-- Tabella fasi da spedire -->
<h4 class="mx-2 mt-2" style="color:#28a745;">Da consegnare</h4>
<div class="table-wrapper">
    <table class="table table-bordered table-sm table-striped" id="tabDaSpedire">
        <thead class="table-dark">
            <tr>
                <th>Azione</th>
                <th>Note</th>
                <th>Commessa</th>
                <th>Cliente</th>
                <th>Cod. Articolo</th>
                <th>Qta</th>
                <th>Descrizione</th>
                <th>Data Consegna</th>
                <th>Progresso</th>
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
                        if ($diff < -5) $rowClass = 'row-scaduta';
                        elseif ($diff <= 3) $rowClass = 'row-warning';
                    }
                    $pct = $fase->percentuale ?? 0;
                    $pctColor = $pct == 100 ? '#28a745' : ($pct >= 75 ? '#17a2b8' : ($pct >= 50 ? '#ffc107' : '#dc3545'));
                @endphp
                <tr class="{{ $rowClass }} searchable">
                    <td style="white-space:nowrap;">
                        <button class="btn-consegna btn-consegna-green" onclick="inviaConsegna({{ $fase->id }}, 'totale', false, this)">Totale</button>
                        <button class="btn-consegna btn-consegna-orange" onclick="inviaConsegna({{ $fase->id }}, 'parziale', false, this)">Parziale</button>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm" style="min-width:150px"
                               value="{{ $fase->note ?? '' }}"
                               onblur="aggiornaNota({{ $fase->id }}, this.value)">
                    </td>
                    <td><a href="{{ route('commesse.show', $fase->ordine->commessa ?? '-') }}" class="commessa-link">{{ $fase->ordine->commessa ?? '-' }}</a></td>
                    <td>{{ $fase->ordine->cliente_nome ?? '-' }}</td>
                    <td>{{ $fase->ordine->cod_art ?? '-' }}</td>
                    <td>{{ $fase->ordine->qta_richiesta ?? '-' }}</td>
                    <td>{{ $fase->ordine->descrizione ?? '-' }}</td>
                    <td>{{ $fase->ordine->data_prevista_consegna ? \Carbon\Carbon::parse($fase->ordine->data_prevista_consegna)->format('d/m/Y') : '-' }}</td>
                    <td>
                        <div class="progress-bar-custom">
                            <div class="fill" style="width:{{ $pct }}%;background:{{ $pctColor }};">{{ $pct }}%</div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-3">Nessuna consegna in coda</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Tabella fasi in attesa -->
@if($fasiInAttesa->count() > 0)
<h4 class="mx-2 mt-4" style="color:#ffc107;">In attesa (lavorazione in corso)</h4>
<div class="table-wrapper">
    <table class="table table-bordered table-sm" id="tabInAttesa">
        <thead style="background:#ffc107; color:#000;">
            <tr>
                <th>Azione</th>
                <th>Commessa</th>
                <th>Cliente</th>
                <th>Cod. Articolo</th>
                <th>Qta</th>
                <th>Descrizione</th>
                <th>Data Consegna</th>
                <th>Progresso fasi</th>
            </tr>
        </thead>
        <tbody>
            @foreach($fasiInAttesa as $fase)
                @php
                    $pct = $fase->percentuale ?? 0;
                    $pctColor = $pct >= 75 ? '#17a2b8' : ($pct >= 50 ? '#ffc107' : '#dc3545');
                @endphp
                <tr class="searchable">
                    <td style="text-align:center; vertical-align:middle; white-space:nowrap;">
                        <button class="btn-consegna btn-consegna-red" onclick="inviaConsegna({{ $fase->id }}, 'totale', true, this)">Totale</button>
                        <button class="btn-consegna btn-consegna-orange" onclick="inviaConsegna({{ $fase->id }}, 'parziale', true, this)">Parziale</button>
                        <div class="fasi-mancanti">{{ $fase->fasiNonTerminate->count() }} fase/i aperte</div>
                    </td>
                    <td><a href="{{ route('commesse.show', $fase->ordine->commessa ?? '-') }}" class="commessa-link">{{ $fase->ordine->commessa ?? '-' }}</a></td>
                    <td>{{ $fase->ordine->cliente_nome ?? '-' }}</td>
                    <td>{{ $fase->ordine->cod_art ?? '-' }}</td>
                    <td>{{ $fase->ordine->qta_richiesta ?? '-' }}</td>
                    <td>{{ $fase->ordine->descrizione ?? '-' }}</td>
                    <td>{{ $fase->ordine->data_prevista_consegna ? \Carbon\Carbon::parse($fase->ordine->data_prevista_consegna)->format('d/m/Y') : '-' }}</td>
                    <td>
                        <div class="progress-bar-custom">
                            <div class="fill" style="width:{{ $pct }}%;background:{{ $pctColor }};">{{ $pct }}%</div>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

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
                            <th>Qta Ordinata</th>
                            <th>Tipo</th>
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
                            <td>
                                @if($fase->tipo_consegna === 'parziale')
                                    <span class="badge bg-warning text-dark">Parziale</span>
                                @else
                                    <span class="badge bg-success">Totale</span>
                                @endif
                            </td>
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
function getHdrs() {
    return {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    };
}

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

function inviaConsegna(faseId, tipo, forza, btn) {
    var msg = tipo === 'parziale' ? 'Confermi consegna PARZIALE?' : 'Confermi consegna TOTALE?';
    if (forza) msg += '\n(Attenzione: ci sono fasi non terminate)';
    if (!confirm(msg)) return;

    btn.disabled = true;
    btn.textContent = '...';

    fetch('{{ route("spedizione.invio") }}', {
        method: 'POST', headers: getHdrs(),
        body: JSON.stringify({ fase_id: faseId, tipo_consegna: tipo, forza: forza })
    })
    .then(parseResponse)
    .then(data => {
        if (data.success) { window.location.reload(); }
        else { alert('Errore: ' + (data.messaggio || data.message || 'operazione fallita')); btn.disabled = false; btn.textContent = tipo === 'parziale' ? 'Parziale' : 'Totale'; }
    })
    .catch(err => { if (err !== 'session_expired') { console.error('Errore:', err); alert('Errore: ' + err); } btn.disabled = false; btn.textContent = tipo === 'parziale' ? 'Parziale' : 'Totale'; });
}

function aggiornaNota(faseId, valore) {
    fetch('{{ route("produzione.aggiornaCampo") }}', {
        method: 'POST', headers: getHdrs(),
        body: JSON.stringify({ fase_id: faseId, campo: 'note', valore: valore })
    })
    .then(parseResponse)
    .then(data => { if (!data.success) alert('Errore salvataggio nota'); })
    .catch(err => { if (err !== 'session_expired') console.error('Errore:', err); });
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
