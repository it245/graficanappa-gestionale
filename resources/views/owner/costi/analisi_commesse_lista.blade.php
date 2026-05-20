@extends('layouts.costi')

@php
$fmtHm = function ($sec) {
    $sec = (int) $sec;
    if ($sec <= 0) return '0m';
    $h = intdiv($sec, 3600);
    $m = intdiv($sec % 3600, 60);
    if ($h === 0) return $m.'m';
    return $h.'h '.str_pad((string)$m, 2, '0', STR_PAD_LEFT).'m';
};
@endphp

@section('content')
<link rel="stylesheet" href="{{ asset('css/costi-ui.css') }}?v={{ filemtime(public_path('css/costi-ui.css')) }}">

<div class="gn-page">
    <h1>Analisi Commesse Terminate</h1>
    <div class="gn-subtitle">Visualizza e analizza i costi delle commesse concluse</div>

    <form method="GET" action="{{ route('owner.costi.analisi.index') }}" class="gn-filters">
        <div class="gn-search">
            <input type="text" name="q" value="{{ $search }}" placeholder="Cerca per commessa, cliente, descrizione...">
        </div>
        <button class="gn-btn gn-btn-primary">Filtra</button>
        @if($search)
        <a href="{{ route('owner.costi.analisi.index') }}" class="gn-btn gn-btn-secondary">Reset</a>
        @endif
        <a href="{{ route('owner.analisi.custom.index') }}?op_token={{ request('op_token') }}" class="gn-btn gn-btn-secondary" style="margin-left:auto;">📊 Analisi Custom</a>
    </form>

    <div class="gn-card">
        <div style="overflow-x:auto;">
        <table class="gn-table" id="tblCommesse">
            @php
                $sortLink = function ($col, $label, $align = 'left') use ($sort, $dir, $search) {
                    $newDir = ($sort === $col && $dir === 'asc') ? 'desc' : 'asc';
                    $icon = $sort === $col ? ($dir === 'asc' ? '▲' : '▼') : '⇅';
                    $color = $sort === $col ? 'color:var(--gn-primary);' : 'color:#9ca3af;';
                    $url = url()->current() . '?' . http_build_query(['q' => $search, 'sort' => $col, 'dir' => $newDir, 'op_token' => request('op_token')]);
                    $cls = $align === 'right' ? 'num' : '';
                    return '<th class="'.$cls.'"><a href="'.$url.'" style="color:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:4px;">'.$label.' <span style="'.$color.'font-size:10px;">'.$icon.'</span></a></th>';
                };
            @endphp
            <thead>
                <tr>
                    {!! $sortLink('commessa', 'Commessa') !!}
                    {!! $sortLink('cliente_nome', 'Cliente') !!}
                    {!! $sortLink('descrizione', 'Descrizione') !!}
                    {!! $sortLink('data_prevista_consegna', 'Consegna') !!}
                    {!! $sortLink('ore_sec', 'Ore tot', 'right') !!}
                    {!! $sortLink('fogli_max', 'Fogli', 'right') !!}
                    {!! $sortLink('tiri_tot', 'Tiri', 'right') !!}
                    {!! $sortLink('inchiostro_tot', 'Inchiostro (g)', 'right') !!}
                    {!! $sortLink('scarti_tot', 'Scarti', 'right') !!}
                    {!! $sortLink('altri_tot', 'Altri €', 'right') !!}
                    <th>Azione</th>
                </tr>
            </thead>
            <tbody>
                @forelse($righe as $r)
                @php
                    $agg = $aggregates[$r->commessa] ?? null;
                    $fg  = $fogli[$r->commessa] ?? null;
                    $ac  = $altri[$r->commessa] ?? null;
                    $orepr = $oreReparti[$r->commessa] ?? collect();
                    $tooltipOre = $orepr->map(fn($x) => $x->reparto.': '.$fmtHm($x->sec))->implode("\n");
                @endphp
                <tr>
                    <td><a href="{{ route('owner.costi.analisi.show', $r->commessa) }}?op_token={{ request('op_token') }}" class="gn-commessa-link">{{ $r->commessa }}</a></td>
                    <td style="max-width:170px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $r->cliente_nome ?? '' }}">{{ $r->cliente_nome ?? '-' }}</td>
                    <td style="max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $r->descrizione ?? '' }}">{{ $r->descrizione ?? '-' }}</td>
                    <td>{{ $r->data_prevista_consegna ? \Carbon\Carbon::parse($r->data_prevista_consegna)->format('d/m/Y') : '-' }}</td>
                    <td class="num" title="{{ $tooltipOre }}">{{ $agg ? $fmtHm($agg->ore_sec) : '—' }}</td>
                    <td class="num">{{ $fg && $fg->fogli ? number_format($fg->fogli, 0, ',', '.') : '—' }}</td>
                    <td class="num">{{ $agg && $agg->tiri_tot > 0 ? number_format($agg->tiri_tot, 0, ',', '.') : '—' }}</td>
                    <td class="num">{{ $agg && $agg->inchiostro_tot > 0 ? number_format($agg->inchiostro_tot, 0, ',', '.') : '—' }}</td>
                    <td class="num" style="color:#dc2626;">{{ $agg && $agg->scarti_tot > 0 ? number_format($agg->scarti_tot, 0, ',', '.') : '—' }}</td>
                    <td class="num">{{ $ac && $ac->tot > 0 ? '€ '.number_format($ac->tot, 2, ',', '.') : '—' }}</td>
                    <td>
                        <a href="{{ route('owner.costi.analisi.show', $r->commessa) }}?op_token={{ request('op_token') }}" class="gn-btn gn-btn-primary gn-btn-sm">Apri</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="11" style="text-align:center;color:#9ca3af;padding:48px;">Nessuna commessa terminata trovata.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
        <div class="gn-pagination">
            <div style="font-size:12px;color:var(--gn-muted);">{{ $righe->firstItem() ?? 0 }}–{{ $righe->lastItem() ?? 0 }} di {{ $righe->total() }} risultati</div>
            <div class="pager">{{ $righe->links() }}</div>
        </div>
    </div>
</div>

@endsection
