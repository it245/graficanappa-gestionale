@extends('layouts.app')

@section('content')
<div class="container-fluid px-3">
<style>
    body { background: #f0f2f5; }
    .rov-header {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 20px; padding: 12px 0;
    }
    .rov-header h1 { font-size: 22px; font-weight: 700; color: #1a1a2e; margin: 0; }
    .rov-header .nav-links a {
        color: #495057; text-decoration: none; font-size: 13px;
        padding: 6px 14px; border: 1px solid #dee2e6; border-radius: 6px;
        margin-left: 8px; transition: all 0.2s;
    }
    .rov-header .nav-links a:hover { background: #e9ecef; }
    .rov-header .nav-links a.active { background: #1d4ed8; color: #fff; border-color: #1d4ed8; }

    .rep-card {
        background: #fff; border-radius: 12px; border: 1px solid #dee2e6;
        margin-bottom: 16px; box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        overflow: hidden;
    }
    .rep-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 12px 18px; background: #f8f9fa; border-bottom: 1px solid #e9ecef;
        cursor: pointer; user-select: none;
    }
    .rep-header:hover { background: #eef0f3; }
    .rep-header h2 {
        font-size: 15px; font-weight: 700; margin: 0; text-transform: uppercase;
        letter-spacing: 0.5px; color: #1a1a2e;
    }
    .rep-badge {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 28px; height: 28px; border-radius: 14px;
        font-size: 13px; font-weight: 700; color: #fff; padding: 0 10px;
    }

    .rep-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .rep-table th {
        background: #f8f9fa; font-size: 11px; text-transform: uppercase;
        letter-spacing: 0.3px; color: #6c757d; font-weight: 600;
        padding: 8px 12px; border-bottom: 1px solid #e9ecef; text-align: left;
        position: sticky; top: 0;
    }
    .rep-table td {
        padding: 7px 12px; border-bottom: 1px solid #f0f1f3;
        vertical-align: middle; color: #1a1a2e;
    }
    .rep-table tr:hover td { background: #f8f9fb; }
    .rep-table .desc-cell { max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .rep-table .comm-link { color: #1d4ed8; font-weight: 600; text-decoration: none; }
    .rep-table .comm-link:hover { text-decoration: underline; }

    .stato-dot {
        display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 3px;
    }
    .stato-dot.attesa { background: #d1d5db; }
    .stato-dot.inizio { background: #3b82f6; }
    .stato-dot.terminato { background: #22c55e; }

    .consegna-scaduta { color: #dc2626; font-weight: 600; }
    .consegna-oggi { color: #f59e0b; font-weight: 600; }
    .consegna-ok { color: #6c757d; }

    .fasi-tags { display: flex; flex-wrap: wrap; gap: 3px; }
    .fasi-tag {
        font-size: 10px; padding: 1px 6px; border-radius: 4px;
        background: #e9ecef; color: #495057; white-space: nowrap;
    }

    .rep-body { max-height: 600px; overflow-y: auto; }
    .rep-body.collapsed { display: none; }

    .summary-bar {
        display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 16px;
        padding: 14px 18px; background: #fff; border-radius: 12px;
        border: 1px solid #dee2e6; box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    }
    .summary-item {
        display: flex; align-items: center; gap: 8px;
        font-size: 13px; color: #495057;
    }
    .summary-item strong { font-size: 20px; color: #1a1a2e; }

    @media print {
        .rov-header .nav-links, .summary-bar { display: none; }
        .rep-body.collapsed { display: block !important; }
        .rep-card { break-inside: avoid; page-break-inside: avoid; }
    }
</style>

<div class="rov-header">
    <h1>Panoramica Reparti</h1>
    <div class="nav-links">
        <a href="{{ route('owner.dashboard', ['op_token' => $opToken]) }}">Dashboard</a>
        <a href="{{ route('owner.repartiOverview', ['op_token' => $opToken]) }}" class="active">Reparti</a>
        <a href="{{ route('owner.reportOre', ['op_token' => $opToken]) }}">Report Ore</a>
        <a href="{{ route('owner.esterne', ['op_token' => $opToken]) }}">Esterne</a>
    </div>
</div>

{{-- Summary --}}
@php
    $totCommesse = $data->sum('totale');
    $totFasi = $data->sum(fn($r) => $r->commesse->sum('n_fasi'));
    $nReparti = $data->count();
@endphp
<div class="summary-bar">
    <div class="summary-item"><strong>{{ $nReparti }}</strong> reparti attivi</div>
    <div class="summary-item"><strong>{{ $totCommesse }}</strong> commesse</div>
    <div class="summary-item"><strong>{{ $totFasi }}</strong> fasi in lavorazione</div>
</div>

{{-- Reparti --}}
@php
    $colors = [
        'stampa offset' => '#2563eb',
        'digitale' => '#7c3aed',
        'finitura digitale' => '#a855f7',
        'prestampa' => '#0891b2',
        'plastificazione' => '#0d9488',
        'piegaincolla' => '#d97706',
        'legatoria' => '#b45309',
        'fustella' => '#dc2626',
        'stampa a caldo' => '#e11d48',
        'spedizione' => '#059669',
        'magazzino' => '#6b7280',
        'produzione' => '#4b5563',
        'esterno' => '#78716c',
    ];
@endphp

@foreach($data as $item)
@php $color = $colors[strtolower($item->reparto->nome)] ?? '#6b7280'; @endphp
<div class="rep-card">
    <div class="rep-header" onclick="this.nextElementSibling.classList.toggle('collapsed')">
        <div style="display:flex;align-items:center;gap:12px;">
            <h2 style="color:{{ $color }}">{{ $item->reparto->nome }}</h2>
            <span class="rep-badge" style="background:{{ $color }}">{{ $item->totale }}</span>
        </div>
        <span style="font-size:18px;color:#adb5bd;" class="chevron">&#9660;</span>
    </div>
    <div class="rep-body">
        <table class="rep-table">
            <thead>
                <tr>
                    <th style="width:110px">Commessa</th>
                    <th style="width:160px">Cliente</th>
                    <th>Descrizione</th>
                    <th style="width:60px;text-align:center">Qta</th>
                    <th style="width:90px;text-align:center">Consegna</th>
                    <th style="width:80px;text-align:center">Stato</th>
                    <th style="width:60px;text-align:right">Priorita</th>
                    <th>Fasi</th>
                </tr>
            </thead>
            <tbody>
            @foreach($item->commesse as $c)
                @php
                    $scaduta = $c->consegna && \Carbon\Carbon::parse($c->consegna)->lt(today());
                    $oggi = $c->consegna && \Carbon\Carbon::parse($c->consegna)->isToday();
                    $consClass = $scaduta ? 'consegna-scaduta' : ($oggi ? 'consegna-oggi' : 'consegna-ok');
                @endphp
                <tr>
                    <td>
                        <a class="comm-link" href="{{ route('owner.dettaglioCommessa', ['commessa' => $c->commessa, 'op_token' => $opToken]) }}">
                            {{ $c->commessa }}
                        </a>
                    </td>
                    <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $c->cliente }}">
                        {{ $c->cliente }}
                    </td>
                    <td class="desc-cell" title="{{ $c->descrizione }}">{{ $c->descrizione }}</td>
                    <td style="text-align:center">{{ number_format($c->qta, 0, ',', '.') }}</td>
                    <td style="text-align:center" class="{{ $consClass }}">
                        {{ $c->consegna ? \Carbon\Carbon::parse($c->consegna)->format('d/m') : '-' }}
                    </td>
                    <td style="text-align:center;font-weight:600;">2</td>
                    <td style="text-align:right;font-family:monospace;font-size:12px;">{{ number_format($c->priorita, 1) }}</td>
                    <td>
                        <div class="fasi-tags">
                            @foreach($c->fasi as $f)
                                <span class="fasi-tag">{{ $f }}</span>
                            @endforeach
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endforeach

@if($data->isEmpty())
<div style="text-align:center;padding:60px;color:#9ca3af;font-size:16px;">
    Nessuna commessa attiva al momento
</div>
@endif

</div>
@endsection
