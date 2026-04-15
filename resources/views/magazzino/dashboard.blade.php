@extends('layouts.mes')

@section('page-title', 'Magazzino')
@section('topbar-title', 'Magazzino')

@section('sidebar-items')
    @include('magazzino._sidebar')
@endsection

@section('content')
{{-- KPI Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="background:var(--bg-card);">
            <div class="card-body text-center">
                <div style="font-size:2rem; font-weight:700; color:var(--accent);">{{ number_format($totArticoli) }}</div>
                <div class="text-muted small">Articoli</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="background:var(--bg-card);">
            <div class="card-body text-center">
                <div style="font-size:2rem; font-weight:700; color:var(--success);">{{ number_format($totGiacenza, 0, ',', '.') }}</div>
                <div class="text-muted small">Giacenza totale (fg)</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="background:var(--bg-card);">
            <div class="card-body text-center">
                <div style="font-size:2rem; font-weight:700; color:var(--info);">{{ $movOggi }}</div>
                <div class="text-muted small">Movimenti oggi</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm h-100" style="background:var(--bg-card);">
            <div class="card-body text-center">
                <div style="font-size:2rem; font-weight:700; color:var(--danger);">{{ count($alertSoglia) }}</div>
                <div class="text-muted small">Sotto soglia</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Giacenze basse --}}
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm" style="background:var(--bg-card);">
            <div class="card-header d-flex align-items-center" style="background:transparent; border-bottom:1px solid var(--border-color);">
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="2" style="width:18px; height:18px; margin-right:8px;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <strong>Giacenze sotto soglia</strong>
            </div>
            <div class="card-body p-0">
                @if(count($alertSoglia) > 0)
                <div class="table-responsive">
                    <table class="table table-sm mb-0" style="color:var(--text-primary);">
                        <thead><tr>
                            <th>Codice</th><th>Descrizione</th><th class="text-end">Giacenza</th><th class="text-end">Soglia</th>
                        </tr></thead>
                        <tbody>
                        @foreach($alertSoglia as $a)
                            <tr style="background:rgba(220,38,38,0.08);">
                                <td><code>{{ $a['articolo']->codice }}</code></td>
                                <td>{{ Str::limit($a['articolo']->descrizione, 30) }}</td>
                                <td class="text-end fw-bold text-danger">{{ number_format($a['giacenza'], 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($a['soglia'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                    <div class="p-3 text-muted text-center">Nessun articolo sotto soglia</div>
                @endif
            </div>
        </div>
    </div>

    {{-- Ultimi movimenti --}}
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm" style="background:var(--bg-card);">
            <div class="card-header d-flex align-items-center" style="background:transparent; border-bottom:1px solid var(--border-color);">
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2" style="width:18px; height:18px; margin-right:8px;"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                <strong>Ultimi movimenti</strong>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0" style="color:var(--text-primary);">
                        <thead><tr>
                            <th>Tipo</th><th>Articolo</th><th class="text-end">Qta</th><th>Operatore</th><th>Data</th>
                        </tr></thead>
                        <tbody>
                        @foreach($ultimiMovimenti as $mov)
                            <tr>
                                <td>
                                    @php
                                        $badge = match($mov->tipo) {
                                            'carico' => ['bg-success', 'CARICO'],
                                            'scarico' => ['bg-warning text-dark', 'SCARICO'],
                                            'reso' => ['bg-info', 'RESO'],
                                            'rettifica' => ['bg-secondary', 'RETTIFICA'],
                                            default => ['bg-light', $mov->tipo],
                                        };
                                    @endphp
                                    <span class="badge {{ $badge[0] }}" style="font-size:10px;">{{ $badge[1] }}</span>
                                </td>
                                <td>{{ Str::limit($mov->articolo->descrizione ?? '-', 25) }}</td>
                                <td class="text-end fw-bold">{{ number_format(abs($mov->quantita), 0, ',', '.') }}</td>
                                <td>{{ $mov->operatore?->nome ?? '-' }}</td>
                                <td>{{ $mov->created_at->format('d/m H:i') }}</td>
                            </tr>
                        @endforeach
                        @if($ultimiMovimenti->isEmpty())
                            <tr><td colspan="5" class="text-center text-muted py-3">Nessun movimento</td></tr>
                        @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
