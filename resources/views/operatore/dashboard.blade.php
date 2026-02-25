@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
   <style>
    html, body {
        margin:0; padding:0; width:100%;
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
    .action-icons img {
        height:35px;
        cursor:pointer;
        margin-right:15px;
        transition: transform 0.2s;
    }
    .action-icons img:hover { transform: scale(1.2); }
    .table-wrapper { 
        width:100%;
        max-width:100%;
        overflow-x:auto;
        overflow-y:visible;
        margin: 0 4px; 
    }
    table th, table td { white-space:nowrap; }

    /* CAMPO DESCRIZIONE */
    th:nth-child(9),
    table td:nth-child(9){
        min-width: 500px;
    }
    th, td { white-space:nowrap; }
    td:nth-child(9){ white-space:normal; }

    a.commessa-link{
        color:#000;
        text-decoration: underline;
    }
</style>

<div class="top-bar">
    <div class="operatore-info" id="operatoreInfo">
        <img src="{{ asset('images/icons8-utente-uomo-cerchiato-50.png') }}" alt="Operatore">
        <div class="operatore-popup" id="operatorePopup">
            <div><strong>{{ $operatore->nome }} {{ $operatore->cognome }}</strong></div>
            <div>
                @if($operatore->reparti->isEmpty())
                    Nessun reparto assegnato
                @else
                    <p>Reparto: <strong>{{ $operatore->reparti->pluck('nome')->join(', ') }} </strong></p>
                @endif
            </div>
            <form action="{{ route('operatore.logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-secondary btn-sm mt-2">Logout</button>
            </form>
        </div>
    </div>
    <div class="action-icons">
        <img src="{{ asset('images/icons8-ricerca-50.png') }}"
             title="Cerca commessa"
             onclick="cercaCommessa()">
    </div>
</div>

<!-- BOX RICERCA COMMESSA -->
<div id="searchBox" style="display:none; margin:10px 8px;">
    <input type="text" id="searchInput" class="form-control" placeholder="Digita commessa da cercare...">
</div>

<h2>Dashboard Operatore</h2>

<div class="table-wrapper">
    <table class="table table-bordered table-sm table-striped">
        <thead class="table-dark">
            <tr>
                <th>Priorità</th>
                <th>Operatori</th>
                <th>Fase</th>
                <th>Stato</th>
                <th>Commessa</th>
                <th>Data Registrazione</th>
                <th>Cliente</th>
                <th>Codice Articolo</th>
                <th>Descrizione Articolo</th>
                <th>Quantità Richiesta</th>
                <th>UM</th>
                <th>Data Prevista Consegna</th>
                <th>Qta Prodotta</th>
                <th>Codice Carta</th>
                <th>Carta</th>
                <th>Quantità Carta</th>
                <th>UM Carta</th>
                <th>Note Operatore</th>
                <th>Timeout</th>
            </tr>
        </thead>
        <tbody>
            @foreach($fasiVisibili as $fase)
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
                    <td>{{ $fase->priorita !== null ? number_format($fase->priorita, 2) : '-' }}</td>
                    <td id="operatore-{{ $fase->id }}">
                        @foreach($fase->operatori as $op)
                            {{ $op->nome }} ({{ $op->pivot->data_inizio ? \Carbon\Carbon::parse($op->pivot->data_inizio)->format('d/m/Y H:i:s') : '-' }})<br>
                        @endforeach
                    </td>
                    <td>{{ $fase->faseCatalogo->nome_display ?? '-' }}</td>
                    @php $statoBg = [0 => '#e9ecef', 1 => '#cfe2ff', 2 => '#fff3cd', 3 => '#d1e7dd']; @endphp
                    <td id="stato-{{ $fase->id }}" style="background:{{ $statoBg[$fase->stato] ?? '#e9ecef' }};font-weight:bold;text-align:center;">{{ $fase->stato }}</td>

                    {{-- COMMESSA CLICCABILE --}}
                    <td>
                        <a href="{{ route('commesse.show', $fase->ordine->commessa) }}?fase={{ $fase->id }}" class="commessa-link"
                           style="font-weight:bold">
                           {{ $fase->ordine->commessa }}
                        </a>
                    </td>

                    <td>{{ $fase->ordine->data_registrazione ? \Carbon\Carbon::parse($fase->ordine->data_registrazione)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $fase->ordine->cliente_nome ?? '-' }}</td>
                    <td>{{ $fase->ordine->cod_art ?? '-' }}</td>
                    <td class="descrizione">{{ $fase->ordine->descrizione ?? '-' }}</td>
                    <td>{{ $fase->ordine->qta_richiesta ?? '-' }}</td>
                    <td>{{ $fase->ordine->um ?? '-' }}</td>
                    <td>{{ $fase->ordine->data_prevista_consegna ? \Carbon\Carbon::parse($fase->ordine->data_prevista_consegna)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $fase->qta_prod ?? '-' }}</td>
                    <td>{{ $fase->ordine->cod_carta ?? '-' }}</td>
                    <td>{{ $fase->ordine->carta ?? '-' }}</td>
                    <td>{{ $fase->ordine->qta_carta ?? '-' }}</td>
                    <td>{{ $fase->ordine->UM_carta ?? '-' }}</td>
                    <td>{{ $fase->note ?? '-' }}</td>
                    <td id="timeout-{{ $fase->id }}">{{ $fase->timeout ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
</div>

<script>
document.getElementById('operatoreInfo').addEventListener('click', function(){
    const popup = document.getElementById('operatorePopup');
    popup.style.display = popup.style.display === 'block' ? 'none' : 'block';
});

document.addEventListener('click', function(e){
    if(!document.getElementById('operatoreInfo').contains(e.target)){
        document.getElementById('operatorePopup').style.display='none';
    }
});

function cercaCommessa(){
    const box = document.getElementById('searchBox');
    const input = document.getElementById('searchInput');

    box.style.display = box.style.display === 'none' ? 'block' : 'none';
    input.focus();

    input.onkeyup = function(){
        const filtro = input.value.toLowerCase();
        document.querySelectorAll("tbody tr").forEach(riga=>{
            const commessa = riga.cells[4]?.innerText.toLowerCase() || '';
            riga.style.display = commessa.includes(filtro) ? '' : 'none';
        });
    };

    // chiudi con ESC
    input.onkeydown = function(e){
        if(e.key === "Escape"){
            box.style.display = 'none';
            input.value = '';
            document.querySelectorAll("tbody tr").forEach(riga=>riga.style.display='');
        }
    };
}
</script>
@endsection