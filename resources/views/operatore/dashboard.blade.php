@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
   <style>
    html, body {
        margin:0; padding:0; width:100%;
        overflow-x: hidden;
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
        overflow-y:auto;
        max-height: calc(100vh - 220px);
        margin: 0 4px;
    }
    .table-wrapper thead th {
        position: sticky;
        top: 0;
        z-index: 10;
    }
    table th, table td { white-space:nowrap; }

    /* CAMPO DESCRIZIONE */
    td.descrizione {
        min-width: 500px;
        white-space: normal;
    }
    th, td { white-space:nowrap; }

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

    /* Barra filtri */
    .filtri-bar {
        display: flex;
        gap: 12px;
        align-items: center;
        padding: 8px 15px;
        background: #f0f2f5;
        margin: 0 4px;
        border-bottom: 1px solid #dee2e6;
        flex-wrap: wrap;
    }
    .filtri-bar label {
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 0;
        white-space: nowrap;
    }
    .filtri-bar select,
    .filtri-bar input {
        font-size: 13px;
        padding: 3px 8px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        height: 30px;
    }
    .filtri-bar input { width: 160px; }
    .filtri-bar select { width: 80px; }
    .filtri-bar .btn-reset-filtri {
        font-size: 12px;
        padding: 3px 10px;
        border: 1px solid #adb5bd;
        background: #fff;
        border-radius: 4px;
        cursor: pointer;
    }
    .filtri-bar .btn-reset-filtri:hover { background: #e9ecef; }
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
            <div class="filtri-bar filtri-reparto" data-reparto="{{ $repartoId }}">
                <label>Stato:</label>
                <select class="filtro-stato" onchange="applicaFiltri(this)">
                    <option value="">Tutti</option>
                    <option value="0">0</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                </select>
                <label>Cliente:</label>
                <input type="text" class="filtro-cliente" placeholder="Cerca cliente..." oninput="applicaFiltri(this)">
                <label>Descrizione:</label>
                <input type="text" class="filtro-descrizione" placeholder="Cerca descrizione..." oninput="applicaFiltri(this)">
                <button type="button" class="btn-reset-filtri" onclick="resetFiltri(this)">Reset</button>
            </div>
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
                            @if($showEsterno ?? false)<th>Esterno</th>@endif
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
                            <tr><td colspan="{{ 19 + ($showFustella ? 1 : 0) + ($showColori ? 1 : 0) + ($showEsterno ? 1 : 0) }}" class="text-center text-muted">Nessuna fase attiva</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            </div>
        </div>
    @endforeach
@else
    {{-- SINGOLO REPARTO: tabella unica come prima --}}
    <div class="filtri-bar filtri-reparto" data-reparto="singolo">
        <label>Stato:</label>
        <select class="filtro-stato" onchange="applicaFiltri(this)">
            <option value="">Tutti</option>
            <option value="0">0</option>
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
        </select>
        <label>Cliente:</label>
        <input type="text" class="filtro-cliente" placeholder="Cerca cliente..." oninput="applicaFiltri(this)">
        <label>Descrizione:</label>
        <input type="text" class="filtro-descrizione" placeholder="Cerca descrizione..." oninput="applicaFiltri(this)">
        <button type="button" class="btn-reset-filtri" onclick="resetFiltri(this)">Reset</button>
    </div>
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
                    @if($showEsterno ?? false)<th>Esterno</th>@endif
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

// ===== Filtri per stato, cliente, descrizione =====
function applicaFiltri(el) {
    var bar = el.closest('.filtri-reparto');
    var filtroStato = bar.querySelector('.filtro-stato').value;
    var filtroCliente = bar.querySelector('.filtro-cliente').value.toLowerCase().trim();
    var filtroDescrizione = bar.querySelector('.filtro-descrizione').value.toLowerCase().trim();

    // Trova la tabella associata a questa barra filtri
    var tableWrapper = bar.nextElementSibling;
    // Per multi-reparto la struttura è: filtri-bar → reparto-body → table-wrapper → table
    // Per singolo reparto: filtri-bar → table-wrapper → table
    if (tableWrapper.classList.contains('reparto-body')) {
        tableWrapper = tableWrapper.querySelector('table');
    } else {
        tableWrapper = tableWrapper.querySelector('table');
    }
    if (!tableWrapper) return;

    var righe = tableWrapper.querySelectorAll('tbody tr');
    righe.forEach(function(riga) {
        var statoCell = riga.querySelector('.td-stato');
        var clienteCell = riga.querySelector('.td-cliente');
        var descCell = riga.querySelector('.td-descrizione');

        var statoOk = !filtroStato || (statoCell && statoCell.textContent.trim() === filtroStato);
        var clienteOk = !filtroCliente || (clienteCell && clienteCell.textContent.toLowerCase().includes(filtroCliente));
        var descOk = !filtroDescrizione || (descCell && descCell.textContent.toLowerCase().includes(filtroDescrizione));

        riga.style.display = (statoOk && clienteOk && descOk) ? '' : 'none';
    });
}

function resetFiltri(el) {
    var bar = el.closest('.filtri-reparto');
    bar.querySelector('.filtro-stato').value = '';
    bar.querySelector('.filtro-cliente').value = '';
    bar.querySelector('.filtro-descrizione').value = '';
    applicaFiltri(el);
}

function salvaQtaProd(faseId, valore) {
    fetch('{{ route("produzione.aggiornaCampo") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': window.csrfToken(),
            'X-Op-Token': window.opToken(),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ fase_id: faseId, campo: 'qta_prod', valore: valore })
    }).then(function(r) {
        if (r.ok) {
            // Flash verde breve sull'input
            var input = document.querySelector('#fase-' + faseId + ' input[type="number"]');
            if (input) {
                input.style.borderColor = '#28a745';
                setTimeout(function() { input.style.borderColor = '#ced4da'; }, 1500);
            }
        } else {
            alert('Errore nel salvataggio');
        }
    }).catch(function() { alert('Errore di connessione'); });
}

</script>
@endsection