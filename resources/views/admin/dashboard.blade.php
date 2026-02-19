@extends('layouts.app')

@section('content')
<div class="container mt-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2>Dashboard Admin</h2>
            <p class="text-muted mb-0">Benvenuto, {{ session('operatore_nome') }}</p>
        </div>
        <div>
            <a href="{{ route('admin.operatore.crea') }}" class="btn btn-success me-2">+ Nuovo operatore</a>
            <form method="POST" action="{{ route('admin.logout') }}" style="display:inline;">
                @csrf
                <button type="submit" class="btn btn-outline-secondary">Logout</button>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-dark text-white">
            <strong>Operatori ({{ $operatori->count() }})</strong>
        </div>
        <div class="card-body p-0">
            <table class="table table-bordered table-sm table-striped mb-0" style="font-size:13px;">
                <thead class="table-dark">
                    <tr>
                        <th>Codice</th>
                        <th>Nome</th>
                        <th>Cognome</th>
                        <th>Ruolo</th>
                        <th>Reparti</th>
                        <th>Attivo</th>
                        <th>Azioni</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($operatori as $op)
                    <tr class="{{ !$op->attivo ? 'table-secondary' : '' }}">
                        <td><strong>{{ $op->codice_operatore }}</strong></td>
                        <td>{{ $op->nome }}</td>
                        <td>{{ $op->cognome }}</td>
                        <td>
                            @if($op->ruolo === 'admin')
                                <span class="badge bg-dark">admin</span>
                            @elseif($op->ruolo === 'owner')
                                <span class="badge bg-primary">owner</span>
                            @else
                                <span class="badge bg-secondary">operatore</span>
                            @endif
                        </td>
                        <td>{{ $op->reparti->pluck('nome')->join(', ') ?: '-' }}</td>
                        <td class="text-center">
                            @if($op->attivo)
                                <span class="badge bg-success">Si</span>
                            @else
                                <span class="badge bg-danger">No</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('admin.operatore.modifica', $op->id) }}" class="btn btn-sm btn-outline-primary">Modifica</a>
                            <form method="POST" action="{{ route('admin.operatore.toggleAttivo', $op->id) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-sm {{ $op->attivo ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                    {{ $op->attivo ? 'Disattiva' : 'Attiva' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
