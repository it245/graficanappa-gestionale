@extends('layouts.app')

@section('content')
<div class="container-fluid px-0">
<style>
html, body {
    margin: 0 !important;
    padding: 0 !important;
    width: 100%;
    height: 100%;
    overflow-x: hidden;
}

/* Rimuovi padding del container Bootstrap */
.container-fluid {
    padding-left: 1px !important;
    padding-right: 1px !important;
    margin-left: 0 !important;
}

/* Titoli */
h2, p {
    margin: 4px 1px !important;

}

/* Icone */
.action-icons {
    margin-left: 1px !important;
    padding-left: 0 !important;
}

.action-icons img {
    height: 35px;
    cursor: pointer;
    margin-right: 14px;
    transition: transform 0.15s ease;
}
.action-icons img:hover {
    transform: scale(1.15);
}

/* =========================
   TABELLA (EXCEL STYLE)
   ========================= */

table {
    width: 2000px;              /* LIMITE ASSOLUTO */
    max-width: 2000px;
    border-collapse: collapse;
    table-layout: fixed;        /* FONDAMENTALE */
    font-size: 12px;
}

thead, tbody, tr {
    width: 100%;
}

th, td {
    border: 1px solid #dee2e6;
    padding: 2px 6px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    line-height: 1.2;
}

/* Header sticky */
thead th {
    background: #000000;
    color: #ffffff; 
    font-size: 11.5px;
    
}

/* =========================
   LARGHEZZA COLONNE (≈ 1980px)
   ========================= */

/* Commessa */
th:nth-child(1), td:nth-child(1) { width: 90px; }

/* Cliente */
th:nth-child(2), td:nth-child(2) {
    width: 180px;
    white-space: normal;
}

/* Codice articolo */
th:nth-child(3), td:nth-child(3) { width: 110px; }

/* DESCRIZIONE (MAX 280px) */
th:nth-child(4), td:nth-child(4) {
    width: 280px;
    max-width: 280px;
    white-space: normal;
}

/* Qta / UM / Priorità */
th:nth-child(5), td:nth-child(5),
th:nth-child(6), td:nth-child(6),
th:nth-child(7), td:nth-child(7) {
    width: 70px;
    text-align: center;
}

/* Date */
th:nth-child(8), td:nth-child(8),
th:nth-child(9), td:nth-child(9),
th:nth-child(19), td:nth-child(19),
th:nth-child(20), td:nth-child(20) {
    width: 115px;
}

/* Carta */
th:nth-child(10), td:nth-child(10),
th:nth-child(11), td:nth-child(11) {
    width: 200px;
     white-space: normal;
}

th:nth-child(12), td:nth-child(12),
th:nth-child(13), td:nth-child(13) {
    width: 80px;
    text-align: center;
}

/* Fase + Reparto */
th:nth-child(14), td:nth-child(14),
th:nth-child(15), td:nth-child(15) {
    width: 110px;
}

/* Operatori */
th:nth-child(16), td:nth-child(16) {
    width: 100px;
    white-space: normal;
}

/* Qta prodotta */
th:nth-child(17), td:nth-child(17) {
    width: 80px;
    text-align: center;
}

/* Note */
th:nth-child(18), td:nth-child(18) {
    width: 160px;
    white-space: normal;
}

/* Stato */
th:nth-child(21), td:nth-child(21) {
    width: 60px;
    text-align: center;
}

/* =========================
   SELEZIONE EXCEL
   ========================= */

td.selected, th.selected {
    outline: 1px solid #000000;
    background: rgb(0, 0, 0);
}

/* Box selezione */
.selection-box {
    position: absolute;
    border: 2px dashed #000000;
    background-color: rgba(0, 0, 0, 0.18);
    pointer-events: none;
    z-index: 9999;
}

/* =========================
   FILTRI
   ========================= */
/* Filtri */
#filterBox {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: flex-start;
    margin: 5px 1px !important;
    padding-left: 0 !important;
    margin-left:1px !important;
}

