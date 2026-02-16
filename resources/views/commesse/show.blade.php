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

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Commessa {{ $ordine->commessa }}</h2>
    <a href="{{ route('operatore.dashboard') }}" class="btn btn-primary d-flex align-items-center">
        <img src="{{ asset('images/turn-left_15441589.png') }}" alt="Dashboard" style="width:20px; height:20px; margin-right:5px;">
        Dashboard
    </a>
</div>

@php
    $operatore = auth('operatore')->user();
    $repartiOperatore = $operatore?->reparti?->pluck('id')->toArray() ?? [];
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
        </div>
    </div>

    <!-- Fase selezionata (con pulsanti) -->
    @foreach($fasiGestibili as $fase)
        @if($fase->id === $faseSelezionataId)
        <div class="card mb-3 border-primary">
            <div class="card-header bg-primary text-white">
                <strong>{{ $fase->faseCatalogo->nome ?? '-' }}</strong>
                <span class="badge bg-light text-dark ms-2">Stato: {{ $fase->stato }}</span>
            </div>
            <div class="card-body d-flex align-items-start gap-3">
                <div class="flex-grow-1">
                    <label for="note-fase-{{ $fase->id }}"><strong>Note:</strong></label>
                    <textarea id="note-fase-{{ $fase->id }}" class="form-control" rows="2"
                              onblur="aggiornaCampo({{ $fase->id }}, 'note', this.value)">{{ $fase->note ?? '' }}</textarea>
                    <div class="mt-2">
                        <label><strong>Qta prodotta:</strong></label>
                        <input type="text" class="form-control form-control-sm" style="width:120px"
                               value="{{ $fase->qta_prod ?? '' }}"
                               onblur="aggiornaCampo({{ $fase->id }}, 'qta_prod', this.value)">
                    </div>
                </div>
                <div class="azioni-cerchi">
                    <input type="checkbox" id="avvia-{{ $fase->id }}" onchange="aggiornaStato({{ $fase->id }}, 'avvia', this.checked)">
                    <label for="avvia-{{ $fase->id }}" class="badge-avvia">Avvia</label>

                    <input type="checkbox" id="pausa-{{ $fase->id }}" onchange="gestisciPausa({{ $fase->id }}, this.checked)">
                    <label for="pausa-{{ $fase->id }}" class="badge-pausa">Pausa</label>

                    <input type="checkbox" id="termina-{{ $fase->id }}" onchange="aggiornaStato({{ $fase->id }}, 'termina', this.checked)">
                    <label for="termina-{{ $fase->id }}" class="badge-termina">Termina</label>
                </div>
            </div>
        </div>
        @endif
    @endforeach

    <!-- Tabella altre fasi del tuo reparto (sola lettura) -->
    @php
        $altreFasiMieNonSelezionate = $fasiGestibili->filter(fn($f) => $f->id !== $faseSelezionataId);
    @endphp
    @if($altreFasiMieNonSelezionate->isNotEmpty())
    <h4>Altre tue fasi</h4>
    <table class="table table-sm table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Fase</th>
                <th>Stato</th>
                <th>Operatori</th>
                <th>Qta Prodotta</th>
                <th>Timeout</th>
            </tr>
        </thead>
        <tbody>
            @foreach($altreFasiMieNonSelezionate as $fase)
            <tr style="cursor:pointer" onclick="window.location='{{ route('commesse.show', $ordine->commessa) }}?fase={{ $fase->id }}'">
                <td><a href="{{ route('commesse.show', $ordine->commessa) }}?fase={{ $fase->id }}" style="color:#000; text-decoration:underline; font-weight:bold">{{ $fase->faseCatalogo->nome ?? '-' }}</a></td>
                <td id="stato-{{ $fase->id }}">{{ $fase->stato ?? '-' }}</td>
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
                <td>{{ $fase->stato ?? '-' }}</td>
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
            document.getElementById('stato-'+faseId).innerText = data.nuovo_stato;

            let opCell = document.getElementById('operatore-'+faseId);
            if(opCell){
                opCell.innerHTML = '';
                data.operatori.forEach(op=>{
                    opCell.innerHTML += `${op.nome} (${op.data_inizio})<br>`;
                });
            }

            ['avvia','pausa','termina'].forEach(a=>{
                let el = document.getElementById(a+'-'+faseId);
                if(el && a!==azione) el.checked=false;
            });

            if(azione==='termina'){
                let row = document.getElementById('fase-'+faseId);
                if(row) row.style.display='none';
            }
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
            document.getElementById('stato-'+faseId).innerText = motivo;
            let timeoutEl = document.getElementById('timeout-'+faseId);
            if(timeoutEl) timeoutEl.innerText = data.timeout;
            ['avvia','termina'].forEach(a=>{
                let el = document.getElementById(a+'-'+faseId);
                if(el) el.checked=false;
            });
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
