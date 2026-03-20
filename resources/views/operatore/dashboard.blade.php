@extends('layouts.app')

@section('title'){{ ($operatore->nome ?? '') . ' ' . ($operatore->cognome ?? '') }}@endsection

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
    /* CAMPO CLIENTE */
    td.td-cliente {
        max-width: 140px;
        overflow: hidden;
        text-overflow: ellipsis;
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

    /* ===== RESPONSIVE MOBILE ===== */
    @media (max-width: 768px) {
        h2 { font-size: 18px; }
        .top-bar {
            flex-wrap: wrap;
            gap: 8px;
            padding: 4px 8px;
        }
        .operatore-info img {
            width: 40px; height: 40px;
        }
        .action-icons img {
            height: 44px;
            padding: 6px;
            margin-right: 4px;
        }
        .table-wrapper {
            max-height: calc(100vh - 280px);
            margin: 0;
            -webkit-overflow-scrolling: touch;
        }
        table { font-size: 12px; }
        table th, table td { padding: 4px 6px; }
        td.descrizione { min-width: 200px; }
        td.td-cliente { max-width: 100px; }

        /* Hide less important columns on mobile */
        table th:nth-child(6),  table td:nth-child(6),  /* Fustella */
        table th:nth-child(7),  table td:nth-child(7),  /* Codice Articolo */
        table th:nth-child(12), table td:nth-child(12), /* UM */
        table th:nth-child(16), table td:nth-child(16), /* Codice Carta */
        table th:nth-child(17), table td:nth-child(17), /* Carta */
        table th:nth-child(18), table td:nth-child(18), /* Qta Carta */
        table th:nth-child(19), table td:nth-child(19)  /* UM Carta */
        {
            display: none;
        }

        /* Filtri bar: stack vertically */
        .filtri-bar {
            flex-direction: column;
            align-items: stretch;
            gap: 6px;
            padding: 8px 10px;
        }
        .filtri-bar label {
            font-size: 12px;
        }
        .filtri-bar select,
        .filtri-bar input {
            width: 100% !important;
            height: 40px;
            font-size: 14px;
            min-height: 44px;
        }
        .filtri-bar .btn-reset-filtri {
            min-height: 44px;
            font-size: 14px;
            padding: 8px 16px;
        }

        /* Reparto section headers */
        .reparto-section h3 {
            font-size: 15px;
            padding: 8px 10px;
            margin: 0 2px;
        }

        /* Search box */
        #searchBox input {
            font-size: 16px;
            min-height: 44px;
        }

        /* Buttons in table rows */
        table .btn, table button, table a.btn {
            min-height: 44px;
            min-width: 44px;
            font-size: 12px;
            padding: 6px 10px;
        }
    }

    @media (max-width: 480px) {
        h2 { font-size: 16px; }
        table { font-size: 11px; }
        td.descrizione { min-width: 150px; }
        .reparto-section h3 { font-size: 14px; }

        /* Hide even more columns on very small screens */
        table th:nth-child(13), table td:nth-child(13), /* Data Registrazione */
        table th:nth-child(21), table td:nth-child(21)  /* Timeout */
        {
            display: none;
        }
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
            <a href="{{ route('operatore.logout') }}" class="btn btn-secondary btn-sm mt-2">Logout</a>
        </div>
    </div>
    </div>
    <div class="action-icons" style="display:flex; align-items:center; gap:12px;">
        <img src="{{ asset('images/icons8-ricerca-50.png') }}"
             title="Cerca commessa"
             onclick="cercaCommessa()">
        @if($isFustellaOperatore ?? false)
            <a href="{{ route('operatore.fustelle', ['op_token' => request('op_token')]) }}"
               title="Fustelle"
               style="font-size:28px; text-decoration:none; margin-right:15px;">&#9881;</a>
        @endif
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
        @if($info['fasi']->isEmpty()) @continue @endif
        <div class="reparto-section">
            <h3>
                <span>{{ $info['nome'] }} <small>({{ $info['fasi']->count() }})</small></span>
            </h3>
            @php $fasiDistinte = $info['fasi']->map(fn($f) => $f->faseCatalogo->nome_display ?? $f->fase)->unique()->sort()->values(); @endphp
            <div class="filtri-bar filtri-reparto" data-reparto="{{ $repartoId }}">
                <label>Stato:</label>
                <select class="filtro-stato" onchange="applicaFiltri(this)">
                    <option value="1,2" selected>1 + 2</option>
                    <option value="">Tutti</option>
                    <option value="0">0</option>
                    <option value="1">1</option>
                    <option value="2">2</option>
                    <option value="3">3</option>
                </select>
                <label>Fase:</label>
                <select class="filtro-fase" onchange="applicaFiltri(this)">
                    <option value="">Tutte</option>
                    @foreach($fasiDistinte as $nomeFase)
                        <option value="{{ $nomeFase }}">{{ $nomeFase }}</option>
                    @endforeach
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
                            <th>Fase</th>
                            <th>Stato</th>
                            <th>Commessa</th>
                            <th>Cliente</th>
                            <th>Fustella</th>
                            <th>Codice Articolo</th>
                            @if($showColori)<th>Colori</th>@endif
                            @if($showEsterno ?? false)<th>Esterno</th>@endif
                            <th>Descrizione Articolo</th>
                            <th>Quantità Richiesta</th>
                            <th>UM</th>
                            <th>Data Registrazione</th>
                            <th>Data Prevista Consegna</th>
                            <th>Qta Prodotta</th>
                            <th>Codice Carta</th>
                            <th>Carta</th>
                            <th>Quantità Carta</th>
                            <th>UM Carta</th>
                            <th>Operatori</th>
                            <th>Note Operatore</th>
                            <th>Timeout</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($info['fasi'] as $fase)
                            @include('operatore._fase_row', ['fase' => $fase])
                        @empty
                            <tr><td colspan="{{ 20 + ($showColori ? 1 : 0) + ($showEsterno ? 1 : 0) }}" class="text-center text-muted">Nessuna fase attiva</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            </div>
        </div>
    @endforeach
@else
    {{-- SINGOLO REPARTO: tabella unica come prima --}}
    @php $fasiDistinte = $fasiVisibili->map(fn($f) => $f->faseCatalogo->nome_display ?? $f->fase)->unique()->sort()->values(); @endphp
    <div class="filtri-bar filtri-reparto" data-reparto="singolo">
        <label>Stato:</label>
        <select class="filtro-stato" onchange="applicaFiltri(this)">
            <option value="1,2" selected>1 + 2</option>
            <option value="">Tutti</option>
            <option value="0">0</option>
            <option value="1">1</option>
            <option value="2">2</option>
            <option value="3">3</option>
        </select>
        <label>Fase:</label>
        <select class="filtro-fase" onchange="applicaFiltri(this)">
            <option value="">Tutte</option>
            @foreach($fasiDistinte as $nomeFase)
                <option value="{{ $nomeFase }}">{{ $nomeFase }}</option>
            @endforeach
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
                    <th>Fase</th>
                    <th>Stato</th>
                    <th>Commessa</th>
                    <th>Cliente</th>
                    <th>Fustella</th>
                    <th>Codice Articolo</th>
                    @if($showColori)<th>Colori</th>@endif
                    @if($showEsterno ?? false)<th>Esterno</th>@endif
                    <th>Descrizione Articolo</th>
                    <th>Quantità Richiesta</th>
                    <th>UM</th>
                    <th>Data Registrazione</th>
                    <th>Data Prevista Consegna</th>
                    <th>Qta Prodotta</th>
                    @if($showScarti ?? false)<th>Scarti Reali</th><th>Scarti Prinect</th>@endif
                    <th>Codice Carta</th>
                    <th>Carta</th>
                    <th>Quantità Carta</th>
                    <th>UM Carta</th>
                    <th>Operatori</th>
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
        if (filtro) {
            // Ricerca attiva: mostra tutte le righe che corrispondono, ignorando filtri stato
            document.querySelectorAll("tbody tr").forEach(riga=>{
                const commessa = riga.cells[3]?.innerText.toLowerCase() || '';
                riga.style.display = commessa.includes(filtro) ? '' : 'none';
            });
        } else {
            // Campo vuoto: riapplica i filtri stato attivi
            document.querySelectorAll('.filtri-reparto').forEach(function(bar) {
                applicaFiltri(bar.querySelector('.filtro-stato'));
            });
        }
    };

    // chiudi con ESC
    input.onkeydown = function(e){
        if(e.key === "Escape"){
            box.style.display = 'none';
            input.value = '';
            // Riapplica filtri stato
            document.querySelectorAll('.filtri-reparto').forEach(function(bar) {
                applicaFiltri(bar.querySelector('.filtro-stato'));
            });
        }
    };
}

