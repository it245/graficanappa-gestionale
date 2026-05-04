@extends('layouts.mes')

@section('page-title', 'Magazzino')
@section('topbar-title', 'Magazzino')

@section('sidebar-items')
    @include('magazzino._sidebar')
@endsection

@section('styles')
    @include('magazzino._styles')
@endsection

@section('content')
<div class="mag-page">

{{-- KPI Cards --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="mag-card mag-kpi h-100">
            <div class="card-body text-center">
                <div class="mag-kpi-value" style="color:var(--mes-primary, #3b82f6);">{{ number_format($totArticoli) }}</div>
                <div class="text-muted small">Articoli</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="mag-card mag-kpi h-100">
            <div class="card-body text-center">
                <div class="mag-kpi-value" style="color:var(--mes-success, #10b981);">{{ number_format($totGiacenza, 2, ',', '.') }}</div>
                <div class="text-muted small">Giacenza totale (fg)</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="mag-card mag-kpi h-100">
            <div class="card-body text-center">
                <div class="mag-kpi-value" style="color:var(--mes-primary, #3b82f6);">{{ $movOggi }}</div>
                <div class="text-muted small">Movimenti oggi</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="mag-card mag-kpi h-100">
            <div class="card-body text-center">
                <div class="mag-kpi-value" style="color:var(--mes-danger, #ef4444);">{{ count($alertSoglia) }}</div>
                <div class="text-muted small">Sotto soglia</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Giacenze basse --}}
    <div class="col-lg-6">
        <div class="mag-card">
            <div class="card-header d-flex align-items-center" style="background:transparent; border-bottom:1px solid var(--mes-border, #e5e7eb);">
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--mes-danger, #ef4444)" stroke-width="2" style="width:18px; height:18px; margin-right:8px;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <strong>Giacenze sotto soglia</strong>
            </div>
            <div class="card-body p-0">
                @if(count($alertSoglia) > 0)
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr>
                            <th>Codice</th><th>Descrizione</th><th class="text-end">Giacenza</th><th class="text-end">Soglia</th><th>Stato</th>
                        </tr></thead>
                        <tbody>
                        @foreach($alertSoglia as $a)
                            <tr style="background:rgba(239,68,68,0.06);">
                                <td><code class="mag-num">{{ $a['articolo']->codice }}</code></td>
                                <td>{{ Str::limit($a['articolo']->descrizione, 30) }}</td>
                                <td class="text-end fw-bold mag-num" style="color: var(--mes-danger, #ef4444);">{{ number_format($a['giacenza'], 2, ',', '.') }}</td>
                                <td class="text-end mag-num">{{ number_format($a['soglia'], 2, ',', '.') }}</td>
                                <td><span class="mag-pill mag-pill-warning">Scarsa</span></td>
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
        <div class="mag-card">
            <div class="card-header d-flex align-items-center" style="background:transparent; border-bottom:1px solid var(--mes-border, #e5e7eb);">
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--mes-primary, #3b82f6)" stroke-width="2" style="width:18px; height:18px; margin-right:8px;"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                <strong>Ultimi movimenti</strong>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr>
                            <th>Tipo</th><th>Articolo</th><th class="text-end">Qta</th><th>Operatore</th><th>Data</th>
                        </tr></thead>
                        <tbody>
                        @foreach($ultimiMovimenti as $mov)
                            <tr>
                                <td>
                                    @php
                                        $pill = match($mov->tipo) {
                                            'carico'    => ['mag-pill-success', 'CARICO'],
                                            'scarico'   => ['mag-pill-warning', 'SCARICO'],
                                            'reso'      => ['mag-pill-info',    'RESO'],
                                            'rettifica' => ['mag-pill-neutral', 'RETTIFICA'],
                                            default     => ['mag-pill-neutral', strtoupper($mov->tipo)],
                                        };
                                    @endphp
                                    <span class="mag-pill {{ $pill[0] }}">{{ $pill[1] }}</span>
                                </td>
                                <td>{{ Str::limit($mov->articolo->descrizione ?? '-', 25) }}</td>
                                <td class="text-end fw-bold mag-num">{{ number_format(abs($mov->quantita), 2, ',', '.') }}</td>
                                <td>{{ $mov->operatore?->nome ?? '-' }}</td>
                                <td class="mag-num">{{ $mov->created_at->format('d/m H:i') }}</td>
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
</div>{{-- /.mag-page --}}
@endsection
