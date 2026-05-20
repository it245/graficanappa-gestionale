@extends('layouts.app')

@section('content')
<div class="container py-3" style="max-width:700px;">
    <h2 class="mb-3">+ Nuova analisi custom</h2>
    <form method="POST" action="{{ route('owner.analisi.custom.store') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Nome *</label>
            <input type="text" name="nome" class="form-control" required maxlength="200" placeholder="es. Maxtris maggio 2026">
        </div>
        <div class="mb-3">
            <label class="form-label">Descrizione</label>
            <textarea name="descrizione" class="form-control" rows="2" maxlength="500" placeholder="opzionale: scopo dell'analisi, criteri, obiettivi..."></textarea>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary">Crea</button>
            <a href="{{ route('owner.analisi.custom.index') }}" class="btn btn-outline-secondary">Annulla</a>
        </div>
    </form>
</div>
@endsection
