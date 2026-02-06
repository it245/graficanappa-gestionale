@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
    <style>
        html,body{
            margin:0;
            padding:0;
            overflow-x:hidden;
            width:100%;
        }
        .table-wrapper{
            width:100%;
            max-width:100%;
            overflow-x:auto;
            overflow-y:visible;
            margin: 0 4px;
        }
        h2,p{
            margin-left:4px;
            margin-right:4px
        }
        tr.selected, th.selected{
            outline: 1px solid #3399ff;
        }
        .selection-box{
            position:absolute;
            border: 2px dashed #3399ff;
            background-color: rgba(51,153,255,0.2);
            pointer-events: none;
            z-index:1000;
        }
        table th:nth-child(4),
        table td:nth-child(4){
            min-width: 380px;
        }
        th,td{
            white-space:nowrap;
        }
        td:nth-child(4){
            white-space:normal;
        }

        /* Stile icone PNG */
        .action-icons img{
            height: 35px;
            cursor: pointer;
            margin-right: 25px;
            transition: transform 0.2s;
        }
        .action-icons img:hover{
            transform: scale(1.2);
        }
     /* Contenitore filtri */
/* FILTRI */
#filterBox {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: flex-start;
    margin-left: 10px;
}

/* input normali */
#filterBox input {
    min-height: 38px;
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
}

/* Choices.js container */
#filterBox .choices {
    display: inline-flex;
    align-items: center;
}

/* riquadro interno Choices */
#filterBox .choices__inner {
    min-height: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    padding: 0 8px;
    box-sizing: border-box;
}

/* larghezze */
#filterStato + .choices { width: 120px; }
#filterFase + .choices { width: 250px; }
#filterReparto + .choices { width: 200px; }

/* dropdown */
.choices__list--dropdown {
    max-height: 250px;
    overflow-y: auto;
}

/* larghezza fissa dei filtri */
#filterStato + .choices,
#filterFase + .choices,
#filterReparto + .choices {
    flex-direction: column;
    align-items: stretch;
}

/* contenitore interno */
#filterBox .choices__inner {
    height: auto !important;
    min-height: 38px;
    align-items: flex-start;
}

/* lista selezioni → VERTICALE */
#filterBox .choices__list--multiple {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

/* ogni selezione a tutta larghezza */
#filterBox .choices__item--selectable {
    width: 100%;
    box-sizing: border-box;
}
/* massimo 3 selezioni visibili */
#filterBox .choices__list--multiple .choices__item:nth-child(n+10) {
    display: none;
}
    </style>

    <h2>Dashboard Owner</h2>
    <p>
        Benvenuto: {{ auth()->user()->nome ?? session('operatore_nome') }}
        | Ruolo: {{ auth()->user()->ruolo ?? session('operatore_ruolo') }}
    </p>

    {{-- ICONE AZIONI --}}
    <div class="mb-3 d-flex align-items-center action-icons">

        {{-- Importa Ordini --}}
        <form action="{{ route('owner.importOrdini') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <label for="file-upload" title="Importa Ordini">
                <img src="{{ asset('images/import.png') }}" alt="Importa">
            </label>
            <input id="file-upload" type="file" name="file" style="display:none" onchange="this.form.submit()">
        </form>
        {{-- ICONA FILTRO --}}
        <img
            src="{{ asset('images/icons8-filtro-50.png') }}"
            id="toggleFilter"
            title="Mostra / Nascondi filtri"
            alt="Filtri"
        >

        {{-- Aggiungi Operatore --}}
        <a href="#" data-bs-toggle="modal" data-bs-target="#aggiungiOperatoreModal" title="Aggiungi Operatore">
            <img src="{{ asset('images/add-user.png') }}" alt="Aggiungi Operatore">
        </a>

        {{-- Visualizza fasi terminate --}}
        <a href="{{ route('owner.fasiTerminate') }}" title="Visualizza fasi terminate">
            <img src="{{ asset('images/out-of-the-box.png') }}" alt="Fasi terminate">
        </a>

        {{-- Stampa celle selezionate --}}
        <button id="printButton" class="btn p-0" style="background:none; border:none;" title="Stampa celle selezionate">
            <img src="{{ asset('images/printer.png') }}" alt="Stampa">
        </button>

    </div>
    
        {{-- FILTRI --}}
<div class="mb-3" id="filterBox" style="display:none;">
    <!-- Filtri multi-valore (virgola) -->
    <input type="text" id="filterCommessa" class="form-control form-control-sm" placeholder="Filtra Commessa (più valori ,)" style="max-width:200px;">
    <input type="text" id="filterCliente" class="form-control form-control-sm" placeholder="Filtra Cliente (più valori ,)" style="max-width:200px;">
    <input type="text" id="filterDescrizione" class="form-control form-control-sm" placeholder="Filtra Descrizione (più valori ,)" style="max-width:300px;">

    <!-- Filtri multi-selezione -->
   <select id="filterStato" multiple>
    <option value="0">0</option>
    <option value="1">1</option>
