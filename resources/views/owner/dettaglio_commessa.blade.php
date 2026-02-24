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
    td[contenteditable] {
        user-select: text;
        cursor: text;
    }
    td[contenteditable]:focus {
        outline: 2px solid #0d6efd;
        outline-offset: -2px;
        background: #f0f7ff !important;
    }
    .stato-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: bold;
        cursor: pointer;
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
    .preview-card {
        border-radius: 12px;
        border: 1px solid #dee2e6;
        overflow: hidden;
        margin-bottom: 15px;
    }
    .preview-card img {
        max-width: 100%;
        max-height: 220px;
        border-radius: 8px;
        border: 1px solid #dee2e6;
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-2 mt-2">
    <div>
        <a href="{{ route('owner.dashboard') }}" class="btn-back">&larr; Torna alla dashboard</a>
        <h2 class="d-inline ms-3">Commessa: <strong>{{ $commessa }}</strong></h2>
    </div>
    <div class="d-flex gap-2">
        @php $jobIdNum = ltrim(substr($commessa, 0, 7), '0'); @endphp
        @if($jobIdNum && is_numeric($jobIdNum))
            <a href="{{ route('mes.prinect.jobDetail', $jobIdNum) }}" class="btn btn-outline-secondary btn-sm">Dettaglio Prinect</a>
        @endif
        <a href="{{ route('mes.prinect.report', $commessa) }}" class="btn btn-outline-success btn-sm">Report Stampa</a>
    </div>
</div>

{{-- Anteprima foglio di stampa --}}
@if($preview)
<div class="row mb-3">
    <div class="col-md-4">
        <div class="preview-card p-3 text-center bg-white shadow-sm">
            <div class="fw-bold mb-2" style="font-size:13px;">Anteprima foglio di stampa</div>
            <img src="data:{{ $preview['mimeType'] }};base64,{{ $preview['data'] }}" alt="Preview">
        </div>
    </div>
</div>
@endif

{{-- Info Onda --}}
@if($ordine)
<div class="row g-2 mb-3" style="font-size:13px;">
    <div class="col-md-4">
        <div class="border rounded p-2 h-100" style="background:#f8f9fa">
            <strong class="d-block mb-1">Operatore Prestampa</strong>
            <span class="{{ $ordine->responsabile ? '' : 'text-muted' }}">{{ $ordine->responsabile ?: '-' }}</span>
        </div>
    </div>
    <div class="col-md-4">
        <div class="border rounded p-2 h-100" style="background:#f8f9fa">
            <strong class="d-block mb-1">Note Prestampa</strong>
            <span class="{{ $ordine->note_prestampa ? '' : 'text-muted' }}">{{ $ordine->note_prestampa ?: '-' }}</span>
        </div>
    </div>
    <div class="col-md-4">
        <div class="border rounded p-2 h-100" style="background:#f8f9fa">
            <strong class="d-block mb-1">Commento Produzione</strong>
            <span class="{{ $ordine->commento_produzione ? '' : 'text-muted' }}">{{ $ordine->commento_produzione ?: '-' }}</span>
        </div>
    </div>
</div>
@endif

@php
    $statoBg = [0 => '#e9ecef', 1 => '#cfe2ff', 2 => '#fff3cd', 3 => '#d1e7dd', 4 => '#c3c3c3'];
    $statoColor = [0 => '#333', 1 => '#084298', 2 => '#664d03', 3 => '#0f5132', 4 => '#1a1a1a'];
    $statoLabel = [0 => 'Caricato', 1 => 'Pronto', 2 => 'Avviato', 3 => 'Terminato', 4 => 'Consegnato'];
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
            <tr id="fase-row-{{ $fase->id }}" data-id="{{ $fase->id }}">
                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'priorita', this.innerText)">{{ $fase->priorita !== null ? number_format($fase->priorita, 2) : '-' }}</td>
                <td contenteditable onblur="aggiornaStato({{ $fase->id }}, this.innerText)">
                    <span class="stato-badge" style="background:{{ $statoBg[$fase->stato] ?? '#e9ecef' }};color:{{ $statoColor[$fase->stato] ?? '#333' }}">
                        {{ $fase->stato }}
                    </span>
                </td>
                <td>{{ $fase->faseCatalogo->nome_display ?? $fase->fase ?? '-' }}</td>
                <td>{{ $fase->reparto_nome ?? '-' }}</td>
                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'qta_carta', this.innerText)">{{ $fase->ordine->qta_carta ?? '-' }}</td>
                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'qta_prod', this.innerText)">{{ $fase->qta_prod ?? '-' }}</td>
                <td>
                    @forelse($fase->operatori as $op)
                        {{ $op->nome }} {{ $op->cognome }}@if(!$loop->last), @endif
                    @empty
                        -
                    @endforelse
                </td>
                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'note', this.innerText)">{{ $fase->note ?? '-' }}</td>
                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'descrizione', this.innerText)">{{ $fase->ordine->descrizione ?? '-' }}</td>
                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'data_inizio', this.innerText)">{{ $fase->data_inizio ?? '-' }}</td>
                <td contenteditable onblur="aggiornaCampo({{ $fase->id }}, 'data_fine', this.innerText)">{{ $fase->data_fine ?? '-' }}</td>
                <td><button class="btn-elimina" onclick="eliminaFase({{ $fase->id }})">&times;</button></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
</div>

<script>
function aggiornaCampo(faseId, campo, valore) {
    valore = valore.trim();
    if (valore === '-') valore = '';

    const campiNumerici = ['qta_richiesta','qta_prod','priorita','qta_carta','ore'];
    if (campiNumerici.includes(campo)) {
        valore = valore.replace(',', '.');
        if (valore && isNaN(parseFloat(valore))) {
            alert('Valore numerico non valido');
            return;
        }
    }

    fetch('{{ route("owner.aggiornaCampo") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ fase_id: faseId, campo: campo, valore: valore })
    })
    .then(r => r.json())
    .then(d => {
        if (!d.success) {
            alert('Errore salvataggio: ' + (d.messaggio || ''));
        } else if (d.reload) {
            window.location.reload();
        }
    })
    .catch(err => { console.error(err); alert('Errore di connessione'); });
}

function aggiornaStato(faseId, testo) {
    const nuovoStato = parseInt(testo.trim());
    if (isNaN(nuovoStato) || nuovoStato < 0 || nuovoStato > 4) {
        alert('Stato non valido. Usa: 0, 1, 2, 3, 4');
        return;
    }
    fetch('{{ route("owner.aggiornaStato") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ fase_id: faseId, stato: nuovoStato })
    })
    .then(r => r.json())
    .then(d => {
        if (!d.success) {
            alert('Errore: ' + (d.messaggio || ''));
        } else {
            const bgMap = {0:'#e9ecef', 1:'#cfe2ff', 2:'#fff3cd', 3:'#d1e7dd', 4:'#c3c3c3'};
            const colorMap = {0:'#333', 1:'#084298', 2:'#664d03', 3:'#0f5132', 4:'#1a1a1a'};
            const row = document.querySelector('tr[data-id="' + faseId + '"]');
            if (row) {
                const badge = row.querySelector('.stato-badge');
                if (badge) {
                    badge.style.background = bgMap[nuovoStato] || '#e9ecef';
                    badge.style.color = colorMap[nuovoStato] || '#333';
                    badge.innerText = nuovoStato;
                }
            }
        }
    })
    .catch(err => { console.error(err); alert('Errore di connessione'); });
}

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
