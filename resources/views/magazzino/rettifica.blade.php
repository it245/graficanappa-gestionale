@extends('layouts.mes')

@section('page-title', 'Rettifica Inventariale')
@section('topbar-title', 'Magazzino — Rettifica')

@section('sidebar-items')
    @include('magazzino._sidebar')
@endsection

@section('content')
<div class="container" style="max-width:800px;">
    <h4 class="mb-3">Rettifica Inventariale</h4>
    <p class="text-muted mb-3">Correggi la giacenza quando il valore nel sistema non corrisponde a quello fisico.</p>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-sm table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Codice</th>
                <th>Descrizione</th>
                <th>Lotto</th>
                <th>Giacenza attuale</th>
                <th>Nuova quantità</th>
                <th>Note</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($giacenze as $g)
            <tr>
                <form method="POST" action="{{ route('magazzino.rettifica.store', ['op_token' => request('op_token')]) }}">
                    @csrf
                    <input type="hidden" name="giacenza_id" value="{{ $g->id }}">
                    <td>{{ $g->articolo->codice ?? '-' }}</td>
                    <td>{{ $g->articolo->descrizione ?? '-' }}</td>
                    <td>{{ $g->lotto ?? '-' }}</td>
                    <td><strong>{{ number_format($g->quantita, 2) }}</strong> {{ $g->articolo->um ?? 'fg' }}</td>
                    <td><input type="number" name="nuova_quantita" class="form-control form-control-sm" step="0.01" min="0" value="{{ $g->quantita }}" style="width:120px;"></td>
                    <td><input type="text" name="note" class="form-control form-control-sm" placeholder="Motivo" style="width:150px;"></td>
                    <td><button type="submit" class="btn btn-sm btn-warning">Rettifica</button></td>
                </form>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-muted">Nessuna giacenza</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
