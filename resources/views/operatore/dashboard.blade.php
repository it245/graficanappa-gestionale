
@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Dashboard Operatore</h2>
    <p>Operatore: {{ session('operatore_nome') }}</p>
    <p>Reparto: {{ session('operatore_reparto') }}</p>

    <h4>Fasi visibili</h4>

    <table class="table table-bordered table-sm">
        <thead>
            <tr>
                <th>Priorità</th>
                <th>Operatore</th>
                <th>Fase</th>
                <th>Azioni</th>
                <th>Stato</th>
                <th>Commessa</th>
                <th>Data Registrazione</th>
                <th>Cliente</th>
                <th>Codice Articolo</th>
                <th>Descrizione Articolo</th>
                <th>Quantità Richiesta</th>
                <th>UM</th>
                <th>Data Prevista Consegna</th>
                <th>Qta Prodotta</th>
                <th>Carta</th>
                <th>Quantità Carta</th>
                <th>Note</th>
                <th>Ore</th>
                <th>Timeout</th>
            </tr>
        </thead>
        <tbody>
            @foreach($fasiVisibili as $fase)
            <tr id="fase-{{ $fase->id }}">
                <td>{{ $fase->ordine->priorita ?? '-' }}</td>
<td id="operatore-{{ $fase->id }}">
    @foreach($fase->operatori as $op)
        {{ $op->nome }} ({{ $op->pivot->data_inizio ? \Carbon\Carbon::parse($op->pivot->data_inizio)->format('d/m/Y H:i:s') : '-' }})<br>
    @endforeach
</td>                <td>{{ $fase->fase_catalogo->nome ?? '-' }}</td>
                <td>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="avvia-{{ $fase->id }}" 
                               onchange="aggiornaStato({{ $fase->id }}, 'avvia', this.checked)">
                        <label class="form-check-label" for="avvia-{{ $fase->id }}">Avvia</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="pausa-{{ $fase->id }}" 
                               onchange="gestisciPausa({{ $fase->id }}, this.checked)">
                        <label class="form-check-label" for="pausa-{{ $fase->id }}">Pausa</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="checkbox" id="termina-{{ $fase->id }}" 
                               onchange="aggiornaStato({{ $fase->id }}, 'termina', this.checked)">
                        <label class="form-check-label" for="termina-{{ $fase->id }}">Termina</label>
                    </div>
                </td>
                <td id="stato-{{ $fase->id }}">{{ $fase->stato ?? '-' }}</td>
                <td>{{ $fase->ordine->commessa ?? '-' }}</td>
                <td>{{ $fase->ordine->data_registrazione ?? '-' }}</td>
                <td>{{ $fase->ordine->cliente_nome ?? '-' }}</td>
                <td>{{ $fase->ordine->cod_art ?? '-' }}</td>
                <td>{{ $fase->ordine->descrizione ?? '-' }}</td>
                <td>{{ $fase->ordine->qta_richiesta ?? '-' }}</td>
                <td>{{ $fase->ordine->um ?? '-' }}</td>
                <td>{{ $fase->ordine->data_prevista_consegna ?? '-' }}</td>

                <!-- Qta Prodotta modificabile -->
                <td>
                    <input type="text" class="form-control form-control-sm"
                           value="{{ $fase->qta_prod ?? '' }}"
                           onblur="aggiornaCampo({{ $fase->id }}, 'qta_prod', this.value)">
                </td>

                <td>{{ $fase->ordine->carta ?? '-' }}</td>
                <td>{{ $fase->ordine->qta_carta ?? '-' }}</td>

                <!-- Note modificabili -->
                <td>
                    <textarea style="width: 300px;; height:60px;"
                              onblur="aggiornaCampo({{ $fase->id }}, 'note', this.value)">{{ $fase->note ?? '' }}</textarea>
                </td>

                <td>{{ $fase->ore ?? '-' }}</td>
                <td id="timeout-{{ $fase->id }}">{{ $fase->timeout ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <form action="{{ route('operatore.logout') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-secondary">Logout</button>
    </form>
</div>

<script>
const motiviPausa = ["Attesa materiale", "Problema macchina", "Pranzo", "Altro"];

// Aggiorna stato fase (avvia/termina)
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

// Gestione pausa con prompt standard
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

// Aggiorna i campi qta_prodotta e note sull'ordine
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