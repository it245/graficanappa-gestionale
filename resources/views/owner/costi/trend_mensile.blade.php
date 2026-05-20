@extends('layouts.costi')

@section('content')
<link rel="stylesheet" href="{{ asset('css/costi-ui.css') }}?v={{ filemtime(public_path('css/costi-ui.css')) }}">

@php
$fmtHm = function ($sec) {
    $h = intdiv((int)$sec, 3600);
    return $h . 'h';
};
$totaleAggregato = array_sum(array_column($mesiList, 'totale'));
$commesseAggregate = array_sum(array_column($mesiList, 'commesse'));
$mediaMensile = count($mesiList) > 0 ? $totaleAggregato / count($mesiList) : 0;
@endphp

<div class="gn-page">
    <a href="{{ route('owner.costi.analisi.index') }}?op_token={{ request('op_token') }}" style="font-size:13px;color:var(--gn-primary);text-decoration:none;">← Torna analisi costi</a>

    <div style="display:flex;justify-content:space-between;align-items:center;margin:8px 0 14px 0;">
        <div>
            <h1>📈 Trend mensile costi</h1>
            <div class="gn-subtitle">Andamento costi commesse terminate ultimi {{ $mesi }} mesi.</div>
        </div>
        <form method="GET" style="display:flex;gap:8px;align-items:center;">
            <input type="hidden" name="op_token" value="{{ request('op_token') }}">
            <label style="font-size:12px;color:var(--gn-muted);">Mesi:</label>
            <select name="mesi" onchange="this.form.submit()" style="padding:7px 10px;border:1px solid var(--gn-border);border-radius:6px;font-size:13px;">
                @foreach([3, 6, 12, 18, 24] as $m)
                <option value="{{ $m }}" {{ $mesi == $m ? 'selected' : '' }}>{{ $m }}</option>
                @endforeach
            </select>
            <a href="{{ route('owner.costi.trend', ['mesi' => $mesi, 'refresh' => 1, 'op_token' => request('op_token')]) }}" class="gn-btn gn-btn-secondary" title="Ricalcola">🔄</a>
        </form>
    </div>

    @if(session('success'))
    <div style="background:#d1fae5;border:1px solid #6ee7b7;color:#065f46;padding:10px 14px;border-radius:8px;margin-bottom:14px;">{{ session('success') }}</div>
    @endif

    <div style="background:#eff6ff;border:1px solid #93c5fd;color:#1e40af;padding:10px 14px;border-radius:8px;font-size:12px;margin-bottom:14px;display:flex;justify-content:space-between;align-items:center;gap:14px;">
        <div>
            📊 <strong>{{ $infoCache['cached'] ?? 0 }}</strong> commesse precise (cache) · <strong>{{ $infoCache['stimati'] ?? 0 }}</strong> stimate (€150/h + €0.30/fg) su <strong>{{ $infoCache['totale_comm'] ?? 0 }}</strong> totali.
            @if(($infoCache['stimati'] ?? 0) > 0)
            <br><span style="color:#92400e;">⚠️ Per maggiore precisione precalcola le commesse stimate →</span>
            @endif
        </div>
        @if(($infoCache['stimati'] ?? 0) > 0)
        <form method="POST" action="{{ route('owner.costi.trend.precalcola') }}?op_token={{ request('op_token') }}">
            @csrf
            <button class="gn-btn gn-btn-primary" title="Calcola totale preciso 50 commesse (Prinect API, carta, ecc). Richiede ~1-2 min.">⚡ Precalcola 50</button>
        </form>
        @endif
    </div>

    <div class="gn-kpi-grid">
        <div class="gn-kpi">
            <div class="gn-kpi-icon blue">📋</div>
            <div class="gn-kpi-body">
                <div class="gn-kpi-value">{{ $commesseAggregate }}</div>
                <div class="gn-kpi-label">Commesse</div>
                <div class="gn-kpi-sub">in {{ $mesi }} mesi</div>
            </div>
        </div>
        <div class="gn-kpi">
            <div class="gn-kpi-icon green">💰</div>
            <div class="gn-kpi-body">
                <div class="gn-kpi-value">€ {{ number_format($totaleAggregato, 0, ',', '.') }}</div>
                <div class="gn-kpi-label">Totale costi</div>
                <div class="gn-kpi-sub">aggregato</div>
            </div>
        </div>
        <div class="gn-kpi">
            <div class="gn-kpi-icon amber">📊</div>
            <div class="gn-kpi-body">
                <div class="gn-kpi-value">€ {{ number_format($mediaMensile, 0, ',', '.') }}</div>
                <div class="gn-kpi-label">Media mensile</div>
                <div class="gn-kpi-sub">€/mese</div>
            </div>
        </div>
    </div>

    <div class="gn-card">
        <div class="gn-card-header"><h3>Distribuzione mensile</h3></div>
        <div class="gn-card-body">
            @foreach($mesiList as $m => $dati)
            @php
                $pct = $maxTotale > 0 ? $dati['totale'] / $maxTotale * 100 : 0;
                $label = \Carbon\Carbon::createFromFormat('Y-m', $m)->locale('it')->isoFormat('MMM YYYY');
            @endphp
            <div style="display:grid;grid-template-columns:100px 1fr 140px 80px 80px;gap:12px;align-items:center;padding:8px 0;border-bottom:1px solid #f3f4f6;">
                <div style="font-size:12px;font-weight:600;color:var(--gn-text);text-transform:capitalize;">{{ $label }}</div>
                <div style="height:24px;background:#f3f4f6;border-radius:6px;overflow:hidden;">
                    <div style="width:{{ $pct }}%;height:100%;background:linear-gradient(90deg, #2563eb, #3b82f6);border-radius:6px;transition:width .3s;"></div>
                </div>
                <div class="num" style="font-size:13px;font-weight:600;color:var(--gn-primary-dark);">€ {{ number_format($dati['totale'], 2, ',', '.') }}</div>
                <div class="num" style="font-size:12px;color:var(--gn-muted);">{{ $dati['commesse'] }} comm.</div>
                <div class="num" style="font-size:12px;color:var(--gn-muted);">{{ $fmtHm($dati['ore_sec']) }}</div>
            </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
