@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="m-0">📊 {{ $analisi->nome }}</h2>
            <div class="text-muted small">{{ $analisi->descrizione ?? 'Nessuna descrizione' }} · Autore: {{ $analisi->autore }}</div>
        </div>
        <a href="{{ route('owner.analisi.custom.index') }}?op_token={{ request('op_token') }}" class="btn btn-sm btn-outline-secondary">← Lista analisi</a>
    </div>

    @if(session('success'))<div class="alert alert-success py-2">{{ session('success') }}</div>@endif

    {{-- KPI aggregati --}}
    <div class="row g-2 mb-3">
        <div class="col-md-3">
            <div class="card border-primary">
                <div class="card-body py-2">
                    <div class="small text-muted">Commesse</div>
                    <div class="h4 m-0">{{ count($datiCommesse) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body py-2">
                    <div class="small text-muted">Totale costi</div>
                    <div class="h4 m-0">€ {{ number_format($totaleGenerale, 2, ',', '.') }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body py-2">
                    <div class="small text-muted">Costo medio/commessa</div>
                    <div class="h4 m-0">€ {{ count($datiCommesse) > 0 ? number_format($totaleGenerale / count($datiCommesse), 2, ',', '.') : '0,00' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body py-2">
                    <div class="small text-muted">Voci/categoria</div>
                    <div class="h6 m-0 small">{{ count($categorieTot) }} categorie</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Aggiungi commessa --}}
    <div class="card mb-3">
        <div class="card-header bg-light"><strong>+ Aggiungi commessa</strong></div>
        <div class="card-body py-2">
            <form method="POST" action="{{ route('owner.analisi.custom.aggiungi', $analisi->id) }}" class="row g-2">
                @csrf
                <div class="col-md-4"><input type="text" name="commessa" class="form-control form-control-sm" placeholder="es. 0067200-26" required></div>
                <div class="col-md-4"><input type="text" name="etichetta" class="form-control form-control-sm" placeholder="etichetta opzionale (es. articolo)"></div>
                <div class="col-md-2"><button class="btn btn-sm btn-success w-100">+ Aggiungi</button></div>
            </form>
        </div>
    </div>

    {{-- Distribuzione voci per categoria --}}
    @if(!empty($categorieTot))
    <div class="card mb-3">
        <div class="card-header bg-light"><strong>📊 Distribuzione costi per categoria</strong></div>
        <table class="table table-sm mb-0">
            <thead><tr><th>Categoria</th><th class="text-end" style="width:140px;">Totale €</th><th class="text-end" style="width:80px;">%</th></tr></thead>
            <tbody>
                @foreach($categorieTot as $cat => $val)
                <tr>
                    <td><span class="badge bg-secondary">{{ $cat }}</span></td>
                    <td class="text-end font-monospace">€ {{ number_format($val, 2, ',', '.') }}</td>
                    <td class="text-end font-monospace">{{ $totaleGenerale > 0 ? number_format($val / $totaleGenerale * 100, 1, ',', '.') : 0 }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Tabella commesse --}}
    <div class="card">
        <div class="card-header bg-primary text-white"><strong>Commesse incluse ({{ count($datiCommesse) }})</strong></div>
        <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:120px;">Commessa</th>
                    <th>Cliente / Descrizione</th>
                    <th style="width:140px;">Etichetta</th>
                    <th style="width:120px;text-align:right;">Costo totale</th>
                    <th style="width:80px;">Azioni</th>
                </tr>
            </thead>
            <tbody>
            @forelse($datiCommesse as $c)
                <tr>
                    <td><strong>{{ $c['commessa'] }}</strong></td>
                    <td class="small">
                        {{ $c['cliente'] }}<br>
                        <span class="text-muted">{{ \Illuminate\Support\Str::limit($c['descrizione'], 80) }}</span>
                    </td>
                    <td class="small">{{ $c['etichetta'] ?? '-' }}</td>
                    <td class="text-end font-monospace fw-bold">€ {{ number_format($c['totale'], 2, ',', '.') }}</td>
                    <td>
                        <a href="{{ route('owner.costi.analisi.show', $c['commessa']) }}?op_token={{ request('op_token') }}" target="_blank" class="btn btn-sm btn-outline-primary py-0" title="Apri dettaglio">↗</a>
                        <form method="POST" action="{{ route('owner.analisi.custom.rimuovi', [$analisi->id, $c['pivot_id']]) }}" class="d-inline" onsubmit="return confirm('Rimuovere?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger py-0">×</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">Nessuna commessa aggiunta. Usa il form sopra.</td></tr>
            @endforelse
            </tbody>
            @if(!empty($datiCommesse))
            <tfoot>
                <tr class="table-primary">
                    <th colspan="3" class="text-end">TOTALE ANALISI</th>
                    <th class="text-end font-monospace">€ {{ number_format($totaleGenerale, 2, ',', '.') }}</th>
                    <th></th>
                </tr>
            </tfoot>
            @endif
        </table>
        </div>
    </div>
</div>
@endsection
