@extends('layouts.costi')

@section('content')
<link rel="stylesheet" href="{{ asset('css/costi-ui.css') }}?v={{ filemtime(public_path('css/costi-ui.css')) }}">

@php
$fmtHm = function ($sec) {
    $h = intdiv((int)$sec, 3600);
    $m = intdiv(((int)$sec) % 3600, 60);
    return $h . 'h ' . str_pad((string)$m, 2, '0', STR_PAD_LEFT) . 'm';
};
$maxTotale = max(array_column($colonne, 'totale')) ?: 1;
@endphp

<div class="gn-page">
    <a href="{{ route('owner.analisi.custom.confrontaSelect') }}?op_token={{ request('op_token') }}" style="font-size:13px;color:var(--gn-primary);text-decoration:none;">← Cambia selezione</a>

    <h1 style="margin-top:8px;">⚖️ Confronto {{ count($colonne) }} analisi</h1>
    <div class="gn-subtitle">Confronto side-by-side delle analisi selezionate.</div>

    {{-- Header columns: nome --}}
    <div class="gn-card">
        <div class="gn-card-header"><h3>📊 Metriche principali</h3></div>
        <table class="gn-table">
            <thead>
                <tr>
                    <th style="width:200px;">Metrica</th>
                    @foreach($colonne as $c)
                    <th>{{ $c['analisi']->nome }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Commesse</strong></td>
                    @foreach($colonne as $c)
                    <td><span style="font-size:18px;font-weight:700;">{{ $c['n_commesse'] }}</span></td>
                    @endforeach
                </tr>
                <tr>
                    <td><strong>Totale costi €</strong></td>
                    @foreach($colonne as $c)
                    @php $pct = $maxTotale > 0 ? $c['totale'] / $maxTotale * 100 : 0; @endphp
                    <td>
                        <div style="font-size:18px;font-weight:700;color:var(--gn-primary-dark);">€ {{ number_format($c['totale'], 2, ',', '.') }}</div>
                        <div style="height:6px;background:#f3f4f6;border-radius:3px;overflow:hidden;margin-top:4px;width:160px;">
                            <div style="width:{{ $pct }}%;height:100%;background:var(--gn-primary);"></div>
                        </div>
                    </td>
                    @endforeach
                </tr>
                <tr>
                    <td><strong>Costo medio commessa</strong></td>
                    @foreach($colonne as $c)
                    <td>€ {{ number_format($c['media'], 2, ',', '.') }}</td>
                    @endforeach
                </tr>
                <tr>
                    <td><strong>Ore totali</strong></td>
                    @foreach($colonne as $c)
                    <td>{{ $fmtHm($c['ore_sec']) }}</td>
                    @endforeach
                </tr>
                <tr>
                    <td><strong>Autore</strong></td>
                    @foreach($colonne as $c)
                    <td><small style="color:var(--gn-muted);">{{ $c['analisi']->autore }}</small></td>
                    @endforeach
                </tr>
                <tr>
                    <td><strong>Apri</strong></td>
                    @foreach($colonne as $c)
                    <td><a href="{{ route('owner.analisi.custom.show', $c['analisi']->id) }}?op_token={{ request('op_token') }}" class="gn-btn gn-btn-secondary gn-btn-sm">↗ Dettaglio</a></td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>

    <div class="gn-card">
        <div class="gn-card-header"><h3>💸 Distribuzione costi per categoria</h3></div>
        <table class="gn-table">
            <thead>
                <tr>
                    <th>Categoria</th>
                    @foreach($colonne as $c)
                    <th class="num">{{ $c['analisi']->nome }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($tutteCategorie as $cat)
                <tr>
                    <td><span class="gn-badge gn-badge-{{ $cat }}">{{ $cat }}</span></td>
                    @foreach($colonne as $c)
                    @php $val = $c['per_categoria'][$cat] ?? 0; $pct = $c['totale'] > 0 ? $val / $c['totale'] * 100 : 0; @endphp
                    <td class="num">
                        @if($val > 0)
                        € {{ number_format($val, 2, ',', '.') }}
                        <small style="color:var(--gn-muted);"> ({{ number_format($pct, 1, ',', '.') }}%)</small>
                        @else
                        <small style="color:#9ca3af;">—</small>
                        @endif
                    </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
