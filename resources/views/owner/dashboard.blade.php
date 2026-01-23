@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Dashboard Owner</h2>
    <p>Benvenuto: {{ auth()->user()->nome ?? session('operatore_nome') }} | Ruolo: {{ auth()->user()->ruolo ?? session('operatore_ruolo') }}</p>

    <!-- Pulsanti azione -->
    <div class="mb-3 d-flex gap-2 align-items-center">
        <!-- Import Excel -->
        <form action="{{ route('owner.importOrdini') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="file" name="file" accept=".xlsx,.xls" required>
            <button class="btn btn-primary">Importa Ordini</button>
        </form>

        <!-- Aggiungi Operatore -->
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#aggiungiOperatoreModal">
            Aggiungi Operatore
        </button>
    </div>

    <table class="table table-bordered table-sm table-striped">
        <thead class="table-dark">
            <tr>
                <th>Commessa</th>
                <th>Cliente</th>
                <th>Codice Articolo</th>
                <th>Descrizione</th>
                <th>Qta Richiesta</th>
                <th>UM</th>
                <th>Priorità</th>
                <th>Data Registrazione</th>
                <th>Data Prevista Consegna</th>
                <th>Codice Carta</th>
                <th>Carta</th>
                <th>Qta Carta</th>
                <th>UM Carta</th>
                <th>Fase</th>
                <th>Reparto</th>
                <th>Operatori</th>
                <th>Qta Prodotta</th>
                <th>Note</th>
                <th>Data Inizio</th>
                <th>Data Fine</th>
                <th>Stato Fase</th>
            </tr>
        </thead>
        <tbody>
            @foreach($fasi as $fase)
            <tr>
                <!-- Ordine -->
                <td>{{ $fase->ordine->commessa ?? '-' }}</td>
                <td>{{ $fase->ordine->cliente_nome ?? '-' }}</td>
                <td>{{ $fase->ordine->cod_art ?? '-' }}</td>
                <td>{{ $fase->ordine->descrizione ?? '-' }}</td>
                <td>{{ $fase->ordine->qta_richiesta ?? '-' }}</td>
                <td>{{ $fase->ordine->um ?? '-' }}</td>
                <td>{{ $fase->priorita ?? '-' }}</td>
                <td>{{ $fase->ordine->data_registrazione ?? '-' }}</td>
                <td>{{ $fase->ordine->data_prevista_consegna ?? '-' }}</td>
                <td>{{ $fase->ordine->cod_carta ?? '-' }}</td>
                <td>{{ $fase->ordine->carta ?? '-' }}</td>
                <td>{{ $fase->ordine->qta_carta ?? '-' }}</td>
                <td>{{ $fase->ordine->UM_carta ?? '-' }}</td>

                <!-- Fase -->
                <td>{{ $fase->faseCatalogo->nome ?? '-' }}</td>
                <td>{{ $fase->reparto ?? '-' }}</td>

                <!-- Operatori multipli -->
                <td>
                    @if($fase->operatori->isNotEmpty())
                        @foreach($fase->operatori as $op)
                            {{ $op->nome }} ({{ \Carbon\Carbon::parse($op->pivot->data_inizio)->format('d/m/Y H:i:s') }})<br>
                        @endforeach
                    @else
                        -
                    @endif
                </td>

                <td>{{ $fase->qta_prod ?? '-' }}</td>
                <td>{{ $fase->note ?? '-' }}</td>

                <!-- Data Inizio = primo operatore -->
                <td>{{ $fase->data_inizio ?? '-' }}</td>
                <td>{{ $fase->data_fine ?? '-' }}</td>
                <td>{{ $fase->stato ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Modale Aggiungi Operatore -->
<div class="modal fade" id="aggiungiOperatoreModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="{{ route('owner.aggiungiOperatore') }}">
        @csrf
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuovo Operatore</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label>Nome</label>
                    <input type="text" name="nome" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label>Cognome</label>
                    <input type="text" name="cognome" class="form-control">
                </div>
                <div class="mb-2">
                    <label>Codice Operatore</label>
                    <input type="text" name="codice_operatore" class="form-control" required>
                </div>
                <div class="mb-2">
                    <label>Ruolo</label>
                    <select name="ruolo" class="form-control" required>
                        <option value="operatore">Operatore</option>
                        <option value="owner">Owner</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label>Reparto</label>
                    <input type="text" name="reparto" class="form-control" required>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Chiudi</button>
                <button class="btn btn-primary" type="submit">Salva</button>
            </div>
        </div>
    </form>
  </div>
</div>

<script>
function aggiornaCampo(faseId, campo, valore){
    fetch('{{ route("produzione.aggiornaCampo") }}',{
        method:'POST',
        headers:{
            'X-CSRF-TOKEN':'{{ csrf_token() }}',
            'Content-Type':'application/json'
        },
        body:JSON.stringify({fase_id:faseId, campo:campo, valore:valore})
    })
    .then(res=>res.json())
    .then(data=>{
        if(!data.success) alert('Errore durante il salvataggio: '+(data.messaggio||''));
    })
    .catch(err=>console.error('Errore:', err));
}
</script>
@endsection