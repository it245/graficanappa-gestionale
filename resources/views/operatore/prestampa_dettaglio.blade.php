@extends('layouts.mes')

@section('page-title')Prestampa - {{ $commessa }}@endsection
@section('topbar-title')Prestampa - {{ $commessa }}@endsection

@section('sidebar-items')
<div class="mes-sidebar-section">
    <div class="mes-sidebar-section-label">Prestampa</div>
    <a href="{{ route('operatore.prestampa', ['op_token' => request('op_token')]) }}" class="mes-sidebar-item">
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
    .btn-back {
        background: #333; color: #fff; border: none; padding: 6px 16px;
        border-radius: 4px; font-size: 13px; cursor: pointer; text-decoration: none;
    }
    .btn-back:hover { background: #555; color: #fff; }
    .campo-editabile {
        border: 1px solid transparent; border-radius: 4px; padding: 4px 8px;
        min-height: 36px; cursor: text; transition: border-color 0.2s;
    }
    .campo-editabile:hover { border-color: #ccc; }
    .campo-editabile:focus {
        outline: 2px solid #0d6efd; outline-offset: -2px;
        background: #f0f7ff !important; border-color: #0d6efd;
    }
    .campo-salvato { border-color: #28a745 !important; transition: border-color 0.3s; }
    .prestampa-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .prestampa-table thead th {
        background: #000; color: #fff; padding: 6px 8px; border: 1px solid #dee2e6; font-size: 12px;
    }
    .prestampa-table td { border: 1px solid #dee2e6; padding: 4px 8px; }
    .prestampa-table tr:hover td { background: rgba(0,0,0,0.03); }
    .stato-badge {
        display: inline-block; padding: 3px 10px; border-radius: 12px;
        font-size: 12px; font-weight: bold;
    }

    /* ===== RESPONSIVE MOBILE ===== */
    @media (max-width: 768px) {
        /* Back button + title */
        .d-flex.justify-content-between.align-items-center.mb-2 {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 6px;
        }
        .btn-back {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            padding: 8px 16px !important;
            font-size: 14px !important;
        }
        h2 { font-size: 18px; }
        h2.d-inline { display: block !important; margin-left: 0 !important; margin-top: 4px; }

        /* Info cards: full width columns */
        .row.g-2.mb-2 > [class*="col-md-"] {
            flex: 0 0 100%;
            max-width: 100%;
        }
        .row.g-2.mb-3 > [class*="col-md-"] {
            flex: 0 0 100%;
            max-width: 100%;
        }

        /* Editable fields */
        .campo-editabile {
            min-height: 44px;
            font-size: 14px;
            padding: 8px 10px;
        }

        /* Progress bar section */
        .border.rounded.p-3.mb-3 {
            padding: 10px !important;
        }
        .border.rounded.p-3.mb-3 .d-flex {
            flex-direction: column;
            gap: 4px;
        }

        /* Table */
        .prestampa-table { font-size: 12px; }
        .prestampa-table thead th { padding: 6px; font-size: 11px; }
        .prestampa-table td { padding: 6px; }

        /* Hide less important columns on mobile */
        .prestampa-table th:nth-child(4), .prestampa-table td:nth-child(4), /* Qta Carta */
        .prestampa-table th:nth-child(8), .prestampa-table td:nth-child(8), /* Descrizione */
        .prestampa-table th:nth-child(9), .prestampa-table td:nth-child(9), /* Data Inizio */
        .prestampa-table th:nth-child(10), .prestampa-table td:nth-child(10) /* Data Fine */
        {
            display: none;
        }
    }

    @media (max-width: 480px) {
        .prestampa-table { font-size: 11px; }
        /* Hide even more columns */
        .prestampa-table th:nth-child(3), .prestampa-table td:nth-child(3), /* Reparto */
        .prestampa-table th:nth-child(6), .prestampa-table td:nth-child(6)  /* Operatori */
        {
            display: none;
        }
    }
</style>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-2">
    <div>
        <a href="{{ route('operatore.prestampa', ['op_token' => request('op_token')]) }}" class="btn-back">&larr; Lista Commesse</a>
        <h2 class="d-inline ms-3">Commessa: <strong>{{ $commessa }}</strong></h2>
    </div>
</div>

{{-- Info Commessa --}}
@php
    $tutteDesc = $ordini->pluck('descrizione')->filter()->unique()->implode(' | ');
    $coloriCalc = \App\Helpers\DescrizioneParser::parseColori($tutteDesc, $ordine->cliente_nome ?? '');
    $fustellaCalc = \App\Helpers\DescrizioneParser::parseFustella($tutteDesc, $ordine->cliente_nome ?? '', $ordine->note_prestampa ?? '');
    $mirko = $isMirko ?? false;
@endphp
<div class="row g-2 mb-2" style="font-size:13px;">
    <div class="col-md-4">
        <div class="border rounded p-2 h-100" style="background:{{ $mirko ? '#e8f4fd' : '#fff3cd' }}">
            <strong class="d-block mb-1">Descrizione</strong>
            @if($mirko)
                <span>{{ $ordine->descrizione ?: '-' }}</span>
            @else
                <div contenteditable class="campo-editabile" data-campo="descrizione" data-ordine="{{ $ordine->id }}"
                     onblur="salvaCampoPrestampa(this)" style="min-height:40px;">{{ $ordine->descrizione ?: '' }}</div>
            @endif
        </div>
    </div>
    <div class="col-md-2">
        <div class="border rounded p-2 h-100" style="background:#e8f4fd">
            <strong class="d-block mb-1">Cliente</strong>
            <span>{{ $ordine->cliente_nome ?: '-' }}</span>
        </div>
    </div>
    <div class="col-md-1">
        <div class="border rounded p-2 h-100" style="background:{{ $mirko ? '#e8f4fd' : '#fff3cd' }}">
            <strong class="d-block mb-1">Qta</strong>
            @if($mirko)
                <span>{{ $ordine->qta_richiesta ? number_format($ordine->qta_richiesta, 0, ',', '.') : '-' }}</span>
            @else
                <div contenteditable class="campo-editabile" data-campo="qta_richiesta" data-ordine="{{ $ordine->id }}"
                     onblur="salvaCampoPrestampa(this)">{{ $ordine->qta_richiesta ? number_format($ordine->qta_richiesta, 0, ',', '.') : '' }}</div>
            @endif
        </div>
    </div>
    <div class="col-md-1">
        <div class="border rounded p-2 h-100" style="background:#e8f4fd">
            <strong class="d-block mb-1">Data Reg.</strong>
            <span>{{ $ordine->data_registrazione ? \Carbon\Carbon::parse($ordine->data_registrazione)->format('d/m/Y') : '-' }}</span>
        </div>
    </div>
    <div class="col-md-1">
        <div class="border rounded p-2 h-100" style="background:#e8f4fd">
            <strong class="d-block mb-1">Consegna</strong>
            <span>{{ $ordine->data_prevista_consegna ? \Carbon\Carbon::parse($ordine->data_prevista_consegna)->format('d/m/Y') : '-' }}</span>
        </div>
    </div>
    <div class="col-md-1">
        <div class="border rounded p-2 h-100" style="background:{{ $mirko ? '#e8f4fd' : '#fff3cd' }}">
            <strong class="d-block mb-1">Colori</strong>
            @if($mirko)
                <span>{{ $coloriCalc ?: '-' }}</span>
            @else
                <div contenteditable class="campo-editabile" data-campo="colori" data-ordine="{{ $ordine->id }}"
                     onblur="salvaCampoPrestampa(this)">{{ $coloriCalc ?: '' }}</div>
            @endif
        </div>
    </div>
    <div class="col-md-2">
        <div class="border rounded p-2 h-100" style="background:#fff3cd">
            <strong class="d-block mb-1">Fustella</strong>
            <div contenteditable class="campo-editabile" data-campo="fustella_codice" data-ordine="{{ $ordine->id }}"
                 onblur="salvaCampoPrestampa(this)">{{ $fustellaCalc ?: '' }}</div>
        </div>
    </div>
</div>

{{-- Campi editabili prestampa (nascosti per Mirko) --}}
@if(!$mirko)
<div class="row g-2 mb-3" style="font-size:13px;">
    <div class="col-md-4">
        <div class="border rounded p-2 h-100" style="background:#fff3cd">
            <strong class="d-block mb-1">Operatore Prestampa</strong>
            <div contenteditable class="campo-editabile" data-campo="responsabile" data-ordine="{{ $ordine->id }}"
                 onblur="salvaCampoPrestampa(this)">{{ $ordine->responsabile ?: '' }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="border rounded p-2 h-100" style="background:#fff3cd">
            <strong class="d-block mb-1">Note Prestampa</strong>
            <div contenteditable class="campo-editabile" data-campo="note_prestampa" data-ordine="{{ $ordine->id }}"
                 onblur="salvaCampoPrestampa(this)" style="min-height:60px;">{{ $ordine->note_prestampa ?: '' }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="border rounded p-2 h-100" style="background:#fff3cd">
            <strong class="d-block mb-1">Commento Produzione</strong>
            <div contenteditable class="campo-editabile" data-campo="commento_produzione" data-ordine="{{ $ordine->id }}"
                 onblur="salvaCampoPrestampa(this)" style="min-height:60px;">{{ $ordine->commento_produzione ?: '' }}</div>
        </div>
    </div>
</div>
@endif


{{-- Barra progresso fasi --}}
@php
    $statoBg = [0 => '#e9ecef', 1 => '#cfe2ff', 2 => '#fff3cd', 3 => '#d1e7dd', 4 => '#c3c3c3', 5 => '#e0cffc'];
    $statoColor = [0 => '#333', 1 => '#084298', 2 => '#664d03', 3 => '#0f5132', 4 => '#1a1a1a', 5 => '#6f42c1'];
    $statoLabel = [0 => 'Caricato', 1 => 'Pronto', 2 => 'Avviato', 3 => 'Terminato', 4 => 'Consegnato', 5 => 'Esterno'];
    $totaleFasi = $fasi->count();
    $fasiTerminateCont = $fasi->filter(fn($f) => is_numeric($f->stato) && (int)$f->stato >= 3 && (int)$f->stato != 5)->count();
    $fasiAvviate = $fasi->where('stato', 2)->count();
    $pctCompletamento = $totaleFasi > 0 ? round(($fasiTerminateCont / $totaleFasi) * 100) : 0;
    $pctAvviate = $totaleFasi > 0 ? round(($fasiAvviate / $totaleFasi) * 100) : 0;
@endphp
<div class="border rounded p-3 mb-3" style="background:#fff;">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <strong style="font-size:14px;">Progresso fasi</strong>
        <span style="font-size:13px; color:#6c757d;">{{ $fasiTerminateCont }}/{{ $totaleFasi }} terminate {{ $fasiAvviate > 0 ? '· '.$fasiAvviate.' in corso' : '' }}</span>
    </div>
    <div style="height:24px; border-radius:12px; background:#e9ecef; overflow:hidden; position:relative;">
        @if($pctCompletamento > 0)
        <div style="height:100%; width:{{ $pctCompletamento }}%; background:linear-gradient(90deg, #198754, #28a745); border-radius:12px 0 0 12px; position:absolute; left:0; top:0; z-index:2;">
            <span style="position:absolute; right:8px; top:50%; transform:translateY(-50%); font-size:11px; font-weight:bold; color:#fff;">{{ $pctCompletamento }}%</span>
        </div>
        @endif
        @if($pctAvviate > 0)
        <div style="height:100%; width:{{ $pctCompletamento + $pctAvviate }}%; background:#ffc107; border-radius:12px 0 0 12px; position:absolute; left:0; top:0; z-index:1;"></div>
        @endif
    </div>
</div>

{{-- Tabella fasi --}}
<div style="overflow-x:auto;">
    <table class="prestampa-table">
        <thead>
            <tr>
                <th style="width:50px;">Stato</th>
                <th style="width:140px;">Fase</th>
                <th style="width:100px;">Reparto</th>
                <th style="width:70px;">Qta Carta</th>
                <th style="width:70px;">Qta Prod.</th>
                <th style="width:120px;">Operatori</th>
                <th style="width:200px;">Note</th>
                <th>Descrizione</th>
                <th style="width:110px;">Data Inizio</th>
                <th style="width:110px;">Data Fine</th>
            </tr>
        </thead>
        <tbody>
        @foreach($fasi as $fase)
            @php
                $umFase = strtoupper(trim($fase->um ?? 'FG'));
                $isPezzi = in_array($umFase, ['TR', 'PZ', 'KG']);
                if ($umFase === 'KG') {
                    $qtaFaseVal = $fase->ordine->qta_richiesta ?? 0;
                } else {
                    $qtaFaseVal = $fase->qta_fase ?: ($isPezzi ? ($fase->ordine->qta_richiesta ?? 0) : ($fase->ordine->qta_carta ?? 0));
                }
            @endphp
            <tr>
                <td style="text-align:center;">
                    <span class="stato-badge" style="background:{{ $statoBg[$fase->stato] ?? '#e9ecef' }};color:{{ $statoColor[$fase->stato] ?? '#333' }}">
                        {{ $fase->stato }}
                    </span>
                </td>
                <td>{{ $fase->faseCatalogo->nome_display ?? $fase->fase ?? '-' }}</td>
                <td>{{ $fase->reparto_nome ?? '-' }}</td>
                <td style="text-align:center;">{{ $qtaFaseVal ? number_format($qtaFaseVal, 0, ',', '.') : '-' }}</td>
                <td style="text-align:center;">{{ $fase->qta_prod ?? '-' }}</td>
                <td>
                    @forelse($fase->operatori as $op)
                        {{ $op->nome }}@if(!$loop->last), @endif
                    @empty
                        -
                    @endforelse
                </td>
                @if($mirko)
                <td contenteditable data-campo="note_fase" data-fase="{{ $fase->id }}"
                    onblur="salvaNotaFase(this)" style="cursor:text;">{{ $fase->note ?? '' }}</td>
                @else
                <td>{{ $fase->note ?? '-' }}</td>
                @endif
                <td style="max-width:180px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ $fase->ordine->descrizione ?? '-' }}</td>
                <td>{{ $fase->data_inizio ?? '-' }}</td>
                <td>{{ $fase->data_fine ?? '-' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection

@section('scripts')
<script>
function salvaCampoPrestampa(el) {
    var campo = el.getAttribute('data-campo');
    var ordineId = el.getAttribute('data-ordine');
    var valore = el.innerText.trim();

    fetch('{{ route("operatore.prestampa.aggiornaCampo") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
            'X-Op-Token': new URLSearchParams(window.location.search).get('op_token') || '',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ ordine_id: ordineId, campo: campo, valore: valore })
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success) {
            el.classList.add('campo-salvato');
            setTimeout(function() { el.classList.remove('campo-salvato'); }, 1500);
        } else {
            alert('Errore: ' + (d.messaggio || 'salvataggio fallito'));
        }
    })
    .catch(function() { alert('Errore di connessione'); });
}

function salvaNotaFase(el) {
    var faseId = el.getAttribute('data-fase');
    var valore = el.innerText.trim();

    fetch('{{ route("operatore.prestampa.aggiornaCampo") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
            'X-Op-Token': new URLSearchParams(window.location.search).get('op_token') || '',
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ fase_id: faseId, campo: 'note', valore: valore })
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success) {
            el.classList.add('campo-salvato');
            setTimeout(function() { el.classList.remove('campo-salvato'); }, 1500);
        } else {
            alert('Errore: ' + (d.messaggio || 'salvataggio fallito'));
        }
    })
    .catch(function() { alert('Errore di connessione'); });
}

</script>
@endsection
