@extends('layouts.mes')

@section('page-title', 'Ordini Acquisto')
@section('topbar-title', 'Ordini Acquisto Carta')

@section('sidebar-items')
    @include('magazzino._sidebar')
@endsection

@section('content')
@if(empty($ordini))
    <div class="card border-0 shadow-sm" style="background:var(--bg-card);">
        <div class="card-body text-center py-5">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2" style="width:48px; height:48px; margin-bottom:8px;">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
            </svg>
            <div class="fs-5 fw-bold" style="color:var(--success);">Nessun ordine necessario</div>
            <div class="text-muted">Tutta la carta necessaria per le commesse attive e' disponibile in magazzino</div>
            <a href="{{ route('magazzino.fabbisogno', ['op_token' => request('op_token')]) }}" class="btn btn-outline-primary mt-3">Vedi fabbisogno</a>
        </div>
    </div>
@else
    <div class="mb-3">
        <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">Stampa lista ordini</button>
        <a href="{{ route('magazzino.fabbisogno', ['op_token' => request('op_token')]) }}" class="btn btn-outline-primary btn-sm ms-2">Torna al fabbisogno</a>
    </div>

    @foreach($ordini as $gruppo)
    <div class="card border-0 shadow-sm mb-4" style="background:var(--bg-card);">
        <div class="card-header d-flex align-items-center" style="background:transparent; border-bottom:1px solid var(--border-color);">
            <svg viewBox="0 0 24 24" fill="none" stroke="var(--danger)" stroke-width="2" style="width:18px; height:18px; margin-right:8px;">
                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/>
            </svg>
            <strong>{{ $gruppo['fornitore'] }}</strong>
            <span class="badge bg-danger ms-2" style="font-size:10px;">{{ $gruppo['totale_articoli'] }} articoli da ordinare</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0" style="color:var(--text-primary);">
                    <thead><tr>
                        <th>Cod. Carta</th>
                        <th>Descrizione</th>
                        <th class="text-end">Fabbisogno</th>
                        <th class="text-end">Giacenza</th>
                        <th class="text-end">Da ordinare</th>
                        <th>Commesse</th>
                    </tr></thead>
                    <tbody>
                    @foreach($gruppo['articoli'] as $art)
                        <tr>
                            <td><code style="font-size:11px;">{{ $art['cod_carta'] }}</code></td>
                            <td>{{ $art['descrizione'] }}</td>
                            <td class="text-end">{{ number_format($art['fabbisogno'], 2, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($art['giacenza'], 2, ',', '.') }}</td>
                            <td class="text-end fw-bold text-danger">{{ number_format($art['da_ordinare'], 2, ',', '.') }}</td>
                            <td><span class="text-muted" style="font-size:11px;">{{ $art['commesse'] }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endforeach
@endif
@endsection
