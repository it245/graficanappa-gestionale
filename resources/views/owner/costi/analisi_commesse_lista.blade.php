@extends('layouts.app')

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
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="m-0">Analisi Costi — Commesse Terminate</h2>
        <div class="text-muted small">Tutte le fasi avviate a stato 3 o 4</div>
    </div>

    <form method="GET" class="mb-3" action="{{ route('owner.costi.analisi.index') }}">
        <div class="input-group" style="max-width:500px;">
            <input type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Cerca commessa, cliente, descrizione…">
            <button class="btn btn-primary" type="submit">Cerca</button>
            @if($search)
            <a href="{{ route('owner.costi.analisi.index') }}" class="btn btn-outline-secondary">Reset</a>
            @endif
        </div>
    </form>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
            <table class="table table-hover table-sm mb-0" style="font-size:13px;">
                <thead class="table-dark">
                    <tr>
                        <th style="width:110px;">Commessa</th>
                        <th style="width:160px;">Cliente</th>
                        <th>Descrizione</th>
                        <th style="width:100px;">Consegna</th>
                        <th style="width:80px;text-align:right;">Ore Tot.</th>
                        <th style="width:80px;text-align:right;">Fogli</th>
                        <th style="width:70px;text-align:right;">Scarti</th>
                        <th style="width:90px;text-align:right;">Altri €</th>
                        <th style="width:80px;text-align:right;">Azione</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($righe as $r)
                    @php
                        $agg = $aggregates[$r->commessa] ?? null;
                        $fg  = $fogli[$r->commessa] ?? null;
                        $ac  = $altri[$r->commessa] ?? null;
                    @endphp
                    <tr>
                        <td><strong>{{ $r->commessa }}</strong></td>
                        <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $r->cliente_nome ?? '' }}">{{ $r->cliente_nome ?? '-' }}</td>
                        <td style="max-width:350px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="{{ $r->descrizione ?? '' }}">{{ $r->descrizione ?? '-' }}</td>
                        <td>{{ $r->data_prevista_consegna ? \Carbon\Carbon::parse($r->data_prevista_consegna)->format('d/m/Y') : '-' }}</td>
                        <td class="text-end font-monospace">{{ $agg ? $fmtHm($agg->ore_sec) : '—' }}</td>
                        <td class="text-end font-monospace">{{ $fg && $fg->fogli ? number_format($fg->fogli, 0, ',', '.') : '—' }}</td>
                        <td class="text-end font-monospace text-danger">{{ $agg && $agg->scarti_tot > 0 ? number_format($agg->scarti_tot, 0, ',', '.') : '—' }}</td>
                        <td class="text-end font-monospace">{{ $ac && $ac->tot > 0 ? '€ '.number_format($ac->tot, 2, ',', '.') : '—' }}</td>
                        <td class="text-end">
                            <a href="{{ route('owner.costi.analisi.show', $r->commessa) }}?op_token={{ request('op_token') }}" class="btn btn-sm btn-primary">Dettaglio</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">Nessuna commessa terminata trovata.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        {{ $righe->links() }}
    </div>
</div>
@endsection
