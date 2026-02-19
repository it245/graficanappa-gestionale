@extends('layouts.app')

@section('content')
<style>
    @media print {
        .no-print { display: none !important; }
        body { font-size: 11px; }
        .container-fluid { padding: 0 !important; margin: 0 !important; }
        .card { border: none !important; box-shadow: none !important; page-break-inside: avoid; }
        .card-header { background: #fff !important; color: #000 !important; border-bottom: 2px solid #000 !important; }
        .table th { background: #eee !important; color: #000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .bg-primary { background-color: #0d6efd !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .bg-success { background-color: #28a745 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .bg-danger { background-color: #dc3545 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .bg-warning { background-color: #ffc107 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .bg-info { background-color: #0dcaf0 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .kpi-card { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        @page { margin: 1.5cm; size: A4; }
        .report-header { border-bottom: 3px solid #d11317 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
    .report-header {
        border-bottom: 3px solid #d11317;
        padding-bottom: 15px;
        margin-bottom: 25px;
    }
    .kpi-number {
        font-size: 2.2rem;
        font-weight: 700;
        line-height: 1;
    }
</style>

<div class="container-fluid mt-3 px-3">
    {{-- Header --}}
    <div class="report-header d-flex justify-content-between align-items-center">
        <div>
            <h2 class="mb-0" style="color:#d11317; font-weight:700;">Report Produzione Settimanale</h2>
            <p class="text-muted mb-0 mt-1">
                <strong>Grafica Nappa srl</strong> | Periodo: {{ $dataInizio }} - {{ $dataFine }}
            </p>
        </div>
        <div class="no-print">
            <button onclick="window.print()" class="btn btn-dark me-1">Stampa PDF</button>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">Dashboard</a>
        </div>
    </div>

    {{-- KPI --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-success h-100 kpi-card">
                <div class="card-body text-center py-3">
                    <div class="text-muted small mb-1">Fasi completate</div>
                    <div class="kpi-number text-success">{{ $fasiCompletate }}</div>
                    <div class="text-muted small">ultimi 7 giorni</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info h-100 kpi-card">
                <div class="card-body text-center py-3">
                    <div class="text-muted small mb-1">Ore lavorate</div>
                    <div class="kpi-number text-info">{{ $oreLavorate }}h</div>
                    <div class="text-muted small">ultimi 7 giorni</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-primary h-100 kpi-card">
                <div class="card-body text-center py-3">
                    <div class="text-muted small mb-1">Commesse spedite</div>
                    <div class="kpi-number text-primary">{{ $commesseSpedite }}</div>
                    <div class="text-muted small">ultimi 7 giorni</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card {{ $numCommesseInRitardo > 0 ? 'border-danger' : 'border-success' }} h-100 kpi-card">
                <div class="card-body text-center py-3">
                    <div class="text-muted small mb-1">Commesse in ritardo</div>
                    <div class="kpi-number {{ $numCommesseInRitardo > 0 ? 'text-danger' : 'text-success' }}">{{ $numCommesseInRitardo }}</div>
                    <div class="text-muted small">attualmente</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        {{-- Top 5 operatori --}}
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-dark text-white">
                    <strong>Top 5 operatori (ultimi 7 giorni)</strong>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0" style="font-size:13px;">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Operatore</th>
                                <th class="text-center">Fasi completate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topOperatori as $i => $op)
                            <tr>
                                <td>
                                    @if($i === 0)
                                        <span style="font-size:18px">&#x1F947;</span>
                                    @elseif($i === 1)
                                        <span style="font-size:18px">&#x1F948;</span>
                                    @elseif($i === 2)
                                        <span style="font-size:18px">&#x1F949;</span>
                                    @else
                                        {{ $i + 1 }}
                                    @endif
                                </td>
                                <td><strong>{{ $op->nome }} {{ $op->cognome }}</strong></td>
                                <td class="text-center"><span class="badge bg-success">{{ $op->fasi_completate }}</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-muted text-center py-3">Nessun dato</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Commesse completate --}}
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-dark text-white">
                    <strong>Commesse completate questa settimana ({{ $commesseCompletate->count() }})</strong>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-striped mb-0" style="font-size:13px;">
                        <thead class="table-light">
                            <tr>
                                <th>Commessa</th>
                                <th>Cliente</th>
                                <th>Descrizione</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($commesseCompletate as $c)
                            <tr>
                                <td><strong>{{ $c->commessa }}</strong></td>
                                <td>{{ $c->cliente_nome ?: '-' }}</td>
                                <td><small>{{ \Illuminate\Support\Str::limit($c->descrizione, 40) }}</small></td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-muted text-center py-3">Nessuna commessa completata</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Commesse in ritardo --}}
    @if($commesseInRitardo->count() > 0)
    <div class="card mb-4">
        <div class="card-header bg-danger text-white">
            <strong>Commesse in ritardo ({{ $commesseInRitardo->count() }})</strong>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-striped mb-0" style="font-size:13px;">
                <thead class="table-light">
                    <tr>
                        <th>Commessa</th>
                        <th>Cliente</th>
                        <th class="text-center">Consegna prevista</th>
                        <th class="text-center">Giorni di ritardo</th>
                        <th class="text-center">Avanzamento</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($commesseInRitardo as $c)
                    <tr>
                        <td><strong>{{ $c->commessa }}</strong></td>
                        <td>{{ $c->cliente_nome ?: '-' }}</td>
                        <td class="text-center">{{ \Carbon\Carbon::parse($c->data_prevista_consegna)->format('d/m/Y') }}</td>
                        <td class="text-center">
                            <span class="badge bg-danger">{{ $c->giorni_ritardo }}gg</span>
                        </td>
                        <td class="text-center">
                            <div class="progress" style="height:16px; min-width:60px;">
                                <div class="progress-bar bg-warning" style="width:{{ $c->avanzamento }}%">{{ $c->avanzamento }}%</div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Footer --}}
    <div class="text-muted small text-center mb-3" style="border-top: 2px solid #e0e0e0; padding-top:10px;">
        <strong>Grafica Nappa srl</strong> &mdash; Report generato il {{ now()->format('d/m/Y H:i') }} &mdash; Sistema MES
    </div>
</div>
@endsection
