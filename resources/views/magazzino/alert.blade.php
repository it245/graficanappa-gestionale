@extends('layouts.mes')

@section('page-title', 'Alert Magazzino')
@section('topbar-title', 'Alert Sotto Soglia')

@section('sidebar-items')
    @include('magazzino._sidebar')
@endsection

@section('content')
<div class="card border-0 shadow-sm" style="background:var(--bg-card);">
    <div class="card-header d-flex align-items-center" style="background:transparent; border-bottom:1px solid var(--border-color);">
        <svg viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="2" style="width:18px; height:18px; margin-right:8px;">
            <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
            <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
        </svg>
        <strong>Articoli sotto soglia minima ({{ count($alert) }})</strong>
    </div>
    <div class="card-body p-0">
        @if(count($alert) > 0)
        <div class="table-responsive">
            <table class="table table-sm mb-0" style="color:var(--text-primary);">
                <thead><tr>
                    <th>Codice</th><th>Descrizione</th><th>Tipo</th><th>Formato</th>
                    <th class="text-end">Giacenza</th><th class="text-end">Soglia</th><th class="text-end">Mancanti</th>
                    <th>Fornitore</th>
                </tr></thead>
                <tbody>
                @foreach($alert as $a)
                    <tr style="background:rgba(220,38,38,0.08);">
                        <td><code>{{ $a['articolo']->codice }}</code></td>
                        <td>{{ $a['articolo']->descrizione }}</td>
                        <td>{{ $a['articolo']->tipo_carta ?? '-' }}</td>
                        <td>{{ $a['articolo']->formato ?? '-' }}</td>
                        <td class="text-end fw-bold text-danger">{{ number_format($a['giacenza'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($a['soglia'], 0, ',', '.') }}</td>
                        <td class="text-end fw-bold text-danger">{{ number_format($a['mancanti'], 0, ',', '.') }}</td>
                        <td>{{ $a['articolo']->fornitore ?? '-' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="p-4 text-center text-muted">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2" style="width:48px; height:48px; margin-bottom:8px;">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <div class="fs-5 fw-bold" style="color:var(--success);">Tutto in ordine</div>
            <div>Nessun articolo sotto soglia minima</div>
        </div>
        @endif
    </div>
</div>
@endsection
