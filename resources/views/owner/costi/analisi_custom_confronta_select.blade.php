@extends('layouts.costi')

@section('content')
<link rel="stylesheet" href="{{ asset('css/costi-ui.css') }}?v={{ filemtime(public_path('css/costi-ui.css')) }}">

<div class="gn-page">
    <a href="{{ route('owner.analisi.custom.index') }}?op_token={{ request('op_token') }}" style="font-size:13px;color:var(--gn-primary);text-decoration:none;">← Torna a analisi custom</a>
    <h1 style="margin-top:8px;">⚖️ Confronta analisi</h1>
    <div class="gn-subtitle">Seleziona 2-3 analisi da confrontare side-by-side.</div>

    @if(session('error'))
    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:10px 14px;border-radius:8px;margin-bottom:14px;">{{ session('error') }}</div>
    @endif

    <form method="GET" action="{{ route('owner.analisi.custom.confronta') }}">
        <input type="hidden" name="op_token" value="{{ request('op_token') }}">
        <div class="gn-card">
            <div class="gn-card-header"><h3>Analisi disponibili ({{ $analisiList->count() }})</h3></div>
            <table class="gn-table">
                <thead><tr><th style="width:50px;">Sel</th><th>Nome</th><th>Autore</th><th class="num">Commesse</th><th>Ultimo accesso</th></tr></thead>
                <tbody>
                @forelse($analisiList as $a)
                <tr>
                    <td><input type="checkbox" name="ids[]" value="{{ $a->id }}"></td>
                    <td><strong>{{ $a->nome }}</strong>@if($a->descrizione)<br><small style="color:var(--gn-muted);">{{ $a->descrizione }}</small>@endif</td>
                    <td>{{ $a->autore }}</td>
                    <td class="num">{{ $a->commesse()->count() }}</td>
                    <td>{{ $a->ultimo_accesso?->format('d/m/Y H:i') ?? '-' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" style="text-align:center;color:var(--gn-muted);padding:32px;">Nessuna analisi creata.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div style="margin-top:14px;">
            <button class="gn-btn gn-btn-primary">⚖️ Confronta selezionate</button>
        </div>
    </form>
</div>
@endsection
