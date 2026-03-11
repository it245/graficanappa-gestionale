@extends('layouts.app')

@section('title', 'Report Ore')

@section('content')
@php
    $totPreviste = $commesse->sum('ore_previste');
    $totLavorate = $commesse->sum('ore_lavorate');
    $diff = $totLavorate - $totPreviste;
    $efficienza = $totPreviste > 0 ? round(($totLavorate / $totPreviste) * 100) : 0;
    $commesseSforate = $commesse->filter(fn($c) => $c->ore_lavorate > $c->ore_previste && $c->ore_lavorate > 0)->count();
@endphp

<div style="min-height:100vh; background:#0f172a; color:#e2e8f0; font-family:'Inter','Segoe UI',system-ui,sans-serif;">

    {{-- Top bar --}}
    <div style="background:#1e293b; border-bottom:1px solid #334155; padding:16px 24px;">
        <div style="max-width:1400px; margin:0 auto; display:flex; align-items:center; justify-content:space-between;">
            <div style="display:flex; align-items:center; gap:12px;">
                <div style="width:36px; height:36px; border-radius:8px; background:linear-gradient(135deg,#3b82f6,#8b5cf6); display:flex; align-items:center; justify-content:center;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <div>
                    <h1 style="font-size:18px; font-weight:700; margin:0; color:#f1f5f9; letter-spacing:-0.3px;">Report Ore</h1>
                    <span style="font-size:12px; color:#64748b;">Ore lavorate vs previste per commessa</span>
                </div>
            </div>
            <a href="{{ route('owner.dashboard', ['op_token' => request()->query('op_token')]) }}" style="display:inline-flex; align-items:center; gap:6px; padding:8px 16px; border-radius:6px; background:#334155; color:#94a3b8; text-decoration:none; font-size:13px; font-weight:500; border:1px solid #475569; transition:all .15s;" onmouseover="this.style.background='#475569';this.style.color='#e2e8f0'" onmouseout="this.style.background='#334155';this.style.color='#94a3b8'">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
                Dashboard
            </a>
        </div>
    </div>

    <div style="max-width:1400px; margin:0 auto; padding:24px;">

        {{-- Filtri --}}
        <form method="GET" style="margin-bottom:24px;">
            @if(request()->query('op_token'))
                <input type="hidden" name="op_token" value="{{ request()->query('op_token') }}">
            @endif
            <div style="background:#1e293b; border:1px solid #334155; border-radius:10px; padding:16px 20px; display:flex; align-items:flex-end; gap:12px; flex-wrap:wrap;">
                <div style="flex:1; min-width:180px;">
                    <label style="display:block; font-size:11px; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px; font-weight:600;">Commessa</label>
                    <input type="text" name="commessa" value="{{ $filtroCommessa ?? '' }}" placeholder="Cerca..." style="width:100%; padding:8px 12px; border-radius:6px; border:1px solid #334155; background:#0f172a; color:#e2e8f0; font-size:13px; outline:none;" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#334155'">
                </div>
                <div style="flex:1; min-width:180px;">
                    <label style="display:block; font-size:11px; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px; font-weight:600;">Reparto</label>
                    <select name="reparto" style="width:100%; padding:8px 12px; border-radius:6px; border:1px solid #334155; background:#0f172a; color:#e2e8f0; font-size:13px; outline:none;" onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#334155'">
                        <option value="">Tutti i reparti</option>
                        @foreach($reparti as $id => $nome)
                            <option value="{{ $id }}" {{ ($filtroReparto ?? '') == $id ? 'selected' : '' }}>{{ ucfirst($nome) }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="display:flex; gap:8px;">
                    <button type="submit" style="padding:8px 20px; border-radius:6px; background:#3b82f6; color:#fff; border:none; font-size:13px; font-weight:600; cursor:pointer; transition:background .15s;" onmouseover="this.style.background='#2563eb'" onmouseout="this.style.background='#3b82f6'">Filtra</button>
                    <a href="{{ route('owner.reportOre', ['op_token' => request()->query('op_token')]) }}" style="padding:8px 16px; border-radius:6px; background:transparent; color:#94a3b8; border:1px solid #475569; font-size:13px; text-decoration:none; font-weight:500;" onmouseover="this.style.background='#334155'" onmouseout="this.style.background='transparent'">Reset</a>
                </div>
            </div>
        </form>

        {{-- KPI --}}
        <div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:16px; margin-bottom:24px;">
            <div style="background:#1e293b; border:1px solid #334155; border-radius:10px; padding:20px;">
                <div style="font-size:11px; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; font-weight:600; margin-bottom:8px;">Ore Previste</div>
                <div style="font-size:32px; font-weight:700; color:#3b82f6; letter-spacing:-1px;">{{ number_format($totPreviste, 1) }}<span style="font-size:16px; color:#64748b; font-weight:400;">h</span></div>
            </div>
            <div style="background:#1e293b; border:1px solid #334155; border-radius:10px; padding:20px;">
                <div style="font-size:11px; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; font-weight:600; margin-bottom:8px;">Ore Lavorate</div>
                <div style="font-size:32px; font-weight:700; color:#10b981; letter-spacing:-1px;">{{ number_format($totLavorate, 1) }}<span style="font-size:16px; color:#64748b; font-weight:400;">h</span></div>
            </div>
            <div style="background:#1e293b; border:1px solid #334155; border-radius:10px; padding:20px;">
                <div style="font-size:11px; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; font-weight:600; margin-bottom:8px;">Scostamento</div>
                <div style="font-size:32px; font-weight:700; color:{{ $diff > 0 ? '#ef4444' : '#10b981' }}; letter-spacing:-1px;">{{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 1) }}<span style="font-size:16px; color:#64748b; font-weight:400;">h</span></div>
            </div>
            <div style="background:#1e293b; border:1px solid #334155; border-radius:10px; padding:20px;">
                <div style="font-size:11px; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; font-weight:600; margin-bottom:8px;">Commesse Sforate</div>
                <div style="font-size:32px; font-weight:700; color:{{ $commesseSforate > 0 ? '#ef4444' : '#10b981' }}; letter-spacing:-1px;">{{ $commesseSforate }}<span style="font-size:16px; color:#64748b; font-weight:400;"> / {{ $commesse->count() }}</span></div>
            </div>
        </div>

        {{-- Tabella --}}
        <div style="background:#1e293b; border:1px solid #334155; border-radius:10px; overflow:hidden;">
            <div style="overflow-x:auto;">
                <table style="width:100%; border-collapse:collapse; font-size:13px; white-space:nowrap;">
                    <thead>
                        <tr style="background:#0f172a;">
                            <th style="padding:12px 10px; text-align:left; color:#64748b; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; font-weight:600; width:30px;"></th>
                            <th style="padding:12px 10px; text-align:left; color:#64748b; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Commessa</th>
                            <th style="padding:12px 10px; text-align:left; color:#64748b; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Cliente</th>
                            <th style="padding:12px 10px; text-align:center; color:#64748b; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Fasi</th>
                            <th style="padding:12px 10px; text-align:right; color:#64748b; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Previste</th>
                            <th style="padding:12px 10px; text-align:right; color:#64748b; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Lavorate</th>
                            <th style="padding:12px 10px; text-align:right; color:#64748b; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; font-weight:600;">Scostamento</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($commesse as $c)
                        @php
                            $d = $c->ore_lavorate - $c->ore_previste;
                            $sforata = $c->ore_lavorate > 0 && $d > 0;
                            $completata = $c->num_terminate == $c->num_fasi && $c->ore_lavorate > 0;
                            $rowId = 'fasi-' . str_replace(['-', '.'], '_', $c->commessa);
                        @endphp
                        <tr onclick="toggleRow('{{ $rowId }}')" style="cursor:pointer; border-bottom:1px solid #1e293b; transition:background .1s;{{ $sforata ? 'border-left:3px solid #ef4444;' : ($completata && $d <= 0 ? 'border-left:3px solid #10b981;' : 'border-left:3px solid transparent;') }}" onmouseover="this.style.background='#263044'" onmouseout="this.style.background='transparent'">
                            <td style="padding:12px 10px; text-align:center;">
                                <svg id="icon-{{ $rowId }}" class="row-chevron" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="transition:transform .2s;"><polyline points="9 18 15 12 9 6"/></svg>
                            </td>
                            <td style="padding:12px 10px; font-weight:600; color:#f1f5f9;">{{ $c->commessa }}</td>
                            <td style="padding:12px 10px; color:#94a3b8;">{{ $c->cliente }}</td>
                            <td style="padding:12px 10px; text-align:center;">
                                <span style="display:inline-block; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:600; {{ $completata ? 'background:#064e3b; color:#34d399;' : 'background:#1e293b; color:#64748b; border:1px solid #334155;' }}">{{ $c->num_terminate }}/{{ $c->num_fasi }}</span>
                            </td>
                            <td style="padding:12px 10px; text-align:right; font-weight:600; color:#93c5fd;">{{ number_format($c->ore_previste, 1) }}h</td>
                            <td style="padding:12px 10px; text-align:right; font-weight:600; color:#6ee7b7;">{{ number_format($c->ore_lavorate, 1) }}h</td>
                            <td style="padding:12px 10px; text-align:right; font-weight:700; color:{{ $sforata ? '#ef4444' : '#10b981' }};">
                                {{ $d > 0 ? '+' : '' }}{{ number_format($d, 1) }}h
                            </td>
                        </tr>
                        <tr id="{{ $rowId }}" style="display:none;">
                            <td colspan="7" style="padding:0; background:#0f172a;">
                                <div style="margin:0 0 0 16px; border-left:2px solid #3b82f6; padding:12px 0 12px 16px;">
                                    <table style="width:100%; border-collapse:collapse; font-size:12px;">
                                        <thead>
                                            <tr>
                                                <th style="padding:6px 10px; text-align:left; color:#475569; font-size:10px; text-transform:uppercase; letter-spacing:0.5px;">Fase</th>
                                                <th style="padding:6px 10px; text-align:left; color:#475569; font-size:10px; text-transform:uppercase; letter-spacing:0.5px;">Reparto</th>
                                                <th style="padding:6px 10px; text-align:left; color:#475569; font-size:10px; text-transform:uppercase; letter-spacing:0.5px;">Stato</th>
                                                <th style="padding:6px 10px; text-align:right; color:#475569; font-size:10px; text-transform:uppercase; letter-spacing:0.5px;">Qta Carta</th>
                                                <th style="padding:6px 10px; text-align:right; color:#475569; font-size:10px; text-transform:uppercase; letter-spacing:0.5px;">Previste</th>
                                                <th style="padding:6px 10px; text-align:right; color:#475569; font-size:10px; text-transform:uppercase; letter-spacing:0.5px;">Lavorate</th>
                                                <th style="padding:6px 10px; text-align:left; color:#475569; font-size:10px; text-transform:uppercase; letter-spacing:0.5px;">Fonte</th>
                                                <th style="padding:6px 10px; text-align:right; color:#475569; font-size:10px; text-transform:uppercase; letter-spacing:0.5px;">Diff</th>
                                                <th style="padding:6px 10px; text-align:left; color:#475569; font-size:10px; text-transform:uppercase; letter-spacing:0.5px;">Operatori</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($c->fasi as $fase)
                                            @php $df = $fase->ore_lavorate - $fase->ore_previste; @endphp
                                            <tr style="border-bottom:1px solid #1e293b;">
                                                <td style="padding:8px 10px; color:#e2e8f0; font-weight:500;">{{ $fase->faseCatalogo->nome_display ?? $fase->fase }}</td>
                                                <td style="padding:8px 10px; color:#94a3b8;">{{ optional($fase->faseCatalogo)->reparto->nome ?? '-' }}</td>
                                                <td style="padding:8px 10px;">
                                                    @switch($fase->stato)
                                                        @case(0) <span style="display:inline-block; padding:2px 6px; border-radius:4px; font-size:10px; font-weight:600; background:#334155; color:#94a3b8;">Caricato</span> @break
                                                        @case(1) <span style="display:inline-block; padding:2px 6px; border-radius:4px; font-size:10px; font-weight:600; background:#164e63; color:#67e8f9;">Pronto</span> @break
                                                        @case(2) <span style="display:inline-block; padding:2px 6px; border-radius:4px; font-size:10px; font-weight:600; background:#713f12; color:#fde047;">Avviato</span> @break
                                                        @case(3) <span style="display:inline-block; padding:2px 6px; border-radius:4px; font-size:10px; font-weight:600; background:#064e3b; color:#34d399;">Terminato</span> @break
                                                        @case(4) <span style="display:inline-block; padding:2px 6px; border-radius:4px; font-size:10px; font-weight:600; background:#1e3a5f; color:#93c5fd;">Consegnato</span> @break
                                                    @endswitch
                                                </td>
                                                <td style="padding:8px 10px; text-align:right; color:#94a3b8;">{{ number_format($fase->ordine->qta_carta ?? 0, 0, ',', '.') }}</td>
                                                <td style="padding:8px 10px; text-align:right; color:#93c5fd;">{{ number_format($fase->ore_previste, 2) }}h</td>
                                                <td style="padding:8px 10px; text-align:right; color:#6ee7b7;">{{ number_format($fase->ore_lavorate, 2) }}h</td>
                                                <td style="padding:8px 10px;">
                                                    @if($fase->fonte_ore === 'Prinect')
                                                        <span style="display:inline-block; padding:2px 6px; border-radius:4px; font-size:10px; font-weight:600; background:#1e3a5f; color:#93c5fd;">Prinect</span>
                                                    @elseif($fase->fonte_ore === 'MES')
                                                        <span style="display:inline-block; padding:2px 6px; border-radius:4px; font-size:10px; font-weight:600; background:#334155; color:#e2e8f0;">MES</span>
                                                    @else
                                                        <span style="color:#475569;">-</span>
                                                    @endif
                                                </td>
                                                <td style="padding:8px 10px; text-align:right; font-weight:600; color:{{ $df > 0 ? '#ef4444' : '#10b981' }};">
                                                    @if($fase->ore_lavorate > 0)
                                                        {{ $df > 0 ? '+' : '' }}{{ number_format($df, 2) }}h
                                                    @else
                                                        <span style="color:#475569;">-</span>
                                                    @endif
                                                </td>
                                                <td style="padding:8px 10px; color:#94a3b8;">
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

        @if($commesse->isEmpty())
        <div style="text-align:center; padding:60px 0;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#334155" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <p style="color:#475569; margin-top:16px;">Nessuna commessa con ore lavorate trovata.</p>
        </div>
        @endif
    </div>
</div>

<script>
function toggleRow(id) {
    var row = document.getElementById(id);
    var icon = document.getElementById('icon-' + id);
    if (row.style.display === 'none') {
        row.style.display = '';
        if (icon) icon.style.transform = 'rotate(90deg)';
    } else {
        row.style.display = 'none';
        if (icon) icon.style.transform = 'rotate(0deg)';
    }
}
</script>
@endsection
