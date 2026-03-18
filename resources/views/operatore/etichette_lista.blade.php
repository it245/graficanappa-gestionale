@extends('layouts.app')

@section('content')
<div class="container-fluid px-3">
    <div class="d-flex justify-content-between align-items-center mb-3 mt-2">
        <h2>Stampa Etichette</h2>
    </div>

    <form method="GET" action="{{ route('etichette.lista') }}" class="mb-3 d-flex gap-2" style="max-width:500px;">
        <input type="text" name="q" class="form-control" placeholder="Cerca commessa, cliente o descrizione..." value="{{ $filtro }}" autofocus>
        <button type="submit" class="btn btn-primary" style="white-space:nowrap;">Cerca</button>
        @if($filtro)
        <a href="{{ route('etichette.lista') }}" class="btn btn-outline-secondary" style="white-space:nowrap;">Reset</a>
        @endif
    </form>

    <div style="overflow-x:auto;">
        <table class="table table-bordered table-hover table-sm" style="font-size:13px;">
            <thead class="table-dark">
                <tr>
                    <th style="width:110px;">Commessa</th>
                    <th>Cliente</th>
                    <th>Descrizione</th>
                    <th style="width:70px;">Qta</th>
                    <th style="width:100px;">Etichetta</th>
                </tr>
            </thead>
            <tbody>
                @forelse($commesse as $c)
                <tr>
                    <td><strong>{{ $c->commessa }}</strong></td>
                    <td>{{ $c->cliente_nome ?? '-' }}</td>
                    <td style="max-width:350px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $c->descrizione ?? '-' }}</td>
                    <td>{{ $c->qta_richiesta ? number_format($c->qta_richiesta, 0, ',', '.') : '-' }}</td>
                    <td>
                        @if($c->ordini->count() > 1)
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" style="font-size:12px;">
                                    Stampa ({{ $c->ordini->count() }})
                                </button>
                                <ul class="dropdown-menu">
                                    @foreach($c->ordini as $ord)
                                    <li><a class="dropdown-item" href="{{ route('operatore.etichetta', $ord->id) }}" style="font-size:12px;">{{ Str::limit($ord->descrizione, 45) }}</a></li>
                                    @endforeach
                                </ul>
                            </div>
                        @else
                            <a href="{{ route('operatore.etichetta', $c->ordini->first()->id) }}" class="btn btn-sm btn-primary" style="font-size:12px;">Stampa</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-3">Nessuna commessa trovata</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="text-muted mt-2" style="font-size:12px;">
        {{ $commesse->count() }} commesse
    </div>
</div>
@endsection
