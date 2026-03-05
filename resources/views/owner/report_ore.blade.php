@extends('layouts.app')

@section('title', 'Report Ore')

@section('content')
<div class="container-fluid px-3 py-3">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h4 class="mb-0 fw-bold">Report Ore Lavorate vs Previste</h4>
        <a href="{{ route('owner.dashboard') }}" class="btn btn-outline-secondary btn-sm">&larr; Dashboard</a>
    </div>

    {{-- Filtri --}}
    <form method="GET" class="row g-2 mb-3 align-items-end">
        <div class="col-auto">
            <input type="text" name="commessa" class="form-control form-control-sm" placeholder="Filtra commessa..." value="{{ $filtroCommessa ?? '' }}">
        </div>
        <div class="col-auto">
            <select name="reparto" class="form-select form-select-sm">
                <option value="">Tutti i reparti</option>
                @foreach($reparti as $id => $nome)
                    <option value="{{ $id }}" {{ ($filtroReparto ?? '') == $id ? 'selected' : '' }}>{{ ucfirst($nome) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto">
            <button type="submit" class="btn btn-primary btn-sm">Filtra</button>
            <a href="{{ route('owner.reportOre') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
        </div>
    </form>

    {{-- Riepilogo --}}
    @php
        $totPreviste = $commesse->sum('ore_previste');
        $totLavorate = $commesse->sum('ore_lavorate');
        $diff = $totLavorate - $totPreviste;
    @endphp
    <div class="row mb-3 g-2">
        <div class="col-auto">
            <div class="card border-0 shadow-sm" style="min-width:160px;">
                <div class="card-body py-2 px-3 text-center">
                    <div style="font-size:11px; color:#666;">Ore Previste</div>
                    <div style="font-size:22px; font-weight:700; color:#0d6efd;">{{ number_format($totPreviste, 1) }}h</div>
                </div>
            </div>
        </div>
        <div class="col-auto">
            <div class="card border-0 shadow-sm" style="min-width:160px;">
                <div class="card-body py-2 px-3 text-center">
                    <div style="font-size:11px; color:#666;">Ore Lavorate</div>
                    <div style="font-size:22px; font-weight:700; color:#198754;">{{ number_format($totLavorate, 1) }}h</div>
                </div>
            </div>
        </div>
        <div class="col-auto">
            <div class="card border-0 shadow-sm" style="min-width:160px;">
                <div class="card-body py-2 px-3 text-center">
                    <div style="font-size:11px; color:#666;">Differenza</div>
                    <div style="font-size:22px; font-weight:700; color:{{ $diff > 0 ? '#dc3545' : '#198754' }};">
                        {{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 1) }}h
                    </div>
                </div>
            </div>
        </div>
        <div class="col-auto">
            <div class="card border-0 shadow-sm" style="min-width:160px;">
                <div class="card-body py-2 px-3 text-center">
                    <div style="font-size:11px; color:#666;">Commesse</div>
                    <div style="font-size:22px; font-weight:700;">{{ $commesse->count() }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tabella per commessa --}}
    <div style="overflow-x:auto;">
        <table class="table table-bordered table-sm" style="white-space:nowrap; font-size:13px;">
            <thead class="table-dark">
                <tr>
                    <th></th>
                    <th>Commessa</th>
                    <th>Cliente</th>
                    <th>Fasi</th>
                    <th class="text-end">Ore Previste</th>
                    <th class="text-end">Ore Lavorate</th>
                    <th class="text-end">Differenza</th>
                </tr>
            </thead>
            <tbody>
                @foreach($commesse as $c)
                @php
                    $d = $c->ore_lavorate - $c->ore_previste;
                    $rowClass = '';
                    if ($c->ore_lavorate > 0 && $d > 0) $rowClass = 'table-danger';
                    elseif ($c->num_terminate == $c->num_fasi && $c->ore_lavorate > 0 && $d <= 0) $rowClass = 'table-success';
                    $rowId = 'fasi-' . str_replace(['-', '.'], '_', $c->commessa);
                @endphp
                <tr class="{{ $rowClass }}" style="cursor:pointer;" data-bs-toggle="collapse" data-bs-target="#{{ $rowId }}">
                    <td class="text-center" style="width:30px;">
                        <i class="bi bi-chevron-right" id="icon-{{ $rowId }}"></i>
                    </td>
                    <td><strong>{{ $c->commessa }}</strong></td>
                    <td>{{ $c->cliente }}</td>
                    <td>{{ $c->num_terminate }}/{{ $c->num_fasi }}</td>
                    <td class="text-end fw-bold">{{ number_format($c->ore_previste, 2) }}h</td>
                    <td class="text-end fw-bold">{{ number_format($c->ore_lavorate, 2) }}h</td>
                    <td class="text-end fw-bold" style="color:{{ $d > 0 ? '#dc3545' : '#198754' }};">
                        {{ $d > 0 ? '+' : '' }}{{ number_format($d, 2) }}h
                    </td>
                </tr>
                <tr class="collapse" id="{{ $rowId }}">
                    <td colspan="7" class="p-0">
                        <table class="table table-sm table-striped mb-0" style="white-space:nowrap; font-size:12px; background:#f8f9fa;">
                            <thead>
                                <tr class="table-light">
                                    <th style="padding-left:40px;">Fase</th>
                                    <th>Reparto</th>
                                    <th>Stato</th>
                                    <th>Qta Carta</th>
                                    <th class="text-end">Ore Previste</th>
                                    <th class="text-end">Ore Lavorate</th>
                                    <th>Fonte</th>
                                    <th class="text-end">Diff</th>
                                    <th>Operatori</th>
                                    <th>Descrizione</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($c->fasi as $fase)
                                @php
                                    $df = $fase->ore_lavorate - $fase->ore_previste;
                                @endphp
                                <tr>
                                    <td style="padding-left:40px;">{{ $fase->faseCatalogo->nome_display ?? $fase->fase }}</td>
                                    <td>{{ optional($fase->faseCatalogo)->reparto->nome ?? '-' }}</td>
                                    <td>
                                        @switch($fase->stato)
                                            @case(0) <span class="badge bg-secondary">Caricato</span> @break
                                            @case(1) <span class="badge bg-info text-dark">Pronto</span> @break
                                            @case(2) <span class="badge bg-warning text-dark">Avviato</span> @break
                                            @case(3) <span class="badge bg-success">Terminato</span> @break
                                            @case(4) <span class="badge bg-primary">Consegnato</span> @break
                                        @endswitch
                                    </td>
                                    <td>{{ number_format($fase->ordine->qta_carta ?? 0, 0, ',', '.') }}</td>
                                    <td class="text-end">{{ number_format($fase->ore_previste, 2) }}h</td>
                                    <td class="text-end">{{ number_format($fase->ore_lavorate, 2) }}h</td>
                                    <td><span class="badge {{ $fase->fonte_ore === 'Prinect' ? 'bg-primary' : ($fase->fonte_ore === 'MES' ? 'bg-secondary' : 'bg-light text-muted') }}" style="font-size:10px;">{{ $fase->fonte_ore ?: '-' }}</span></td>
                                    <td class="text-end" style="color:{{ $df > 0 ? '#dc3545' : '#198754' }};">
                                        @if($fase->ore_lavorate > 0)
                                            {{ $df > 0 ? '+' : '' }}{{ number_format($df, 2) }}h
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        @foreach($fase->operatori as $op)
                                            {{ $op->nome }} {{ $op->cognome }}@if(!$loop->last), @endif
                                        @endforeach
                                    </td>
                                    <td style="white-space:normal; max-width:200px;">{{ $fase->ordine->descrizione ?? '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($commesse->isEmpty())
        <p class="text-muted text-center py-4">Nessuna commessa con ore lavorate trovata.</p>
    @endif
</div>

<style>
    .collapse.show + tr .bi-chevron-right,
    tr[aria-expanded="true"] .bi-chevron-right {
        transform: rotate(90deg);
    }
    .bi-chevron-right {
        transition: transform 0.2s;
        display: inline-block;
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(function(row) {
        var target = document.querySelector(row.dataset.bsTarget);
        if (target) {
            target.addEventListener('show.bs.collapse', function() {
                row.querySelector('.bi-chevron-right')?.style.setProperty('transform', 'rotate(90deg)');
            });
            target.addEventListener('hide.bs.collapse', function() {
                row.querySelector('.bi-chevron-right')?.style.setProperty('transform', 'rotate(0deg)');
            });
        }
    });
});
</script>
@endsection
