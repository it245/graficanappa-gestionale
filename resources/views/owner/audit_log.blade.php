@extends('layouts.mes')

@section('page-title', 'Audit Log - MES Grafica Nappa')
@section('topbar-title', 'Audit Log')

@section('sidebar-items')
<div class="mes-sidebar-section">
    <div class="mes-sidebar-section-label">Navigazione</div>
    <a href="{{ route('owner.dashboard') }}" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        Dashboard
    </a>
    <a href="{{ route('owner.auditLog') }}" class="mes-sidebar-item active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        Audit Log
    </a>
</div>
@endsection

@section('content')
<div>
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
