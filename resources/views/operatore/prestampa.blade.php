@extends('layouts.app')

@section('content')
<div class="container-fluid px-3">
    {{-- Header con logo, nome operatore e logout --}}
    <div class="d-flex justify-content-between align-items-center mb-3 mt-2" style="border-bottom:1px solid #dee2e6; padding-bottom:10px;">
        <div class="d-flex align-items-center gap-3">
            <img src="{{ asset('images/logo_graficanappa.png') }}" alt="Logo" style="height:36px;">
            <h2 class="mb-0">Prestampa</h2>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span style="font-size:13px; color:#555;">{{ $operatore->nome ?? '' }} {{ $operatore->cognome ?? '' }}</span>
            <a href="{{ route('operatore.logout', ['op_token' => request('op_token')]) }}" class="btn btn-outline-secondary btn-sm">Logout</a>
        </div>
    </div>

    <div class="d-flex align-items-center gap-3 mb-3 flex-wrap">
        <input type="text" id="filtroCommessa" class="form-control form-control-sm" placeholder="Filtra per commessa, cliente o descrizione..." style="max-width:400px;">
        <form method="POST" action="{{ route('owner.syncOnda') }}" style="margin:0;" onsubmit="this.querySelector('button').disabled=true; this.querySelector('button').textContent='Sincronizzando...';">
            @csrf
            <input type="hidden" name="redirect" value="{{ route('operatore.prestampa', ['op_token' => request('op_token')]) }}">
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
</div>

<script>
document.getElementById('filtroCommessa').addEventListener('input', function() {
    var filtro = this.value.toLowerCase();
    document.querySelectorAll('.riga-commessa').forEach(function(row) {
        row.style.display = row.textContent.toLowerCase().includes(filtro) ? '' : 'none';
    });
});
</script>
@endsection
