@extends('layouts.mes')

@section('page-title', 'Giacenze Magazzino')
@section('topbar-title', 'Giacenze Carta')

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
<div class="mag-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead><tr>
                    <th>Codice</th><th>Descrizione</th><th>Formato</th><th>g</th>
                    <th class="text-end">Giacenza</th><th class="text-end">Soglia</th>
                    <th>Stato</th><th>Lotto</th><th>Ultimo carico</th>
                </tr></thead>
                <tbody>
                @foreach($giacenze as $g)
                    @php
                        $soglia = $g->articolo->soglia_minima ?? 0;
                        $sottoSoglia = $g->articolo && $soglia > 0 && $g->quantita < $soglia;
                        $vicinoSoglia = $g->articolo && $soglia > 0 && !$sottoSoglia && $g->quantita < ($soglia * 1.25);
                        if ($sottoSoglia)      { $pillCls = 'mag-pill-danger';  $pillTxt = 'Critica'; }
                        elseif ($vicinoSoglia) { $pillCls = 'mag-pill-warning'; $pillTxt = 'Scarsa';  }
                        else                   { $pillCls = 'mag-pill-success'; $pillTxt = 'OK';      }
                    @endphp
                    <tr style="{{ $sottoSoglia ? 'background:rgba(239,68,68,0.06);' : '' }}">
                        <td><code class="mag-num" style="font-size:11px;">{{ $g->articolo->codice ?? '-' }}</code></td>
                        <td>{{ Str::limit($g->articolo->descrizione ?? '-', 30) }}</td>
                        <td>{{ $g->articolo->formato ?? '-' }}</td>
                        <td class="mag-num">{{ $g->articolo->grammatura ?? '-' }}</td>
                        <td class="text-end fw-bold mag-num" style="{{ $sottoSoglia ? 'color: var(--mes-danger, #ef4444);' : '' }}">
                            {{ number_format($g->quantita, 2, ',', '.') }}
                        </td>
                        <td class="text-end mag-num">{{ $g->articolo && $soglia > 0 ? number_format($soglia, 2, ',', '.') : '-' }}</td>
                        <td><span class="mag-pill {{ $pillCls }}">{{ $pillTxt }}</span></td>
                        <td>{{ $g->lotto ?? '-' }}</td>
                        <td class="mag-num">{{ $g->data_ultimo_carico ? $g->data_ultimo_carico->format('d/m/Y') : '-' }}</td>
                    </tr>
                @endforeach
                @if($giacenze->isEmpty())
                    <tr><td colspan="9" class="text-center text-muted py-3">Nessuna giacenza trovata</td></tr>
                @endif
                </tbody>
            </table>
        </div>
        <div class="p-2">{{ $giacenze->appends(request()->query())->links() }}</div>
    </div>
</div>
</div>{{-- /.mag-page --}}
@endsection
