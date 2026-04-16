@extends('layouts.mes')

@section('page-title', 'Reso Materiale')
@section('topbar-title', 'Magazzino — Reso')

@section('sidebar-items')
    @include('magazzino._sidebar')
@endsection

@section('content')
<div class="container" style="max-width:700px;">
    <h4 class="mb-3">Reso Materiale (rientro fogli/materiale avanzato)</h4>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <form method="POST" action="{{ route('magazzino.reso.store', ['op_token' => request('op_token')]) }}">
        @csrf
        <div class="mb-3">
            <label class="form-label fw-bold">Articolo</label>
            <select name="articolo_id" class="form-select" required>
                <option value="">-- Seleziona articolo --</option>
                @foreach($articoli as $art)
                    <option value="{{ $art->id }}">{{ $art->codice }} — {{ $art->descrizione }} ({{ $art->um }})</option>
                @endforeach
            </select>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-6">
                <label class="form-label fw-bold">Quantità</label>
                <input type="number" name="quantita" class="form-control" step="0.01" min="0.01" required>
            </div>
            <div class="col-6">
                <label class="form-label fw-bold">Lotto</label>
                <input type="text" name="lotto" class="form-control" placeholder="Opzionale">
            </div>
        </div>
        <div class="mb-3">
            <label class="form-label">Commessa (se associato)</label>
            <input type="text" name="commessa" class="form-control" placeholder="es. 0067024-26">
        </div>
        <div class="mb-3">
            <label class="form-label">Note</label>
            <input type="text" name="note" class="form-control" placeholder="Motivo del reso">
        </div>
        <button type="submit" class="btn btn-success btn-lg w-100">Registra Reso</button>
    </form>
</div>
@endsection
