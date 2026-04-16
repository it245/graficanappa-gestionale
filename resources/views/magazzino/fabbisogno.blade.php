@extends('layouts.mes')

@section('page-title', 'Fabbisogno Materiali')
@section('topbar-title', 'Fabbisogno Carta per Commesse')

@section('sidebar-items')
    @include('magazzino._sidebar')
@endsection

@section('content')
{{-- KPI --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm h-100" style="background:var(--bg-card);">
            <div class="card-body text-center">
                <div style="font-size:2rem; font-weight:700; color:var(--accent);">{{ count($fabbisogno) }}</div>
                <div class="text-muted small">Tipi carta richiesti</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm h-100" style="background:var(--bg-card);">
            <div class="card-body text-center">
                <div style="font-size:2rem; font-weight:700; color:var(--success);">{{ $totDisponibile }}</div>
                <div class="text-muted small">Disponibili</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-4">
        <div class="card border-0 shadow-sm h-100" style="background:var(--bg-card);">
            <div class="card-body text-center">
                <div style="font-size:2rem; font-weight:700; color:var(--danger);">{{ $totMancante }}</div>
                <div class="text-muted small">Da ordinare</div>
            </div>
        </div>
    </div>
</div>

{{-- Tabella fabbisogno --}}
<div class="card border-0 shadow-sm" style="background:var(--bg-card);">
    <div class="card-header d-flex justify-content-between align-items-center" style="background:transparent; border-bottom:1px solid var(--border-color);">
        <strong>Fabbisogno carta — Commesse con STAMPA in attesa</strong>
        @if($totMancante > 0)
        <a href="{{ route('magazzino.ordiniAcquisto', ['op_token' => request('op_token')]) }}" class="btn btn-sm btn-danger">
            Genera ordini acquisto ({{ $totMancante }})
        </a>
        @endif
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm mb-0" style="color:var(--text-primary);">
                <thead><tr>
                    <th>Cod. Carta</th>
                    <th>Descrizione</th>
                    <th class="text-end">Fabbisogno</th>
                    <th class="text-end">Giacenza</th>
                    <th class="text-end">Deficit</th>
                    <th>Stato</th>
                    <th>Commesse</th>
                </tr></thead>
                <tbody>
                @foreach($fabbisogno as $item)
                    @php $mancante = $item['deficit'] > 0; @endphp
                    <tr style="{{ $mancante ? 'background:rgba(220,38,38,0.08);' : '' }}">
                        <td><code style="font-size:11px;">{{ $item['cod_carta'] }}</code></td>
                        <td>{{ Str::limit($item['descrizione_carta'], 35) }}</td>
                        <td class="text-end fw-bold">{{ number_format($item['fabbisogno_totale'], 2, ',', '.') }}</td>
                        <td class="text-end">
                            @if($item['articolo_magazzino'])
                                {{ number_format($item['giacenza'], 2, ',', '.') }}
                            @else
                                <span class="text-muted">non in mag.</span>
                            @endif
                        </td>
                        <td class="text-end fw-bold {{ $mancante ? 'text-danger' : 'text-success' }}">
                            {{ $mancante ? '-' . number_format($item['deficit'], 2, ',', '.') : 'OK' }}
                        </td>
                        <td>
                            @if($mancante)
                                <span class="badge bg-danger" style="font-size:10px;">MANCANTE</span>
                            @else
                                <span class="badge bg-success" style="font-size:10px;">DISPONIBILE</span>
                            @endif
                        </td>
                        <td>
                            @foreach($item['commesse'] as $c)
                                <span class="badge bg-light text-dark" style="font-size:10px;">
                                    {{ $c['commessa'] }} ({{ number_format($c['qta_carta'], 2, ',', '.') }} {{ $c['um'] }})
                                </span>
                            @endforeach
                        </td>
                    </tr>
                @endforeach
                @if(empty($fabbisogno))
                    <tr><td colspan="7" class="text-center text-muted py-3">Nessuna commessa in attesa di stampa con carta assegnata</td></tr>
                @endif
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
