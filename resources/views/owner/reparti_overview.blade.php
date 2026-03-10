@extends('layouts.app')

@section('content')
<div class="container-fluid px-3">
<style>
    body { background: #f0f2f5; }
    .rov-header {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 24px; padding: 16px 0;
    }
    .rov-header h1 { font-size: 22px; font-weight: 700; color: #1a1a2e; margin: 0; letter-spacing: -0.5px; }
    .rov-header .nav-links a {
        color: #495057; text-decoration: none; font-size: 13px;
        padding: 6px 14px; border: 1px solid #dee2e6; border-radius: 6px;
        margin-left: 8px; transition: all 0.2s;
    }
    .rov-header .nav-links a:hover { background: #e9ecef; }
    .rov-header .nav-links a.active { background: #1d4ed8; color: #fff; border-color: #1d4ed8; }

    /* KPI Cards */
    .kpi-row {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 14px; margin-bottom: 24px;
    }
    .kpi-card {
        background: #fff; border-radius: 12px; padding: 20px 22px;
        border: 1px solid #e5e7eb; position: relative; overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        transition: transform 0.15s, box-shadow 0.15s;
    }
    .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .kpi-card::before {
        content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px;
    }
    .kpi-label {
        font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.8px;
        color: #9ca3af; margin-bottom: 8px;
    }
    .kpi-value {
        font-size: 32px; font-weight: 800; color: #111827; line-height: 1;
        letter-spacing: -1px;
    }
    .kpi-sub {
        font-size: 12px; color: #6b7280; margin-top: 6px;
    }
    .kpi-icon {
        position: absolute; top: 18px; right: 18px; width: 40px; height: 40px;
        border-radius: 10px; display: flex; align-items: center; justify-content: center;
        opacity: 0.9;
    }
    .kpi-icon svg { width: 22px; height: 22px; }

    /* Reparto Cards */
    .rep-card {
        background: #fff; border-radius: 12px; border: 1px solid #e5e7eb;
        margin-bottom: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        overflow: hidden;
    }
    .rep-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 14px 20px; background: #fafbfc; border-bottom: 1px solid #f0f1f3;
        cursor: pointer; user-select: none; transition: background 0.15s;
    }
    .rep-header:hover { background: #f0f2f5; }
    .rep-header h2 {
        font-size: 14px; font-weight: 700; margin: 0; text-transform: uppercase;
        letter-spacing: 0.6px;
    }
    .rep-badge {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 30px; height: 26px; border-radius: 8px;
        font-size: 13px; font-weight: 700; color: #fff; padding: 0 10px;
    }
    .rep-meta {
        display: flex; align-items: center; gap: 16px;
    }
    .rep-meta-item {
        font-size: 12px; color: #9ca3af; display: flex; align-items: center; gap: 4px;
    }
    .rep-meta-item strong { color: #374151; font-weight: 600; }
    .chevron-icon {
        width: 20px; height: 20px; color: #c9cdd3; transition: transform 0.2s;
    }
    .rep-header.collapsed-hdr .chevron-icon { transform: rotate(-90deg); }

    /* Table */
    .rep-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .rep-table th {
        background: #fafbfc; font-size: 10px; text-transform: uppercase;
        letter-spacing: 0.5px; color: #9ca3af; font-weight: 600;
        padding: 10px 14px; border-bottom: 1px solid #f0f1f3; text-align: left;
        position: sticky; top: 0;
    }
    .rep-table td {
        padding: 9px 14px; border-bottom: 1px solid #f5f6f7;
        vertical-align: middle; color: #374151;
    }
    .rep-table tbody tr { transition: background 0.1s; }
    .rep-table tbody tr:hover td { background: #f8f9fb; }
    .rep-table .desc-cell { max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .rep-table .comm-link {
        color: #1d4ed8; font-weight: 600; text-decoration: none;
        font-family: 'SF Mono', 'Consolas', monospace; font-size: 12px;
    }
    .rep-table .comm-link:hover { text-decoration: underline; }

    .stato-badge {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 24px; height: 22px; border-radius: 6px; padding: 0 8px;
        font-size: 11px; font-weight: 700;
        background: #dbeafe; color: #1d4ed8;
    }

    .consegna-scaduta { color: #dc2626; font-weight: 600; }
    .consegna-oggi { color: #f59e0b; font-weight: 600; }
    .consegna-ok { color: #6b7280; }

    .fasi-tags { display: flex; flex-wrap: wrap; gap: 4px; }
    .fasi-tag {
        font-size: 10px; padding: 2px 8px; border-radius: 5px;
        background: #f3f4f6; color: #4b5563; white-space: nowrap;
        font-weight: 500; border: 1px solid #e5e7eb;
    }

    .rep-body { max-height: 600px; overflow-y: auto; }
    .rep-body.collapsed { display: none; }

    @media print {
        .rov-header .nav-links, .kpi-row { display: none; }
        .rep-body.collapsed { display: block !important; }
        .rep-card { break-inside: avoid; page-break-inside: avoid; }
    }
</style>

<div class="rov-header">
    <h1>Panoramica Reparti</h1>
    <div class="nav-links">
        <a href="{{ route('owner.dashboard', ['op_token' => $opToken]) }}">Dashboard</a>
        <a href="{{ route('owner.repartiOverview', ['op_token' => $opToken]) }}" class="active">Reparti</a>
        <a href="{{ route('owner.esterne', ['op_token' => $opToken]) }}">Esterne</a>
    </div>
</div>

{{-- KPI --}}
@php
    $totCommesse = $data->sum('totale');
    $totFasi = $data->sum(fn($r) => $r->commesse->sum('n_fasi'));
    $nReparti = $data->count();
    $scadute = $data->sum(fn($r) => $r->commesse->filter(fn($c) => $c->consegna && \Carbon\Carbon::parse($c->consegna)->lt(today()))->count());
@endphp
<div class="kpi-row">
    <div class="kpi-card" style="--accent:#2563eb;">
        <div style="position:absolute;top:0;left:0;right:0;height:3px;background:#2563eb;"></div>
        <div class="kpi-icon" style="background:#eff6ff;">
            <svg viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
            </svg>
        </div>
        <div class="kpi-label">Reparti attivi</div>
        <div class="kpi-value">{{ $nReparti }}</div>
        <div class="kpi-sub">su 12 totali</div>
    </div>

    <div class="kpi-card">
        <div style="position:absolute;top:0;left:0;right:0;height:3px;background:#7c3aed;"></div>
        <div class="kpi-icon" style="background:#f5f3ff;">
            <svg viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/>
            </svg>
        </div>
        <div class="kpi-label">Commesse in corso</div>
        <div class="kpi-value">{{ $totCommesse }}</div>
        <div class="kpi-sub">stato 2 — in lavorazione</div>
    </div>

    <div class="kpi-card">
        <div style="position:absolute;top:0;left:0;right:0;height:3px;background:#059669;"></div>
        <div class="kpi-icon" style="background:#ecfdf5;">
            <svg viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
            </svg>
        </div>
        <div class="kpi-label">Fasi attive</div>
        <div class="kpi-value">{{ $totFasi }}</div>
        <div class="kpi-sub">in lavorazione ora</div>
    </div>

    <div class="kpi-card">
        <div style="position:absolute;top:0;left:0;right:0;height:3px;background:{{ $scadute > 0 ? '#dc2626' : '#22c55e' }};"></div>
        <div class="kpi-icon" style="background:{{ $scadute > 0 ? '#fef2f2' : '#f0fdf4' }};">
            <svg viewBox="0 0 24 24" fill="none" stroke="{{ $scadute > 0 ? '#dc2626' : '#22c55e' }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
        </div>
        <div class="kpi-label">Scadute</div>
        <div class="kpi-value" style="color:{{ $scadute > 0 ? '#dc2626' : '#22c55e' }};">{{ $scadute }}</div>
        <div class="kpi-sub">{{ $scadute > 0 ? 'consegna superata' : 'tutto in regola' }}</div>
    </div>
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
@php
    $color = $colors[strtolower($item->reparto->nome)] ?? '#6b7280';
    $nScadute = $item->commesse->filter(fn($c) => $c->consegna && \Carbon\Carbon::parse($c->consegna)->lt(today()))->count();
@endphp
<div class="rep-card">
    <div class="rep-header" onclick="this.classList.toggle('collapsed-hdr'); this.nextElementSibling.classList.toggle('collapsed')">
        <div style="display:flex;align-items:center;gap:12px;">
            <h2 style="color:{{ $color }}">{{ $item->reparto->nome }}</h2>
            <span class="rep-badge" style="background:{{ $color }}">{{ $item->totale }}</span>
            @if($nScadute > 0)
            <span style="font-size:11px;color:#dc2626;font-weight:600;">{{ $nScadute }} scadut{{ $nScadute === 1 ? 'a' : 'e' }}</span>
            @endif
        </div>
        <div class="rep-meta">
            <div class="rep-meta-item">
                <strong>{{ $item->commesse->sum('n_fasi') }}</strong> fasi
            </div>
            <svg class="chevron-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </div>
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
                    <th style="width:55px;text-align:center">Stato</th>
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
                    <td style="text-align:center"><span class="stato-badge">2</span></td>
                    <td style="text-align:right;font-family:'SF Mono','Consolas',monospace;font-size:12px;color:#6b7280;">{{ number_format($c->priorita, 1) }}</td>
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
    Nessuna commessa in lavorazione al momento
</div>
@endif

</div>
@endsection
