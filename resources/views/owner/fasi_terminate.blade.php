@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Fasi Terminate</h2>
  <a href = "{{ route('owner.dashboard')}}" class="btn btn-secondary mb-3">Torna alla Dashboard</a>
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
            @foreach($fasiTerminate as $fase)
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
@endsection