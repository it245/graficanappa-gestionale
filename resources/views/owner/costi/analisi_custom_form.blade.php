@extends('layouts.costi')

@section('content')
<link rel="stylesheet" href="{{ asset('css/costi-ui.css') }}?v={{ filemtime(public_path('css/costi-ui.css')) }}">

<div class="gn-page" style="max-width:720px;">
    <a href="{{ route('owner.analisi.custom.index') }}?op_token={{ request('op_token') }}" class="gn-btn gn-btn-secondary gn-btn-sm" style="margin-bottom:14px;">← Torna alla lista</a>

    <h1>+ Nuova analisi custom</h1>
    <div class="gn-subtitle">Crea un workspace per analizzare un gruppo di commesse</div>

    <div class="gn-card">
        <div class="gn-card-body">
            <form method="POST" action="{{ route('owner.analisi.custom.store') }}">
                @csrf
                <input type="hidden" name="op_token" value="{{ request('op_token') }}">

                <div style="margin-bottom:14px;">
                    <label style="display:block;font-size:12px;color:var(--gn-muted);margin-bottom:4px;font-weight:500;">Nome *</label>
                    <input type="text" name="nome" required maxlength="200" placeholder="es. Maxtris maggio 2026"
                           style="width:100%;padding:9px 12px;border:1px solid var(--gn-border);border-radius:8px;font-size:14px;">
                </div>

                <div style="margin-bottom:18px;">
                    <label style="display:block;font-size:12px;color:var(--gn-muted);margin-bottom:4px;font-weight:500;">Descrizione (opzionale)</label>
                    <textarea name="descrizione" rows="3" maxlength="500" placeholder="Scopo dell'analisi, criteri, obiettivi..."
                              style="width:100%;padding:9px 12px;border:1px solid var(--gn-border);border-radius:8px;font-size:14px;resize:vertical;font-family:inherit;"></textarea>
                </div>

                <div style="display:flex;gap:8px;">
                    <button class="gn-btn gn-btn-primary">+ Crea analisi</button>
                    <a href="{{ route('owner.analisi.custom.index') }}?op_token={{ request('op_token') }}" class="gn-btn gn-btn-secondary">Annulla</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
