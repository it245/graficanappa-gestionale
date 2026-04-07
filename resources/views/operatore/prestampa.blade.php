@extends('layouts.mes')

@section('page-title', 'Prestampa')
@section('topbar-title', 'Prestampa')

@section('sidebar-items')
<div class="mes-sidebar-section">
    <div class="mes-sidebar-section-label">Prestampa</div>
    <a href="{{ route('operatore.prestampa', ['op_token' => request('op_token')]) }}" class="mes-sidebar-item active">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
        Lista Commesse
    </a>
    <a href="{{ route('operatore.dashboard', ['op_token' => request('op_token')]) }}" class="mes-sidebar-item">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
        Dashboard Operatore
    </a>
</div>
@endsection

@section('styles')
<style>
    /* ===== RESPONSIVE MOBILE ===== */
    @media (max-width: 768px) {
        /* Filters: full width */
        .d-flex.align-items-center.gap-3.mb-3.flex-wrap {
            flex-direction: column;
            align-items: stretch !important;
            gap: 8px !important;
        }
        #filtroCommessa, #filtroFustella {
            max-width: 100% !important;
            width: 100% !important;
            min-height: 44px;
            font-size: 16px !important;
        }
        .btn-outline-dark.btn-sm {
            min-height: 44px;
            font-size: 14px !important;
            justify-content: center;
            width: 100%;
        }

        /* Table */
        #tabellaCommesse { font-size: 12px !important; }
        #tabellaCommesse th, #tabellaCommesse td { padding: 6px 8px; }

        /* Hide less important columns */
        #tabellaCommesse th:nth-child(5), #tabellaCommesse td:nth-child(5), /* Qta */
        #tabellaCommesse th:nth-child(6), #tabellaCommesse td:nth-child(6), /* Data Reg */
        #tabellaCommesse th:nth-child(8), #tabellaCommesse td:nth-child(8)  /* Op. Prestampa */
        {
            display: none;
        }

        /* Make rows touch-friendly */
        .riga-commessa td { padding: 10px 8px !important; }
    }

    @media (max-width: 480px) {
        #tabellaCommesse { font-size: 11px !important; }
        /* Hide even more */
        #tabellaCommesse th:nth-child(4), #tabellaCommesse td:nth-child(4) /* Fustella */
        {
            display: none;
        }
    }
</style>
@endsection

@section('content')
<div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
    <input type="text" id="filtroCommessa" class="form-control form-control-sm" placeholder="Filtra per commessa, cliente o descrizione..." style="max-width:400px;">
    <input type="text" id="filtroFustella" class="form-control form-control-sm" placeholder="Filtra per fustella (es. FS2236)" style="max-width:200px;">
    <form method="POST" action="{{ route('operatore.prestampa.syncOnda') }}" style="margin:0;" onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').textContent='Sincronizzando...';">
        @csrf
        <button type="submit" class="btn btn-outline-dark btn-sm d-flex align-items-center gap-1">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21.5 2v6h-6"/><path d="M2.5 22v-6h6"/><path d="M2.5 11.5a10 10 0 0 1 18.8-4.3"/><path d="M21.5 12.5a10 10 0 0 1-18.8 4.2"/></svg>
            Sincronizza Onda
        </button>
    </form>
</div>

<div style="overflow-x:auto;">
    <table class="table table-bordered table-hover table-sm" style="font-size:13px;" id="tabellaCommesse">
        <thead class="table-dark">
            <tr>
                <th style="width:120px;">Commessa</th>
                <th>Cliente</th>
                <th>Descrizione</th>
                <th style="width:110px;">Fustella</th>
                <th style="width:80px;">Qta</th>
                <th style="width:110px;">Data Reg.</th>
                <th style="width:110px;">Consegna</th>
                <th style="width:150px;">Op. Prestampa</th>
                <th style="width:60px;">Note</th>
            </tr>
        </thead>
        <tbody>
            @foreach($commesse as $c)
            <tr class="riga-commessa" style="cursor:pointer;" onclick="window.location='{{ route('operatore.prestampa.dettaglio', $c->commessa) }}?op_token={{ request('op_token') }}'">
                <td><a href="{{ route('operatore.prestampa.dettaglio', $c->commessa) }}?op_token={{ request('op_token') }}" class="fw-bold text-decoration-none">{{ $c->commessa }}</a></td>
                <td>{{ $c->cliente_nome ?? '-' }}</td>
                <td style="max-width:300px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $c->descrizione ?? '-' }}</td>
                @php
                    $fustPre = \App\Helpers\DescrizioneParser::parseFustella($c->tutte_descrizioni ?? $c->descrizione ?? '', $c->cliente_nome ?? '', $c->note_prestampa ?? '');
                @endphp
                <td class="fustella-cell" style="font-weight:600;">{{ $fustPre ?: '-' }}</td>
                <td>{{ $c->qta_richiesta ? number_format($c->qta_richiesta, 0, ',', '.') : '-' }}</td>
                <td>{{ $c->data_registrazione ? \Carbon\Carbon::parse($c->data_registrazione)->format('d/m/Y') : '-' }}</td>
                <td>{{ $c->data_prevista_consegna ? \Carbon\Carbon::parse($c->data_prevista_consegna)->format('d/m/Y') : '-' }}</td>
                <td>
                    @if($c->responsabile)
                        <span class="badge bg-success">{{ $c->responsabile }}</span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td class="text-center">
                    @if($c->note_prestampa)
                        <span class="badge bg-info" title="{{ $c->note_prestampa }}">Si</span>
                    @else
                        -
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="text-muted mt-2" style="font-size:12px;">
    Totale: <strong>{{ $commesse->count() }}</strong> commesse attive
</div>
@endsection

@section('scripts')
<script>
function filtraPrestampa() {
    var filtro = document.getElementById('filtroCommessa').value.toLowerCase();
    var filtroFs = document.getElementById('filtroFustella').value.toLowerCase();
    document.querySelectorAll('.riga-commessa').forEach(function(row) {
        var testo = row.textContent.toLowerCase();
        var fustella = row.querySelector('.fustella-cell')?.textContent?.toLowerCase() || '';
        var matchTesto = !filtro || testo.includes(filtro);
        var matchFs = !filtroFs || fustella.includes(filtroFs);
        row.style.display = (matchTesto && matchFs) ? '' : 'none';
    });
}
document.getElementById('filtroCommessa').addEventListener('input', filtraPrestampa);
document.getElementById('filtroFustella').addEventListener('input', filtraPrestampa);
</script>
@endsection
