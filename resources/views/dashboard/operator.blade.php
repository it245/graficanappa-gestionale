@extends('layouts.app')

@section('content')
<h1>Simulazione MES - Produzione</h1>

@foreach($ordini as $ordine)
    <div style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
        <h3>Ordine: {{ $ordine->commessa }} - {{ $ordine->cliente_nome }}</h3>

        <p>
            Articolo: {{ $ordine->cod_art }} - {{ $ordine->descrizione }}
        </p>

        <p>
            Quantità richiesta: {{ $ordine->qta_richiesta }}
            |
            Quantità prodotta: {{ $ordine->qta_prodotta }}
        </p>

        <h4>Fasi del tuo reparto</h4>
        <ul>
            @foreach($ordine->fasi as $fase)
                <li>
                    <strong>{{ $fase->fase }}</strong>
                    ({{ $fase->reparto }})
                    —
                    Stato:
                    @if($fase->stato == 0) Non avviata
                    @elseif($fase->stato == 1) In lavorazione
                    @else Terminata
                    @endif
                    |
                    Qta prodotta: {{ $fase->qta_prod }}
                </li>
            @endforeach
        </ul>
    </div>
@endforeach

@endsection
