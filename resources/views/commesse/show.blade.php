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
</style>

@php
    $operatore = auth('operatore')->user();
    $repartiOperatore = $operatore?->reparti?->pluck('id')->toArray() ?? [];
    $isSpedizione = $operatore?->reparti?->pluck('nome')->map(fn($n) => strtolower($n))->contains('spedizione');
@endphp

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Commessa {{ $ordine->commessa }}</h2>
    <a href="{{ $isSpedizione ? route('spedizione.dashboard') : route('operatore.dashboard') }}" class="btn btn-primary d-flex align-items-center">
        <img src="{{ asset('images/turn-left_15441589.png') }}" alt="Dashboard" style="width:20px; height:20px; margin-right:5px;">
        Dashboard
    </a>
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
            <p><strong>Cliente:</strong> {{ $ordine->cliente_nome }}</p>
            <p><strong>Descrizione:</strong> {{ $ordine->descrizione }}</p>
            <p><strong>Quantita totale:</strong> {{ $ordine->qta_richiesta }} {{ $ordine->um }}</p>
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

    <!-- Tutte le fasi gestibili dall'operatore -->
    @foreach($fasiGestibili as $fase)
        @php
            $isSelezionata = ($fase->id === $faseSelezionataId);
            $badgeBg = [0=>'bg-secondary',1=>'bg-info',2=>'bg-warning text-dark',3=>'bg-success'];
        @endphp
        <div class="card mb-3 {{ $isSelezionata ? 'border-primary' : '' }}" id="card-fase-{{ $fase->id }}">
            <div class="card-header {{ $isSelezionata ? 'bg-primary text-white' : 'bg-light' }}">
                <strong>{{ $fase->faseCatalogo->nome ?? '-' }}</strong>
                <span class="badge {{ $badgeBg[$fase->stato] ?? 'bg-dark' }} ms-2 fs-5" id="badge-fase-{{ $fase->id }}">{{ $fase->stato }}</span>
                <span class="ms-2" id="operatori-fase-{{ $fase->id }}">
                    @foreach($fase->operatori as $op)
                        <small class="badge bg-light text-dark">{{ $op->nome }} ({{ $op->pivot->data_inizio ? \Carbon\Carbon::parse($op->pivot->data_inizio)->format('d/m/Y H:i:s') : '-' }})</small>
                    @endforeach
                </span>
            </div>
            <div class="card-body d-flex align-items-start gap-3">
                <div class="flex-grow-1">
                    <small class="text-muted d-block mb-2">{{ $fase->ordine_descrizione ?? $fase->ordine->descrizione ?? '-' }}</small>
                    <label for="note-fase-{{ $fase->id }}"><strong>Note Operatore:</strong></label>
                    <textarea id="note-fase-{{ $fase->id }}" class="form-control" rows="2"
                              onblur="aggiornaCampo({{ $fase->id }}, 'note', this.value)">{{ $fase->note ?? '' }}</textarea>
                    <div class="mt-2">
                        <label><strong>Qta prodotta:</strong></label>
                        <input type="text" class="form-control form-control-sm" style="width:120px"
                               value="{{ $fase->qta_prod ?? '' }}"
                               onblur="aggiornaCampo({{ $fase->id }}, 'qta_prod', this.value)">
                    </div>
                </div>
                <div class="azioni-cerchi" id="azioni-fase-{{ $fase->id }}">
                    @if(is_numeric($fase->stato) && $fase->stato < 2)
                        <input type="checkbox" id="avvia-{{ $fase->id }}" onchange="aggiornaStato({{ $fase->id }}, 'avvia', this.checked)">
                        <label for="avvia-{{ $fase->id }}" class="badge-avvia">Avvia</label>
                    @elseif($fase->stato == 2)
                        <input type="checkbox" id="pausa-{{ $fase->id }}" onchange="gestisciPausa({{ $fase->id }}, this.checked)">
                        <label for="pausa-{{ $fase->id }}" class="badge-pausa">Pausa</label>
                        <input type="checkbox" id="termina-{{ $fase->id }}" onchange="aggiornaStato({{ $fase->id }}, 'termina', this.checked)">
                        <label for="termina-{{ $fase->id }}" class="badge-termina">Termina</label>
                    @elseif(!is_numeric($fase->stato))
                        <input type="checkbox" id="riprendi-{{ $fase->id }}" onchange="riprendiFase({{ $fase->id }}, this.checked)">
                        <label for="riprendi-{{ $fase->id }}" class="badge-avvia">Riprendi</label>
                        <input type="checkbox" id="termina-{{ $fase->id }}" onchange="aggiornaStato({{ $fase->id }}, 'termina', this.checked)">
                        <label for="termina-{{ $fase->id }}" class="badge-termina">Termina</label>
                    @elseif($fase->stato == 3)
                        <span class="text-success fw-bold">Terminata</span>
                    @endif
                </div>
            </div>
        </div>
    @endforeach

    <!-- Fasi di altri reparti (sola lettura) -->
    @php
        $altreFasi = $ordine->fasi->filter(function($f) use ($repartiOperatore) {
            return !in_array($f->faseCatalogo->reparto_id ?? null, $repartiOperatore);
        });
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
                <td>{{ $fase->faseCatalogo->nome ?? '-' }}</td>
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

