@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="m-0">Analisi Costi — Commesse Terminate</h2>
        <div class="text-muted small">Solo commesse con tutte le fasi a stato 3 o 4</div>
    </div>

    <form method="GET" class="mb-3" action="{{ route('admin.costi.analisi.index') }}">
        <div class="input-group" style="max-width:500px;">
            <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Cerca commessa, cliente, descrizione…">
            <button class="btn btn-primary" type="submit">Cerca</button>
            @if($search)
            <a href="{{ route('admin.costi.analisi.index') }}" class="btn btn-outline-secondary">Reset</a>
            @endif
        </div>
    </form>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover table-sm mb-0" style="font-size:13px;">
                <thead class="table-dark">
                    <tr>
                        <th style="width:120px;">Commessa</th>
                        <th>Cliente</th>
                        <th>Descrizione</th>
                        <th style="width:130px;">Data Consegna</th>
                        <th style="width:80px;text-align:right;">Azione</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($righe as $r)
                    <tr>
                        <td><strong>{{ $r->commessa }}</strong></td>
                        <td>{{ $r->cliente_nome ?? '-' }}</td>
                        <td style="max-width:400px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $r->descrizione ?? '-' }}</td>
                        <td>{{ $r->data_prevista_consegna ? \Carbon\Carbon::parse($r->data_prevista_consegna)->format('d/m/Y') : '-' }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.costi.analisi.show', $r->commessa) }}" class="btn btn-sm btn-primary">Dettaglio</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Nessuna commessa terminata trovata.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $righe->links() }}
    </div>
</div>
@endsection
