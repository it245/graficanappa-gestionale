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

    /* Sezioni reparto separate */
    .reparto-section {
        margin-bottom: 30px;
    }
    .reparto-section h3 {
        background: #343a40;
        color: #fff;
        padding: 10px 15px;
        margin: 0 4px 0 4px;
        border-radius: 6px 6px 0 0;
        font-size: 18px;
        font-weight: bold;
        user-select: none;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .reparto-body {
        overflow: hidden;
    }

    /* Lampeggio tasto Avvia quando stato = 2 */
    @keyframes lampeggio {
        0%, 100% { opacity: 1; background-color: #28a745; }
        50% { opacity: 0.3; background-color: #ff6600; }
    }
    .badge-avvia-lampeggia {
        animation: lampeggio 1s ease-in-out infinite;
        color: #fff !important;
        font-weight: bold;
    }
</style>

<div class="top-bar">
    <div style="display:flex; align-items:center; gap:10px;">
        <img src="{{ asset('images/logo_gn.png') }}" alt="Logo" style="height:40px;">
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
    </div>
    <div class="action-icons" style="display:flex; align-items:center;">
        <img src="{{ asset('images/icons8-ricerca-50.png') }}"
             title="Cerca commessa"
             onclick="cercaCommessa()">
        <span title="Storico fasi terminate"
              style="font-size:28px; cursor:pointer; user-select:none; margin-right:15px;"
              data-bs-toggle="offcanvas" data-bs-target="#offcanvasStorico">&#9776;</span>
    </div>
</div>

<!-- BOX RICERCA COMMESSA -->
<div id="searchBox" style="display:none; margin:10px 8px;">
    <input type="text" id="searchInput" class="form-control" placeholder="Digita commessa da cercare...">
</div>

<h2>Dashboard Operatore</h2>

@if(!empty($fasiPerReparto))
    {{-- MULTI-REPARTO: sezioni separate per ogni reparto --}}
    @foreach($fasiPerReparto as $repartoId => $info)
        <div class="reparto-section">
            <h3>
                <span>{{ $info['nome'] }} <small>({{ $info['fasi']->count() }})</small></span>
            </h3>
            <div class="reparto-body">
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
                            @if($showColori)<th>Colori</th>@endif
                            @if($showFustella)<th>Fustella</th>@endif
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
                        @forelse($info['fasi'] as $fase)
                            @include('operatore._fase_row', ['fase' => $fase])
                        @empty
                            <tr><td colspan="{{ 19 + ($showColori ? 1 : 0) + ($showFustella ? 1 : 0) }}" class="text-center text-muted">Nessuna fase attiva</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            </div>
        </div>
    @endforeach
@else
    {{-- SINGOLO REPARTO: tabella unica come prima --}}
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
                    @include('operatore._fase_row', ['fase' => $fase])
                @endforeach
            </tbody>
        </table>
    </div>
@endif

<!-- OFFCANVAS STORICO FASI TERMINATE -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasStorico" style="width:75vw; max-width:900px;">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">Storico fasi terminate (ultimi 30 giorni)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body p-0">
        <table class="table table-bordered table-sm table-striped mb-0">
            <thead class="table-dark">
                <tr>
                    <th>Commessa</th>
                    <th>Cliente</th>
                    <th>Fase</th>
                    <th>Descrizione</th>
                    <th>Data Fine</th>
                    <th>Etichetta</th>
                </tr>
            </thead>
            <tbody>
                @forelse($fasiTerminate as $ft)
                    <tr>
                        <td>{{ $ft->ordine->commessa ?? '-' }}</td>
                        <td>{{ $ft->ordine->cliente_nome ?? '-' }}</td>
                        <td>{{ $ft->fase }}</td>
                        <td style="white-space:normal; max-width:300px;">{{ Str::limit($ft->ordine->descrizione ?? '-', 80) }}</td>
                        <td>{{ $ft->data_fine ? \Carbon\Carbon::parse($ft->data_fine)->format('d/m/Y H:i') : '-' }}</td>
                        <td>
                            @if($ft->ordine)
                                <a href="{{ route('operatore.etichetta', $ft->ordine->id) }}" class="btn btn-sm btn-outline-primary" target="_blank">Stampa</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">Nessuna fase terminata</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
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