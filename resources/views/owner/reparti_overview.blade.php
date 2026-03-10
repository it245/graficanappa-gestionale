@extends('layouts.app')

@section('content')
<div class="container-fluid px-3">
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

    body { background: #f0f2f5; font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; }

    /* Header */
    .rov-header {
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 28px; padding: 18px 0;
    }
    .rov-header h1 {
        font-size: 24px; font-weight: 800; color: #111827; margin: 0; letter-spacing: -0.5px;
    }
    .rov-header h1 small {
        font-size: 13px; font-weight: 500; color: #9ca3af; margin-left: 10px;
        letter-spacing: 0;
    }
    .rov-header .nav-links { display: flex; gap: 6px; }
    .rov-header .nav-links a {
        color: #4b5563; text-decoration: none; font-size: 13px; font-weight: 500;
        padding: 7px 16px; border: 1px solid #e5e7eb; border-radius: 8px;
        transition: all 0.15s; background: #fff;
    }
    .rov-header .nav-links a:hover { background: #f3f4f6; border-color: #d1d5db; }
    .rov-header .nav-links a.active {
        background: #111827; color: #fff; border-color: #111827;
    }

    /* KPI Cards */
    .kpi-row {
        display: grid; grid-template-columns: repeat(4, 1fr);
        gap: 16px; margin-bottom: 28px;
    }
    @media (max-width: 992px) { .kpi-row { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 576px) { .kpi-row { grid-template-columns: 1fr; } }

    .kpi-card {
        background: #fff; border-radius: 14px; padding: 22px 24px;
        border: 1px solid #e5e7eb; position: relative; overflow: hidden;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    }
    .kpi-accent {
        position: absolute; top: 0; left: 0; right: 0; height: 3px;
    }
    .kpi-top { display: flex; align-items: flex-start; justify-content: space-between; }
    .kpi-label {
        font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.6px;
        color: #9ca3af; margin-bottom: 10px;
    }
    .kpi-value {
        font-size: 36px; font-weight: 800; color: #111827; line-height: 1;
        letter-spacing: -1.5px;
    }
    .kpi-sub { font-size: 12px; color: #9ca3af; margin-top: 8px; font-weight: 500; }
    .kpi-icon {
        width: 44px; height: 44px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
    }
    .kpi-icon svg { width: 22px; height: 22px; }

    /* Reparto Cards */
    .rep-card {
        background: #fff; border-radius: 14px; border: 1px solid #e5e7eb;
        margin-bottom: 16px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        overflow: hidden;
        transition: box-shadow 0.2s;
    }
    .rep-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.06); }

    .rep-header {
        display: flex; align-items: center; justify-content: space-between;
        padding: 16px 22px;
        cursor: pointer; user-select: none; transition: background 0.15s;
        border-bottom: 1px solid transparent;
    }
    .rep-header:not(.collapsed-hdr) { border-bottom-color: #f0f1f3; }
    .rep-header:hover { background: #fafbfc; }
    .rep-left { display: flex; align-items: center; gap: 14px; }
    .rep-color-bar {
        width: 4px; height: 32px; border-radius: 2px; flex-shrink: 0;
    }
    .rep-header h2 {
        font-size: 14px; font-weight: 700; margin: 0; text-transform: uppercase;
        letter-spacing: 0.5px; color: #111827;
    }
    .rep-badge {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 28px; height: 24px; border-radius: 7px;
        font-size: 12px; font-weight: 700; color: #fff; padding: 0 9px;
    }
    .rep-scadute-tag {
        font-size: 11px; font-weight: 600; color: #dc2626;
        background: #fef2f2; padding: 3px 10px; border-radius: 6px;
        border: 1px solid #fecaca;
    }
    .rep-right { display: flex; align-items: center; gap: 20px; }
    .rep-stat {
        text-align: center;
    }
    .rep-stat-value { font-size: 16px; font-weight: 700; color: #374151; line-height: 1; }
    .rep-stat-label { font-size: 10px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.4px; margin-top: 2px; }
    .chevron-icon {
        width: 20px; height: 20px; color: #c9cdd3; transition: transform 0.25s ease;
    }
    .rep-header.collapsed-hdr .chevron-icon { transform: rotate(-90deg); }

    /* Table */
    .rep-body { max-height: 700px; overflow-y: auto; }
    .rep-body.collapsed { display: none; }
    .rep-body::-webkit-scrollbar { width: 6px; }
    .rep-body::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 3px; }
    .rep-body::-webkit-scrollbar-track { background: transparent; }

    .rep-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13px; }
    .rep-table thead { position: sticky; top: 0; z-index: 2; }
    .rep-table th {
        background: #f9fafb; font-size: 10px; text-transform: uppercase;
        letter-spacing: 0.6px; color: #6b7280; font-weight: 600;
        padding: 11px 16px; border-bottom: 2px solid #e5e7eb; text-align: left;
        white-space: nowrap;
    }
    .rep-table td {
        padding: 12px 16px; border-bottom: 1px solid #f3f4f6;
        vertical-align: middle; color: #374151; font-size: 13px;
    }
    .rep-table tbody tr { transition: background 0.1s; }
    .rep-table tbody tr:hover td { background: #f8fafc; }
    .rep-table tbody tr:last-child td { border-bottom: none; }

    /* Row scaduta */
    .rep-table tbody tr.row-scaduta td { background: #fff5f5; }
    .rep-table tbody tr.row-scaduta:hover td { background: #fef2f2; }

    /* Commessa link */
    .comm-cell {
        display: flex; align-items: center; gap: 8px;
    }
    .comm-link {
        color: #1d4ed8; font-weight: 600; text-decoration: none;
        font-family: 'SF Mono', 'Cascadia Code', 'Consolas', monospace; font-size: 12px;
        padding: 3px 8px; border-radius: 5px; background: #eff6ff;
        transition: all 0.15s; white-space: nowrap;
    }
    .comm-link:hover { background: #dbeafe; color: #1e40af; text-decoration: none; }

    /* Cliente */
    .cliente-cell {
        max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        font-weight: 500; color: #1f2937;
    }

    /* Descrizione */
    .desc-cell {
        max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
        color: #6b7280; font-size: 12px;
    }

    /* Qta */
    .qta-cell {
        text-align: right; font-weight: 600; color: #374151;
        font-variant-numeric: tabular-nums;
    }

    /* Consegna */
    .consegna-cell { text-align: center; white-space: nowrap; font-weight: 500; font-size: 12px; }
    .consegna-scaduta {
        color: #dc2626; font-weight: 700;
        background: #fef2f2; padding: 3px 10px; border-radius: 5px;
        display: inline-block;
    }
    .consegna-oggi {
        color: #b45309; font-weight: 700;
        background: #fffbeb; padding: 3px 10px; border-radius: 5px;
        display: inline-block;
    }
    .consegna-ok { color: #6b7280; }

    /* Stato badge */
    .stato-badge {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 4px 10px; border-radius: 6px;
        font-size: 11px; font-weight: 700; white-space: nowrap;
        background: #dbeafe; color: #1e40af;
    }
    .stato-badge::before {
        content: ''; width: 6px; height: 6px; border-radius: 50%;
        background: #3b82f6; flex-shrink: 0;
        animation: pulse-dot 2s ease-in-out infinite;
    }
    @keyframes pulse-dot {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.4; }
    }

    /* Priorita */
    .priorita-cell {
        text-align: right;
        font-family: 'SF Mono', 'Cascadia Code', 'Consolas', monospace;
        font-size: 12px; color: #9ca3af; font-weight: 500;
    }

    /* Fasi tags */
    .fasi-tags { display: flex; flex-wrap: wrap; gap: 4px; }
    .fasi-tag {
        font-size: 10px; padding: 3px 9px; border-radius: 6px;
        background: #f3f4f6; color: #4b5563; white-space: nowrap;
        font-weight: 600; letter-spacing: 0.2px;
    }

    /* Empty state */
    .empty-state {
        text-align: center; padding: 80px 20px;
    }
    .empty-state svg { width: 48px; height: 48px; color: #d1d5db; margin-bottom: 16px; }
    .empty-state p { color: #9ca3af; font-size: 15px; font-weight: 500; }

    /* Footer count */
    .rep-footer {
        padding: 10px 22px; background: #f9fafb;
        border-top: 1px solid #f0f1f3;
        font-size: 11px; color: #9ca3af; font-weight: 500;
        display: flex; align-items: center; justify-content: space-between;
    }

    @media print {
        .rov-header .nav-links, .kpi-row { display: none; }
        .rep-body.collapsed { display: block !important; }
        .rep-card { break-inside: avoid; page-break-inside: avoid; box-shadow: none; }
        .kpi-card { box-shadow: none; }
    }
</style>

<div class="rov-header">
    <h1>Panoramica Reparti <small>Commesse in lavorazione</small></h1>
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
    <div class="kpi-card">
        <div class="kpi-accent" style="background:#2563eb;"></div>
        <div class="kpi-top">
            <div>
                <div class="kpi-label">Reparti attivi</div>
                <div class="kpi-value">{{ $nReparti }}</div>
                <div class="kpi-sub">su {{ $totReparti }} totali</div>
            </div>
            <div class="kpi-icon" style="background:#eff6ff;">
                <svg viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-accent" style="background:#7c3aed;"></div>
        <div class="kpi-top">
            <div>
                <div class="kpi-label">Commesse in corso</div>
                <div class="kpi-value">{{ $totCommesse }}</div>
                <div class="kpi-sub">stato 2 — in lavorazione</div>
            </div>
            <div class="kpi-icon" style="background:#f5f3ff;">
                <svg viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-accent" style="background:#059669;"></div>
        <div class="kpi-top">
            <div>
                <div class="kpi-label">Fasi attive</div>
                <div class="kpi-value">{{ $totFasi }}</div>
                <div class="kpi-sub">in lavorazione ora</div>
            </div>
            <div class="kpi-icon" style="background:#ecfdf5;">
                <svg viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                </svg>
            </div>
        </div>
    </div>

    <div class="kpi-card">
        <div class="kpi-accent" style="background:{{ $scadute > 0 ? '#dc2626' : '#22c55e' }};"></div>
        <div class="kpi-top">
            <div>
                <div class="kpi-label">Scadute</div>
                <div class="kpi-value" style="color:{{ $scadute > 0 ? '#dc2626' : '#22c55e' }};">{{ $scadute }}</div>
                <div class="kpi-sub">{{ $scadute > 0 ? 'consegna superata' : 'tutto in regola' }}</div>
            </div>
            <div class="kpi-icon" style="background:{{ $scadute > 0 ? '#fef2f2' : '#f0fdf4' }};">
                <svg viewBox="0 0 24 24" fill="none" stroke="{{ $scadute > 0 ? '#dc2626' : '#22c55e' }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    @if($scadute > 0)
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                    @else
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                    @endif
                </svg>
            </div>
        </div>
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
    $totQta = $item->commesse->sum('qta');
@endphp
<div class="rep-card">
    <div class="rep-header" onclick="this.classList.toggle('collapsed-hdr'); this.nextElementSibling.classList.toggle('collapsed')">
        <div class="rep-left">
            <div class="rep-color-bar" style="background:{{ $color }};"></div>
            <h2>{{ $item->reparto->nome }}</h2>
            <span class="rep-badge" style="background:{{ $color }}">{{ $item->totale }}</span>
            @if($nScadute > 0)
            <span class="rep-scadute-tag">{{ $nScadute }} scadut{{ $nScadute === 1 ? 'a' : 'e' }}</span>
            @endif
        </div>
        <div class="rep-right">
            <div class="rep-stat">
                <div class="rep-stat-value">{{ $item->commesse->sum('n_fasi') }}</div>
                <div class="rep-stat-label">Fasi</div>
            </div>
            <div class="rep-stat">
                <div class="rep-stat-value">{{ number_format($totQta, 0, ',', '.') }}</div>
                <div class="rep-stat-label">Pezzi tot.</div>
            </div>
            @php $oreTotReparto = $item->commesse->sum('ore_previste'); @endphp
            <div class="rep-stat">
                <div class="rep-stat-value">{{ number_format($oreTotReparto, 1, ',', '.') }}</div>
                <div class="rep-stat-label">Ore tot.</div>
            </div>
            <svg class="chevron-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="6 9 12 15 18 9"/>
            </svg>
        </div>
    </div>
    <div class="rep-body">
        <table class="rep-table">
            <thead>
                <tr>
                    <th>Commessa</th>
                    <th>Cliente</th>
                    <th>Descrizione</th>
                    <th style="text-align:right">Qta</th>
                    <th style="text-align:center">Consegna</th>
                    <th style="text-align:center">Stato</th>
                    <th style="text-align:right">Priorita</th>
                    <th>Fasi</th>
                </tr>
            </thead>
            <tbody>
            @foreach($item->commesse as $c)
                @php
                    $scaduta = $c->consegna && \Carbon\Carbon::parse($c->consegna)->lt(today());
                    $oggi = $c->consegna && \Carbon\Carbon::parse($c->consegna)->isToday();
                @endphp
                <tr class="{{ $scaduta ? 'row-scaduta' : '' }}">
                    <td>
                        <a class="comm-link" href="{{ route('owner.dettaglioCommessa', ['commessa' => $c->commessa, 'op_token' => $opToken]) }}">
                            {{ $c->commessa }}
                        </a>
                    </td>
                    <td><div class="cliente-cell" title="{{ $c->cliente }}">{{ $c->cliente }}</div></td>
                    <td><div class="desc-cell" title="{{ $c->descrizione }}">{{ $c->descrizione }}</div></td>
                    <td class="qta-cell">{{ number_format($c->qta, 0, ',', '.') }}</td>
                    <td class="consegna-cell">
                        @if($scaduta)
                            <span class="consegna-scaduta">{{ \Carbon\Carbon::parse($c->consegna)->format('d/m') }}</span>
                        @elseif($oggi)
                            <span class="consegna-oggi">OGGI</span>
                        @elseif($c->consegna)
                            <span class="consegna-ok">{{ \Carbon\Carbon::parse($c->consegna)->format('d/m') }}</span>
                        @else
                            <span style="color:#d1d5db;">—</span>
                        @endif
                    </td>
                    <td style="text-align:center"><span class="stato-badge">In corso</span></td>
                    <td class="priorita-cell">{{ number_format($c->priorita, 1) }}</td>
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
        <div class="rep-footer">
            <span>{{ $item->totale }} commess{{ $item->totale === 1 ? 'a' : 'e' }} &middot; {{ $item->commesse->sum('n_fasi') }} fasi</span>
            <span>{{ number_format($totQta, 0, ',', '.') }} pezzi totali</span>
        </div>
    </div>
</div>
@endforeach

@if($data->isEmpty())
<div class="empty-state">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
    </svg>
    <p>Nessuna commessa in lavorazione al momento</p>
</div>
@endif

</div>
@endsection
