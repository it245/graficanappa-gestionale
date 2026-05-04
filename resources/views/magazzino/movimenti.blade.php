@extends('layouts.mes')

@section('page-title', 'Movimenti Magazzino')
@section('topbar-title', 'Storico Movimenti')

@section('sidebar-items')
    @include('magazzino._sidebar')
@endsection

@section('styles')
    @include('magazzino._styles')
@endsection

@section('content')
<div class="mag-page">
{{-- Filtri --}}
<div class="mag-card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="op_token" value="{{ request('op_token') }}">
            <div class="col-auto">
                <label class="form-label small mb-0">Tipo</label>
                <select name="tipo" class="form-select form-select-sm">
                    <option value="">Tutti</option>
                    <option value="carico" {{ request('tipo') == 'carico' ? 'selected' : '' }}>Carico</option>
                    <option value="scarico" {{ request('tipo') == 'scarico' ? 'selected' : '' }}>Scarico</option>
                    <option value="reso" {{ request('tipo') == 'reso' ? 'selected' : '' }}>Reso</option>
                    <option value="rettifica" {{ request('tipo') == 'rettifica' ? 'selected' : '' }}>Rettifica</option>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">Da</label>
                <input type="date" name="da" class="form-control form-control-sm" value="{{ request('da') }}">
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">A</label>
                <input type="date" name="a" class="form-control form-control-sm" value="{{ request('a') }}">
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-primary">Filtra</button>
                <a href="{{ route('magazzino.movimenti', ['op_token' => request('op_token')]) }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

{{-- Tabella movimenti --}}
<div class="mag-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead><tr>
                    <th>Data</th><th>Tipo</th><th>Articolo</th><th class="text-end">Qta</th>
                    <th class="text-end">Giacenza dopo</th><th>Commessa</th><th>Lotto</th>
                    <th>Operatore</th><th>Note</th>
                </tr></thead>
                <tbody>
                @foreach($movimenti as $mov)
                    <tr>
                        <td class="mag-num">{{ $mov->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            @php
                                $pill = match($mov->tipo) {
                                    'carico'    => ['mag-pill-success', 'CARICO'],
                                    'scarico'   => ['mag-pill-warning', 'SCARICO'],
                                    'reso'      => ['mag-pill-info',    'RESO'],
                                    'rettifica' => ['mag-pill-neutral', 'RETTIFICA'],
                                    default     => ['mag-pill-neutral', strtoupper((string)$mov->tipo)],
                                };
                            @endphp
                            <span class="mag-pill {{ $pill[0] }}">{{ $pill[1] }}</span>
                        </td>
                        <td>{{ Str::limit($mov->articolo->descrizione ?? '-', 25) }}</td>
                        <td class="text-end fw-bold mag-num">{{ $mov->quantita > 0 ? '+' : '' }}{{ number_format($mov->quantita, 2, ',', '.') }}</td>
                        <td class="text-end mag-num">{{ number_format($mov->giacenza_dopo, 2, ',', '.') }}</td>
                        <td>{{ $mov->commessa ?? '-' }}</td>
                        <td>{{ $mov->lotto ?? '-' }}</td>
                        <td>{{ $mov->operatore?->nome ?? '-' }}</td>
                        <td>{{ Str::limit($mov->note ?? '', 20) }}</td>
                    </tr>
                @endforeach
                @if($movimenti->isEmpty())
                    <tr><td colspan="9" class="text-center text-muted py-3">Nessun movimento trovato</td></tr>
                @endif
                </tbody>
            </table>
        </div>
        <div class="p-2">{{ $movimenti->appends(request()->query())->links() }}</div>
    </div>
</div>
</div>{{-- /.mag-page --}}
@endsection
