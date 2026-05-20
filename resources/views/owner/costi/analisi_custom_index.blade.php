@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="m-0">📊 Analisi Custom</h2>
        <div class="d-flex gap-2">
            <a href="{{ route('owner.costi.analisi.index') }}?op_token={{ request('op_token') }}" class="btn btn-sm btn-outline-secondary">← Lista commesse</a>
            <a href="{{ route('owner.analisi.custom.create') }}?op_token={{ request('op_token') }}" class="btn btn-sm btn-success">+ Nuova analisi</a>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover table-sm mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="width:50px;">#</th>
                        <th>Nome</th>
                        <th>Descrizione</th>
                        <th style="width:80px;text-align:right;">Commesse</th>
                        <th style="width:120px;">Autore</th>
                        <th style="width:140px;">Ultimo accesso</th>
                        <th style="width:120px;text-align:right;">Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($analisiList as $a)
                    <tr>
                        <td>{{ $a->id }}</td>
                        <td><a href="{{ route('owner.analisi.custom.show', $a->id) }}?op_token={{ request('op_token') }}"><strong>{{ $a->nome }}</strong></a></td>
                        <td class="small text-muted">{{ $a->descrizione ?? '-' }}</td>
                        <td class="text-end">{{ $a->commesse()->count() }}</td>
                        <td class="small">{{ $a->autore }}</td>
                        <td class="small">{{ $a->ultimo_accesso ? $a->ultimo_accesso->format('d/m H:i') : '-' }}</td>
                        <td class="text-end">
                            <a href="{{ route('owner.analisi.custom.show', $a->id) }}?op_token={{ request('op_token') }}" class="btn btn-sm btn-primary py-0">Apri</a>
                            <form method="POST" action="{{ route('owner.analisi.custom.destroy', $a->id) }}" class="d-inline" onsubmit="return confirm('Cancellare analisi?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger py-0">×</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">Nessuna analisi. Crea la prima con "+ Nuova analisi".</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-2">{{ $analisiList->links() }}</div>
</div>
@endsection
