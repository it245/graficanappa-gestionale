@extends('layouts.costi')

@section('content')
<link rel="stylesheet" href="{{ asset('css/costi-ui.css') }}?v={{ filemtime(public_path('css/costi-ui.css')) }}">

<div class="gn-page">
    <a href="{{ route('owner.costi.analisi.index') }}?op_token={{ request('op_token') }}" style="font-size:13px;color:var(--gn-primary);text-decoration:none;">← Torna analisi costi</a>

    <h1 style="margin-top:8px;">🏷️ Categorie altri costi</h1>
    <div class="gn-subtitle">Personalizza le categorie disponibili nei costi aggiuntivi per commessa.</div>

    <div class="gn-card" style="margin-bottom:14px;">
        <div class="gn-card-header"><h3>Aggiungi categoria</h3></div>
        <div class="gn-card-body">
            <form method="POST" action="{{ route('owner.costi.categorie.store') }}" style="display:grid;grid-template-columns:1fr 2fr 100px auto;gap:10px;">
                @csrf
                <input type="text" name="nome" placeholder="Slug (es. fustella, cliche)" required style="padding:8px 12px;border:1px solid var(--gn-border);border-radius:6px;font-size:13px;">
                <input type="text" name="descrizione" placeholder="Descrizione visibile" style="padding:8px 12px;border:1px solid var(--gn-border);border-radius:6px;font-size:13px;">
                <input type="number" name="ordine" value="99" style="padding:8px 12px;border:1px solid var(--gn-border);border-radius:6px;font-size:13px;">
                <button class="gn-btn gn-btn-primary">+ Aggiungi</button>
            </form>
        </div>
    </div>

    <div class="gn-card">
        <div class="gn-card-header"><h3>Categorie esistenti ({{ count($categorie) }})</h3></div>
        <table class="gn-table">
            <thead>
                <tr><th>Ordine</th><th>Nome (slug)</th><th>Descrizione</th><th>Attiva</th><th>Azioni</th></tr>
            </thead>
            <tbody>
            @forelse($categorie as $cat)
                <tr>
                    <form method="POST" action="{{ route('owner.costi.categorie.update', $cat->id) }}">
                        @csrf
                        <td style="width:80px;"><input type="number" name="ordine" value="{{ $cat->ordine }}" style="width:60px;"></td>
                        <td><input type="text" name="nome" value="{{ $cat->nome }}" style="width:160px;"></td>
                        <td><input type="text" name="descrizione" value="{{ $cat->descrizione }}" style="width:100%;"></td>
                        <td><input type="checkbox" name="attiva" value="1" {{ $cat->attiva ? 'checked' : '' }}></td>
                        <td>
                            <button class="gn-btn gn-btn-primary gn-btn-sm">💾</button>
                    </form>
                            <form method="POST" action="{{ route('owner.costi.categorie.destroy', $cat->id) }}" onsubmit="return confirm('Eliminare categoria?')" style="display:inline;">
                                @csrf @method('DELETE')
                                <button class="gn-btn gn-btn-secondary gn-btn-sm">🗑</button>
                            </form>
                        </td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center;color:var(--gn-muted);padding:32px;">Nessuna categoria.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
