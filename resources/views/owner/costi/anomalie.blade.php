@extends('layouts.costi')

@section('content')
<link rel="stylesheet" href="{{ asset('css/costi-ui.css') }}?v={{ filemtime(public_path('css/costi-ui.css')) }}">

<div class="gn-page">
    <a href="{{ route('owner.costi.analisi.index') }}?op_token={{ request('op_token') }}" style="font-size:13px;color:var(--gn-primary);text-decoration:none;">← Torna analisi costi</a>

    <h1 style="margin-top:8px;">⚠️ Anomalie commesse</h1>
    <div class="gn-subtitle">Outlier identificati su scarti, ore, costo per pezzo (vs media cliente).</div>

    {{-- Filtri soglie --}}
    <form method="GET" class="gn-card" style="padding:14px;margin-bottom:14px;">
        <input type="hidden" name="op_token" value="{{ request('op_token') }}">
        <div style="display:grid;grid-template-columns:repeat(4, 1fr) auto;gap:10px;align-items:end;">
            <div>
                <label style="font-size:11px;color:var(--gn-muted);font-weight:600;">Mesi indietro</label>
                <input type="number" min="1" max="24" name="mesi" value="{{ $mesi }}" style="width:100%;padding:7px 10px;border:1px solid var(--gn-border);border-radius:6px;font-size:13px;">
            </div>
            <div>
                <label style="font-size:11px;color:var(--gn-muted);font-weight:600;">Soglia scarti %</label>
                <input type="number" step="0.5" min="1" name="soglia_scarti" value="{{ $sogliaScarti }}" style="width:100%;padding:7px 10px;border:1px solid var(--gn-border);border-radius:6px;font-size:13px;">
            </div>
            <div>
                <label style="font-size:11px;color:var(--gn-muted);font-weight:600;">Ore × media cliente</label>
                <input type="number" step="0.1" min="1" name="soglia_ore" value="{{ $sogliaOre }}" style="width:100%;padding:7px 10px;border:1px solid var(--gn-border);border-radius:6px;font-size:13px;">
            </div>
            <div>
                <label style="font-size:11px;color:var(--gn-muted);font-weight:600;">Costo × media cliente</label>
                <input type="number" step="0.1" min="1" name="soglia_costo" value="{{ $sogliaCosto }}" style="width:100%;padding:7px 10px;border:1px solid var(--gn-border);border-radius:6px;font-size:13px;">
            </div>
            <button class="gn-btn gn-btn-primary">Applica</button>
        </div>
    </form>

    {{-- KPI --}}
    <div class="gn-kpi-grid">
        <div class="gn-kpi">
            <div class="gn-kpi-icon blue">📋</div>
            <div class="gn-kpi-body">
                <div class="gn-kpi-value">{{ $statsGlobali['commesse_totali'] }}</div>
                <div class="gn-kpi-label">Commesse analizzate</div>
                <div class="gn-kpi-sub">ultimi {{ $mesi }} mesi</div>
            </div>
        </div>
        <div class="gn-kpi">
            <div class="gn-kpi-icon amber">⚠️</div>
            <div class="gn-kpi-body">
                <div class="gn-kpi-value">{{ $statsGlobali['anomale'] }}</div>
                <div class="gn-kpi-label">Anomalie</div>
                <div class="gn-kpi-sub">{{ $statsGlobali['pct_anomale'] }}% del totale</div>
            </div>
        </div>
        <div class="gn-kpi">
            <div class="gn-kpi-icon red">🔥</div>
            <div class="gn-kpi-body">
                <div class="gn-kpi-value">{{ $statsGlobali['alta_severita'] }}</div>
                <div class="gn-kpi-label">Severità alta</div>
                <div class="gn-kpi-sub">>2x soglia</div>
            </div>
        </div>
    </div>

    {{-- Tabella anomalie --}}
    <div class="gn-card">
        <div class="gn-card-header"><h3>Commesse anomale ({{ count($anomalie) }})</h3></div>
        <table class="gn-table">
            <thead>
                <tr>
                    <th>Sev.</th>
                    <th>Commessa</th>
                    <th>Cliente / Descrizione</th>
                    <th>Anomalie</th>
                    <th>Consegna</th>
                    <th class="num">Qta</th>
                    <th class="num">Costo €</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($anomalie as $a)
                @php
                    $maxSeverita = collect($a['tipi'])->contains('severita', 'alta') ? 'alta' : 'media';
                    $bgRow = $maxSeverita === 'alta' ? '#fef2f2' : '#fffbeb';
                @endphp
                <tr style="background:{{ $bgRow }};">
                    <td>
                        @if($maxSeverita === 'alta')
                            <span style="font-size:20px;" title="Severità alta">🔥</span>
                        @else
                            <span style="font-size:20px;" title="Severità media">⚠️</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('owner.costi.analisi.show', $a['commessa']) }}?op_token={{ request('op_token') }}" class="gn-commessa-link" target="_blank">{{ $a['commessa'] }}</a>
                    </td>
                    <td style="max-width:300px;">
                        <div>{{ $a['cliente'] }}</div>
                        <small style="color:var(--gn-muted);">{{ \Illuminate\Support\Str::limit($a['descrizione'], 70) }}</small>
                    </td>
                    <td>
                        @foreach($a['tipi'] as $t)
                        <div style="margin:2px 0;">
                            @php
                                $cls = $t['tipo'] === 'scarti' ? 'gn-badge-scarti' : ($t['tipo'] === 'ore' ? 'gn-badge-manodopera' : 'gn-badge-cliche');
                                $emoji = $t['tipo'] === 'scarti' ? '🗑️' : ($t['tipo'] === 'ore' ? '⏱️' : '💸');
                            @endphp
                            <span class="gn-badge {{ $cls }}">{{ $emoji }} {{ strtoupper($t['tipo']) }} {{ $t['valore'] }}</span>
                            <small style="color:var(--gn-muted);font-size:11px;">{{ $t['descrizione'] }}</small>
                        </div>
                        @endforeach
                    </td>
                    <td>{{ $a['consegna'] ? \Carbon\Carbon::parse($a['consegna'])->format('d/m/Y') : '-' }}</td>
                    <td class="num">{{ number_format($a['qta'] ?? 0, 0, ',', '.') }}</td>
                    <td class="num">{{ $a['totale'] !== null ? '€ '.number_format($a['totale'], 2, ',', '.') : '—' }}</td>
                    <td>
                        <a href="{{ route('owner.costi.analisi.show', $a['commessa']) }}?op_token={{ request('op_token') }}" target="_blank" class="gn-btn gn-btn-primary gn-btn-sm">Apri</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;color:#9ca3af;padding:48px;">✅ Nessuna anomalia trovata con le soglie attuali.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div style="background:#eff6ff;border:1px solid #93c5fd;color:#1e40af;padding:10px 14px;border-radius:8px;font-size:11px;margin-top:14px;">
        ℹ️ <strong>Come funziona</strong>: scarti = scarti/fogli_stampati%. Ore = ore_fasi vs media stesso cliente. Costo = €/pezzo vs media stesso cliente. Costi da cache (clicca commessa per popolare).
    </div>
</div>
@endsection
