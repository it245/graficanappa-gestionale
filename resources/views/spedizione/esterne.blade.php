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
<div class="container-fluid px-3 py-3">
<style>
    .esterne-toolbar {
        display:flex; gap:12px; align-items:center; flex-wrap:wrap;
        margin-bottom:16px;
    }
    .search-box {
        max-width:480px; flex:1; font-size:15px; padding:10px 16px;
        border-radius:8px; border:1px solid #d1d5db; transition:all 0.15s;
    }
    .search-box:focus {
        outline:none; border-color:#2563eb;
        box-shadow:0 0 0 3px rgba(37,99,235,0.12);
    }
    .stat-pill {
        background:#f3f4f6; padding:6px 14px; border-radius:20px;
        font-size:13px; color:#374151; font-weight:500;
    }

    .commessa-card {
        background:#fff; border:1px solid #e5e7eb; border-radius:10px;
        margin-bottom:10px; overflow:hidden; transition:box-shadow 0.15s;
    }
    .commessa-card:hover { box-shadow:0 2px 8px rgba(0,0,0,0.06); }
    .commessa-card.expanded { box-shadow:0 4px 12px rgba(0,0,0,0.08); }

    .commessa-header {
        display:grid; grid-template-columns: 36px 120px 130px 1fr 200px 120px 110px;
        gap:14px; padding:14px 16px; align-items:center; cursor:pointer;
        background:#fafbfc; border-left:4px solid transparent;
    }
    .commessa-header:hover { background:#f3f4f6; }
    .commessa-header.percorso-base { border-left-color:#22c55e; }
    .commessa-header.percorso-rilievi { border-left-color:#eab308; }
    .commessa-header.percorso-caldo { border-left-color:#f96f2a; }
    .commessa-header.percorso-completo { border-left-color:#dc2626; }

    .chevron {
        width:24px; height:24px; transition:transform 0.2s;
        color:#6b7280;
    }
    .commessa-card.expanded .chevron { transform:rotate(90deg); }

    .commessa-codice { font-weight:700; color:#111827; font-size:14px; }
    .commessa-codice a { color:inherit; text-decoration:none; }
    .commessa-codice a:hover { color:#2563eb; text-decoration:underline; }

    .commessa-cliente { font-size:13px; color:#374151; font-weight:500; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
    .commessa-desc { font-size:12px; color:#6b7280; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

    .commessa-fasi-count {
        background:#e0f2fe; color:#0369a1; padding:4px 10px;
        border-radius:14px; font-size:12px; font-weight:600; text-align:center;
    }
    .commessa-data {
        font-size:12px; color:#374151; font-weight:500;
    }
    .commessa-data.urgente { color:#dc2626; font-weight:700; }

    .btn-rientro-tutte {
        background:#7c3aed; color:#fff; border:none; padding:8px 14px;
        border-radius:7px; font-size:13px; font-weight:600;
        cursor:pointer; transition:background 0.15s; white-space:nowrap;
    }
    .btn-rientro-tutte:hover { background:#6d28d9; }
    .btn-rientro-tutte:disabled { background:#d1d5db; cursor:not-allowed; }

    .commessa-body {
        display:none; padding:0; border-top:1px solid #e5e7eb;
        background:#fff;
    }
    .commessa-card.expanded .commessa-body { display:block; }

    .fase-table {
        width:100%; border-collapse:collapse; font-size:13px;
    }
    .fase-table th {
        background:#f9fafb; color:#6b7280; font-weight:600;
        font-size:11px; text-transform:uppercase; letter-spacing:0.04em;
        padding:8px 12px; text-align:left; border-bottom:1px solid #e5e7eb;
    }
    .fase-table td {
        padding:10px 12px; border-bottom:1px solid #f3f4f6;
        vertical-align:middle;
    }
    .fase-table tr:last-child td { border-bottom:none; }

    .badge-stato {
        display:inline-block; padding:3px 9px; border-radius:11px;
        font-size:11px; font-weight:600;
    }
    .badge-pronto { background:#dbeafe; color:#1e40af; }
    .badge-corso { background:#dcfce7; color:#166534; }
    .badge-ext { background:#ede9fe; color:#6d28d9; }
    .badge-ext-inviata { background:#d1fae5; color:#065f46; }
    .badge-pausa { background:#fef3c7; color:#92400e; }
    .badge-dafare { background:#f3f4f6; color:#374151; }

    .legenda {
        display:flex; gap:14px; flex-wrap:wrap; align-items:center;
        background:#fff; border:1px solid #e5e7eb; border-radius:8px;
        padding:10px 14px; margin-bottom:14px; font-size:12px;
    }
    .legenda-titolo { font-weight:600; color:#6b7280; font-size:11px; text-transform:uppercase; letter-spacing:0.04em; }
    .legenda-item { display:flex; align-items:center; gap:6px; color:#374151; }
    .legenda-bordo {
        width:14px; height:14px; border-radius:3px; border-left:4px solid;
        background:#fafbfc;
    }

    .btn-fase {
        background:#16a34a; color:#fff; border:none;
        padding:6px 12px; border-radius:6px; font-size:12px;
        font-weight:600; cursor:pointer; transition:background 0.15s;
    }
    .btn-fase:hover { background:#15803d; }
    .btn-fase.warn { background:#f59e0b; }
    .btn-fase.warn:hover { background:#d97706; }

    .nota-input {
        font-size:12px; padding:5px 8px; border:1px solid #d1d5db;
        border-radius:5px; min-width:160px; width:100%;
    }
    .nota-input:focus { outline:none; border-color:#2563eb; }

    .empty-state {
        text-align:center; padding:60px 20px; color:#9ca3af;
        background:#f9fafb; border-radius:10px;
    }

    @media (max-width:1024px) {
        .commessa-header {
            grid-template-columns: 30px 100px 1fr auto;
            gap:10px;
        }
        .commessa-header > .commessa-desc,
        .commessa-header > .commessa-data,
        .commessa-header > .commessa-fasi-count { display:none; }
    }
</style>

<div class="esterne-toolbar">
    <input type="text" id="searchBox" class="search-box" placeholder="Cerca commessa, cliente, descrizione...">
    <span class="stat-pill"><strong>{{ $gruppiEsterne->count() }}</strong> commesse</span>
    <span class="stat-pill"><strong>{{ $fasiEsterne->count() }}</strong> fasi totali</span>
</div>


@forelse($gruppiEsterne as $gruppo)
    @php
        $ordine = $gruppo->ordine;
        $rowClass = $ordine ? $ordine->getPercorsoClass() : '';
        $hasAttive = $gruppo->fasi->contains(fn($f) => in_array((string)$f->stato, ['2','5'], true) || (is_string($f->stato) && !is_numeric($f->stato)));
        $fasiAttiveIds = $gruppo->fasi
            ->filter(fn($f) => in_array((string)$f->stato, ['2','5'], true))
            ->pluck('id')->values()->toJson();
        $dataConsegna = $ordine && $ordine->data_prevista_consegna
            ? \Carbon\Carbon::parse($ordine->data_prevista_consegna)
            : null;
        $isUrgente = $dataConsegna && $dataConsegna->isPast();
    @endphp
    <div class="commessa-card searchable" data-search="{{ strtolower(($ordine->commessa ?? '') . ' ' . ($ordine->cliente_nome ?? '') . ' ' . ($ordine->descrizione ?? '') . ' ' . ($ordine->cod_art ?? '')) }}">
        <div class="commessa-header {{ $rowClass }}" onclick="toggleCard(this)">
            <svg class="chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
            <div>
                @if($hasAttive)
                    <button type="button" class="btn-rientro-tutte"
                            onclick="event.stopPropagation(); rientroTutteFasi({{ $fasiAttiveIds }}, '{{ $ordine->commessa ?? '' }}')">
                        Rientro tutte
                    </button>
                @endif
            </div>
            <div class="commessa-codice">
                <a href="{{ route('commesse.show', $ordine->commessa ?? '-') }}" onclick="event.stopPropagation()">{{ $ordine->commessa ?? '-' }}</a>
            </div>
            <div>
                <div class="commessa-cliente">{{ $ordine->cliente_nome ?? '-' }}</div>
                <div class="commessa-desc">{{ $ordine->descrizione ?? '-' }}</div>
            </div>
            <div class="commessa-desc">{{ $ordine->cod_art ?? '' }}</div>
            <div class="commessa-data {{ $isUrgente ? 'urgente' : '' }}">
                {{ $dataConsegna ? $dataConsegna->format('d/m/Y') : '-' }}
            </div>
            <div class="commessa-fasi-count">{{ $gruppo->fasi->count() }} {{ $gruppo->fasi->count() === 1 ? 'fase' : 'fasi' }}</div>
        </div>
        <div class="commessa-body">
            <table class="fase-table">
                <thead>
                    <tr>
                        <th>Fase</th>
                        <th>Stato</th>
                        <th>Note</th>
                        <th style="width:200px;">Azione</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($gruppo->fasi as $fase)
                        @php
                            $statoFase = $fase->stato;
                            $inPausa = is_string($statoFase) && !is_numeric($statoFase);
                        @endphp
                        <tr>
                            <td><strong>{{ $fase->faseCatalogo->nome_display ?? '-' }}</strong></td>
                            <td>
                                @if($statoFase == 0)
                                    <span class="badge-stato badge-dafare" title="Non iniziata">Da fare</span>
                                @elseif($statoFase == 1)
                                    <span class="badge-stato badge-pronto" title="Pronto — da inviare al fornitore">Pronto</span>
                                @elseif($statoFase == 5)
                                    @if($fase->ddt_fornitore_id)
                                        <span class="badge-stato badge-ext-inviata" title="DDT inviato al fornitore">EXT ✓</span>
                                    @else
                                        <span class="badge-stato badge-ext" title="Esterno — DDT da inviare">EXT</span>
                                    @endif
                                @elseif($statoFase == 2)
                                    <span class="badge-stato badge-corso" title="In corso — dal terzista">In corso</span>
                                @elseif($inPausa)
                                    <span class="badge-stato badge-pausa" title="In pausa">Pausa: {{ $statoFase }}</span>
                                @endif
                            </td>
                            <td>
                                <input type="text" class="nota-input"
                                       value="{{ $fase->note ?? '' }}"
                                       onblur="aggiornaNota({{ $fase->id }}, this.value)">
                            </td>
                            <td>
                                @if($statoFase == 0 || $statoFase == 1)
                                    <button class="btn-fase" onclick="esternoAvvia({{ $fase->id }}, this)">Avvia</button>
                                @elseif($statoFase == 2 || $statoFase == 5)
                                    <button class="btn-fase"
                                            data-qta-fase="{{ $fase->qta_fase ?? 0 }}"
                                            data-fogli-buoni="{{ $fase->fogli_buoni ?? 0 }}"
                                            data-fogli-scarto="{{ $fase->fogli_scarto ?? 0 }}"
                                            data-qta-prod="{{ $fase->qta_prod ?? 0 }}"
                                            onclick="esternoTermina({{ $fase->id }}, this)">Rientro</button>
                                @elseif($inPausa)
                                    <button class="btn-fase" onclick="esternoRiprendi({{ $fase->id }}, this)">Riprendi</button>
                                    <button class="btn-fase warn"
                                            data-qta-fase="{{ $fase->qta_fase ?? 0 }}"
                                            data-fogli-buoni="{{ $fase->fogli_buoni ?? 0 }}"
                                            data-fogli-scarto="{{ $fase->fogli_scarto ?? 0 }}"
                                            data-qta-prod="{{ $fase->qta_prod ?? 0 }}"
                                            onclick="esternoTermina({{ $fase->id }}, this)">Rientro</button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@empty
    <div class="empty-state">Nessuna lavorazione esterna in corso</div>
@endforelse

<!-- Modal Termina Fase (singola o batch) -->
<div class="modal fade" id="modalTerminaEsterno" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="modalTerminaTitle">Termina Fase Esterna</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="terminaEsternoFaseId">
                <input type="hidden" id="terminaEsternoFaseIdsJson">
                <input type="hidden" id="terminaEsternoIsBatch" value="0">

                <div id="batchInfo" class="alert alert-info mb-3" style="display:none;">
                    <strong id="batchCount"></strong> fasi attive saranno aggiornate insieme per la commessa <strong id="batchCommessa"></strong>.
                </div>

                <div id="stepTipoRientro">
                    <p class="fw-bold mb-3">La lavorazione esterna è completata?</p>
                    <button type="button" class="btn btn-success btn-lg w-100 mb-3" onclick="setTipoRientro('terminata')">
                        <strong>Terminata</strong><br>
                        <small>La lavorazione è completata</small>
                    </button>
                    <button type="button" class="btn btn-warning btn-lg w-100 text-dark mb-3" onclick="setTipoRientro('rientro')">
                        <strong>Rientrata - servono altre lavorazioni</strong><br>
                        <small>Il materiale è rientrato ma servono lavorazioni aggiuntive</small>
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

function toggleCard(headerEl) {
    var card = headerEl.parentElement;
    card.classList.toggle('expanded');
    var commessa = headerEl.querySelector('.commessa-codice a').textContent.trim();
    var expanded = JSON.parse(sessionStorage.getItem('esterneExpanded') || '[]');
    if (card.classList.contains('expanded')) {
        if (!expanded.includes(commessa)) expanded.push(commessa);
    } else {
        expanded = expanded.filter(c => c !== commessa);
    }
    sessionStorage.setItem('esterneExpanded', JSON.stringify(expanded));
}

(function restoreExpanded() {
    var expanded = JSON.parse(sessionStorage.getItem('esterneExpanded') || '[]');
    document.querySelectorAll('.commessa-card').forEach(function(card) {
        var link = card.querySelector('.commessa-codice a');
        if (link && expanded.includes(link.textContent.trim())) {
            card.classList.add('expanded');
        }
    });
})();

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

function esternoTermina(faseId, btn) {
    var qtaFase = btn.getAttribute('data-qta-fase') || 0;
    var fogliBuoni = parseInt(btn.getAttribute('data-fogli-buoni') || 0) || 0;
    var fogliScarto = parseInt(btn.getAttribute('data-fogli-scarto') || 0) || 0;
    var qtaProd = parseInt(btn.getAttribute('data-qta-prod') || 0) || 0;

    document.getElementById('terminaEsternoFaseId').value = faseId;
    document.getElementById('terminaEsternoIsBatch').value = '0';
    document.getElementById('batchInfo').style.display = 'none';
    document.getElementById('modalTerminaTitle').textContent = 'Termina Fase Esterna';
    document.getElementById('terminaEsternoQtaFase').value = qtaFase;

    var prefillQta = fogliBuoni > 0 ? fogliBuoni : (qtaProd > 0 ? qtaProd : '');
    document.getElementById('terminaEsternoQtaProdotta').value = prefillQta;
    document.getElementById('terminaEsternoScarti').value = fogliScarto > 0 ? fogliScarto : 0;
    document.getElementById('stepTipoRientro').style.display = '';

    new bootstrap.Modal(document.getElementById('modalTerminaEsterno')).show();
}

function rientroTutteFasi(faseIds, commessa) {
    if (!Array.isArray(faseIds) || faseIds.length === 0) {
        alert('Nessuna fase attiva da rientrare');
        return;
    }
    document.getElementById('terminaEsternoIsBatch').value = '1';
    document.getElementById('terminaEsternoFaseIdsJson').value = JSON.stringify(faseIds);
    document.getElementById('batchCount').textContent = faseIds.length;
    document.getElementById('batchCommessa').textContent = commessa;
    document.getElementById('batchInfo').style.display = '';
    document.getElementById('modalTerminaTitle').textContent = 'Rientro Tutte le Fasi - Commessa ' + commessa;
    document.getElementById('terminaEsternoQtaProdotta').value = 0;
    document.getElementById('terminaEsternoScarti').value = 0;
    document.getElementById('stepTipoRientro').style.display = '';

    new bootstrap.Modal(document.getElementById('modalTerminaEsterno')).show();
}

function setTipoRientro(tipo) {
    bootstrap.Modal.getInstance(document.getElementById('modalTerminaEsterno')).hide();

    var isBatch = document.getElementById('terminaEsternoIsBatch').value === '1';
    var qtaProdotta = parseInt(document.getElementById('terminaEsternoQtaProdotta').value) || 0;
    var scarti = parseInt(document.getElementById('terminaEsternoScarti').value) || 0;

    var ids = isBatch
        ? JSON.parse(document.getElementById('terminaEsternoFaseIdsJson').value || '[]')
        : [parseInt(document.getElementById('terminaEsternoFaseId').value)];

    if (ids.length === 0) { alert('Nessuna fase'); return; }

    var promises = ids.map(function(faseId) {
        var body = { fase_id: faseId, qta_prodotta: qtaProdotta, scarti: scarti };
        if (tipo === 'rientro' || tipo === 'nessuna') body.rientro = true;
        return fetch('{{ route("produzione.termina") }}', {
            method: 'POST', headers: getHdrs(),
            body: JSON.stringify(body)
        }).then(parseResponse);
    });

    Promise.all(promises)
        .then(function(results) {
            var failed = results.filter(r => !r.success);
            if (failed.length > 0) {
                alert('Errore su ' + failed.length + ' fasi su ' + results.length);
            }
            window.location.reload();
        })
        .catch(function(err) {
            if (err !== 'session_expired') { console.error('Errore:', err); alert('Errore: ' + err); }
        });
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

function filtraEsterne() {
    var query = document.getElementById('searchBox').value.toLowerCase().trim();
    sessionStorage.setItem('searchEsterne', query);
    document.querySelectorAll('.commessa-card.searchable').forEach(function(card) {
        var text = card.getAttribute('data-search') || '';
        card.style.display = (!query || text.includes(query)) ? '' : 'none';
    });
}
document.getElementById('searchBox').addEventListener('input', filtraEsterne);

(function() {
    var saved = sessionStorage.getItem('searchEsterne');
    if (saved) {
        document.getElementById('searchBox').value = saved;
        filtraEsterne();
    }
})();
</script>
@endsection
