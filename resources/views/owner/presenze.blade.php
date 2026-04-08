@extends('layouts.mes')

@section('page-title', 'Presenze - MES Grafica Nappa')
@section('topbar-title', 'Presenze')

@section('topbar-actions')
    <span class="text-muted" style="font-size:12px;">{{ $data->format('l d/m/Y') }}{{ $data->isToday() ? ' (oggi)' : '' }}</span>
    @if(!$data->isToday())
        <a href="{{ route('owner.presenze') }}" class="mes-topbar-logout" style="border-color:var(--success); color:var(--success);">Oggi</a>
    @endif
@endsection

@section('sidebar-items')
<div class="mes-sidebar-section">
    <div class="mes-sidebar-section-label">Navigazione</div>
    <a href="{{ route('owner.dashboard') }}" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        Dashboard
    </a>
    <a href="{{ route('owner.presenze') }}" class="mes-sidebar-item active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        Presenze
    </a>
</div>
@endsection

@section('content')
<style>
    .pz-kpi-row { display:flex; gap:16px; margin-bottom:20px; flex-wrap:wrap; }
    .pz-kpi { flex:1; min-width:160px; background:var(--bg-card); border-radius:10px; padding:16px 20px; border:1px solid var(--border-color); }
    .pz-kpi-label { font-size:11px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.5px; font-weight:600; }
    .pz-kpi-value { font-size:28px; font-weight:700; color:var(--text-primary); margin-top:4px; }
    .pz-kpi-value.green { color:var(--success); }
    .pz-kpi-value.red { color:var(--danger); }
    .pz-kpi-value.blue { color:var(--accent); }
    .pz-card { background:var(--bg-card); border-radius:10px; border:1px solid var(--border-color); margin-bottom:20px; overflow:hidden; }
    .pz-card-header { padding:14px 20px; border-bottom:1px solid var(--border-color); display:flex; align-items:center; justify-content:space-between; }
    .pz-card-title { font-size:14px; font-weight:600; color:var(--text-primary); display:flex; align-items:center; gap:8px; }
    .pz-table { width:100%; border-collapse:collapse; font-size:13px; }
    .pz-table th { background:var(--bg-sidebar); padding:8px 12px; text-align:left; font-weight:600; color:#fff; font-size:11px; text-transform:uppercase; border-bottom:1px solid var(--border-color); }
    .pz-table td { padding:8px 12px; border-bottom:1px solid var(--border-color); color:var(--text-primary); }
    .pz-table tr:hover { background:rgba(0,0,0,0.02); }
    .pz-badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:600; }
    .pz-badge.presente { background:#d1fae5; color:#065f46; }
    .pz-badge.uscito { background:#f3f4f6; color:#6b7280; }
    .pz-ore { font-variant-numeric:tabular-nums; }
    .pz-giorni { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:16px; }
    .pz-giorno { padding:6px 12px; border-radius:6px; font-size:12px; font-weight:500; text-decoration:none; border:1px solid var(--border-color); color:var(--text-secondary); background:var(--bg-card); }
    .pz-giorno:hover { background:var(--border-color); color:var(--text-primary); }
    .pz-giorno.active { background:var(--accent); color:#fff; border-color:var(--accent); }
    .pz-timbrature { font-size:11px; color:var(--text-secondary); }
    .pz-timbrature span { display:inline-block; margin-right:6px; }
    .pz-timbrature .e { color:var(--success); }
    .pz-timbrature .u { color:var(--danger); }
    .pz-intervalli { display:flex; gap:4px; flex-wrap:wrap; align-items:center; }
    .pz-int { display:inline-flex; align-items:center; gap:3px; padding:2px 8px; border-radius:4px; font-size:11px; font-weight:500; }
    .pz-int.lavoro { background:#d1fae5; color:#065f46; }
    .pz-int.pausa { background:#fef3c7; color:#92400e; }
    .pz-int .pz-int-ore { font-weight:700; }
    .pz-int-arrow { color:#d1d5db; font-size:10px; }
    .pz-anagrafica-search { padding:6px 12px; border:1px solid var(--border-color); border-radius:6px; font-size:12px; width:220px; background:var(--bg-card); color:var(--text-primary); }
</style>

<div>
        {{-- Navigazione giorni --}}
        <div class="pz-giorni">
            @foreach($giorniDisponibili as $g)
                <a href="{{ route('owner.presenze', ['data' => $g]) }}"
                   class="pz-giorno {{ $data->format('Y-m-d') === $g ? 'active' : '' }}">
                    {{ \Carbon\Carbon::parse($g)->format('D d/m') }}
                </a>
            @endforeach
        </div>

        {{-- KPI --}}
        <div class="pz-kpi-row">
            <div class="pz-kpi">
                <div class="pz-kpi-label">Presenti ora</div>
                <div class="pz-kpi-value green">{{ $totalePresenti }}</div>
            </div>
            <div class="pz-kpi">
                <div class="pz-kpi-label">Usciti</div>
                <div class="pz-kpi-value red">{{ $totaleUsciti }}</div>
            </div>
            <div class="pz-kpi">
                <div class="pz-kpi-label">Totale giornata</div>
                <div class="pz-kpi-value blue">{{ count($dipendenti) }}</div>
            </div>
            <div class="pz-kpi">
                <div class="pz-kpi-label">Anagrafica totale</div>
                <div class="pz-kpi-value">{{ $anagrafica->count() }}</div>
            </div>
        </div>

        {{-- Presenze del giorno --}}
        <div class="pz-card">
            <div class="pz-card-header">
                <div class="pz-card-title">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#059669" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    Timbrature {{ $data->format('d/m/Y') }}
                </div>
                <input type="text" id="searchDip" class="pz-anagrafica-search" placeholder="Cerca dipendente..." oninput="filtraDipendenti()">
            </div>
            @if(count($dipendenti) > 0)
            <table class="pz-table" id="tabellaPresenze">
                <thead>
                    <tr>
                        <th>Dipendente</th>
                        <th>Stato</th>
                        <th>Entrata</th>
                        <th>Uscita</th>
                        <th>Ore Lavoro</th>
                        <th>Pausa</th>
                        <th>Intervalli</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dipendenti as $dip)
                    <tr class="riga-dip">
                        <td><strong>{{ $dip['nome'] }}</strong><br><span style="font-size:11px; color:#9ca3af;">{{ $dip['matricola'] }}</span></td>
                        <td>
                            @if($dip['presente'])
                                <span class="pz-badge presente">Presente</span>
                            @else
                                <span class="pz-badge uscito">Uscito</span>
                            @endif
                        </td>
                        <td>{{ $dip['prima_entrata'] ?? '-' }}</td>
                        <td>{{ $dip['ultima_uscita'] ?? '-' }}</td>
                        <td class="pz-ore">
                            @php
                                $h = floor($dip['ore_lavorate'] / 60);
                                $m = $dip['ore_lavorate'] % 60;
                            @endphp
                            <strong>{{ $h }}h {{ str_pad($m, 2, '0', STR_PAD_LEFT) }}m</strong>
                        </td>
                        <td class="pz-ore">
                            @php
                                $hp = floor(($dip['minuti_pausa'] ?? 0) / 60);
                                $mp = ($dip['minuti_pausa'] ?? 0) % 60;
                            @endphp
                            @if(($dip['minuti_pausa'] ?? 0) > 0)
                                {{ $hp }}h {{ str_pad($mp, 2, '0', STR_PAD_LEFT) }}m
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <div class="pz-intervalli">
                                @foreach($dip['intervalli'] ?? [] as $int)
                                    <span class="pz-int {{ $int['tipo'] }}">
                                        @if($int['tipo'] === 'lavoro')
                                            &#9654;
                                        @else
                                            &#9208;
                                        @endif
                                        {{ $int['da'] }}-{{ $int['a'] }}
                                        <span class="pz-int-ore">
                                            @php $ih = floor($int['minuti'] / 60); $im = $int['minuti'] % 60; @endphp
                                            {{ $ih > 0 ? $ih.'h' : '' }}{{ str_pad($im, 2, '0', STR_PAD_LEFT) }}m
                                        </span>
                                    </span>
                                @endforeach
                            </div>
                            <div class="pz-timbrature" style="margin-top:3px;">
                                @foreach($dip['timbrature'] as $t)
                                    <span class="{{ $t->verso === 'E' ? 'e' : 'u' }}">
                                        {{ $t->verso }}{{ \Carbon\Carbon::parse($t->data_ora)->format('H:i') }}
                                    </span>
                                @endforeach
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @else
            <div style="padding:40px; text-align:center; color:#9ca3af;">
                Nessuna timbratura per questa giornata
            </div>
            @endif
        </div>

        {{-- Anagrafica completa --}}
        <div class="pz-card">
            <div class="pz-card-header">
                <div class="pz-card-title">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="#6b7280" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    Anagrafica dipendenti ({{ $anagrafica->count() }})
                </div>
            </div>
            <table class="pz-table">
                <thead>
                    <tr>
                        <th>Matricola</th>
                        <th>Cognome</th>
                        <th>Nome</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($anagrafica as $a)
                    <tr>
                        <td>{{ $a->matricola }}</td>
                        <td>{{ $a->cognome }}</td>
                        <td>{{ $a->nome }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
</div>

<script>
function filtraDipendenti() {
    var q = document.getElementById('searchDip').value.toLowerCase();
    document.querySelectorAll('.riga-dip').forEach(function(tr) {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
}
</script>
@endsection
