@extends('layouts.mes')

@section('page-title', 'Giacenze Magazzino')
@section('topbar-title', 'Giacenze Carta')

@section('sidebar-items')
    @include('magazzino._sidebar')
@endsection

@section('content')
{{-- Filtri --}}
<div class="card border-0 shadow-sm mb-3" style="background:var(--bg-card);">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <input type="hidden" name="op_token" value="{{ request('op_token') }}">
            <div class="col-auto">
                <label class="form-label small mb-0">Categoria</label>
                <select name="categoria" class="form-select form-select-sm">
                    <option value="">Tutti</option>
                    @foreach($filtri['categorie'] as $t)
                        <option value="{{ $t }}" {{ request('categoria') == $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">Formato</label>
                <select name="formato" class="form-select form-select-sm">
                    <option value="">Tutti</option>
                    @foreach($filtri['formati'] as $f)
                        <option value="{{ $f }}" {{ request('formato') == $f ? 'selected' : '' }}>{{ $f }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">Grammatura</label>
                <select name="grammatura" class="form-select form-select-sm">
                    <option value="">Tutte</option>
                    @foreach($filtri['grammature'] as $g)
                        <option value="{{ $g }}" {{ request('grammatura') == $g ? 'selected' : '' }}>{{ $g }}g</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-sm btn-primary">Filtra</button>
                <a href="{{ route('magazzino.giacenze', ['op_token' => request('op_token')]) }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

{{-- Tabella giacenze --}}
<div class="card border-0 shadow-sm" style="background:var(--bg-card);">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" style="color:var(--text-primary);">
                <thead><tr>
                    <th>Codice</th><th>Descrizione</th><th>Formato</th><th>g</th>
                    <th class="text-end">Giacenza</th><th class="text-end">Soglia</th>
                    <th>Lotto</th><th>Ultimo carico</th>
                </tr></thead>
                <tbody>
                @foreach($giacenze as $g)
                    @php $sottoSoglia = $g->articolo && $g->articolo->soglia_minima > 0 && $g->quantita < $g->articolo->soglia_minima; @endphp
                    <tr style="{{ $sottoSoglia ? 'background:rgba(220,38,38,0.08);' : '' }}">
                        <td><code style="font-size:11px;">{{ $g->articolo->codice ?? '-' }}</code></td>
                        <td>{{ Str::limit($g->articolo->descrizione ?? '-', 30) }}</td>
                        <td>{{ $g->articolo->formato ?? '-' }}</td>
                        <td>{{ $g->articolo->grammatura ?? '-' }}</td>
                        <td class="text-end fw-bold {{ $sottoSoglia ? 'text-danger' : '' }}">
                            {{ number_format($g->quantita, 0, ',', '.') }}
                        </td>
                        <td class="text-end">{{ $g->articolo && $g->articolo->soglia_minima > 0 ? number_format($g->articolo->soglia_minima, 0, ',', '.') : '-' }}</td>
                        <td>{{ $g->lotto ?? '-' }}</td>
                        <td>{{ $g->data_ultimo_carico ? $g->data_ultimo_carico->format('d/m/Y') : '-' }}</td>
                    </tr>
                @endforeach
                @if($giacenze->isEmpty())
                    <tr><td colspan="8" class="text-center text-muted py-3">Nessuna giacenza trovata</td></tr>
                @endif
                </tbody>
            </table>
        </div>
        <div class="p-2">{{ $giacenze->appends(request()->query())->links() }}</div>
    </div>
</div>
@endsection
