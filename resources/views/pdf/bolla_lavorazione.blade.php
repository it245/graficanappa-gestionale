<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Bolla Lavorazione — {{ $ordine?->commessa }}</title>
<style>
    @page { margin: 18mm 14mm; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10pt; color: #111; }
    h1 { margin: 0; font-size: 16pt; }
    h2 { margin: 14px 0 6px; font-size: 11pt; background: #222; color: #fff; padding: 4px 8px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    td, th { border: 1px solid #bbb; padding: 5px 7px; vertical-align: top; }
    th { background: #f2f2f2; text-align: left; width: 28%; }
    .header { display: table; width: 100%; margin-bottom: 12px; border-bottom: 2px solid #222; padding-bottom: 8px; }
    .hleft, .hright { display: table-cell; vertical-align: middle; }
    .hright { text-align: right; font-size: 9pt; color: #555; }
    .commessa-box { font-size: 22pt; font-weight: bold; letter-spacing: 2px; }
    .fase-box { font-size: 14pt; font-weight: bold; color: #0a58ca; }
    .note-box { background: #fff8e1; border: 1px solid #f59e0b; padding: 8px; min-height: 40px; }
    .firma-box { border: 1px solid #333; height: 60px; padding: 6px; }
    .grid2 { display: table; width: 100%; }
    .col { display: table-cell; width: 50%; padding-right: 6px; }
    .col:last-child { padding-right: 0; padding-left: 6px; }
    .small { font-size: 8pt; color: #666; }
    .badge { display: inline-block; background: #0a58ca; color: #fff; padding: 2px 8px; border-radius: 3px; font-size: 9pt; }
</style>
</head>
<body>

<div class="header">
    <div class="hleft">
        <h1>GRAFICA NAPPA srl</h1>
        <div class="small">Bolla di Lavorazione</div>
    </div>
    <div class="hright">
        Data stampa: {{ now()->format('d/m/Y H:i') }}<br>
        ID Fase: #{{ $fase->id }}
    </div>
</div>

<table>
    <tr>
        <th>Commessa</th>
        <td><span class="commessa-box">{{ $ordine?->commessa ?? '—' }}</span></td>
        <th>Fase</th>
        <td><span class="fase-box">{{ $fase->fase }}</span>
            @if($fase->faseCatalogo?->reparto?->nome)
                <br><span class="badge">{{ $fase->faseCatalogo->reparto->nome }}</span>
            @endif
        </td>
    </tr>
    <tr>
        <th>Cliente</th>
        <td>{{ $ordine?->cliente_nome ?? '—' }}</td>
        <th>Cod. Articolo</th>
        <td>{{ $ordine?->cod_art ?? '—' }}</td>
    </tr>
    <tr>
        <th>Descrizione</th>
        <td colspan="3">{{ $ordine?->descrizione ?? '—' }}</td>
    </tr>
    <tr>
        <th>Data prevista consegna</th>
        <td>
            @if($ordine?->data_prevista_consegna)
                {{ \Carbon\Carbon::parse($ordine->data_prevista_consegna)->format('d/m/Y') }}
            @else — @endif
        </td>
        <th>Qta richiesta / Qta fase</th>
        <td>{{ $ordine?->qta_richiesta ?? '—' }} {{ $ordine?->um }} / {{ $fase->qta_fase ?? '—' }}</td>
    </tr>
</table>

<h2>Carta / Supporto</h2>
<table>
    <tr>
        <th>Cod. carta</th>
        <td>{{ $ordine?->cod_carta ?? '—' }}</td>
        <th>Descrizione carta</th>
        <td>{{ $ordine?->carta ?? '—' }}</td>
    </tr>
    <tr>
        <th>Qta carta</th>
        <td>{{ $ordine?->qta_carta ?? '—' }} {{ $ordine?->UM_carta }}</td>
        <th>Formato</th>
        <td>
            @if($ordine?->supp_base_cm && $ordine?->supp_altezza_cm)
                {{ $ordine->supp_base_cm }} × {{ $ordine->supp_altezza_cm }} cm
            @else — @endif
            — resa: {{ $ordine?->resa ?? '—' }}
        </td>
    </tr>
</table>

@if($cliche)
<h2>Cliché</h2>
<table>
    <tr>
        <th>Numero</th>
        <td>{{ $cliche->numero }}@if($cliche->scatola) — Scatola {{ $cliche->scatola }} @endif</td>
        <th>Qta cliché</th>
        <td>{{ $cliche->qta ?? '—' }}</td>
    </tr>
    @if($cliche->descrizione_raw)
    <tr>
        <th>Descrizione</th>
        <td colspan="3">{{ $cliche->descrizione_raw }}</td>
    </tr>
    @endif
</table>
@endif

<h2>Note</h2>
<div class="grid2">
    <div class="col">
        <div class="small"><b>Note prestampa</b></div>
        <div class="note-box">{{ $ordine?->note_prestampa ?: '—' }}</div>
    </div>
    <div class="col">
        <div class="small"><b>Note fasi successive</b></div>
        <div class="note-box">{{ $ordine?->note_fasi_successive ?: '—' }}</div>
    </div>
</div>

@if($fase->note)
<div class="small" style="margin-top:6px;"><b>Note fase</b></div>
<div class="note-box">{{ $fase->note }}</div>
@endif

<h2>Operatori assegnati / Firma</h2>
<div class="grid2">
    <div class="col">
        <div class="small"><b>Operatore assegnato</b></div>
        <div class="firma-box">
            @forelse($fase->operatori as $op)
                {{ $op->nome }} {{ $op->cognome ?? '' }}@if(!$loop->last), @endif
            @empty
                <span class="small">— da assegnare —</span>
            @endforelse
        </div>
    </div>
    <div class="col">
        <div class="small"><b>Firma operatore</b></div>
        <div class="firma-box"></div>
    </div>
</div>

<div class="grid2" style="margin-top:10px;">
    <div class="col">
        <div class="small"><b>Qta prodotta</b></div>
        <div class="firma-box"></div>
    </div>
    <div class="col">
        <div class="small"><b>Scarti</b></div>
        <div class="firma-box"></div>
    </div>
</div>

<div class="small" style="margin-top:18px; text-align:center; color:#999;">
    Grafica Nappa srl — Aversa (CE) — MES v2.0 · Fase #{{ $fase->id }} · Stampato il {{ now()->format('d/m/Y H:i:s') }}
</div>

</body>
</html>
