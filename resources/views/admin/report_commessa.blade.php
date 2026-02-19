@extends('layouts.app')

@section('content')
<style>
    @media print {
        .no-print { display: none !important; }
        body { font-size: 11px; }
        .container-fluid { padding: 0 !important; margin: 0 !important; }
        .card { border: none !important; box-shadow: none !important; }
        .card-header { background: #fff !important; color: #000 !important; border-bottom: 2px solid #000 !important; }
        .table th { background: #eee !important; color: #000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .progress { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .progress-bar { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .bg-success { background-color: #28a745 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .bg-danger { background-color: #dc3545 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .barra-delta { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        @page { margin: 1cm; }
    }
</style>

<div class="container-fluid mt-3 px-3">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-1">Report Commessa: {{ $commessa }}</h2>
            <p class="text-muted mb-0">
                <strong>Cliente:</strong> {{ $cliente ?: '-' }} |
                <strong>Descrizione:</strong> {{ $descrizione ?: '-' }} |
                <strong>Consegna:</strong> {{ $consegna ? \Carbon\Carbon::parse($consegna)->format('d/m/Y') : '-' }} |
                <strong>Stato:</strong>
                @if($completata)
                    <span class="badge bg-success">Completata</span>
                @else
                    <span class="badge bg-warning text-dark">In corso</span>
                @endif
            </p>
        </div>
        <div class="no-print">
            <button onclick="window.print()" class="btn btn-sm btn-dark me-1">Stampa PDF</button>
            <a href="{{ route('admin.commesse') }}" class="btn btn-sm btn-outline-secondary">Lista commesse</a>
        </div>
    </div>

    {{-- Tabella fasi --}}
    <div class="card mb-3">
        <div class="card-header bg-dark text-white">
            <strong>Dettaglio fasi ({{ $fasi->count() }})</strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm table-striped mb-0" style="font-size:12px;">
                    <thead class="table-dark">
                        <tr>
                            <th>Fase</th>
                            <th>Reparto</th>
                            <th>Operatore</th>
                            <th class="text-center">Qta</th>
                            <th class="text-center">Ore stimate</th>
                            <th class="text-center">Ore effettive</th>
                            <th class="text-center">Delta</th>
                            <th class="text-center">%</th>
                            <th style="min-width:120px">Scostamento</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($fasi as $f)
                        <tr>
                            <td>
                                <strong>{{ $f->fase }}</strong>
                                @if($f->stato == 3)
                                    <span class="badge bg-success ms-1" style="font-size:9px">OK</span>
                                @elseif($f->stato == 2)
                                    <span class="badge bg-info ms-1" style="font-size:9px">Pausa</span>
                                @elseif($f->stato == 1)
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:9px">In corso</span>
                                @else
                                    <span class="badge bg-secondary ms-1" style="font-size:9px">Attesa</span>
                                @endif
                            </td>
                            <td>{{ ucfirst($f->reparto) }}</td>
                            <td>{{ $f->operatore }}</td>
                            <td class="text-center">{{ number_format($f->qta) }}</td>
                            <td class="text-center">{{ $f->ore_stimate }}h</td>
                            <td class="text-center">
                                @if($f->ore_effettive > 0)
                                    {{ $f->ore_effettive }}h
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center fw-bold {{ $f->delta > 0 ? 'text-danger' : ($f->delta < 0 ? 'text-success' : '') }}">
                                @if($f->ore_effettive > 0)
                                    {{ $f->delta > 0 ? '+' : '' }}{{ $f->delta }}h
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-center fw-bold {{ $f->percentuale > 0 ? 'text-danger' : ($f->percentuale < 0 ? 'text-success' : '') }}">
                                @if($f->ore_effettive > 0)
                                    {{ $f->percentuale > 0 ? '+' : '' }}{{ $f->percentuale }}%
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($f->ore_effettive > 0 && $f->ore_stimate > 0)
                                    @php
                                        $ratio = $f->ore_effettive / $f->ore_stimate;
                                        $barWidth = min($ratio * 100, 200);
                                        $barColor = $ratio <= 1 ? '#28a745' : '#dc3545';
                                    @endphp
                                    <div class="barra-delta" style="background:#e9ecef; border-radius:4px; height:14px; position:relative; overflow:hidden;">
                                        <div style="width:{{ min($barWidth, 100) }}%; height:100%; background:{{ $barColor }}; border-radius:4px;"></div>
                                        @if($ratio <= 1)
                                            <div style="position:absolute; left:{{ $barWidth }}%; top:0; width:2px; height:100%; background:#000;"></div>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold" style="font-size:13px;">
                            <td colspan="4" class="text-end">TOTALE</td>
                            <td class="text-center">{{ $totaleOreStimate }}h</td>
                            <td class="text-center">{{ $totaleOreEffettive }}h</td>
                            <td class="text-center {{ $deltaComplessivo > 0 ? 'text-danger' : ($deltaComplessivo < 0 ? 'text-success' : '') }}">
                                {{ $deltaComplessivo > 0 ? '+' : '' }}{{ $deltaComplessivo }}h
                            </td>
                            <td class="text-center {{ $percentualeComplessiva > 0 ? 'text-danger' : ($percentualeComplessiva < 0 ? 'text-success' : '') }}">
                                {{ $percentualeComplessiva > 0 ? '+' : '' }}{{ $percentualeComplessiva }}%
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- Riepilogo --}}
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card border-primary h-100">
                <div class="card-body text-center py-2">
                    <div class="text-muted small">Ore stimate</div>
                    <div class="fs-3 fw-bold text-primary">{{ $totaleOreStimate }}h</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info h-100">
                <div class="card-body text-center py-2">
                    <div class="text-muted small">Ore effettive</div>
                    <div class="fs-3 fw-bold text-info">{{ $totaleOreEffettive }}h</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card {{ $deltaComplessivo > 0 ? 'border-danger' : 'border-success' }} h-100">
                <div class="card-body text-center py-2">
                    <div class="text-muted small">Delta complessivo</div>
                    <div class="fs-3 fw-bold {{ $deltaComplessivo > 0 ? 'text-danger' : 'text-success' }}">
                        {{ $deltaComplessivo > 0 ? '+' : '' }}{{ $deltaComplessivo }}h
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card {{ $percentualeComplessiva > 0 ? 'border-danger' : 'border-success' }} h-100">
                <div class="card-body text-center py-2">
                    <div class="text-muted small">Scostamento %</div>
                    <div class="fs-3 fw-bold {{ $percentualeComplessiva > 0 ? 'text-danger' : 'text-success' }}">
                        {{ $percentualeComplessiva > 0 ? '+' : '' }}{{ $percentualeComplessiva }}%
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="text-muted small text-center mb-3">
        Report generato il {{ now()->format('d/m/Y H:i') }} | Verde = sotto la stima | Rosso = sopra la stima
    </div>
</div>
@endsection