</select>

<select id="filterFase" multiple>
    @foreach($fasiCatalogo as $faseCat)
        <option value="{{ $faseCat->nome }}">{{ $faseCat->nome }}</option>
    @endforeach
</select>

<select id="filterReparto" multiple>
    @foreach($reparti as $id => $rep)
        <option value="{{ $rep }}">{{ $rep }}</option>
    @endforeach
</select>
</div>

    {{-- MODALE AGGIUNGI OPERATORE --}}
    <div class="modal fade" id="aggiungiOperatoreModal" tabindex="-1" aria-labelledby="aggiungiOperatoreModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form method="POST" action="{{ route('owner.aggiungiOperatore') }}">
                @csrf
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="aggiungiOperatoreModalLabel">Aggiungi Operatore</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Nome</label>
                            <input type="text" name="nome" id="nome" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Cognome</label>
                            <input type="text" name="cognome" id="cognome" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Ruolo</label>
                            <select name="ruolo" class="form-select" required>
                                <option value="operatore">Operatore</option>
                                <option value="owner">Owner</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Codice operatore</label>
                            <input type="text" id="codice_operatore" class="form-control" value="{{ $prossimoCodice }}" data-numero="{{ $prossimoNumero}}" disabled>
                            <small class="text-muted">Il codice sarà confermato alla creazione</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reparto Principale</label>
                            <select name="reparto_principale" class="form-select" required>
                                @foreach($reparti as $id => $rep)
                                    <option value="{{ $id }}">{{ $rep }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reparto Secondario (facoltativo)</label>
                            <select name="reparto_secondario" class="form-select">
                                <option value="">-- Nessuno --</option>
                                @foreach($reparti as $id => $rep)
                                    <option value="{{ $id }}">{{ $rep }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Aggiungi</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Chiudi</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

@php
    /* Helper date italiane */
    function formatItalianDate($date, $withTime = false) {
        if (!$date) return '-';
        try {
            return \Carbon\Carbon::parse($date)
                ->format($withTime ? 'd/m/Y H:i' : 'd/m/Y');
        } catch (\Exception $e) {
            return $date;
        }
    }
@endphp

    {{-- TABELLA --}}
    <div class="table-wrapper">
        <table class="table table-bordered table-sm table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Commessa</th>
                    <th>Cliente</th>
                    <th>Codice Articolo</th>
                    <th>Descrizione</th>
                    <th>Qta</th>
                    <th>UM</th>
                    <th>Priorità</th>
                    <th>Data Registrazione</th>
                    <th>Data Prevista Consegna</th>
                    <th>Cod Carta</th>
                    <th>Carta</th>
                    <th>Qta Carta</th>
                    <th>UM Carta</th>
                    <th>Fase</th>
                    <th>Reparto</th>
                    <th>Operatori</th>
                    <th>Qta Prod.</th>
                    <th>Note</th>
                    <th>Data Inizio</th>
                    <th>Data Fine</th>
                    <th>Stato</th>
                </tr>
            </thead>
            <tbody>
            @foreach($fasi as $fase)
                @php
                    $rowClass = '';
                    if ($fase->ordine->data_prevista_consegna) {
                        $dataPrevista = \Carbon\Carbon::parse($fase->ordine->data_prevista_consegna)->startOfDay();
                        $oggi = \Carbon\Carbon::today();
                        $diffGiorni = $oggi->diffInDays($dataPrevista, false);
                        if ($diffGiorni <= -5) $rowClass = 'scaduta';
                        elseif ($diffGiorni <= 3) $rowClass = 'warning-strong';
                        elseif ($diffGiorni <= 5) $rowClass = 'warning-light';
                    }
                @endphp
                <tr class="{{ $rowClass }}" data-id="{{ $fase->id }}">
                    <td>{{ $fase->ordine->commessa ?? '-' }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'cliente_nome', this.innerText)">{{ $fase->ordine->cliente_nome ?? '-' }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'cod_art', this.innerText)">{{ $fase->ordine->cod_art ?? '-' }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'descrizione', this.innerText)">{{ $fase->ordine->descrizione ?? '-' }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'qta_richiesta', this.innerText)">{{ $fase->ordine->qta_richiesta ?? '-' }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'um', this.innerText)">{{ $fase->ordine->um ?? '-' }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'priorita', this.innerText)">{{ $fase->priorita ?? '-' }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'data_registrazione', this.innerText)">{{ formatItalianDate($fase->ordine->data_registrazione) }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'data_prevista_consegna', this.innerText)">{{ formatItalianDate($fase->ordine->data_prevista_consegna) }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'cod_carta', this.innerText)">{{ $fase->ordine->cod_carta ?? '-' }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'carta', this.innerText)">{{ $fase->ordine->carta ?? '-' }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'qta_carta', this.innerText)">{{ $fase->ordine->qta_carta ?? '-' }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'UM_carta', this.innerText)">{{ $fase->ordine->UM_carta ?? '-' }}</td>
                    <td>{{ $fase->faseCatalogo->nome ?? '-' }}</td>
                    <td>{{ $fase->faseCatalogo->reparto->nome ?? '-' }}</td>
                    <td>
                        @forelse($fase->operatori as $op)
                            {{ $op->nome }} ({{ formatItalianDate($op->pivot->data_inizio, true) }})<br>
                        @empty
                            -
                        @endforelse
                    </td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'qta_prod', this.innerText)">{{ $fase->qta_prod ?? '-' }}</td>
                    <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'note', this.innerText)">{{ $fase->note ?? '-' }}</td>
                    <td>{{ formatItalianDate($fase->data_inizio, true) }}</td>
                    <td>{{ formatItalianDate($fase->data_fine, true) }}</td>
                    <td>{{ $fase->stato ?? '-' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" />
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
{{-- JS --}}
<script>
function aggiornaCampo(faseId, campo, valore){
    fetch('{{ route("produzione.aggiornaCampo") }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json'},
        body: JSON.stringify({ fase_id: faseId, campo: campo, valore: valore })
    }).then(r => r.json()).then(d => {
        if (!d.success) alert('Errore salvataggio');
    });
}
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const nomeInput = document.querySelector('input[name="nome"]');
    const cognomeInput = document.querySelector('input[name="cognome"]');
    const codiceInput = document.getElementById('codice_operatore');
    if (!nomeInput || !cognomeInput || !codiceInput) return;
    const numero = codiceInput.dataset.numero;
    function aggiornaCodice() {
        const nome = nomeInput.value.trim();
        const cognome = cognomeInput.value.trim();
        codiceInput.value = nome && cognome ? nome.charAt(0).toUpperCase() + cognome.charAt(0).toUpperCase() + numero.toString().padStart(3,'0') : '__' + numero.toString().padStart(3,'0');
    }
    nomeInput.addEventListener('input', aggiornaCodice);
    cognomeInput.addEventListener('input', aggiornaCodice);
});
</script>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const table = document.querySelector('table');
    const cells = table.querySelectorAll('td, th');
    let isSelecting = false, startX, startY, selectionBox;
    const selectedCellsSet = new Set();

    table.addEventListener('mousedown', e => {
        if(e.button!==0) return;
        isSelecting = true;
        startX = e.pageX; startY = e.pageY;
        selectionBox = document.createElement('div');
        selectionBox.classList.add('selection-box');
        selectionBox.style.left = `${startX}px`; selectionBox.style.top = `${startY}px`;
        document.body.appendChild(selectionBox);
    });

    document.addEventListener('mousemove', e => {
        if(!isSelecting) return;
        const x = Math.min(e.pageX,startX), y = Math.min(e.pageY,startY);
        const w = Math.abs(e.pageX-startX), h = Math.abs(e.pageY-startY);
        selectionBox.style.left = `${x}px`; selectionBox.style.top = `${y}px`;
        selectionBox.style.width = `${w}px`; selectionBox.style.height = `${h}px`;

        const rect = selectionBox.getBoundingClientRect();
        cells.forEach(cell => {
            const r = cell.getBoundingClientRect();
            if(rect.bottom<r.top||rect.top>r.bottom||rect.right<r.left||rect.left>r.right){
                cell.classList.remove('selected');
            } else {
                cell.classList.add('selected');
                selectedCellsSet.add(cell);
            }
        });
    });

    document.addEventListener('mouseup', () => { isSelecting=false; if(selectionBox) selectionBox.remove(); });

    const printButton = document.getElementById('printButton');
    printButton.addEventListener('click', () => {
        if(selectedCellsSet.size===0){ alert('Seleziona almeno una cella!'); return; }
        const rowsMap = new Map();
        selectedCellsSet.forEach(cell=>{
            const row=cell.parentElement;
            if(!rowsMap.has(row)) rowsMap.set(row,[]);
            const style=window.getComputedStyle(cell);
            rowsMap.get(row).push({content:cell.innerHTML, tag:cell.tagName, background:style.backgroundColor, color:style.color});
        });
        const printWindow = window.open('','width=1200,height=800');
        printWindow.document.write('<html><head><title>Stampa</title>');
        printWindow.document.write('<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">');
        printWindow.document.write('<style>table{border-collapse: collapse;} td, th{border:1px solid #dee2e6; padding:0.25rem;}</style>');
        printWindow.document.write('</head><body><table class="table table-bordered table-sm">');
        rowsMap.forEach(cellsArr=>{
            printWindow.document.write('<tr>');
            cellsArr.forEach(c=>{
                printWindow.document.write(`<${c.tag} style="background:${c.background}; color:${c.color}">${c.content}</${c.tag}>`);
            });
            printWindow.document.write('</tr>');
        });
        printWindow.document.write('</table></body></html>');
        printWindow.document.close(); printWindow.print();
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Inizializza Choices.js
    const choicesOptions = {
        removeItemButton: true,
        searchEnabled: true,
        itemSelectText: '',
        placeholder:true,
    };
    const choiceStato = new Choices('#filterStato',{...choicesOptions,placeholderValue:'Seleziona Stato'});
    const choiceFase = new Choices('#filterFase',{...choicesOptions,placeholderValue:'Seleziona Fase'})
    const choiceReparto = new Choices('#filterReparto',{...choicesOptions,placeholderValue:'Seleziona Reparto'})

    const toggleFilter = document.getElementById('toggleFilter');
    const filterBox = document.getElementById('filterBox');

    const fCommessa = document.getElementById('filterCommessa');
    const fCliente = document.getElementById('filterCliente');
    const fDescrizione = document.getElementById('filterDescrizione');
    const fStato = document.getElementById('filterStato');
    const fFase = document.getElementById('filterFase');
    const fReparto = document.getElementById('filterReparto');

    const rows = Array.from(document.querySelectorAll('table tbody tr'));

    const rowData = rows.map(row => ({
        row,
        commessa: row.cells[0].innerText.toLowerCase(),
        cliente: row.cells[1].innerText.toLowerCase(),
        descrizione: row.cells[3].innerText.toLowerCase(),
        stato: row.cells[20].innerText.toLowerCase(), // ora contiene "1" o "2"
        fase: row.cells[13].innerText.toLowerCase(),
        reparto: row.cells[14].innerText.toLowerCase()
    }));

    function parseValues(input) {
        return input.split(',').map(v => v.trim().toLowerCase()).filter(v => v);
    }

    function getSelectedOptions(select) {
        return Array.from(select.selectedOptions).map(opt => opt.value.toLowerCase());
    }

    function filtra() {
        const commesse = parseValues(fCommessa.value);
        const clienti = parseValues(fCliente.value);
        const descrizioni = parseValues(fDescrizione.value);
        const stati = getSelectedOptions(fStato);
        const fasi = getSelectedOptions(fFase);
        const reparti = getSelectedOptions(fReparto);

        requestAnimationFrame(() => {
            rowData.forEach(data => {
                let match = true;
                if(commesse.length) match = match && commesse.some(v => data.commessa.includes(v));
                if(clienti.length) match = match && clienti.some(v => data.cliente.includes(v));
                if(descrizioni.length) match = match && descrizioni.some(v => data.descrizione.includes(v));
                if(stati.length) match = match && stati.includes(data.stato);
                if(fasi.length) match = match && fasi.includes(data.fase);
                if(reparti.length) match = match && reparti.includes(data.reparto);
                data.row.style.display = match ? '' : 'none';
            });
        });
    }

    function debounce(func, delay) {
        let timeout;
        return function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, arguments), delay);
        }
    }

    const filtraDebounced = debounce(filtra, 100);

    fCommessa.addEventListener('input', filtraDebounced);
    fCliente.addEventListener('input', filtraDebounced);
    fDescrizione.addEventListener('input', filtraDebounced);
    fStato.addEventListener('change', filtraDebounced);
    fFase.addEventListener('change', filtraDebounced);
    fReparto.addEventListener('change', filtraDebounced);

    if (toggleFilter) {
        toggleFilter.addEventListener('click', () => {
            if (filterBox.style.display === 'none') {
                filterBox.style.display = 'flex';
                filterBox.style.opacity = 0;
                filterBox.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    filterBox.style.opacity = 1;
                    filterBox.style.transform = 'translateY(0)';
                }, 10);
            } else {
                filterBox.style.opacity = 0;
                filterBox.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    filterBox.style.display = 'none';
                    fCommessa.value = '';
                    fCliente.value = '';
                    fDescrizione.value = '';
                    choiceStato.removeActiveItems();
                    choiceFase.removeActiveItems();
                    choiceReparto.removeActiveItems();
                    rowData.forEach(data => data.row.style.display = '');
                }, 300);
            }
        });
    }
});
</script>
@endsection