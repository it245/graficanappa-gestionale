@extends('layouts.mes')

@section('topbar-title', 'Lavorazioni Esterne')

@section('sidebar-items')
<div class="mes-sidebar-section">
    <div class="mes-sidebar-section-label">Spedizione</div>
    <a href="{{ route('spedizione.dashboard') }}" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
        Dashboard Spedizione
    </a>
    <a href="{{ route('spedizione.esterne') }}" class="mes-sidebar-item active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
        Esterne
    </a>
    <a href="{{ route('magazzino.dashboard', ['op_token' => request('op_token')]) }}" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
        Magazzino Carta
    </a>
</div>
@endsection

@section('content')
<div class="container-fluid px-0">
<style>
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
    .percorso-base { background: #d4edda !important; }
    .percorso-rilievi { background: #fff3cd !important; }
    .percorso-caldo { background: #f96f2a !important; }
    .percorso-completo { background: #f8d7da !important; }
    a.commessa-link { color:#000; font-weight:bold; text-decoration:underline; }
    a.commessa-link:hover { color:#0d6efd; }
</style>

<h2 class="mb-2 mx-2" style="color:#17a2b8;">Lavorazioni Esterne ({{ $fasiEsterne->count() }})</h2>

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
                    $rowClass = $fase->ordine ? $fase->ordine->getPercorsoClass() : '';
                    $statoFase = $fase->stato;
                    $inPausa = is_string($statoFase) && !is_numeric($statoFase);
                @endphp
                <tr class="{{ $rowClass }} searchable">
                    <td style="white-space:nowrap;">
                        @if($statoFase == 0 || $statoFase == 1)
                            <button class="btn btn-sm btn-success fw-bold" onclick="esternoAvvia({{ $fase->id }}, this)">Avvia</button>
                        @elseif($statoFase == 2)
                            <button class="btn btn-sm btn-success fw-bold"
                                    data-qta-fase="{{ $fase->qta_fase ?? 0 }}"
                                    data-fogli-buoni="{{ $fase->fogli_buoni ?? 0 }}"
                                    data-fogli-scarto="{{ $fase->fogli_scarto ?? 0 }}"
                                    data-qta-prod="{{ $fase->qta_prod ?? 0 }}"
                                    onclick="esternoTermina({{ $fase->id }}, this)">Rientro</button>
                        @elseif($inPausa)
                            <button class="btn btn-sm btn-success fw-bold" onclick="esternoRiprendi({{ $fase->id }}, this)">Riprendi</button>
                            <button class="btn btn-sm btn-success fw-bold"
                                    data-qta-fase="{{ $fase->qta_fase ?? 0 }}"
                                    data-fogli-buoni="{{ $fase->fogli_buoni ?? 0 }}"
                                    data-fogli-scarto="{{ $fase->fogli_scarto ?? 0 }}"
                                    data-qta-prod="{{ $fase->qta_prod ?? 0 }}"
                                    onclick="esternoTermina({{ $fase->id }}, this)">Rientro</button>
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
                    <td>{{ $fase->faseCatalogo->nome_display ?? '-' }}</td>
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

<!-- Modal Termina Fase -->
<div class="modal fade" id="modalTerminaEsterno" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Termina Fase Esterna</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="terminaEsternoFaseId">

                <!-- Step 1: Tipo rientro -->
                <div id="stepTipoRientro">
                    <p class="fw-bold mb-3">La lavorazione esterna e completata?</p>
                    <button type="button" class="btn btn-success btn-lg w-100 mb-3" onclick="setTipoRientro('terminata')">
                        <strong>Terminata</strong><br>
                        <small>La lavorazione e completata</small>
                    </button>
                    <button type="button" class="btn btn-warning btn-lg w-100 text-dark mb-3" onclick="setTipoRientro('rientro')">
                        <strong>Rientrata - servono altre lavorazioni</strong><br>
                        <small>Il materiale e rientrato ma servono lavorazioni aggiuntive</small>
                    </button>
                    <button type="button" class="btn btn-secondary btn-lg w-100" onclick="setTipoRientro('nessuna')">
                        <strong>Rientrata senza lavorazione</strong><br>
                        <small>Nessuna lavorazione effettuata, torna in attesa</small>
                    </button>
                </div>

                <input type="hidden" id="terminaEsternoQtaFase">
                <input type="hidden" id="terminaEsternoQtaProdotta">
                <input type="hidden" id="terminaEsternoScarti" value="0">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pausa -->
<div class="modal fade" id="modalPausaEsterno" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">Pausa Fase Esterna</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="pausaEsternoFaseId">
                <div class="mb-3">
                    <label class="form-label fw-bold">Motivo della pausa</label>
                    <select id="pausaMotivoSelect" class="form-select" onchange="toggleAltroPausa()">
                        <option value="">-- Seleziona --</option>
                        <option>Attesa materiale</option>
                        <option>Problema macchina</option>
                        <option>Pranzo</option>
                        <option>Fine turno</option>
                        <option value="__altro__">Altro...</option>
                    </select>
                </div>
                <div class="mb-3" id="pausaAltroWrap" style="display:none;">
                    <label class="form-label fw-bold">Specifica motivo</label>
                    <input type="text" id="pausaAltroInput" class="form-control" placeholder="Scrivi il motivo...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-warning fw-bold" onclick="confermaPausa()">Conferma Pausa</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal selezione terzista -->
<div class="modal fade" id="modalTerzista" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">Seleziona fornitore (terzista)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="terzistaFaseId">
                <div class="mb-3">
                    <label class="form-label fw-bold">Fornitore</label>
                    <select id="terzistaSelect" class="form-select" onchange="toggleAltroTerzista()">
                        <option value="">-- Seleziona --</option>
                        <option>4GRAPH S.R.L.</option>
                        <option>CARD S.R.L.</option>
                        <option>CLEVEX S.R.L.</option>
                        <option>KRESIA SRL</option>
                        <option>LASER LINE FUSTELLE S.R.L.</option>
                        <option>LEGATORIA SALVATORE TONTI SRL</option>
                        <option>LEGOKART S.A.S.</option>
                        <option>LEGRAF S.R.L.</option>
                        <option>LP FUSTELLE S.R.L.</option>
                        <option>PACKINGRAF SRL</option>
                        <option>POLYEDRA S.P.A.</option>
                        <option>SAE SRL</option>
                        <option>SOL GROUP SRL</option>
                        <option>SOLUZIONI IMBALLAGGI SRL</option>
                        <option>TECNOCART S.R.L.</option>
                        <option>TIPOGRAFIA BIANCO S.R.L.</option>
                        <option>TIPOGRAFIA EFFEGI SRL</option>
                        <option>TIPOLITOGRAFIA NEO PRINT SERVICE</option>
                        <option value="__altro__">Altro...</option>
                    </select>
                </div>
                <div class="mb-3" id="terzistaAltroWrap" style="display:none;">
                    <label class="form-label fw-bold">Nome fornitore</label>
                    <input type="text" id="terzistaAltroInput" class="form-control" placeholder="Scrivi il nome del fornitore...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-success fw-bold" onclick="confermaTerzista()">Conferma e Avvia</button>
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

function aggiornaNota(faseId, valore) {
    fetch('{{ route("produzione.aggiornaCampo") }}', {
        method: 'POST', headers: getHdrs(),
        body: JSON.stringify({ fase_id: faseId, campo: 'note', valore: valore })
    })
    .then(parseResponse)
    .then(data => { if (!data.success) alert('Errore salvataggio nota'); })
    .catch(err => { if (err !== 'session_expired') console.error('Errore:', err); });
}

function esternoAvvia(faseId, btn) {
    document.getElementById('terzistaFaseId').value = faseId;
    document.getElementById('terzistaSelect').value = '';
    document.getElementById('terzistaAltroInput').value = '';
    document.getElementById('terzistaAltroWrap').style.display = 'none';
    new bootstrap.Modal(document.getElementById('modalTerzista')).show();
}

function toggleAltroTerzista() {
    var wrap = document.getElementById('terzistaAltroWrap');
    wrap.style.display = document.getElementById('terzistaSelect').value === '__altro__' ? '' : 'none';
}

function confermaTerzista() {
    var sel = document.getElementById('terzistaSelect').value;
    var terzista = sel === '__altro__' ? document.getElementById('terzistaAltroInput').value.trim() : sel;
    if (!terzista) { alert('Seleziona un fornitore'); return; }
    var faseId = document.getElementById('terzistaFaseId').value;
    bootstrap.Modal.getInstance(document.getElementById('modalTerzista')).hide();
    fetch('{{ route("produzione.avvia") }}', {
        method: 'POST', headers: getHdrs(),
        body: JSON.stringify({ fase_id: faseId, terzista: terzista })
    })
    .then(parseResponse)
    .then(data => {
        if (data.success) { window.location.reload(); }
        else { alert('Errore: ' + (data.messaggio || data.message || 'operazione fallita')); }
    })
    .catch(err => { if (err !== 'session_expired') { console.error('Errore:', err); alert('Errore: ' + err); } });
}

function esternoPausa(faseId, btn) {
    document.getElementById('pausaEsternoFaseId').value = faseId;
    document.getElementById('pausaMotivoSelect').value = '';
    document.getElementById('pausaAltroInput').value = '';
    document.getElementById('pausaAltroWrap').style.display = 'none';
    new bootstrap.Modal(document.getElementById('modalPausaEsterno')).show();
}

function toggleAltroPausa() {
    document.getElementById('pausaAltroWrap').style.display =
        document.getElementById('pausaMotivoSelect').value === '__altro__' ? '' : 'none';
}

function confermaPausa() {
    var sel = document.getElementById('pausaMotivoSelect').value;
    var motivo = sel === '__altro__' ? (document.getElementById('pausaAltroInput').value.trim() || 'Altro') : sel;
    if (!motivo) { alert('Seleziona un motivo'); return; }
    var faseId = document.getElementById('pausaEsternoFaseId').value;
    bootstrap.Modal.getInstance(document.getElementById('modalPausaEsterno')).hide();
    fetch('{{ route("produzione.pausa") }}', {
        method: 'POST', headers: getHdrs(),
        body: JSON.stringify({ fase_id: faseId, motivo: motivo })
    })
    .then(parseResponse)
    .then(data => {
        if (data.success) { window.location.reload(); }
        else { alert('Errore: ' + (data.messaggio || data.message || 'operazione fallita')); }
    })
    .catch(err => { if (err !== 'session_expired') { console.error('Errore:', err); alert('Errore: ' + err); } });
}

let tipoRientroSelezionato = 'terminata';

function esternoTermina(faseId, btn) {
    var qtaFase = btn.getAttribute('data-qta-fase') || 0;
    var fogliBuoni = parseInt(btn.getAttribute('data-fogli-buoni') || 0) || 0;
    var fogliScarto = parseInt(btn.getAttribute('data-fogli-scarto') || 0) || 0;
    var qtaProd = parseInt(btn.getAttribute('data-qta-prod') || 0) || 0;

    document.getElementById('terminaEsternoFaseId').value = faseId;
    document.getElementById('terminaEsternoQtaFase').value = qtaFase;

    var prefillQta = fogliBuoni > 0 ? fogliBuoni : (qtaProd > 0 ? qtaProd : '');
    document.getElementById('terminaEsternoQtaProdotta').value = prefillQta;
    document.getElementById('terminaEsternoScarti').value = fogliScarto > 0 ? fogliScarto : 0;

    document.getElementById('stepTipoRientro').style.display = '';
    tipoRientroSelezionato = 'terminata';

    new bootstrap.Modal(document.getElementById('modalTerminaEsterno')).show();
}

function setTipoRientro(tipo) {
    tipoRientroSelezionato = tipo;
    bootstrap.Modal.getInstance(document.getElementById('modalTerminaEsterno')).hide();

    var faseId = document.getElementById('terminaEsternoFaseId').value;
    var qtaProdotta = document.getElementById('terminaEsternoQtaProdotta').value || 0;
    var scarti = document.getElementById('terminaEsternoScarti').value || 0;

    var body = {
        fase_id: faseId,
        qta_prodotta: parseInt(qtaProdotta),
        scarti: parseInt(scarti) || 0
    };

    if (tipo === 'rientro' || tipo === 'nessuna') {
        body.rientro = true;
    }

    fetch('{{ route("produzione.termina") }}', {
        method: 'POST', headers: getHdrs(),
        body: JSON.stringify(body)
    })
    .then(parseResponse)
    .then(data => {
        if (data.success) { window.location.reload(); }
        else { alert('Errore: ' + (data.messaggio || data.message || 'operazione fallita')); }
    })
    .catch(err => { if (err !== 'session_expired') { console.error('Errore:', err); alert('Errore: ' + err); } });
}

function esternoRiprendi(faseId, btn) {
    btn.disabled = true;
    fetch('{{ route("produzione.riprendi") }}', {
        method: 'POST', headers: getHdrs(),
        body: JSON.stringify({ fase_id: faseId })
    })
    .then(parseResponse)
    .then(data => {
        if (data.success) { window.location.reload(); }
        else { alert('Errore: ' + (data.messaggio || data.message || 'operazione fallita')); btn.disabled = false; }
    })
    .catch(err => { if (err !== 'session_expired') { console.error('Errore:', err); alert('Errore: ' + err); } btn.disabled = false; });
}

// Ricerca (include note negli input + persistenza)
function filtraEsterne() {
    var query = document.getElementById('searchBox').value.toLowerCase().trim();
    sessionStorage.setItem('searchEsterne', query);
    document.querySelectorAll('tr.searchable').forEach(function(row) {
        var text = row.innerText.toLowerCase();
        // Includi anche il contenuto degli input (note)
        row.querySelectorAll('input').forEach(function(inp) {
            text += ' ' + (inp.value || '').toLowerCase();
        });
        row.style.display = (!query || text.includes(query)) ? '' : 'none';
    });
}
document.getElementById('searchBox').addEventListener('input', filtraEsterne);

// Ripristina ricerca dopo refresh
(function() {
    var saved = sessionStorage.getItem('searchEsterne');
    if (saved) {
        document.getElementById('searchBox').value = saved;
        filtraEsterne();
    }
})();

</script>
@endsection
