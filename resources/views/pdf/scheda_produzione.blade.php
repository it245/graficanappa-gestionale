<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Scheda Produzione — {{ $commessa }}</title>
<style>
    @page { margin: 12mm 12mm 14mm 12mm; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9.5pt; color: #111; }
    .hidden { display: none; }

    /* Header Grafica Nappa */
    .logo-row { display: table; width: 100%; margin-bottom: 4px; }
    .logo-cell, .scheda-cell, .commessa-cell {
        display: table-cell; vertical-align: top;
    }
    .logo-cell { width: 35%; }
    .logo-cell .brand {
        font-size: 12pt; font-weight: bold; letter-spacing: 0.5px;
    }
    .logo-cell .sub { font-size: 7pt; color: #666; }
    .logo-cell .presa { font-size: 8pt; color: #000; margin-top: 4px; }
    .scheda-cell { width: 35%; font-size: 8pt; line-height: 1.4; }
    .commessa-cell { width: 30%; text-align: right; }
    .commessa-code {
        background: #fff200; color: #000;
        font-weight: bold; font-size: 14pt;
        padding: 3px 10px; border: 1px solid #000;
        display: inline-block;
    }

    /* Sezione cliente giallo */
    .cliente-row {
        background: #fff200; color: #d32f2f;
        font-weight: bold; font-size: 12pt;
        padding: 5px 8px; margin-top: 6px;
        border-top: 1px solid #000; border-bottom: 1px solid #000;
    }

    /* Blocco dati anagrafica */
    .anag { display: table; width: 100%; margin-top: 6px; border-collapse: collapse; }
    .anag .row { display: table-row; }
    .anag .lbl, .anag .val { display: table-cell; padding: 3px 6px; border-bottom: 1px solid #999; font-size: 9pt; }
    .anag .lbl { width: 22%; font-weight: bold; color: #333; }
    .anag .val { width: 28%; }
    .anag-right {
        display: table; width: 100%; margin-top: 6px;
        border: 1px solid #999;
    }
    .anag-right .cell { display: table-cell; padding: 4px 6px; font-size: 8.5pt; }
    .anag-right .rh { background: #fffbe6; font-weight: bold; }

    /* Intestazione 2 col */
    .two-col { display: table; width: 100%; margin-top: 6px; }
    .two-col .col-l { display: table-cell; width: 60%; padding-right: 6px; vertical-align: top; }
    .two-col .col-r { display: table-cell; width: 40%; vertical-align: top; }

    /* Descrizione lavoro giallo */
    .descr-lavoro {
        background: #fff200; color: #d32f2f;
        font-weight: bold; font-size: 11pt;
        padding: 6px 8px; margin: 10px 0 6px;
        border-top: 1px solid #c00; border-bottom: 1px solid #c00;
    }

    /* Tabelle */
    table.tbl { width: 100%; border-collapse: collapse; margin: 4px 0 8px; }
    table.tbl th { background: #f2f2f2; font-size: 8.5pt; text-align: left; border: 1px solid #aaa; padding: 3px 5px; }
    table.tbl td { font-size: 8.5pt; border: 1px solid #ccc; padding: 3px 5px; vertical-align: top; }

    /* Sezione riepilogo */
    .riep-title {
        background: #eee; font-weight: bold; font-size: 9pt;
        padding: 3px 6px; border-left: 3px solid #d32f2f;
        margin-top: 8px;
    }
    .riep-body { padding: 3px 6px; font-size: 8.5pt; min-height: 18px; border-bottom: 1px dashed #ccc; }

    /* Page break per ogni fase */
    .fase-page { page-break-before: always; }

    /* Header fase (stile Lavoro rosso) */
    .fase-header {
        background: #d32f2f; color: #fff;
        font-weight: bold; font-size: 10pt;
        padding: 4px 8px; margin-top: 10px;
    }
    .fase-titolo {
        font-size: 13pt; font-weight: bold; margin: 6px 0 4px;
    }
    .fase-campi { display: table; width: 100%; margin-bottom: 4px; }
    .fc-r { display: table-row; }
    .fc-lbl, .fc-val { display: table-cell; padding: 2px 6px; font-size: 9pt; }
    .fc-lbl { width: 18%; font-weight: bold; color: #333; }
    .fc-val { border-bottom: 1px solid #aaa; }

    .commento-giallo {
        background: #fff200; padding: 3px 6px; font-size: 9pt;
        border-top: 1px solid #000; border-bottom: 1px solid #000;
        margin: 4px 0;
    }

    .footer-fase {
        position: fixed; bottom: -6mm; left: 0; right: 0;
        font-size: 7.5pt; color: #777; padding: 0 12mm;
        display: table; width: calc(100% - 24mm);
    }
    .footer-fase .fl, .footer-fase .fr { display: table-cell; }
    .footer-fase .fr { text-align: right; }

    /* Specifiche tabella lavoro */
    .specifiche-tbl th, .specifiche-tbl td { text-align: center; }

    .note-block {
        background: #fff200; padding: 2px 6px; font-size: 8.5pt;
        margin: 3px 0; font-weight: bold;
    }
    .note-text { font-size: 8.5pt; padding: 2px 6px; min-height: 16px; }

    .sign-box {
        border: 1px solid #999; min-height: 40px; padding: 4px;
        font-size: 8.5pt;
    }
    .grid2 { display: table; width: 100%; margin-top: 6px; }
    .g2c { display: table-cell; width: 50%; padding: 0 4px; vertical-align: top; }
</style>
</head>
<body>

@php
    $o = $ordinePrincipale;
    $dataConsegna = $o?->data_prevista_consegna
        ? (is_string($o->data_prevista_consegna)
            ? date('d/m/Y', strtotime($o->data_prevista_consegna))
            : $o->data_prevista_consegna->format('d/m/Y'))
        : '—';
    $dataReg = $o?->data_registrazione
        ? (is_string($o->data_registrazione)
            ? date('d/m/y', strtotime($o->data_registrazione))
            : $o->data_registrazione->format('d/m/y'))
        : '—';
    $formato = ($o?->supp_base_cm && $o?->supp_altezza_cm)
        ? $o->supp_base_cm . ' x ' . $o->supp_altezza_cm
        : '—';
@endphp

{{-- ============ PAGINA 1: INTESTAZIONE SCHEDA PRODUZIONE ============ --}}
<div class="logo-row">
    <div class="logo-cell">
        <div class="brand">GRAFICA<span style="color:#d32f2f;">NAPPA</span></div>
        <div class="sub">industria poligrafica</div>
        <div class="presa">presa in carico {{ $dataReg }}</div>
    </div>
    <div class="scheda-cell">
        <strong>SCHEDA PRODUZIONE</strong><br>
        N. {{ str_pad($o?->id ?? 0, 7, '0', STR_PAD_LEFT) }} · Rif N. riga 1<br>
        PREVENTIVO N.
    </div>
    <div class="commessa-cell">
        <span class="commessa-code">{{ $commessa }}</span>
    </div>
</div>

<div class="cliente-row">{{ strtoupper($o?->cliente_nome ?? '—') }}</div>

<div class="two-col">
    <div class="col-l">
        <div class="anag">
            <div class="row">
                <div class="lbl">Data di consegna</div>
                <div class="val" style="background:#fff200;font-weight:bold;">{{ $dataConsegna }}</div>
            </div>
            <div class="row">
                <div class="lbl">Reso</div>
                <div class="val">Franco</div>
            </div>
            <div class="row">
                <div class="lbl">Dati spedizione</div>
                <div class="val">Ritiro presso Grafica Nappa</div>
            </div>
        </div>
    </div>
    <div class="col-r">
        <div class="anag-right">
            <div class="cell rh">Riferimenti interni</div>
            <div class="cell">&nbsp;</div>
        </div>
        <div class="anag-right">
            <div class="cell rh" style="width:50%;">Resp commerciale</div>
            <div class="cell">{{ $o?->responsabile ?? 'Antonio Nappa' }}</div>
        </div>
        <div class="anag-right">
            <div class="cell rh" style="width:50%;">Resp produzione</div>
            <div class="cell">Antonio Castellano</div>
        </div>
        <div class="anag-right" style="margin-top:4px;">
            <div class="cell rh">Riferimento</div>
            <div class="cell">&nbsp;</div>
        </div>
        <div class="anag-right">
            <div class="cell rh" style="width:50%;">Tel:</div>
            <div class="cell">Fax:</div>
        </div>
    </div>
</div>

<div class="descr-lavoro">{{ strtoupper($o?->descrizione ?? '—') }}</div>

{{-- Riepilogo commessa --}}
<div class="riep-title">Riepilogo commessa:</div>
<table class="tbl">
    <thead>
        <tr>
            <th style="width:12%;">Ord Prod</th>
            <th style="width:15%;">Codice</th>
            <th style="width:40%;">Descrizione</th>
            <th style="width:12%;">Q.tà</th>
            <th style="width:21%;">Macchina · Reso</th>
        </tr>
    </thead>
    <tbody>
        @foreach($ordini as $ord)
        <tr>
            <td>{{ str_pad($ord->id, 7, '0', STR_PAD_LEFT) }}</td>
            <td>{{ $ord->cod_art ?? '—' }}</td>
            <td>{{ $ord->descrizione ?? '—' }}</td>
            <td style="text-align:right;">{{ number_format($ord->qta_richiesta ?? 0, 0, ',', '.') }} {{ $ord->um }}</td>
            <td>Reso: {{ $ord->resa ?? '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="riep-title">Riepilogo materiali:</div>
<div class="riep-body">
    @if($o?->cod_carta || $o?->carta)
        <strong>Carta:</strong> {{ $o->cod_carta ?? '' }} {{ $o->carta ?? '' }}
        @if($o->qta_carta) — <strong>Qta:</strong> {{ number_format($o->qta_carta, 0, ',', '.') }} {{ $o->UM_carta }} @endif
        @if($formato !== '—') — <strong>Formato:</strong> {{ $formato }} cm @endif
    @else
        —
    @endif
</div>

<div class="riep-title">Riepilogo attrezzature:</div>
<table class="tbl">
    <thead><tr><th>Articolo</th><th>Descrizione</th><th style="width:15%;">Q.tà</th></tr></thead>
    <tbody>
        @if($o?->cliche)
        <tr>
            <td>Cliché {{ $o->cliche->label() }}</td>
            <td>{{ $o->cliche->descrizione_raw ?? '—' }}</td>
            <td>{{ $o->cliche->qta ?? '—' }}</td>
        </tr>
        @else
        <tr><td colspan="3" style="text-align:center;color:#999;">—</td></tr>
        @endif
    </tbody>
</table>

<div class="riep-title">Riepilogo Lavorazioni:</div>
<table class="tbl">
    <thead><tr><th style="width:25%;">Fase</th><th style="width:25%;">Reparto</th><th style="width:15%;">Qta</th><th style="width:15%;">Stato</th><th>Note</th></tr></thead>
    <tbody>
        @foreach($fasi as $f)
        <tr>
            <td><strong>{{ $f->faseCatalogo->nome_display ?? $f->fase }}</strong></td>
            <td>{{ $f->faseCatalogo?->reparto?->nome ?? $f->reparto ?? '—' }}</td>
            <td style="text-align:right;">{{ $f->qta_fase ? number_format($f->qta_fase, 0, ',', '.') : '—' }}</td>
            <td>
                @switch((int)$f->stato)
                    @case(0) da fare @break
                    @case(1) pronto @break
                    @case(2) in corso @break
                    @case(3) terminato @break
                    @case(4) consegnato @break
                    @default —
                @endswitch
            </td>
            <td>{{ $f->note ? \Illuminate\Support\Str::limit($f->note, 50) : '—' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="riep-title">Riepilogo Vostro Riferimento:</div>
<div class="riep-body">{{ $o?->ordine_cliente ?? '—' }}</div>

<div class="riep-title">Commento produzione:</div>
<div class="commento-giallo">{{ $o?->commento_produzione ?? '—' }}</div>

@if($o?->note_prestampa)
<div class="riep-title">Note prestampa:</div>
<div class="note-text">{{ $o->note_prestampa }}</div>
@endif

@if($o?->note_fasi_successive)
<div class="riep-title">Note fasi successive:</div>
<div class="note-text">{{ $o->note_fasi_successive }}</div>
@endif

{{-- ============ PAGINE 2+: UNA PER OGNI FASE ============ --}}
@foreach($fasi as $idx => $fase)
<div class="fase-page">
    {{-- Mini header in cima ogni pagina fase --}}
    <div class="logo-row">
        <div class="logo-cell">
            <div class="brand" style="font-size:10pt;">GRAFICA<span style="color:#d32f2f;">NAPPA</span></div>
            <div class="presa">presa in carico {{ $dataReg }}</div>
        </div>
        <div class="scheda-cell">
            <strong>SCHEDA PRODUZIONE</strong><br>
            N. {{ str_pad($o?->id ?? 0, 7, '0', STR_PAD_LEFT) }} · Rif N. riga {{ $idx + 1 }}<br>
            Fase #{{ $fase->id }}
        </div>
        <div class="commessa-cell">
            <span class="commessa-code" style="font-size:11pt;">{{ $commessa }}</span>
        </div>
    </div>

    <div class="cliente-row" style="font-size:10pt;">{{ strtoupper($o?->cliente_nome ?? '—') }}</div>

    <div class="fase-header">Lavoro</div>

    <div class="fase-titolo">{{ $fase->faseCatalogo->nome_display ?? $fase->fase }}
        <span style="font-size:10pt;color:#666;font-weight:normal;">· {{ $fase->faseCatalogo?->reparto?->nome ?? $fase->reparto ?? '' }}</span>
    </div>

    <div class="fase-campi">
        <div class="fc-r">
            <div class="fc-lbl">Descrizione:</div>
            <div class="fc-val" style="background:#fff200;font-weight:bold;">{{ strtoupper($o?->descrizione ?? '—') }}</div>
        </div>
        <div class="fc-r">
            <div class="fc-lbl">Copie:</div>
            <div class="fc-val">{{ $fase->qta_fase ? number_format($fase->qta_fase, 0, ',', '.') : ($o?->qta_richiesta ? number_format($o->qta_richiesta, 0, ',', '.') : '—') }} PZ</div>
        </div>
        <div class="fc-r">
            <div class="fc-lbl">Formato:</div>
            <div class="fc-val">{{ $formato }}</div>
        </div>
    </div>

    @if($o?->commento_produzione)
    <div style="margin-top:4px;">
        <div class="fc-lbl" style="display:block;padding:2px 0;">Commento produzione:</div>
        <div class="commento-giallo">{{ $o->commento_produzione }}</div>
    </div>
    @endif

    <div style="font-weight:bold;font-size:9pt;margin-top:6px;">Specifiche lavoro</div>
    <table class="tbl specifiche-tbl">
        <thead>
            <tr>
                <th>Segnature</th>
                <th>Colori</th>
                <th>Facciate</th>
                <th>Lastre</th>
                <th>Supporti</th>
                <th>Scarto</th>
                <th>Avv.</th>
                <th>Tempo Avviamenti</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>/</td>
                <td>—</td>
                <td>1</td>
                <td>—</td>
                <td>{{ $fase->qta_fase ? number_format($fase->qta_fase, 0, ',', '.') : '—' }}</td>
                <td>{{ $fase->scarti ?? 0 }}</td>
                <td>0</td>
                <td>,000</td>
            </tr>
        </tbody>
    </table>

    @if($fase->note)
    <div class="note-block">Note fase:</div>
    <div class="note-text">{{ $fase->note }}</div>
    @endif

    @if($o?->note_prestampa && $idx === 0)
    <div class="note-block">Note prestampa:</div>
    <div class="note-text">{{ $o->note_prestampa }}</div>
    @endif

    {{-- Tabella specifica fase (es. STAMPA: patinata Opaca, Base/Altezza, Q.tà) --}}
    <table class="tbl" style="margin-top:8px;">
        <thead>
            <tr>
                <th style="width:15%;">{{ strtoupper($fase->faseCatalogo?->reparto?->nome ?? $fase->fase) }}</th>
                <th style="width:30%;">Specifiche</th>
                <th style="width:18%;">Base/Altezza</th>
                <th style="width:7%;">um</th>
                <th style="width:15%;">Q.tà</th>
                <th>BarCode</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>{{ substr($fase->fase, 0, 6) }}</strong></td>
                <td>{{ $o?->carta ?? '—' }}</td>
                <td>{{ $formato }}</td>
                <td>{{ $o?->UM_carta ?? 'FG' }}</td>
                <td style="text-align:right;">{{ $fase->qta_fase ? number_format($fase->qta_fase, 0, ',', '.') : '—' }}</td>
                <td style="font-family:monospace;font-size:8pt;">*{{ $commessa }}-{{ $fase->id }}*</td>
            </tr>
        </tbody>
    </table>

    {{-- Operatori + firma --}}
    <div class="grid2" style="margin-top:10px;">
        <div class="g2c">
            <div style="font-weight:bold;font-size:8.5pt;margin-bottom:2px;">Operatore assegnato</div>
            <div class="sign-box">
                @forelse($fase->operatori as $op)
                    {{ $op->nome }} {{ $op->cognome ?? '' }}@if(!$loop->last), @endif
                @empty
                    <span style="color:#999;">— da assegnare —</span>
                @endforelse
            </div>
        </div>
        <div class="g2c">
            <div style="font-weight:bold;font-size:8.5pt;margin-bottom:2px;">Firma · Qta prodotta · Scarti</div>
            <div class="sign-box"></div>
        </div>
    </div>

    {{-- Footer Chiusura lavoro --}}
    <div style="margin-top:14px;font-weight:bold;font-size:9pt;">Chiusura lavoro</div>
    <div style="font-size:7.5pt;color:#999;margin-top:2px;text-align:right;">
        Pagina {{ $idx + 2 }} di {{ $fasi->count() + 1 }}
    </div>
</div>
@endforeach

<div style="font-size:7pt;color:#aaa;text-align:center;margin-top:10px;">
    Grafica Nappa srl — Scheda Produzione {{ $commessa }} generata il {{ now()->format('d/m/Y H:i') }} — MES v2.0
</div>

</body>
</html>
