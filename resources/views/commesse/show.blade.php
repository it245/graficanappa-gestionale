@extends('layouts.app')

@section('content')
<style>
/* Cerchi azioni verticali accanto alla card */
.azioni-cerchi {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-left: 20px;
}

/* Cerchi */
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

/* Colori */
.badge-avvia { background-color: #28a745; }  
.badge-pausa { background-color: #ffc107; }  
.badge-termina { background-color: #dc3545; }

/* Nasconde checkbox reale */
.azioni-cerchi input[type="checkbox"] { display: none; }

/* Quando selezionata */
.azioni-cerchi input[type="checkbox"]:checked + label {
    opacity: 0.7;
    box-shadow: inset 0 0 2px rgba(0,0,0,0.5);
}
</style>
<!-- Header con pulsante dashboard -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Commessa {{ $ordine->commessa }}</h2>
        <a href="{{ route('operatore.dashboard') }}" class="btn btn-primary d-flex align-items-center">
            <img src="{{ asset('images/turn-left_15441589.png') }}" alt="Dashboard" style="width:20px; height:20px; margin-right:5px;">
            Dashboard
        </a>
    </div>

<div class="container mt-3 d-flex align-items-start">
    <!-- Card info ordine ridotta -->
    <div class="card mb-3 flex-grow-1">
        <div class="card-body">
            <p><strong>Cliente:</strong> {{ $ordine->cliente_nome }}</p>
            <p><strong>Descrizione:</strong> {{ $ordine->descrizione }}</p>
            <p><strong>Quantità totale:</strong> {{ $ordine->qta_richiesta }} {{ $ordine->um }}</p>

            <!-- NOTE FASI SOTTO QUANTITÀ TOTALE -->
            @php
                $operatore = auth('operatore')->user();
                $repartiOperatore = $operatore?->reparti?->pluck('id')->toArray() ?? [];
                $fasiGestibili = $ordine->fasi->filter(function($f) use ($repartiOperatore) {
                    return in_array($f->faseCatalogo->reparto_id ?? null, $repartiOperatore);
                });
            @endphp

            @foreach($fasiGestibili as $fase)
            <div class="mb-2">
                <label for="note-fase-{{ $fase->id }}"><strong>Note:</strong></label>
                <textarea id="note-fase-{{ $fase->id }}" class="form-control" rows="2"
                          onblur="aggiornaCampo({{ $fase->id }}, 'note', this.value)">{{ $fase->note ?? '' }}</textarea>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Cerchi azioni verticali accanto alla card -->
    <div class="azioni-cerchi">
        @foreach($fasiGestibili as $fase)
            <input type="checkbox" id="avvia-{{ $fase->id }}" onchange="aggiornaStato({{ $fase->id }}, 'avvia', this.checked)">
            <label for="avvia-{{ $fase->id }}" class="badge-avvia" title="Avvia">Avvia</label>

            <input type="checkbox" id="pausa-{{ $fase->id }}" onchange="gestisciPausa({{ $fase->id }}, this.checked)">
            <label for="pausa-{{ $fase->id }}" class="badge-pausa" title="Pausa">Pausa</label>

            <input type="checkbox" id="termina-{{ $fase->id }}" onchange="aggiornaStato({{ $fase->id }}, 'termina', this.checked)">
            <label for="termina-{{ $fase->id }}" class="badge-termina" title="Termina">Termina</label>
        @endforeach
    </div>
</div>

<!-- Tabella "Le tue fasi" senza colonne azioni -->
<h4>Le tue fasi</h4>
<table class="table table-sm table-bordered">
    <thead class="table-dark">
        <tr>
            <th>Fase</th>
            <th>Stato</th>
            <th>Operatori</th>
            <th>Quantità prodotta</th>
            <th>Timeout</th>
        </tr>
    </thead>
    <tbody>
        @foreach($fasiGestibili as $fase)
        <tr id="fase-{{ $fase->id }}">
            <td>{{ $fase->faseCatalogo->nome ?? '-' }}</td>
            <td id="stato-{{ $fase->id }}">{{ $fase->stato ?? '-' }}</td>
            <td id="operatore-{{ $fase->id }}">
                @foreach($fase->operatori as $op)
                    {{ $op->nome }} ({{ $op->pivot->data_inizio ? \Carbon\Carbon::parse($op->pivot->data_inizio)->format('d/m/Y H:i:s') : '-' }})<br>
                @endforeach
            </td>
            <td>
                <input type="text" class="form-control form-control-sm"
                       value="{{ $fase->qta_prod ?? '' }}"
                       onblur="aggiornaCampo({{ $fase->id }}, 'qta_prod', this.value)">
            </td>
            <td id="timeout-{{ $fase->id }}">{{ $fase->timeout ?? '-' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<!-- Fasi non gestibili -->
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
            opCell.innerHTML = '';
            data.operatori.forEach(op=>{
                opCell.innerHTML += `${op.nome} (${op.data_inizio})<br>`;
            });

            ['avvia','pausa','termina'].forEach(a=>{
                if(a!==azione) document.getElementById(a+'-'+faseId).checked=false;
            });

            if(azione==='termina'){
                document.getElementById('fase-'+faseId).style.display='none';
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
            document.getElementById('timeout-'+faseId).innerText = data.timeout;
            ['avvia','termina'].forEach(a=>document.getElementById(a+'-'+faseId).checked=false);
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