@extends('layouts.app')

@section('title', 'Presenze')

@section('content')
<style>
    .pz-page { background:#f0f2f5; min-height:100vh; font-family:'Inter','Segoe UI',system-ui,sans-serif; }
    .pz-topbar { background:#fff; border-bottom:1px solid #e5e7eb; padding:14px 24px; position:sticky; top:0; z-index:100; box-shadow:0 1px 3px rgba(0,0,0,.04); }
    .pz-topbar-inner { max-width:1440px; margin:0 auto; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
    .pz-logo { display:flex; align-items:center; gap:10px; }
    .pz-logo-icon { width:34px; height:34px; border-radius:8px; background:linear-gradient(135deg,#059669,#10b981); display:flex; align-items:center; justify-content:center; }
    .pz-title { font-size:16px; font-weight:700; color:#111827; margin:0; }
    .pz-subtitle { font-size:11px; color:#9ca3af; }
    .pz-back { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:6px; background:#f3f4f6; color:#6b7280; text-decoration:none; font-size:12px; font-weight:500; border:1px solid #e5e7eb; }
    .pz-back:hover { background:#e5e7eb; color:#374151; }
    .pz-body { max-width:1440px; margin:0 auto; padding:20px 24px; }
    .pz-kpi-row { display:flex; gap:16px; margin-bottom:20px; flex-wrap:wrap; }
    .pz-kpi { flex:1; min-width:160px; background:#fff; border-radius:10px; padding:16px 20px; border:1px solid #e5e7eb; }
    .pz-kpi-label { font-size:11px; color:#9ca3af; text-transform:uppercase; letter-spacing:.5px; font-weight:600; }
    .pz-kpi-value { font-size:28px; font-weight:700; color:#111827; margin-top:4px; }
    .pz-kpi-value.green { color:#059669; }
    .pz-kpi-value.red { color:#dc2626; }
    .pz-kpi-value.blue { color:#2563eb; }
    .pz-card { background:#fff; border-radius:10px; border:1px solid #e5e7eb; margin-bottom:20px; overflow:hidden; }
    .pz-card-header { padding:14px 20px; border-bottom:1px solid #f3f4f6; display:flex; align-items:center; justify-content:space-between; }
    .pz-card-title { font-size:14px; font-weight:600; color:#111827; display:flex; align-items:center; gap:8px; }
    .pz-table { width:100%; border-collapse:collapse; font-size:13px; }
    .pz-table th { background:#f9fafb; padding:8px 12px; text-align:left; font-weight:600; color:#6b7280; font-size:11px; text-transform:uppercase; border-bottom:1px solid #e5e7eb; }
    .pz-table td { padding:8px 12px; border-bottom:1px solid #f3f4f6; color:#374151; }
    .pz-table tr:hover { background:#f9fafb; }
    .pz-badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:600; }
    .pz-badge.presente { background:#d1fae5; color:#065f46; }
    .pz-badge.uscito { background:#f3f4f6; color:#6b7280; }
    .pz-ore { font-variant-numeric:tabular-nums; }
    .pz-giorni { display:flex; gap:6px; flex-wrap:wrap; margin-bottom:16px; }
    .pz-giorno { padding:6px 12px; border-radius:6px; font-size:12px; font-weight:500; text-decoration:none; border:1px solid #e5e7eb; color:#6b7280; background:#fff; }
    .pz-giorno:hover { background:#f3f4f6; color:#374151; }
    .pz-giorno.active { background:#059669; color:#fff; border-color:#059669; }
    .pz-timbrature { font-size:11px; color:#9ca3af; }
    .pz-timbrature span { display:inline-block; margin-right:6px; }
    .pz-timbrature .e { color:#059669; }
    .pz-timbrature .u { color:#dc2626; }
    .pz-anagrafica-search { padding:6px 12px; border:1px solid #e5e7eb; border-radius:6px; font-size:12px; width:220px; }
</style>

<div class="pz-page">
    <div class="pz-topbar">
        <div class="pz-topbar-inner">
            <div class="pz-logo">
                <div class="pz-logo-icon">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#fff" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div>
                    <h1 class="pz-title">Presenze</h1>
                    <div class="pz-subtitle">{{ $data->format('l d/m/Y') }}{{ $data->isToday() ? ' (oggi)' : '' }}</div>
                </div>
            </div>
            <div style="display:flex; gap:10px; align-items:center;">
                @if(!$data->isToday())
                    <a href="{{ route('owner.presenze') }}" class="pz-back">Oggi</a>
                @endif
                <a href="{{ route('owner.dashboard') }}" class="pz-back">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
                    Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="pz-body">
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
                        <th>Ore</th>
                        <th>Timbrature</th>
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
                            {{ $h }}h {{ str_pad($m, 2, '0', STR_PAD_LEFT) }}m
                        </td>
                        <td>
                            <div class="pz-timbrature">
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
