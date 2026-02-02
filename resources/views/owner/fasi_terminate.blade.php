@extends('layouts.app')

@section('content')
<div class="container">
    <h2>Fasi Terminate</h2>

    <a href="{{ route('owner.dashboard') }}" class="btn btn-secondary mb-3">
        Torna alla Dashboard
    </a>

@php
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
        @foreach($fasiTerminate as $fase)
            <tr>
                <td>{{ $fase->ordine->commessa ?? '-' }}</td>
                <td>{{ $fase->ordine->cliente_nome ?? '-' }}</td>
                <td>{{ $fase->ordine->cod_art ?? '-' }}</td>
                <td>{{ $fase->ordine->descrizione ?? '-' }}</td>
                <td>{{ $fase->ordine->qta_richiesta ?? '-' }}</td>
                <td>{{ $fase->ordine->um ?? '-' }}</td>
                <td>{{ $fase->priorita ?? '-' }}</td>

                <td>{{ formatItalianDate($fase->ordine->data_registrazione) }}</td>
                <td>{{ formatItalianDate($fase->ordine->data_prevista_consegna) }}</td>

                <td>{{ $fase->ordine->cod_carta ?? '-' }}</td>
                <td>{{ $fase->ordine->carta ?? '-' }}</td>
                <td>{{ $fase->ordine->qta_carta ?? '-' }}</td>
                <td>{{ $fase->ordine->UM_carta ?? '-' }}</td>

                <td>{{ $fase->faseCatalogo->nome ?? '-' }}</td>
                <td>{{ $fase->reparto ?? '-' }}</td>

                <td>
                    @forelse($fase->operatori as $op)
                        {{ $op->nome }}
                        ({{ formatItalianDate($op->pivot->data_inizio, true) }})<br>
                    @empty
                        -
                    @endforelse
                </td>

                <td>{{ $fase->qta_prod ?? '-' }}</td>
                <td>{{ $fase->note ?? '-' }}</td>

                <td>{{ formatItalianDate($fase->data_inizio, true) }}</td>
                <td>{{ formatItalianDate($fase->data_fine, true) }}</td>
                <td>{{ $fase->stato ?? '-' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection