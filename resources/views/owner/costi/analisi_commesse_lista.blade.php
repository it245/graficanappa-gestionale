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
            <thead>
                <tr>
                    <th class="gn-sortable" data-col="0" data-type="str">Commessa <span class="gn-sort-ic">⇅</span></th>
                    <th class="gn-sortable" data-col="1" data-type="str">Cliente <span class="gn-sort-ic">⇅</span></th>
                    <th class="gn-sortable" data-col="2" data-type="str">Descrizione <span class="gn-sort-ic">⇅</span></th>
                    <th class="gn-sortable" data-col="3" data-type="date">Consegna <span class="gn-sort-ic">⇅</span></th>
                    <th class="gn-sortable num" data-col="4" data-type="dur">Ore tot <span class="gn-sort-ic">⇅</span></th>
                    <th class="gn-sortable num" data-col="5" data-type="num">Fogli <span class="gn-sort-ic">⇅</span></th>
                    <th class="gn-sortable num" data-col="6" data-type="num">Tiri <span class="gn-sort-ic">⇅</span></th>
                    <th class="gn-sortable num" data-col="7" data-type="num">Inchiostro (g) <span class="gn-sort-ic">⇅</span></th>
                    <th class="gn-sortable num" data-col="8" data-type="num">Scarti <span class="gn-sort-ic">⇅</span></th>
                    <th class="gn-sortable num" data-col="9" data-type="num">Altri € <span class="gn-sort-ic">⇅</span></th>
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

<style>
.gn-sortable { cursor: pointer; user-select: none; }
.gn-sortable:hover { background: #e5e7eb; }
.gn-sort-ic { font-size: 10px; color: #9ca3af; margin-left: 4px; }
.gn-sortable.sort-asc .gn-sort-ic::before { content: "▲"; color: var(--gn-primary); }
.gn-sortable.sort-desc .gn-sort-ic::before { content: "▼"; color: var(--gn-primary); }
.gn-sortable.sort-asc .gn-sort-ic, .gn-sortable.sort-desc .gn-sort-ic { font-size: 9px; }
.gn-sortable.sort-asc .gn-sort-ic, .gn-sortable.sort-desc .gn-sort-ic { color: var(--gn-primary); }
.gn-sortable.sort-asc .gn-sort-ic { content: ""; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tbl = document.getElementById('tblCommesse');
    if (!tbl) return;
    const tbody = tbl.querySelector('tbody');
    const headers = tbl.querySelectorAll('th.gn-sortable');

    const parseValue = function(text, type) {
        text = (text || '').trim();
        if (text === '—' || text === '-' || text === '') return type === 'num' || type === 'dur' ? -Infinity : '';
        if (type === 'num') return parseFloat(text.replace(/[^\d.,-]/g, '').replace(/\./g, '').replace(',', '.')) || 0;
        if (type === 'dur') {
            // formati "Xh Ym" / "Ym" → minuti
            let h = 0, m = 0;
            const mh = text.match(/(\d+)h/); if (mh) h = parseInt(mh[1]);
            const mm = text.match(/(\d+)m/); if (mm) m = parseInt(mm[1]);
            return h * 60 + m;
        }
        if (type === 'date') {
            const p = text.split('/'); if (p.length === 3) return p[2]+p[1].padStart(2,'0')+p[0].padStart(2,'0');
            return text;
        }
        return text.toLowerCase();
    };

    headers.forEach(function(th) {
        th.addEventListener('click', function() {
            const col = parseInt(th.dataset.col);
            const type = th.dataset.type;
            let dir = 'asc';
            if (th.classList.contains('sort-asc')) dir = 'desc';
            headers.forEach(h => h.classList.remove('sort-asc', 'sort-desc'));
            th.classList.add('sort-' + dir);

            const rows = Array.from(tbody.querySelectorAll('tr')).filter(r => r.children.length > 1);
            rows.sort(function(a, b) {
                const va = parseValue(a.children[col]?.textContent, type);
                const vb = parseValue(b.children[col]?.textContent, type);
                if (va < vb) return dir === 'asc' ? -1 : 1;
                if (va > vb) return dir === 'asc' ? 1 : -1;
                return 0;
            });
            rows.forEach(r => tbody.appendChild(r));
        });
    });
});
</script>
@endsection
