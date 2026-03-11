@extends('layouts.app')

@section('title', 'Report Ore')

@section('content')
@php
    $totPreviste = $commesse->sum('ore_previste');
    $totLavorate = $commesse->sum('ore_lavorate');
    $diff = $totLavorate - $totPreviste;
    $efficienza = $totPreviste > 0 ? round(($totLavorate / $totPreviste) * 100) : 0;
    $commesseSforate = $commesse->filter(fn($c) => $c->ore_lavorate > $c->ore_previste && $c->ore_lavorate > 0)->count();
    $commesseComplete = $commesse->filter(fn($c) => $c->num_terminate == $c->num_fasi)->count();
    $commesseAttive = $commesse->filter(fn($c) => $c->num_avviate > 0)->count();
    $mediaOreCommessa = $commesse->count() > 0 ? round($totLavorate / $commesse->count(), 1) : 0;
@endphp

<style>
    .ro-page { background:#f0f2f5; min-height:100vh; font-family:'Inter','Segoe UI',system-ui,sans-serif; }
    .ro-topbar { background:#fff; border-bottom:1px solid #e5e7eb; padding:14px 24px; position:sticky; top:0; z-index:100; box-shadow:0 1px 3px rgba(0,0,0,.04); }
    .ro-topbar-inner { max-width:1440px; margin:0 auto; display:flex; align-items:center; justify-content:space-between; }
    .ro-logo { display:flex; align-items:center; gap:10px; }
    .ro-logo-icon { width:34px; height:34px; border-radius:8px; background:linear-gradient(135deg,#4f46e5,#7c3aed); display:flex; align-items:center; justify-content:center; }
    .ro-title { font-size:16px; font-weight:700; color:#111827; letter-spacing:-0.3px; margin:0; }
    .ro-subtitle { font-size:11px; color:#9ca3af; }
    .ro-back { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:6px; background:#f3f4f6; color:#6b7280; text-decoration:none; font-size:12px; font-weight:500; border:1px solid #e5e7eb; transition:all .15s; }
    .ro-back:hover { background:#e5e7eb; color:#374151; }
    .ro-container { max-width:1440px; margin:0 auto; padding:20px 24px; }

    .ro-kpi-grid { display:grid; grid-template-columns:repeat(6, 1fr); gap:12px; margin-bottom:20px; }
    .ro-kpi { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:16px 18px; }
    .ro-kpi-label { font-size:10px; color:#9ca3af; text-transform:uppercase; letter-spacing:0.6px; font-weight:600; margin-bottom:6px; }
    .ro-kpi-value { font-size:26px; font-weight:700; letter-spacing:-0.5px; line-height:1; }
    .ro-kpi-unit { font-size:13px; font-weight:400; color:#9ca3af; }
    .ro-kpi-sub { font-size:11px; color:#9ca3af; margin-top:4px; }

    .ro-section { background:#fff; border:1px solid #e5e7eb; border-radius:10px; margin-bottom:16px; overflow:hidden; }
    .ro-section-header { padding:14px 18px; border-bottom:1px solid #f3f4f6; display:flex; align-items:center; justify-content:space-between; }
    .ro-section-title { font-size:13px; font-weight:600; color:#374151; text-transform:uppercase; letter-spacing:0.3px; }

    .ro-filter { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:14px 18px; margin-bottom:16px; display:flex; align-items:flex-end; gap:12px; flex-wrap:wrap; }
    .ro-filter label { display:block; font-size:10px; color:#9ca3af; text-transform:uppercase; letter-spacing:0.5px; font-weight:600; margin-bottom:3px; }
    .ro-filter input, .ro-filter select { padding:7px 10px; border-radius:6px; border:1px solid #d1d5db; font-size:12px; color:#374151; outline:none; background:#fff; min-width:160px; }
    .ro-filter input:focus, .ro-filter select:focus { border-color:#4f46e5; box-shadow:0 0 0 2px rgba(79,70,229,.1); }
    .ro-btn-primary { padding:7px 18px; border-radius:6px; background:#4f46e5; color:#fff; border:none; font-size:12px; font-weight:600; cursor:pointer; }
    .ro-btn-primary:hover { background:#4338ca; }
    .ro-btn-ghost { padding:7px 14px; border-radius:6px; background:transparent; color:#6b7280; border:1px solid #d1d5db; font-size:12px; text-decoration:none; font-weight:500; }
    .ro-btn-ghost:hover { background:#f3f4f6; }

    .ro-table { width:100%; border-collapse:collapse; font-size:12px; }
    .ro-table th { padding:10px 12px; text-align:left; color:#9ca3af; font-size:10px; text-transform:uppercase; letter-spacing:0.5px; font-weight:600; background:#fafafa; border-bottom:1px solid #f3f4f6; }
    .ro-table td { padding:10px 12px; border-bottom:1px solid #f3f4f6; color:#374151; }
    .ro-table tr.ro-row:hover { background:#f9fafb; }
    .ro-table .text-right { text-align:right; }
    .ro-table .text-center { text-align:center; }

    .ro-tag { display:inline-block; padding:2px 7px; border-radius:4px; font-size:10px; font-weight:600; }
    .ro-tag-green { background:#dcfce7; color:#166534; }
    .ro-tag-yellow { background:#fef9c3; color:#854d0e; }
    .ro-tag-red { background:#fee2e2; color:#991b1b; }
    .ro-tag-blue { background:#dbeafe; color:#1e40af; }
    .ro-tag-gray { background:#f3f4f6; color:#6b7280; }
    .ro-tag-cyan { background:#cffafe; color:#155e75; }

    .ro-chevron { transition:transform .2s; cursor:pointer; }
    .ro-detail { display:none; }
    .ro-detail-inner { margin:0 0 0 20px; border-left:2px solid #e0e7ff; padding:10px 0 10px 14px; background:#fafbff; }

    .ro-bar { height:6px; border-radius:3px; background:#f3f4f6; overflow:hidden; min-width:60px; }
    .ro-bar-fill { height:100%; border-radius:3px; transition:width .3s; }

    .ro-reparto-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(200px, 1fr)); gap:10px; padding:14px 18px; }
    .ro-reparto-card { padding:12px 14px; border-radius:8px; background:#fafafa; border:1px solid #f3f4f6; }
    .ro-reparto-name { font-size:12px; font-weight:600; color:#374151; margin-bottom:6px; }
    .ro-reparto-stat { font-size:11px; color:#6b7280; display:flex; justify-content:space-between; margin-top:3px; }

    .ro-border-red { border-left:3px solid #ef4444; }
    .ro-border-green { border-left:3px solid #10b981; }
    .ro-border-none { border-left:3px solid transparent; }

    .ro-eff-ring { display:inline-flex; align-items:center; justify-content:center; width:56px; height:56px; border-radius:50%; position:relative; }
    .ro-eff-ring svg { transform:rotate(-90deg); }

    @media (max-width:768px) {
        .ro-kpi-grid { grid-template-columns:repeat(2, 1fr); }
        .ro-reparto-grid { grid-template-columns:1fr 1fr; }
        .ro-container { padding:12px; }
    }
</style>

<div class="ro-page">
    {{-- Top bar --}}
    <div class="ro-topbar">
        <div class="ro-topbar-inner">
            <div class="ro-logo">
                <div class="ro-logo-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div>
                    <h1 class="ro-title">Report Ore</h1>
                    <span class="ro-subtitle">Analisi produttivita per commessa e reparto</span>
                </div>
            </div>
            <a href="{{ route('owner.dashboard', ['op_token' => request()->query('op_token')]) }}" class="ro-back">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                Dashboard
            </a>
        </div>
    </div>

    <div class="ro-container">

        {{-- Filtri --}}
        <form method="GET" class="ro-filter">
            @if(request()->query('op_token'))
                <input type="hidden" name="op_token" value="{{ request()->query('op_token') }}">
            @endif
            <div>
                <label>Commessa</label>
                <input type="text" name="commessa" value="{{ $filtroCommessa ?? '' }}" placeholder="Cerca...">
            </div>
            <div>
                <label>Reparto</label>
                <select name="reparto">
                    <option value="">Tutti i reparti</option>
                    @foreach($reparti as $id => $nome)
                        <option value="{{ $id }}" {{ ($filtroReparto ?? '') == $id ? 'selected' : '' }}>{{ ucfirst($nome) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="ro-btn-primary">Filtra</button>
            <a href="{{ route('owner.reportOre', ['op_token' => request()->query('op_token')]) }}" class="ro-btn-ghost">Reset</a>
        </form>

        {{-- KPI --}}
        <div class="ro-kpi-grid">
            <div class="ro-kpi">
                <div class="ro-kpi-label">Ore Previste</div>
                <div class="ro-kpi-value" style="color:#4f46e5;">{{ number_format($totPreviste, 1) }}<span class="ro-kpi-unit">h</span></div>
                <div class="ro-kpi-sub">Budget totale</div>
            </div>
            <div class="ro-kpi">
                <div class="ro-kpi-label">Ore Lavorate</div>
                <div class="ro-kpi-value" style="color:#059669;">{{ number_format($totLavorate, 1) }}<span class="ro-kpi-unit">h</span></div>
                <div class="ro-kpi-sub">Effettive registrate</div>
            </div>
            <div class="ro-kpi">
                <div class="ro-kpi-label">Scostamento</div>
                <div class="ro-kpi-value" style="color:{{ $diff > 0 ? '#dc2626' : '#059669' }};">{{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 1) }}<span class="ro-kpi-unit">h</span></div>
                <div class="ro-kpi-sub">{{ $diff > 0 ? 'Sopra budget' : 'Sotto budget' }}</div>
            </div>
            <div class="ro-kpi">
                <div class="ro-kpi-label">Efficienza</div>
                <div class="ro-kpi-value" style="color:{{ $efficienza > 100 ? '#dc2626' : '#059669' }};">{{ $efficienza }}<span class="ro-kpi-unit">%</span></div>
                <div class="ro-kpi-sub">Lavorate / previste</div>
            </div>
            <div class="ro-kpi">
                <div class="ro-kpi-label">Sforate</div>
                <div class="ro-kpi-value" style="color:{{ $commesseSforate > 0 ? '#dc2626' : '#059669' }};">{{ $commesseSforate }}<span class="ro-kpi-unit"> / {{ $commesse->count() }}</span></div>
                <div class="ro-kpi-sub">Commesse oltre budget</div>
            </div>
            <div class="ro-kpi">
                <div class="ro-kpi-label">Media / Commessa</div>
                <div class="ro-kpi-value" style="color:#374151;">{{ $mediaOreCommessa }}<span class="ro-kpi-unit">h</span></div>
                <div class="ro-kpi-sub">{{ $commesseComplete }} completate, {{ $commesseAttive }} in corso</div>
            </div>
        </div>

        {{-- Grafici --}}
        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; margin-bottom:16px;">
            {{-- Grafico barre: previste vs lavorate per reparto --}}
            <div class="ro-section" style="margin-bottom:0;">
                <div class="ro-section-header">
                    <span class="ro-section-title">Ore per Reparto</span>
                </div>
                <div style="padding:14px 18px; height:280px;">
                    <canvas id="chartReparti"></canvas>
                </div>
            </div>

            {{-- Grafico torta: fonti dati ore --}}
            <div class="ro-section" style="margin-bottom:0;">
                <div class="ro-section-header">
                    <span class="ro-section-title">Fonte Dati Ore</span>
                </div>
                <div style="padding:14px 18px; height:280px;">
                    <canvas id="chartFonti"></canvas>
                </div>
            </div>

            {{-- Top 10 commesse sforate --}}
            <div class="ro-section" style="margin-bottom:0;">
                <div class="ro-section-header">
                    <span class="ro-section-title">Top Commesse Sforate</span>
                </div>
                <div style="padding:14px 18px; height:280px;">
                    <canvas id="chartSforate"></canvas>
                </div>
            </div>
        </div>

        {{-- Grafico efficienza per reparto --}}
        <div class="ro-section">
            <div class="ro-section-header">
                <span class="ro-section-title">Efficienza per Reparto</span>
                <span style="font-size:11px; color:#9ca3af;">100% = perfettamente in budget</span>
            </div>
            <div style="padding:14px 18px; height:200px;">
                <canvas id="chartEfficienza"></canvas>
            </div>
        </div>

        {{-- Ore per reparto cards --}}
        <div class="ro-section">
            <div class="ro-section-header">
                <span class="ro-section-title">Ore per Reparto</span>
                <span style="font-size:11px; color:#9ca3af;">{{ $orePerReparto->count() }} reparti attivi</span>
            </div>
            <div class="ro-reparto-grid">
                @foreach($orePerReparto as $nomeReparto => $stats)
                @php
                    $rDiff = $stats->lavorate - $stats->previste;
                    $rPct = $stats->previste > 0 ? min(round(($stats->lavorate / $stats->previste) * 100), 150) : 0;
                @endphp
                <div class="ro-reparto-card">
                    <div class="ro-reparto-name">{{ ucfirst($nomeReparto) }}</div>
                    <div class="ro-bar" style="margin:6px 0;">
                        <div class="ro-bar-fill" style="width:{{ min($rPct, 100) }}%; background:{{ $rPct > 100 ? '#ef4444' : '#4f46e5' }};"></div>
                    </div>
                    <div class="ro-reparto-stat">
                        <span>Previste: <strong>{{ $stats->previste }}h</strong></span>
                        <span>Lavorate: <strong style="color:{{ $rDiff > 0 ? '#dc2626' : '#059669' }};">{{ $stats->lavorate }}h</strong></span>
                    </div>
                    <div class="ro-reparto-stat">
                        <span>{{ $stats->fasi }} fasi</span>
                        <span style="color:{{ $rDiff > 0 ? '#dc2626' : '#059669' }}; font-weight:600;">{{ $rDiff > 0 ? '+' : '' }}{{ number_format($rDiff, 1) }}h</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Tabella commesse --}}
        <div class="ro-section">
            <div class="ro-section-header">
                <span class="ro-section-title">Dettaglio Commesse</span>
                <span style="font-size:11px; color:#9ca3af;">{{ $commesse->count() }} commesse</span>
            </div>
            <div style="overflow-x:auto;">
                <table class="ro-table">
                    <thead>
                        <tr>
                            <th style="width:28px;"></th>
                            <th>Commessa</th>
                            <th>Descrizione</th>
                            <th>Cliente</th>
                            <th>Consegna</th>
                            <th class="text-center">Fasi</th>
                            <th class="text-right">Previste</th>
                            <th class="text-right">Lavorate</th>
                            <th class="text-right">Scostamento</th>
                            <th style="width:70px;">Rapp.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($commesse as $c)
                        @php
                            $d = $c->ore_lavorate - $c->ore_previste;
                            $sforata = $c->ore_lavorate > 0 && $d > 0;
                            $completata = $c->num_terminate == $c->num_fasi;
                            $rowId = 'fasi-' . str_replace(['-', '.'], '_', $c->commessa);
                            $pct = $c->ore_previste > 0 ? round(($c->ore_lavorate / $c->ore_previste) * 100) : 0;
                            $consegna = $c->data_consegna ? \Carbon\Carbon::parse($c->data_consegna) : null;
                            $inRitardo = $consegna && $consegna->lt(now()) && !$completata;
                        @endphp
                        <tr class="ro-row {{ $sforata ? 'ro-border-red' : ($completata && $d <= 0 ? 'ro-border-green' : 'ro-border-none') }}" onclick="toggleDetail('{{ $rowId }}')" style="cursor:pointer;">
                            <td>
                                <svg id="icon-{{ $rowId }}" class="ro-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                            </td>
                            <td><strong style="color:#111827;">{{ $c->commessa }}</strong></td>
                            <td style="max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; color:#6b7280;" title="{{ $c->descrizione }}">{{ Str::limit($c->descrizione, 40) }}</td>
                            <td style="color:#6b7280;">{{ Str::limit($c->cliente, 25) }}</td>
                            <td>
                                @if($consegna)
                                    <span class="{{ $inRitardo ? 'ro-tag ro-tag-red' : '' }}" style="{{ !$inRitardo ? 'font-size:12px; color:#6b7280;' : '' }}">{{ $consegna->format('d/m/Y') }}</span>
                                @else
                                    <span style="color:#d1d5db;">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($completata)
                                    <span class="ro-tag ro-tag-green">{{ $c->num_terminate }}/{{ $c->num_fasi }}</span>
                                @elseif($c->num_avviate > 0)
                                    <span class="ro-tag ro-tag-yellow">{{ $c->num_terminate }}/{{ $c->num_fasi }}</span>
                                @else
                                    <span class="ro-tag ro-tag-gray">{{ $c->num_terminate }}/{{ $c->num_fasi }}</span>
                                @endif
                            </td>
                            <td class="text-right" style="font-weight:600; color:#4f46e5;">{{ number_format($c->ore_previste, 1) }}h</td>
                            <td class="text-right" style="font-weight:600; color:#059669;">{{ number_format($c->ore_lavorate, 1) }}h</td>
                            <td class="text-right" style="font-weight:700; color:{{ $sforata ? '#dc2626' : '#059669' }};">
                                {{ $d > 0 ? '+' : '' }}{{ number_format($d, 1) }}h
                            </td>
                            <td>
                                <div class="ro-bar">
                                    <div class="ro-bar-fill" style="width:{{ min($pct, 100) }}%; background:{{ $pct > 100 ? '#ef4444' : '#4f46e5' }};"></div>
                                </div>
                                <div style="font-size:10px; color:#9ca3af; text-align:center; margin-top:2px;">{{ $pct }}%</div>
                            </td>
                        </tr>
                        <tr class="ro-detail" id="{{ $rowId }}">
                            <td colspan="10" style="padding:0; border:none;">
                                <div class="ro-detail-inner">
                                    <div style="display:flex; gap:16px; margin-bottom:8px; font-size:11px; color:#6b7280;">
                                        <span>Responsabile: <strong style="color:#374151;">{{ $c->responsabile }}</strong></span>
                                        @if($consegna)
                                        <span>Consegna: <strong style="color:#374151;">{{ $consegna->format('d/m/Y') }}</strong></span>
                                        @endif
                                        <span>Fasi totali: <strong style="color:#374151;">{{ $c->num_fasi }}</strong></span>
                                    </div>
                                    <table class="ro-table" style="font-size:11px;">
                                        <thead>
                                            <tr>
                                                <th>Fase</th>
                                                <th>Reparto</th>
                                                <th>Stato</th>
                                                <th class="text-right">Qta Carta</th>
                                                <th class="text-right">Previste</th>
                                                <th class="text-right">Lavorate</th>
                                                <th>Fonte</th>
                                                <th class="text-right">Diff</th>
                                                <th>Operatori</th>
                                                <th>Inizio</th>
                                                <th>Fine</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($c->fasi as $fase)
                                            @php
                                                $df = $fase->ore_lavorate - $fase->ore_previste;
                                                $dataInizio = null;
                                                $dataFine = null;
                                                if ($fase->operatori->isNotEmpty()) {
                                                    $dataInizio = $fase->operatori->sortBy('pivot.data_inizio')->first()?->pivot->data_inizio;
                                                    $dataFine = $fase->operatori->sortByDesc('pivot.data_fine')->first()?->pivot->data_fine;
                                                }
                                                if (!$dataInizio) $dataInizio = $fase->getAttributes()['data_inizio'] ?? null;
                                                if (!$dataFine) $dataFine = $fase->getAttributes()['data_fine'] ?? null;
                                            @endphp
                                            <tr>
                                                <td style="font-weight:500; color:#111827;">{{ $fase->faseCatalogo->nome_display ?? $fase->fase }}</td>
                                                <td style="color:#6b7280;">{{ optional($fase->faseCatalogo)->reparto->nome ?? '-' }}</td>
                                                <td>
                                                    @switch($fase->stato)
                                                        @case(0) <span class="ro-tag ro-tag-gray">Caricato</span> @break
                                                        @case(1) <span class="ro-tag ro-tag-cyan">Pronto</span> @break
                                                        @case(2) <span class="ro-tag ro-tag-yellow">Avviato</span> @break
                                                        @case(3) <span class="ro-tag ro-tag-green">Terminato</span> @break
                                                        @case(4) <span class="ro-tag ro-tag-blue">Consegnato</span> @break
                                                    @endswitch
                                                </td>
                                                <td class="text-right" style="color:#6b7280;">{{ number_format($fase->ordine->qta_carta ?? 0, 0, ',', '.') }}</td>
                                                <td class="text-right" style="color:#4f46e5;">{{ number_format($fase->ore_previste, 2) }}h</td>
                                                <td class="text-right" style="color:#059669; font-weight:600;">{{ number_format($fase->ore_lavorate, 2) }}h</td>
                                                <td>
                                                    @if($fase->fonte_ore === 'Prinect')
                                                        <span class="ro-tag ro-tag-blue">Prinect</span>
                                                    @elseif($fase->fonte_ore === 'MES')
                                                        <span class="ro-tag ro-tag-gray">MES</span>
                                                    @else
                                                        <span style="color:#d1d5db;">-</span>
                                                    @endif
                                                </td>
                                                <td class="text-right" style="font-weight:600; color:{{ $df > 0 ? '#dc2626' : '#059669' }};">
                                                    @if($fase->ore_lavorate > 0)
                                                        {{ $df > 0 ? '+' : '' }}{{ number_format($df, 2) }}h
                                                    @else
                                                        <span style="color:#d1d5db;">-</span>
                                                    @endif
                                                </td>
                                                <td style="color:#6b7280;">
                                                    @foreach($fase->operatori as $op)
                                                        {{ $op->nome }} {{ $op->cognome }}@if(!$loop->last), @endif
                                                    @endforeach
                                                    @if($fase->operatori->isEmpty()) - @endif
                                                </td>
                                                <td style="color:#9ca3af; font-size:10px;">{{ $dataInizio ? \Carbon\Carbon::parse($dataInizio)->format('d/m H:i') : '-' }}</td>
                                                <td style="color:#9ca3af; font-size:10px;">{{ $dataFine ? \Carbon\Carbon::parse($dataFine)->format('d/m H:i') : '-' }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if($commesse->isEmpty())
        <div style="text-align:center; padding:60px 0;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <p style="color:#9ca3af; margin-top:16px; font-size:14px;">Nessuna commessa con ore lavorate trovata.</p>
        </div>
        @endif
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
function toggleDetail(id) {
    var row = document.getElementById(id);
    var icon = document.getElementById('icon-' + id);
    if (row.style.display === 'none' || row.style.display === '') {
        row.style.display = 'table-row';
        if (icon) icon.style.transform = 'rotate(90deg)';
    } else {
        row.style.display = 'none';
        if (icon) icon.style.transform = 'rotate(0deg)';
    }
}

// === Dati per grafici ===
var repartiLabels = {!! json_encode($orePerReparto->keys()->values()) !!};
var repartiPreviste = {!! json_encode($orePerReparto->pluck('previste')->values()) !!};
var repartiLavorate = {!! json_encode($orePerReparto->pluck('lavorate')->values()) !!};

@php
    $fontePrinect = $commesse->flatMap(fn($c) => $c->fasi)->where('fonte_ore', 'Prinect')->sum('ore_lavorate');
    $fonteMES = $commesse->flatMap(fn($c) => $c->fasi)->where('fonte_ore', 'MES')->sum('ore_lavorate');
    $fonteND = $totLavorate - $fontePrinect - $fonteMES;

    $sforate = $commesse->filter(fn($c) => $c->ore_lavorate > $c->ore_previste && $c->ore_lavorate > 0)
        ->sortByDesc(fn($c) => $c->ore_lavorate - $c->ore_previste)
        ->take(10);
@endphp

// Grafico barre: ore per reparto
var ctxR = document.getElementById('chartReparti');
if (ctxR) {
    new Chart(ctxR, {
        type: 'bar',
        data: {
            labels: repartiLabels.map(function(l){ return l.charAt(0).toUpperCase()+l.slice(1); }),
            datasets: [
                { label: 'Previste', data: repartiPreviste, backgroundColor: '#a5b4fc', borderRadius: 4, barPercentage: 0.7 },
                { label: 'Lavorate', data: repartiLavorate, backgroundColor: '#4f46e5', borderRadius: 4, barPercentage: 0.7 }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                y: { grid: { color: '#f3f4f6' }, ticks: { font: { size: 10 }, callback: function(v){ return v+'h'; } } }
            }
        }
    });
}

// Grafico torta: fonte dati
var ctxF = document.getElementById('chartFonti');
if (ctxF) {
    new Chart(ctxF, {
        type: 'doughnut',
        data: {
            labels: ['Prinect', 'MES', 'Non registrate'],
            datasets: [{
                data: [{{ round($fontePrinect, 1) }}, {{ round($fonteMES, 1) }}, {{ round(max($fonteND, 0), 1) }}],
                backgroundColor: ['#4f46e5', '#059669', '#d1d5db'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false, cutout: '65%',
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 }, padding: 12 } },
                tooltip: { callbacks: { label: function(c){ return c.label + ': ' + c.parsed + 'h'; } } }
            }
        }
    });
}

// Grafico orizzontale: top commesse sforate
var sforateLabels = {!! json_encode($sforate->pluck('commessa')->values()) !!};
var ctxS = document.getElementById('chartSforate');
if (ctxS) {
    var chartSforate = new Chart(ctxS, {
        type: 'bar',
        data: {
            labels: sforateLabels,
            datasets: [{
                label: 'Ore extra',
                data: {!! json_encode($sforate->map(fn($c) => round($c->ore_lavorate - $c->ore_previste, 1))->values()) !!},
                backgroundColor: '#ef4444',
                borderRadius: 4,
                barPercentage: 0.7
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: '#f3f4f6' }, ticks: { font: { size: 10 }, callback: function(v){ return '+'+v+'h'; } } },
                y: { grid: { display: false }, ticks: { font: { size: 10 }, color: '#4f46e5', font: { weight: 'bold' } } }
            },
            onClick: function(e, elements) {
                if (elements.length > 0) {
                    var idx = elements[0].index;
                    var commessa = sforateLabels[idx];
                    var rowId = 'fasi-' + commessa.replace(/[-\.]/g, '_');
                    var row = document.getElementById(rowId);
                    if (row) {
                        // Apri il dettaglio
                        if (row.style.display === 'none' || row.style.display === '') {
                            toggleDetail(rowId);
                        }
                        // Scrolla alla riga
                        row.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        // Flash highlight
                        row.previousElementSibling.style.background = '#fef2f2';
                        setTimeout(function(){ row.previousElementSibling.style.background = ''; }, 2000);
                    }
                }
            }
        }
    });
    // Cursor pointer sulle barre
    ctxS.style.cursor = 'pointer';
}

// Grafico efficienza per reparto
var ctxE = document.getElementById('chartEfficienza');
if (ctxE) {
    var effLabels = repartiLabels.map(function(l){ return l.charAt(0).toUpperCase()+l.slice(1); });
    var effData = repartiPreviste.map(function(p, i){ return p > 0 ? Math.round((repartiLavorate[i] / p) * 100) : 0; });
    var effColors = effData.map(function(v){ return v > 100 ? '#ef4444' : (v > 80 ? '#f59e0b' : '#059669'); });
    new Chart(ctxE, {
        type: 'bar',
        data: {
            labels: effLabels,
            datasets: [{
                label: 'Efficienza %',
                data: effData,
                backgroundColor: effColors,
                borderRadius: 4,
                barPercentage: 0.5
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: function(c){ return c.parsed.y + '%'; } } }
            },
            scales: {
                x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                y: {
                    grid: { color: '#f3f4f6' },
                    ticks: { font: { size: 10 }, callback: function(v){ return v+'%'; } },
                    suggestedMax: 150
                }
            },
            annotation: {
                annotations: {
                    line1: { type: 'line', yMin: 100, yMax: 100, borderColor: '#9ca3af', borderWidth: 1, borderDash: [4, 4] }
                }
            }
        }
    });
}
</script>
@endsection
