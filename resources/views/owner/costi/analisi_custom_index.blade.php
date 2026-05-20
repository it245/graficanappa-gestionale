@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="{{ asset('css/costi-ui.css') }}?v={{ filemtime(public_path('css/costi-ui.css')) }}">

<div class="gn-page">
    <h1>Analisi Custom</h1>
    <div class="gn-subtitle">Crea e gestisci le tue analisi personalizzate</div>

    <div class="gn-filters">
        <div style="margin-left:auto;display:flex;gap:8px;">
            <a href="{{ route('owner.costi.analisi.index') }}?op_token={{ request('op_token') }}" class="gn-btn gn-btn-secondary">← Lista commesse</a>
            <a href="{{ route('owner.analisi.custom.create') }}?op_token={{ request('op_token') }}" class="gn-btn gn-btn-primary">+ Nuova analisi</a>
        </div>
    </div>

    @if(session('success'))
    <div style="background:#d1fae5;border:1px solid #6ee7b7;color:#065f46;padding:10px 14px;border-radius:8px;margin-bottom:14px;">{{ session('success') }}</div>
    @endif

    <div class="gn-card">
        <table class="gn-table">
            <thead>
                <tr>
                    <th>Nome analisi</th>
                    <th>Descrizione</th>
                    <th class="num">N. commesse</th>
                    <th>Autore</th>
                    <th>Ultimo accesso</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                @forelse($analisiList as $a)
                <tr>
                    <td><a href="{{ route('owner.analisi.custom.show', $a->id) }}?op_token={{ request('op_token') }}" class="gn-commessa-link">{{ $a->nome }}</a></td>
                    <td style="color:var(--gn-muted);">{{ $a->descrizione ?? '-' }}</td>
                    <td class="num">{{ $a->commesse()->count() }}</td>
                    <td>{{ $a->autore }}</td>
                    <td style="font-size:12px;color:var(--gn-muted);">{{ $a->ultimo_accesso ? $a->ultimo_accesso->format('d/m/Y H:i') : '-' }}</td>
                    <td>
                        <a href="{{ route('owner.analisi.custom.show', $a->id) }}?op_token={{ request('op_token') }}" class="gn-btn gn-btn-primary gn-btn-icon" title="Apri">↗</a>
                        <form method="POST" action="{{ route('owner.analisi.custom.destroy', $a->id) }}" onsubmit="return confirm('Eliminare?')" style="display:inline;">@csrf @method('DELETE')<button class="gn-btn gn-btn-secondary gn-btn-icon" title="Elimina">🗑</button></form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" style="text-align:center;color:var(--gn-muted);padding:48px;">Nessuna analisi. Crea la prima con "+ Nuova analisi".</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="gn-pagination">
            <div style="font-size:12px;color:var(--gn-muted);">Vista 1–{{ $analisiList->count() }} di {{ $analisiList->total() }} risultati</div>
            <div class="pager">{{ $analisiList->links() }}</div>
        </div>
    </div>
</div>
@endsection
