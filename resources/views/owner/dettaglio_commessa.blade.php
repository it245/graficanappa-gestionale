@extends('layouts.app')

@section('content')
<div class="container-fluid px-3">
<style>
    h2 { margin: 10px 0; }
    .btn-back {
        background: #333;
        color: #fff;
        border: none;
        padding: 6px 16px;
        border-radius: 4px;
        font-size: 13px;
        cursor: pointer;
        text-decoration: none;
    }
    .btn-back:hover { background: #555; color: #fff; }
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        table-layout: fixed;
    }
    thead th {
        background: #000;
        color: #fff;
        padding: 6px 8px;
        border: 1px solid #dee2e6;
        font-size: 12px;
    }
    td {
        border: 1px solid #dee2e6;
        padding: 4px 8px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: normal;
        max-height: 3.9em;
        line-height: 1.3;
    }
    tr:hover td { background: rgba(0,0,0,0.03); }
    .stato-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: bold;
    }
    th:nth-child(1), td:nth-child(1) { width: 70px; text-align: center; }
    th:nth-child(2), td:nth-child(2) { width: 60px; text-align: center; }
    th:nth-child(3), td:nth-child(3) { width: 120px; }
    th:nth-child(4), td:nth-child(4) { width: 100px; }
    th:nth-child(5), td:nth-child(5) { width: 80px; text-align: center; }
    th:nth-child(6), td:nth-child(6) { width: 80px; text-align: center; }
    th:nth-child(7), td:nth-child(7) { width: 120px; }
    th:nth-child(8), td:nth-child(8) { width: 100px; }
    th:nth-child(9), td:nth-child(9) { width: 180px; }
    th:nth-child(10), td:nth-child(10) { width: 110px; }
    th:nth-child(11), td:nth-child(11) { width: 110px; }
    th:nth-child(12), td:nth-child(12) { width: 50px; text-align: center; }
    .btn-elimina {
        background: #dc3545;
        color: #fff;
        border: none;
        border-radius: 4px;
        padding: 2px 8px;
        font-size: 11px;
        cursor: pointer;
    }
    .btn-elimina:hover { background: #c82333; }
</style>

<a href="{{ route('owner.dashboard') }}" class="btn-back">&larr; Torna alla dashboard</a>
<h2>Commessa: <strong>{{ $commessa }}</strong></h2>

@php
    $statoBg = [0 => '#e9ecef', 1 => '#cfe2ff', 2 => '#fff3cd', 3 => '#d1e7dd'];
    $statoColor = [0 => '#333', 1 => '#084298', 2 => '#664d03', 3 => '#0f5132'];
@endphp

<div style="overflow-x:auto; margin-top:10px;">
    <table>
        <thead>
            <tr>
                <th>Priorit&agrave;</th>
                <th>Stato</th>
                <th>Fase</th>
                <th>Reparto</th>
                <th>Qta Carta</th>
                <th>Qta Prod.</th>
                <th>Operatori</th>
                <th>Note</th>
                <th>Descrizione</th>
                <th>Data Inizio</th>
                <th>Data Fine</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @foreach($fasi as $fase)
            <tr id="fase-row-{{ $fase->id }}">
                <td>{{ $fase->priorita !== null ? number_format($fase->priorita, 2) : '-' }}</td>
                <td>
                    <span class="stato-badge" style="background:{{ $statoBg[$fase->stato] ?? '#e9ecef' }};color:{{ $statoColor[$fase->stato] ?? '#333' }}">
                        {{ $fase->stato }}
                    </span>
                </td>
                <td>{{ $fase->faseCatalogo->nome ?? $fase->fase ?? '-' }}</td>
                <td>{{ $fase->reparto_nome ?? '-' }}</td>
                <td>{{ $fase->ordine->qta_carta ?? '-' }}</td>
                <td>{{ $fase->qta_prod ?? '-' }}</td>
                <td>
                    @forelse($fase->operatori as $op)
                        {{ $op->nome }} {{ $op->cognome }}@if(!$loop->last), @endif
                    @empty
                        -
                    @endforelse
                </td>
                <td>{{ $fase->note ?? '-' }}</td>
                <td>{{ $fase->ordine->descrizione ?? '-' }}</td>
                <td>{{ $fase->data_inizio ?? '-' }}</td>
                <td>{{ $fase->data_fine ?? '-' }}</td>
                <td><button class="btn-elimina" onclick="eliminaFase({{ $fase->id }})">&times;</button></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
</div>

<script>
function eliminaFase(faseId) {
    if (!confirm('Sei sicuro di voler eliminare questa fase?')) return;

    fetch('{{ route("owner.eliminaFase") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ fase_id: faseId })
    })
    .then(r => r.json())
    .then(d => {
        if (d.success) {
            document.getElementById('fase-row-' + faseId).remove();
        } else {
            alert('Errore: ' + (d.messaggio || 'eliminazione fallita'));
        }
    })
    .catch(err => { console.error(err); alert('Errore di connessione'); });
}
</script>
@endsection