// ===== Filtri per stato, cliente, descrizione =====
function applicaFiltri(el) {
    var bar = el.closest('.filtri-reparto');
    var filtroStato = bar.querySelector('.filtro-stato').value;
    var statiAttivi = filtroStato ? filtroStato.split(',') : [];
    var tuttiStati = statiAttivi.length === 0;

    var filtroFase = bar.querySelector('.filtro-fase').value.trim();
    var filtroCliente = bar.querySelector('.filtro-cliente').value.toLowerCase().trim();
    var filtroDescrizione = bar.querySelector('.filtro-descrizione').value.toLowerCase().trim();

    // Trova la tabella associata a questa barra filtri
    var tableWrapper = bar.nextElementSibling;
    if (tableWrapper.classList.contains('reparto-body')) {
        tableWrapper = tableWrapper.querySelector('table');
    } else {
        tableWrapper = tableWrapper.querySelector('table');
    }
    if (!tableWrapper) return;

    var righe = tableWrapper.querySelectorAll('tbody tr');
    righe.forEach(function(riga) {
        var statoCell = riga.querySelector('.td-stato');
        var faseCell = riga.querySelector('.td-fase');
        var clienteCell = riga.querySelector('.td-cliente');
        var descCell = riga.querySelector('.td-descrizione');

        // Le fasi in pausa sono sempre visibili (stato non numerico = motivo pausa)
        var statoText = statoCell ? statoCell.textContent.trim() : '';
        var inPausa = statoText !== '' && isNaN(statoText) && statoText !== '0' && statoText !== '1' && statoText !== '2' && statoText !== '3' && statoText !== '4';
        if (inPausa) {
            riga.style.display = '';
            return;
        }

        var statoOk = tuttiStati || statiAttivi.includes(statoText);
        var faseOk = !filtroFase || (faseCell && faseCell.textContent.trim() === filtroFase);
        var clienteOk = !filtroCliente || (clienteCell && clienteCell.textContent.toLowerCase().includes(filtroCliente));
        var descOk = !filtroDescrizione || (descCell && descCell.textContent.toLowerCase().includes(filtroDescrizione));

        riga.style.display = (statoOk && faseOk && clienteOk && descOk) ? '' : 'none';
    });
}

