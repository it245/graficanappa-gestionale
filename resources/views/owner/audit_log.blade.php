@extends('layouts.app')

@section('title', 'Audit Log')

@section('content')
<div class="container-fluid py-3">
    <h4 class="mb-3">Audit Log</h4>

    {{-- Filtri --}}
    <form method="GET" class="row g-2 mb-3">
        <div class="col-auto">
            <select name="azione" class="form-select form-select-sm">
                <option value="">Tutte le azioni</option>
                @foreach($azioni as $a)
                    <option value="{{ $a }}" {{ request('azione') == $a ? 'selected' : '' }}>{{ $a }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <input type="text" name="utente" class="form-control form-control-sm" placeholder="Cerca utente..." value="{{ request('utente') }}">
        </div>
        <div class="col-auto">
            <input type="date" name="data" class="form-control form-control-sm" value="{{ request('data') }}">
        </div>
        <div class="col-auto">
            <button class="btn btn-sm btn-primary">Filtra</button>
            <a href="{{ route('owner.auditLog') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-sm table-striped table-hover" style="font-size:0.85rem;">
            <thead class="table-dark">
                <tr>
                    <th>Data/Ora</th>
                    <th>Utente</th>
                    <th>Azione</th>
                    <th>Modello</th>
                    <th>ID</th>
                    <th>Dettagli</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td class="text-nowrap">{{ \Carbon\Carbon::parse($log->created_at)->format('d/m H:i:s') }}</td>
                    <td>{{ $log->user_name ?? '-' }}</td>
                    <td>
                        @php
                            $badge = match($log->action) {
                                'login' => 'bg-success',
                                'logout' => 'bg-secondary',
                                'update' => 'bg-primary',
                                'create' => 'bg-info',
                                'delete' => 'bg-danger',
                                'sync' => 'bg-warning text-dark',
                                default => 'bg-light text-dark',
                            };
                        @endphp
                        <span class="badge {{ $badge }}">{{ $log->action }}</span>
                    </td>
                    <td>{{ $log->model ?? '-' }}</td>
                    <td>{{ $log->model_id ?? '-' }}</td>
                    <td style="max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="{{ $log->old_values }} → {{ $log->new_values }}">
                        @if($log->old_values && $log->new_values)
                            {{ $log->old_values }} <span class="text-muted">&rarr;</span> {{ $log->new_values }}
                        @elseif($log->extra)
                            {{ $log->extra }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-muted" style="font-size:0.75rem;">{{ $log->ip }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted">Nessun evento registrato</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $logs->withQueryString()->links() }}
</div>
@endsection
