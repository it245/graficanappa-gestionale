@extends('layouts.app')

@section('content')
<div class="container mt-3" style="max-width:600px;">
    <h2>{{ $operatore ? 'Modifica Operatore' : 'Nuovo Operatore' }}</h2>

    @if($errors->any())
        <div class="alert alert-danger mt-2">
            @foreach($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ $operatore ? route('admin.operatore.aggiorna', $operatore->id) : route('admin.operatore.salva') }}" class="mt-3">
        @csrf

        <div class="mb-3">
            <label class="form-label">Nome</label>
            <input type="text" name="nome" class="form-control" value="{{ old('nome', $operatore->nome ?? '') }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Cognome</label>
            <input type="text" name="cognome" class="form-control" value="{{ old('cognome', $operatore->cognome ?? '') }}" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Ruolo</label>
            <select name="ruolo" class="form-select" required>
                <option value="operatore" {{ old('ruolo', $operatore->ruolo ?? '') === 'operatore' ? 'selected' : '' }}>Operatore</option>
                <option value="owner" {{ old('ruolo', $operatore->ruolo ?? '') === 'owner' ? 'selected' : '' }}>Owner</option>
                <option value="admin" {{ old('ruolo', $operatore->ruolo ?? '') === 'admin' ? 'selected' : '' }}>Admin</option>
            </select>
        </div>

        @if($operatore)
            <div class="mb-3">
                <label class="form-label">Codice operatore</label>
                <input type="text" name="codice_operatore" class="form-control" value="{{ old('codice_operatore', $operatore->codice_operatore) }}" required>
            </div>
        @endif

        <div class="mb-3">
            <label class="form-label">Reparto Principale</label>
            <select name="reparto_principale" class="form-select" required>
                @foreach($reparti as $id => $rep)
                    <option value="{{ $id }}" {{ old('reparto_principale', $operatore?->reparti->first()?->id) == $id ? 'selected' : '' }}>{{ $rep }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Reparto Secondario (facoltativo)</label>
            <select name="reparto_secondario" class="form-select">
                <option value="">-- Nessuno --</option>
                @foreach($reparti as $id => $rep)
                    <option value="{{ $id }}" {{ old('reparto_secondario', $operatore?->reparti->skip(1)->first()?->id) == $id ? 'selected' : '' }}>{{ $rep }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Password {{ $operatore ? '(lascia vuoto per non modificare)' : '(facoltativa)' }}</label>
            <input type="password" name="password" class="form-control" autocomplete="new-password">
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success">{{ $operatore ? 'Salva modifiche' : 'Crea operatore' }}</button>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-secondary">Annulla</a>
        </div>
    </form>
</div>
@endsection