<script>
const motiviPausa = ["Attesa materiale", "Problema macchina", "Pranzo", "Altro"];
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

    if (nuovoStato == 2) {
        container.innerHTML =
            '<input type="checkbox" id="pausa-'+faseId+'" onchange="gestisciPausa('+faseId+', this.checked)">' +
            '<label for="pausa-'+faseId+'" class="badge-pausa">Pausa</label>' +
            '<input type="checkbox" id="termina-'+faseId+'" onchange="aggiornaStato('+faseId+', \'termina\', this.checked)">' +
            '<label for="termina-'+faseId+'" class="badge-termina">Termina</label>';
    } else if (nuovoStato == 3) {
        container.innerHTML = '<span class="text-success fw-bold">Terminata</span>';
    } else if (typeof nuovoStato === 'string' && isNaN(nuovoStato)) {
        // Stato pausa (stringa motivo)
        container.innerHTML =
            '<input type="checkbox" id="riprendi-'+faseId+'" onchange="riprendiFase('+faseId+', this.checked)">' +
            '<label for="riprendi-'+faseId+'" class="badge-avvia">Riprendi</label>' +
            '<input type="checkbox" id="termina-'+faseId+'" onchange="aggiornaStato('+faseId+', \'termina\', this.checked)">' +
            '<label for="termina-'+faseId+'" class="badge-termina">Termina</label>';
    }
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
        if(!confirm("Sei sicuro di voler terminare questa fase?")){
            document.getElementById('termina-'+faseId).checked = false;
            return;
        }
    }

    let route = azione==='avvia' ? '{{ route("produzione.avvia") }}' : '{{ route("produzione.termina") }}';

    fetch(route, {
        method:'POST',
        headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json'},
        body:JSON.stringify({fase_id:faseId})
    })
    .then(res=>res.json())
    .then(data=>{
        if(data.success){
            var nuovoStato = azione==='avvia' ? 2 : 3;
            updateBadge(faseId, nuovoStato);
            updateButtons(faseId, nuovoStato);
            if(data.operatori) updateOperatori(faseId, data.operatori);
        } else {
            alert('Errore: ' + (data.messaggio || 'operazione fallita'));
        }
    })
    .catch(err=>console.error('Errore:', err));
}

function gestisciPausa(faseId, checked){
    if(!checked) return;

    let scelta = prompt(
        "Seleziona motivo pausa:\n1) Attesa materiale\n2) Problema macchina\n3) Pranzo\n4) Altro"
    );
    if(!scelta || !["1","2","3","4"].includes(scelta)){
        document.getElementById('pausa-'+faseId).checked=false;
        return alert('Selezione non valida!');
    }

    let motivo = motiviPausa[parseInt(scelta)-1];

    fetch('{{ route("produzione.pausa") }}',{
        method:'POST',
        headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json'},
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
        headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json'},
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
        headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Content-Type':'application/json'},
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