#filterBox input {
    min-height: 38px;
    padding: 0.25rem 0.5rem;
    font-size: 0.875rem;
    flex: 1 1 250px;      /* input flessibili, più larghi */
    max-width: 100%;
}

#filterBox .choices {
    display: inline-flex;
    align-items: center;
    flex: 1 1 200px;
}

#filterBox .choices__inner {
    min-height: 38px;
    height: 38px;
    display: flex;
    align-items: center;
    padding: 0 8px;
    box-sizing: border-box;
}

/* Larghezze filtri */
#filterStato + .choices { width: 180px; }
#filterFase + .choices { width: 350px; }
#filterReparto + .choices { width: 300px; }

.choices__list--dropdown {
    max-height: 250px;
    overflow-y: auto;
}

#filterBox .choices__list--multiple {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

#filterBox .choices__item--selectable {
    width: 100%;
    box-sizing: border-box;
}

#filterBox .choices__list--multiple .choices__item:nth-child(n+10) {
    display: none;
}

/* =========================
   PERFORMANCE
   ========================= */

table * {
    user-select: none;
}

td[contenteditable] {
    user-select: text;
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

    <!-- MODALE AGGIUNGI OPERATORE -->
<div class="modal fade" id="aggiungiOperatoreModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('owner.aggiungiOperatore') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Aggiungi Operatore</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Nome</label>
                        <input type="text" name="nome" id="nome" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Cognome</label>
                        <input type="text" name="cognome" id="cognome" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Ruolo</label>
                        <select name="ruolo" class="form-select" required>
                            <option value="operatore">Operatore</option>
                            <option value="owner">Owner</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Codice operatore</label>
                        <input type="text" id="codice_operatore" class="form-control" 
                               value="{{ $prossimoCodice }}" 
                               data-numero="{{ $prossimoNumero }}" readonly>
                        <small class="text-muted">Il codice sarà confermato alla creazione</small>
                    </div>
                    <div class="mb-3">
                        <label>Reparto Principale</label>
                        <select name="reparto_principale" class="form-select" required>
                            @foreach($reparti as $id => $rep)
                                <option value="{{ $id }}">{{ $rep }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Reparto Secondario (facoltativo)</label>
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

<!-- JS LIVE CODICE -->
<!-- JS LIVE CODICE OPERATORE -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('aggiungiOperatoreModal');

    modal.addEventListener('shown.bs.modal', () => {
        const nomeInput = modal.querySelector('#nome');
        const cognomeInput = modal.querySelector('#cognome');
        const codiceInput = modal.querySelector('#codice_operatore');
        const numero = codiceInput.dataset.numero || 1;

        function aggiornaCodice() {
            const nome = nomeInput.value.trim();
            const cognome = cognomeInput.value.trim();

            const inizialeNome = nome ? nome[0].toUpperCase() : '_';
            const inizialeCognome = cognome ? cognome[0].toUpperCase() : '_';

            codiceInput.value = inizialeNome + inizialeCognome + String(numero).padStart(3, '0');

            // DEBUG: puoi rimuovere
            console.log("Codice aggiornato:", codiceInput.value);
        }

        // Aggiornamento live
        nomeInput.addEventListener('input', aggiornaCodice);
        cognomeInput.addEventListener('input', aggiornaCodice);

        // Inizializza subito quando si apre il modal
        aggiornaCodice();
    });
});
</script>
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
    <div>
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
                            {{ $op->nome }}<br>
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
    valore = valore.trim();

    // Se il campo è numerico o priorità, sostituisci la virgola con punto
    const campiNumerici = ['qta_richiesta','qta_prod','priorita','qta_carta','ore'];
    if(campiNumerici.includes(campo)){
        valore = valore.replace(',', '.');
        if(isNaN(parseFloat(valore))){
            alert('Valore numerico non valido');
            return;
        }
    }

    fetch('{{ route("owner.aggiornaCampo") }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json'},
        body: JSON.stringify({ fase_id: faseId, campo: campo, valore: valore })
    })
    .then(r => r.json())
    .then(d => {
        if (!d.success) {
            alert('Errore salvataggio: ' + (d.messaggio || ''));
        }
    })
    .catch(err => {
        console.error(err);
        alert('Errore di connessione');
    });
}
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const table = document.querySelector('table');
    const allCells = Array.from(table.querySelectorAll('td, th'));
    const selectedCells = new Set();

    let isSelecting = false;
    let startX = 0, startY = 0;
    let currentX = 0, currentY = 0;
    let selectionBox = null;
    let rafPending = false;

    function boxesIntersect(a, b) {
        return !(
            a.right < b.left ||
            a.left > b.right ||
            a.bottom < b.top ||
            a.top > b.bottom
        );
    }

    function updateSelection() {
        rafPending = false;

        const x = Math.min(startX, currentX);
        const y = Math.min(startY, currentY);
        const w = Math.abs(currentX - startX);
        const h = Math.abs(currentY - startY);

        selectionBox.style.left = x + 'px';
        selectionBox.style.top = y + 'px';
        selectionBox.style.width = w + 'px';
        selectionBox.style.height = h + 'px';

        const boxRect = {
            left: x,
            top: y,
            right: x + w,
            bottom: y + h
        };

        selectedCells.forEach(c => c.classList.remove('selected'));
        selectedCells.clear();

        for (const cell of allCells) {
            const r = cell.getBoundingClientRect();
            const cellRect = {
                left: r.left + window.scrollX,
                top: r.top + window.scrollY,
                right: r.right + window.scrollX,
                bottom: r.bottom + window.scrollY
            };

            if (boxesIntersect(boxRect, cellRect)) {
                cell.classList.add('selected');
                selectedCells.add(cell);
            }
        }
    }

    table.addEventListener('mousedown', e => {
        if (e.button !== 0 || !e.target.matches('td, th')) return;

        isSelecting = true;
        startX = currentX = e.pageX;
        startY = currentY = e.pageY;

        selectedCells.forEach(c => c.classList.remove('selected'));
        selectedCells.clear();

        selectionBox = document.createElement('div');
        selectionBox.className = 'selection-box';
        document.body.appendChild(selectionBox);

        e.preventDefault();
    });

    document.addEventListener('mousemove', e => {
        if (!isSelecting) return;

        currentX = e.pageX;
        currentY = e.pageY;

        if (!rafPending) {
            rafPending = true;
            requestAnimationFrame(updateSelection);
        }
    });

    document.addEventListener('mouseup', () => {
        isSelecting = false;
        rafPending = false;
        if (selectionBox) {
            selectionBox.remove();
            selectionBox = null;
        }
    });

    // STAMPA SOLO LE CELLE NEL BOX
    document.getElementById('printButton').addEventListener('click', () => {
        if (selectedCells.size === 0) {
            alert('Seleziona un\'area!');
            return;
        }

        const rows = new Map();

        selectedCells.forEach(cell => {
            const row = cell.parentElement;
            if (!rows.has(row)) rows.set(row, []);
            const style = getComputedStyle(cell);
            rows.get(row).push({
                tag: cell.tagName,
                html: cell.innerHTML,
                bg: style.backgroundColor,
                color: style.color
            });
        });

        const win = window.open('', '', 'width=1200,height=800');
        win.document.write(`
            <html>
            <head>
                <title>Stampa</title>
                <style>
                    table { border-collapse: collapse; }
                    td, th { border:1px solid #ccc; padding:4px; }
                </style>
            </head>
            <body><table>
        `);

        rows.forEach(cells => {
            win.document.write('<tr>');
            cells.forEach(c => {
                win.document.write(
                    `<${c.tag} style="background:${c.bg};color:${c.color}">${c.html}</${c.tag}>`
                );
            });
            win.document.write('</tr>');
        });

        win.document.write('</table></body></html>');
        win.document.close();
        win.print();
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
