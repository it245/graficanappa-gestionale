@extends('layouts.app')

@section('content')
<style>
.azioni-cerchi {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-left: 20px;
}
.azioni-cerchi label {
    display: inline-flex;
    justify-content: center;
    align-items: center;
    width: 75px;
    height: 75px;
    border-radius: 50%;
    color: #fff;
    font-weight: bold;
    font-size: 12px;
    cursor: pointer;
    user-select: none;
}
.badge-avvia { background-color: #28a745; }
.badge-pausa { background-color: #ffc107; }
.badge-termina { background-color: #dc3545; }
.azioni-cerchi input[type="checkbox"] { display: none; }
.azioni-cerchi input[type="checkbox"]:checked + label {
    opacity: 0.7;
    box-shadow: inset 0 0 2px rgba(0,0,0,0.5);
}

/* Lampeggio tasto Avvia quando stato = 2 */
@keyframes lampeggio-avvia {
    0%, 100% { opacity: 1; background-color: #28a745; }
    50% { opacity: 0.3; background-color: #ff6600; }
}
.badge-avvia.lampeggia {
    animation: lampeggio-avvia 1s ease-in-out infinite;
}
</style>

@php
    $operatore = request()->attributes->get('operatore') ?? auth('operatore')->user();
    $repartiOperatore = $operatore?->reparti?->pluck('id')->toArray() ?? [];
    $isSpedizione = $operatore?->reparti?->pluck('nome')->map(fn($n) => strtolower($n))->contains('spedizione');

    $ordineFasi = config('fasi_ordine');
    $getFaseOrdine = function($fase) use ($ordineFasi) {
        $nome = $fase->faseCatalogo->nome ?? '';
        return $ordineFasi[$nome] ?? $ordineFasi[strtolower($nome)] ?? 999;
    };
@endphp

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Commessa {{ $ordine->commessa }}</h2>
    <div class="d-flex gap-2">
        <a href="{{ route('operatore.etichetta', $ordine->id) }}" class="btn btn-outline-dark d-flex align-items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" class="me-1"><path d="M2 1a1 1 0 0 0-1 1v4.586a1 1 0 0 0 .293.707l7 7a1 1 0 0 0 1.414 0l4.586-4.586a1 1 0 0 0 0-1.414l-7-7A1 1 0 0 0 6.586 1H2zm4 3.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/></svg> Stampa Etichetta
        </a>
        <a href="{{ $isSpedizione ? route('spedizione.dashboard') : route('operatore.dashboard') }}" class="btn btn-primary d-flex align-items-center">
            <img src="{{ asset('images/turn-left_15441589.png') }}" alt="Dashboard" style="width:20px; height:20px; margin-right:5px;">
            Dashboard
        </a>
    </div>
</div>

@php
    $fasiGestibili = $ordine->fasi->filter(function($f) use ($repartiOperatore) {
        return in_array($f->faseCatalogo->reparto_id ?? null, $repartiOperatore);
    });
    $faseSelezionataId = (int) request('fase');
@endphp

<div class="container mt-3">
    <!-- Card info ordine -->
    <div class="card mb-3">
        <div class="card-body">
            @php
                $desc = $ordine->descrizione ?? '';
                $cliente = $ordine->cliente_nome ?? '';
                $coloriCalc = \App\Helpers\DescrizioneParser::parseColori($desc, $cliente);
                $fustellaCalc = \App\Helpers\DescrizioneParser::parseFustella($desc);
            @endphp
            <p><strong>Cliente:</strong> {{ $ordine->cliente_nome }}</p>
            <p><strong>Descrizione:</strong> {{ $ordine->descrizione }}</p>
            <p><strong>Quantita totale:</strong> {{ $ordine->qta_richiesta }} {{ $ordine->um }}</p>
            <p>
                <strong>Colori:</strong> {{ $coloriCalc }}
                @if($fustellaCalc)
                    &nbsp; <strong>Fustella:</strong> {{ $fustellaCalc }}
                @endif
            </p>
            <div class="row mt-2 g-2">
                <div class="col-md-4">
                    <div class="border rounded p-2 h-100" style="background:#f8f9fa">
                        <strong class="d-block mb-1">Note Prestampa</strong>
                        <span class="{{ $ordine->note_prestampa ? '' : 'text-muted' }}">{{ $ordine->note_prestampa ?: '-' }}</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-2 h-100" style="background:#f8f9fa">
                        <strong class="d-block mb-1">Operatore Prestampa</strong>
                        <span class="{{ $ordine->responsabile ? '' : 'text-muted' }}">{{ $ordine->responsabile ?: '-' }}</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-2 h-100" style="background:#f8f9fa">
                        <strong class="d-block mb-1">Commento Produzione</strong>
                        <span class="{{ $ordine->commento_produzione ? '' : 'text-muted' }}">{{ $ordine->commento_produzione ?: '-' }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fase selezionata (con pulsanti) + Anteprima affiancata -->
    @foreach($fasiGestibili as $fase)
        @if($fase->id === $faseSelezionataId)
        <div class="row mb-3">
            <div class="{{ !empty($preview) ? 'col-md-8' : 'col-12' }}">
                <div class="card border-primary h-100" id="card-fase-{{ $fase->id }}">
                    <div class="card-header bg-primary text-white">
                        <strong>{{ $fase->faseCatalogo->nome_display ?? '-' }}</strong>
                        @php $badgeBg = [0=>'bg-secondary',1=>'bg-info',2=>'bg-warning text-dark',3=>'bg-success']; @endphp
                        <span class="badge {{ $badgeBg[$fase->stato] ?? 'bg-dark' }} ms-2 fs-5" id="badge-fase-{{ $fase->id }}">{{ $fase->stato }}</span>
                        <span class="ms-2" id="operatori-fase-{{ $fase->id }}">
                            @foreach($fase->operatori as $op)
                                <small class="badge bg-light text-dark">{{ $op->nome }} ({{ $op->pivot->data_inizio ? \Carbon\Carbon::parse($op->pivot->data_inizio)->format('d/m/Y H:i:s') : '-' }})</small>
                            @endforeach
                        </span>
                    </div>
                    <div class="card-body border-bottom py-2">
                        <small class="text-muted">{{ $fase->ordine_descrizione ?? $fase->ordine->descrizione ?? '-' }}</small>
                    </div>
                    <div class="card-body d-flex align-items-start gap-3">
                        <div class="flex-grow-1">
                            <label for="note-fase-{{ $fase->id }}"><strong>Note Operatore:</strong></label>
                            <textarea id="note-fase-{{ $fase->id }}" class="form-control" rows="2"
                                      onblur="aggiornaCampo({{ $fase->id }}, 'note', this.value)">{{ $fase->note ?? '' }}</textarea>
                        </div>
                        <div class="azioni-cerchi" id="azioni-fase-{{ $fase->id }}">
                            {{-- Tutti e 3 i bottoni sempre visibili --}}
                            <input type="checkbox" id="avvia-{{ $fase->id }}" onchange="aggiornaStato({{ $fase->id }}, 'avvia', this.checked)">
                            <label for="avvia-{{ $fase->id }}" class="badge-avvia{{ $fase->stato == 2 ? ' lampeggia' : '' }}">{{ $fase->stato == 2 ? 'Avviato' : 'Avvia' }}</label>

                            <input type="checkbox" id="pausa-{{ $fase->id }}" onchange="gestisciPausa({{ $fase->id }}, this.checked)">
                            <label for="pausa-{{ $fase->id }}" class="badge-pausa">Pausa</label>

                            <input type="checkbox" id="termina-{{ $fase->id }}"
                                   data-qta-fase="{{ $ordine->qta_richiesta ?? 0 }}"
                                   data-fogli-buoni="{{ $fase->fogli_buoni ?? 0 }}"
                                   data-fogli-scarto="{{ $fase->fogli_scarto ?? 0 }}"
                                   data-qta-prod="{{ $fase->qta_prod ?? 0 }}"
                                   onchange="aggiornaStato({{ $fase->id }}, 'termina', this.checked)">
                            <label for="termina-{{ $fase->id }}" class="badge-termina">Termina</label>

                            @if(!is_numeric($fase->stato))
                                <input type="checkbox" id="riprendi-{{ $fase->id }}" onchange="riprendiFase({{ $fase->id }}, this.checked)">
                                <label for="riprendi-{{ $fase->id }}" class="badge-avvia">Riprendi</label>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @if(!empty($preview))
            <div class="col-md-4">
                <div class="card p-3 text-center shadow-sm h-100 d-flex align-items-center justify-content-center">
                    <div class="fw-bold mb-2" style="font-size:13px;">Anteprima foglio di stampa</div>
                    <img src="data:{{ $preview['mimeType'] }};base64,{{ $preview['data'] }}"
                         alt="Preview" style="max-width:100%; max-height:260px; border-radius:8px; cursor:pointer;"
                         onclick="window.open(this.src)">
                </div>
            </div>
            @endif
        </div>
        @endif
    @endforeach

    <!-- Altre fasi del tuo reparto (sola lettura) -->
    @php
        $altreFasiMieNonSelezionate = $fasiGestibili->filter(fn($f) => $f->id !== $faseSelezionataId)->sortBy($getFaseOrdine)->values();
    @endphp
    @if($altreFasiMieNonSelezionate->isNotEmpty())
    <h4>Altre fasi del reparto</h4>
    <table class="table table-sm table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Fase</th>
                <th>Descrizione</th>
                <th>Stato</th>
                <th>Operatori</th>
                <th>Qta Prodotta</th>
                <th>Timeout</th>
            </tr>
        </thead>
        <tbody>
            @foreach($altreFasiMieNonSelezionate as $fase)
            <tr style="cursor:pointer" onclick="window.location='{{ route('commesse.show', $ordine->commessa) }}?fase={{ $fase->id }}'">
                <td><a href="{{ route('commesse.show', $ordine->commessa) }}?fase={{ $fase->id }}" style="color:#000; text-decoration:underline; font-weight:bold">{{ $fase->faseCatalogo->nome_display ?? '-' }}</a></td>
                <td><small>{{ Str::limit($fase->ordine_descrizione ?? $fase->ordine->descrizione ?? '-', 60) }}</small></td>
                @php $sb = [0=>'#e9ecef',1=>'#cfe2ff',2=>'#fff3cd',3=>'#d1e7dd']; @endphp
                <td id="stato-{{ $fase->id }}" style="background:{{ $sb[$fase->stato] ?? '#e9ecef' }};font-weight:bold;text-align:center;">{{ $fase->stato }}</td>
                <td>
                    @foreach($fase->operatori as $op)
                        {{ $op->nome }} ({{ $op->pivot->data_inizio ? \Carbon\Carbon::parse($op->pivot->data_inizio)->format('d/m/Y H:i:s') : '-' }})<br>
                    @endforeach
                </td>
                <td>{{ $fase->qta_prod ?? '-' }}</td>
                <td>{{ $fase->timeout ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Fasi di altri reparti (sola lettura) -->
    @php
        $altreFasi = $ordine->fasi->filter(function($f) use ($repartiOperatore) {
            return !in_array($f->faseCatalogo->reparto_id ?? null, $repartiOperatore);
        })->sortBy($getFaseOrdine)->values();
    @endphp
    @if($altreFasi->count() > 0)
    <h4>Altre fasi</h4>
    <table class="table table-sm table-bordered">
        <thead class="table-secondary">
            <tr>
                <th>Fase</th>
                <th>Stato</th>
                <th>Operatori</th>
            </tr>
        </thead>
        <tbody>
            @foreach($altreFasi as $fase)
            <tr>
                <td>{{ $fase->faseCatalogo->nome_display ?? '-' }}</td>
                @php $sb2 = [0=>'#e9ecef',1=>'#cfe2ff',2=>'#fff3cd',3=>'#d1e7dd']; @endphp
                <td style="background:{{ $sb2[$fase->stato] ?? '#e9ecef' }};font-weight:bold;text-align:center;">{{ $fase->stato }}</td>
                <td>
                    @foreach($fase->operatori as $op)
                        {{ $op->nome }} ({{ $op->pivot->data_inizio ? \Carbon\Carbon::parse($op->pivot->data_inizio)->format('d/m/Y H:i:s') : '-' }})<br>
                    @endforeach
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <!-- Prossime commesse -->
    <h4 class="mt-4">Prossime commesse</h4>
    <ul class="list-group">
        @foreach($prossime as $c)
            <li class="list-group-item">
                <a href="{{ route('commesse.show', $c->commessa) }}">
                    {{ $c->commessa }} – {{ $c->cliente_nome }}
                </a>
            </li>
        @endforeach
    </ul>
</div>

<!-- Modal Termina Fase -->
<div class="modal fade" id="modalTermina" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Termina Fase</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="terminaFaseId">
                <div class="mb-3">
                    <label class="form-label fw-bold">Qta prodotta <span class="text-danger">*</span></label>
                    <input type="number" id="terminaQtaProdotta" class="form-control" min="0" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Scarti</label>
                    <input type="number" id="terminaScarti" class="form-control" min="0" value="0">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annulla</button>
                <button type="button" class="btn btn-danger fw-bold" onclick="confermaTermina()">Conferma e Termina</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pausa -->
<div class="modal fade" id="modalPausa" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title">Pausa Fase</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="pausaFaseId">
                <div class="mb-3">
                    <label class="form-label fw-bold">Motivo della pausa</label>
                    <select id="pausaMotivoSelect" class="form-select" onchange="toggleAltroPausa()">
                        <option value="">-- Seleziona --</option>
                        <option>Attesa materiale</option>
                        <option>Problema macchina</option>
                        <option>Pranzo</option>
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

<script>
const badgeBgMap = {0:'bg-secondary',1:'bg-info',2:'bg-warning text-dark',3:'bg-success'};

function updateBadge(faseId, stato) {
    const badge = document.getElementById('badge-fase-'+faseId);
    if (!badge) return;
    badge.className = 'badge ms-2 fs-5 ' + (badgeBgMap[stato] || 'bg-dark');
    badge.textContent = stato;
}

function updateButtons(faseId, nuovoStato) {
    const container = document.getElementById('azioni-fase-'+faseId);
    if (!container) return;

    // I 3 bottoni base sempre visibili
    var lampeggiaClass = (nuovoStato == 2) ? ' lampeggia' : '';
    let html =
        '<input type="checkbox" id="avvia-'+faseId+'" onchange="aggiornaStato('+faseId+', \'avvia\', this.checked)">' +
        '<label for="avvia-'+faseId+'" class="badge-avvia'+lampeggiaClass+'">'+(nuovoStato == 2 ? 'Avviato' : 'Avvia')+'</label>' +
        '<input type="checkbox" id="pausa-'+faseId+'" onchange="gestisciPausa('+faseId+', this.checked)">' +
        '<label for="pausa-'+faseId+'" class="badge-pausa">Pausa</label>' +
        '<input type="checkbox" id="termina-'+faseId+'" onchange="aggiornaStato('+faseId+', \'termina\', this.checked)">' +
        '<label for="termina-'+faseId+'" class="badge-termina">Termina</label>';

    // Aggiungi Riprendi se in pausa
    if (typeof nuovoStato === 'string' && isNaN(nuovoStato)) {
        html +=
            '<input type="checkbox" id="riprendi-'+faseId+'" onchange="riprendiFase('+faseId+', this.checked)">' +
            '<label for="riprendi-'+faseId+'" class="badge-avvia">Riprendi</label>';
    }

    container.innerHTML = html;
}

function updateOperatori(faseId, operatori) {
    const container = document.getElementById('operatori-fase-'+faseId);
    if (!container || !operatori) return;
    container.innerHTML = operatori.map(function(op) {
        return '<small class="badge bg-light text-dark">' + op.nome + ' (' + op.data_inizio + ')</small>';
    }).join(' ');
}

function aggiornaStato(faseId, azione, checked){
    if(!checked) return;
    if(azione === 'termina'){
        apriModalTermina(faseId);
        return;
    }

    let route = '{{ route("produzione.avvia") }}';

    fetch(route, {
        method:'POST',
        headers:{'X-CSRF-TOKEN':csrfToken(),'Content-Type':'application/json'},
        body:JSON.stringify({fase_id:faseId})
    })
    .then(res=>res.json())
    .then(data=>{
        if(data.success){
            updateBadge(faseId, 2);
            updateButtons(faseId, 2);
            if(data.operatori) updateOperatori(faseId, data.operatori);
        } else {
            alert('Errore: ' + (data.messaggio || 'operazione fallita'));
        }
    })
    .catch(err=>console.error('Errore:', err));
}

function apriModalTermina(faseId) {
    var cb = document.getElementById('termina-'+faseId);
    var qtaFase = cb ? cb.getAttribute('data-qta-fase') : 0;
    var fogliBuoni = parseInt(cb ? cb.getAttribute('data-fogli-buoni') : 0) || 0;
    var fogliScarto = parseInt(cb ? cb.getAttribute('data-fogli-scarto') : 0) || 0;
    var qtaProd = parseInt(cb ? cb.getAttribute('data-qta-prod') : 0) || 0;

    document.getElementById('terminaFaseId').value = faseId;

    // Pre-fill: fogli_buoni se > 0, altrimenti qta_prod se > 0, altrimenti vuoto
    var prefillQta = fogliBuoni > 0 ? fogliBuoni : (qtaProd > 0 ? qtaProd : '');
    document.getElementById('terminaQtaProdotta').value = prefillQta;

    // Pre-fill scarti da fogli_scarto se > 0
    document.getElementById('terminaScarti').value = fogliScarto > 0 ? fogliScarto : 0;

    new bootstrap.Modal(document.getElementById('modalTermina')).show();
}

function confermaTermina() {
    var faseId = document.getElementById('terminaFaseId').value;
    var qtaProdotta = document.getElementById('terminaQtaProdotta').value;
    var scarti = document.getElementById('terminaScarti').value;

    if (qtaProdotta === '' || parseInt(qtaProdotta) <= 0) {
        alert('Inserire la quantita prodotta (deve essere maggiore di 0)');
        return;
    }

    bootstrap.Modal.getInstance(document.getElementById('modalTermina')).hide();

    fetch('{{ route("produzione.termina") }}', {
        method:'POST',
        headers:{'X-CSRF-TOKEN':csrfToken(),'Content-Type':'application/json'},
        body:JSON.stringify({fase_id: faseId, qta_prodotta: parseInt(qtaProdotta), scarti: parseInt(scarti) || 0})
    })
    .then(res=>res.json())
    .then(data=>{
        if(data.success){
            updateBadge(faseId, 3);
            updateButtons(faseId, 3);
            updateOperatori(faseId, []);
        } else {
            alert('Errore: ' + (data.messaggio || 'operazione fallita'));
            document.getElementById('termina-'+faseId).checked = false;
        }
    })
    .catch(err=>{
        console.error('Errore:', err);
        document.getElementById('termina-'+faseId).checked = false;
    });
}

// Reset checkbox when modal is dismissed without confirming
document.getElementById('modalTermina').addEventListener('hidden.bs.modal', function() {
    var faseId = document.getElementById('terminaFaseId').value;
    var cb = document.getElementById('termina-'+faseId);
    if (cb) cb.checked = false;
});

function gestisciPausa(faseId, checked){
    if(!checked) return;
    document.getElementById('pausaFaseId').value = faseId;
    document.getElementById('pausaMotivoSelect').value = '';
    document.getElementById('pausaAltroInput').value = '';
    document.getElementById('pausaAltroWrap').style.display = 'none';
    new bootstrap.Modal(document.getElementById('modalPausa')).show();
}

document.getElementById('modalPausa').addEventListener('hidden.bs.modal', function() {
    var faseId = document.getElementById('pausaFaseId').value;
    var cb = document.getElementById('pausa-'+faseId);
    if (cb) cb.checked = false;
});

function toggleAltroPausa() {
    document.getElementById('pausaAltroWrap').style.display =
        document.getElementById('pausaMotivoSelect').value === '__altro__' ? '' : 'none';
}

function confermaPausa() {
    var sel = document.getElementById('pausaMotivoSelect').value;
    var motivo = sel === '__altro__' ? (document.getElementById('pausaAltroInput').value.trim() || 'Altro') : sel;
    if (!motivo) { alert('Seleziona un motivo'); return; }
    var faseId = document.getElementById('pausaFaseId').value;
    bootstrap.Modal.getInstance(document.getElementById('modalPausa')).hide();

    fetch('{{ route("produzione.pausa") }}',{
        method:'POST',
        headers:{'X-CSRF-TOKEN':csrfToken(),'Content-Type':'application/json'},
        body:JSON.stringify({fase_id:faseId, motivo:motivo})
    })
    .then(res=>res.json())
    .then(data=>{
        if(data.success){
            updateBadge(faseId, data.nuovo_stato);
            updateButtons(faseId, data.nuovo_stato);
        } else {
            alert('Errore: ' + (data.messaggio || 'operazione fallita'));
        }
    })
    .catch(err=>console.error('Errore:', err));
}

function riprendiFase(faseId, checked){
    if(!checked) return;

    fetch('{{ route("produzione.riprendi") }}',{
        method:'POST',
        headers:{'X-CSRF-TOKEN':csrfToken(),'Content-Type':'application/json'},
        body:JSON.stringify({fase_id:faseId})
    })
    .then(res=>res.json())
    .then(data=>{
        if(data.success){
            updateBadge(faseId, 2);
            updateButtons(faseId, 2);
        } else {
            alert('Errore: ' + (data.messaggio || 'operazione fallita'));
            document.getElementById('riprendi-'+faseId).checked = false;
        }
    })
    .catch(err=>console.error('Errore:', err));
}

function aggiornaCampo(faseId, campo, valore){
    fetch('{{ route("produzione.aggiornaCampo") }}',{
        method:'POST',
        headers:{'X-CSRF-TOKEN':csrfToken(),'Content-Type':'application/json'},
        body:JSON.stringify({fase_id:faseId, campo:campo, valore:valore})
    })
    .then(res=>res.json())
    .then(data=>{
        if(!data.success) alert('Errore durante il salvataggio: '+data.messaggio);
    })
    .catch(err=>console.error('Errore:', err));
}

</script>
@endsection
