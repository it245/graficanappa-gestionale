@extends('layouts.app')

@section('title', 'Report Ore')

@section('content')
<div class="container-fluid px-3 py-3" style="max-width:1400px;">
    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h4 class="mb-0 fw-bold" style="letter-spacing:-0.5px;">Report Ore Lavorate</h4>
            <small class="text-muted">Confronto ore previste vs lavorate per commessa</small>
        </div>
        <a href="{{ route('owner.dashboard', ['op_token' => request()->query('op_token')]) }}" class="btn btn-outline-secondary btn-sm">&larr; Dashboard</a>
    </div>

    {{-- Filtri --}}
    <form method="GET" class="mb-4">
        @if(request()->query('op_token'))
            <input type="hidden" name="op_token" value="{{ request()->query('op_token') }}">
        @endif
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small text-muted mb-1">Commessa</label>
                        <input type="text" name="commessa" class="form-control form-control-sm" placeholder="Cerca commessa..." value="{{ $filtroCommessa ?? '' }}">
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <label class="form-label small text-muted mb-1">Reparto</label>
                        <select name="reparto" class="form-select form-select-sm">
                            <option value="">Tutti i reparti</option>
                            @foreach($reparti as $id => $nome)
                                <option value="{{ $id }}" {{ ($filtroReparto ?? '') == $id ? 'selected' : '' }}>{{ ucfirst($nome) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-sm px-4">Filtra</button>
                        <a href="{{ route('owner.reportOre', ['op_token' => request()->query('op_token')]) }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- KPI Cards --}}
    @php
        $totPreviste = $commesse->sum('ore_previste');
        $totLavorate = $commesse->sum('ore_lavorate');
        $diff = $totLavorate - $totPreviste;
        $efficienza = $totPreviste > 0 ? round(($totLavorate / $totPreviste) * 100) : 0;
    @endphp
    <div class="row mb-4 g-3">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3 px-3 text-center">
                    <div class="text-muted" style="font-size:12px; text-transform:uppercase; letter-spacing:0.5px;">Ore Previste</div>
                    <div style="font-size:28px; font-weight:700; color:#0d6efd;">{{ number_format($totPreviste, 1) }}<small style="font-size:14px;">h</small></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3 px-3 text-center">
                    <div class="text-muted" style="font-size:12px; text-transform:uppercase; letter-spacing:0.5px;">Ore Lavorate</div>
                    <div style="font-size:28px; font-weight:700; color:#198754;">{{ number_format($totLavorate, 1) }}<small style="font-size:14px;">h</small></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3 px-3 text-center">
                    <div class="text-muted" style="font-size:12px; text-transform:uppercase; letter-spacing:0.5px;">Scostamento</div>
                    <div style="font-size:28px; font-weight:700; color:{{ $diff > 0 ? '#dc3545' : '#198754' }};">
                        {{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 1) }}<small style="font-size:14px;">h</small>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3 px-3 text-center">
                    <div class="text-muted" style="font-size:12px; text-transform:uppercase; letter-spacing:0.5px;">Commesse</div>
                    <div style="font-size:28px; font-weight:700;">{{ $commesse->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Barra efficienza --}}
    @if($totPreviste > 0)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted" style="font-size:12px; text-transform:uppercase; letter-spacing:0.5px;">Efficienza complessiva</span>
                <span class="fw-bold" style="font-size:16px; color:{{ $efficienza > 100 ? '#dc3545' : '#198754' }};">{{ $efficienza }}%</span>
            </div>
            <div class="progress" style="height:8px; border-radius:4px;">
                <div class="progress-bar {{ $efficienza > 100 ? 'bg-danger' : 'bg-success' }}" style="width:{{ min($efficienza, 100) }}%; border-radius:4px;"></div>
            </div>
            <div class="d-flex justify-content-between mt-1">
                <small class="text-muted">0%</small>
                <small class="text-muted">{{ $efficienza > 100 ? 'Sopra budget' : 'Sotto budget' }}</small>
                <small class="text-muted">100%</small>
            </div>
        </div>
    </div>
    @endif

    {{-- Tabella per commessa --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div style="overflow-x:auto;">
                <table class="table table-hover mb-0" style="white-space:nowrap; font-size:13px;">
                    <thead>
                        <tr style="background:#f8f9fa; border-bottom:2px solid #dee2e6;">
                            <th style="width:30px;"></th>
                            <th style="padding:12px 10px;">Commessa</th>
                            <th style="padding:12px 10px;">Cliente</th>
                            <th style="padding:12px 10px;">Fasi</th>
                            <th class="text-end" style="padding:12px 10px;">Previste</th>
                            <th class="text-end" style="padding:12px 10px;">Lavorate</th>
                            <th class="text-end" style="padding:12px 10px;">Scostamento</th>
                            <th style="padding:12px 10px; width:120px;">Progresso</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($commesse as $c)
                        @php
                            $d = $c->ore_lavorate - $c->ore_previste;
                            $pct = $c->ore_previste > 0 ? round(($c->ore_lavorate / $c->ore_previste) * 100) : 0;
                            $rowClass = '';
                            if ($c->ore_lavorate > 0 && $d > 0) $rowClass = 'border-start border-3 border-danger';
                            elseif ($c->num_terminate == $c->num_fasi && $c->ore_lavorate > 0 && $d <= 0) $rowClass = 'border-start border-3 border-success';
                            $rowId = 'fasi-' . str_replace(['-', '.'], '_', $c->commessa);
                        @endphp
                        <tr class="{{ $rowClass }}" style="cursor:pointer;" data-bs-toggle="collapse" data-bs-target="#{{ $rowId }}">
                            <td class="text-center" style="padding:10px 6px;">
                                <svg class="chevron-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                            </td>
                            <td style="padding:10px;"><strong>{{ $c->commessa }}</strong></td>
                            <td style="padding:10px;">{{ $c->cliente }}</td>
                            <td style="padding:10px;">
                                <span class="badge {{ $c->num_terminate == $c->num_fasi ? 'bg-success' : 'bg-secondary' }}" style="font-size:11px;">{{ $c->num_terminate }}/{{ $c->num_fasi }}</span>
                            </td>
                            <td class="text-end fw-bold" style="padding:10px;">{{ number_format($c->ore_previste, 1) }}h</td>
                            <td class="text-end fw-bold" style="padding:10px;">{{ number_format($c->ore_lavorate, 1) }}h</td>
                            <td class="text-end fw-bold" style="padding:10px; color:{{ $d > 0 ? '#dc3545' : '#198754' }};">
                                {{ $d > 0 ? '+' : '' }}{{ number_format($d, 1) }}h
                            </td>
                            <td style="padding:10px;">
                                <div class="progress" style="height:6px; border-radius:3px; min-width:80px;">
                                    <div class="progress-bar {{ $pct > 100 ? 'bg-danger' : 'bg-primary' }}" style="width:{{ min($pct, 100) }}%; border-radius:3px;"></div>
                                </div>
                            </td>
                        </tr>
                        <tr class="collapse" id="{{ $rowId }}">
                            <td colspan="8" class="p-0" style="border:none;">
                                <div style="background:#f8f9fa; border-left:3px solid #0d6efd; margin:0 0 0 20px; padding:8px 0;">
                                    <table class="table table-sm mb-0" style="white-space:nowrap; font-size:12px;">
                                        <thead>
                                            <tr style="background:transparent;">
                                                <th style="padding:6px 10px 6px 20px; color:#666; font-weight:600;">Fase</th>
                                                <th style="padding:6px 10px; color:#666; font-weight:600;">Reparto</th>
                                                <th style="padding:6px 10px; color:#666; font-weight:600;">Stato</th>
                                                <th style="padding:6px 10px; color:#666; font-weight:600;">Qta Carta</th>
                                                <th class="text-end" style="padding:6px 10px; color:#666; font-weight:600;">Previste</th>
                                                <th class="text-end" style="padding:6px 10px; color:#666; font-weight:600;">Lavorate</th>
                                                <th style="padding:6px 10px; color:#666; font-weight:600;">Fonte</th>
                                                <th class="text-end" style="padding:6px 10px; color:#666; font-weight:600;">Diff</th>
                                                <th style="padding:6px 10px; color:#666; font-weight:600;">Operatori</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($c->fasi as $fase)
                                            @php $df = $fase->ore_lavorate - $fase->ore_previste; @endphp
                                            <tr style="border-bottom:1px solid #e9ecef;">
                                                <td style="padding:6px 10px 6px 20px; font-weight:500;">{{ $fase->faseCatalogo->nome_display ?? $fase->fase }}</td>
                                                <td style="padding:6px 10px;">{{ optional($fase->faseCatalogo)->reparto->nome ?? '-' }}</td>
                                                <td style="padding:6px 10px;">
                                                    @switch($fase->stato)
                                                        @case(0) <span class="badge bg-secondary" style="font-size:10px;">Caricato</span> @break
                                                        @case(1) <span class="badge bg-info text-dark" style="font-size:10px;">Pronto</span> @break
                                                        @case(2) <span class="badge bg-warning text-dark" style="font-size:10px;">Avviato</span> @break
                                                        @case(3) <span class="badge bg-success" style="font-size:10px;">Terminato</span> @break
                                                        @case(4) <span class="badge bg-primary" style="font-size:10px;">Consegnato</span> @break
                                                    @endswitch
                                                </td>
                                                <td style="padding:6px 10px;">{{ number_format($fase->ordine->qta_carta ?? 0, 0, ',', '.') }}</td>
                                                <td class="text-end" style="padding:6px 10px;">{{ number_format($fase->ore_previste, 2) }}h</td>
                                                <td class="text-end" style="padding:6px 10px;">{{ number_format($fase->ore_lavorate, 2) }}h</td>
                                                <td style="padding:6px 10px;">
                                                    @if($fase->fonte_ore === 'Prinect')
                                                        <span class="badge bg-primary" style="font-size:10px;">Prinect</span>
                                                    @elseif($fase->fonte_ore === 'MES')
                                                        <span class="badge bg-dark" style="font-size:10px;">MES</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                                <td class="text-end" style="padding:6px 10px; color:{{ $df > 0 ? '#dc3545' : '#198754' }};">
                                                    @if($fase->ore_lavorate > 0)
                                                        {{ $df > 0 ? '+' : '' }}{{ number_format($df, 2) }}h
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td style="padding:6px 10px;">
                                                    @foreach($fase->operatori as $op)
                                                        {{ $op->nome }} {{ $op->cognome }}@if(!$loop->last), @endif
                                                    @endforeach
                                                </td>
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
    </div>

    @if($commesse->isEmpty())
        <div class="text-center py-5">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <p class="text-muted mt-3">Nessuna commessa con ore lavorate trovata.</p>
        </div>
    @endif
</div>

<style>
    .chevron-icon {
        transition: transform 0.2s ease;
    }
    tr[aria-expanded="true"] .chevron-icon,
    tr:not(.collapsed) + tr.collapse.show ~ .chevron-icon {
        transform: rotate(90deg);
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(function(row) {
        var target = document.querySelector(row.dataset.bsTarget);
        if (target) {
            target.addEventListener('show.bs.collapse', function() {
                var icon = row.querySelector('.chevron-icon');
                if (icon) icon.style.transform = 'rotate(90deg)';
            });
            target.addEventListener('hide.bs.collapse', function() {
                var icon = row.querySelector('.chevron-icon');
                if (icon) icon.style.transform = 'rotate(0deg)';
            });
        }
    });
});
</script>
@endsection