function resetFiltri(el) {
    var bar = el.closest('.filtri-reparto');
    bar.querySelector('.filtro-stato').value = '1,2';
    bar.querySelector('.filtro-fase').value = '';
    bar.querySelector('.filtro-cliente').value = '';
    bar.querySelector('.filtro-descrizione').value = '';
    applicaFiltri(el);
}

function salvaScarti(faseId, valore) {
    fetch('{{ route("produzione.aggiornaCampo") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': window.csrfToken(),
            'X-Op-Token': window.opToken(),
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ fase_id: faseId, campo: 'scarti', valore: valore })
    }).then(function(r) {
        if (r.ok) {
            var input = document.querySelector('#fase-' + faseId + ' input[onchange*="salvaScarti"]');
            if (input) {
                input.style.borderColor = '#28a745';
                setTimeout(function() { input.style.borderColor = '#ced4da'; }, 1500);
            }
        } else {
            alert('Errore nel salvataggio');
        }
    }).catch(function() { alert('Errore di connessione'); });
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


// Applica filtro stato 1+2 al caricamento pagina
document.querySelectorAll('.filtri-reparto').forEach(function(bar) {
    applicaFiltri(bar.querySelector('.filtro-stato'));
});

// Auto-refresh su deploy: controlla ogni 10 minuti se c'è una nuova versione
(function() {
    var currentVersion = null;
    function checkVersion() {
        fetch('/version.txt?t=' + Date.now())
            .then(function(r) { return r.text(); })
            .then(function(v) {
                v = v.trim();
                if (currentVersion === null) {
                    currentVersion = v;
                } else if (v !== currentVersion) {
                    location.reload();
                }
            })
            .catch(function() {});
    }
    checkVersion();
    setInterval(checkVersion, 600000); // 10 minuti
})();

</script>
@endsection