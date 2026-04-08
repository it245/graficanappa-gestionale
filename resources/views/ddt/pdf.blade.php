<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 9pt; color: #000; }
        .page { padding: 30mm 15mm 10mm 15mm; } /* 30mm top: spazio per intestazione pre-stampata (3cm) */

        /* Header rimosso: la carta è già intestata */

        /* Cliente */
        .cliente-box { border: 1px solid #999; padding: 3mm; margin-bottom: 2mm; }
        .cliente-label { font-size: 7pt; color: #666; margin-bottom: 1mm; }
        .cliente-nome { font-size: 10pt; font-weight: bold; margin-bottom: 1mm; }
        .cliente-indirizzo { font-size: 8.5pt; line-height: 1.5; }

        /* Destinazione */
        .dest-label { font-size: 7pt; color: #666; font-style: italic; margin-bottom: 1mm; margin-top: 2mm; }
        .dest-box { border: 1px solid #999; padding: 3mm; }

        /* Blocco DDT */
        .ddt-info { display: table; width: 100%; margin-bottom: 3mm; border: 1px solid #999; }
        .ddt-info td { padding: 2mm 3mm; font-size: 8.5pt; vertical-align: middle; }
        .ddt-info .label { font-size: 7.5pt; color: #333; }
        .ddt-info .value { font-weight: bold; font-size: 10pt; }

        /* Trasporto */
        .trasporto-row { display: table; width: 100%; margin-bottom: 3mm; }
        .trasporto-cell { display: table-cell; vertical-align: top; }
        .trasporto-box { border: 1px solid #999; padding: 2mm 3mm; font-size: 7.5pt; }
        .checkbox { display: inline-block; width: 10px; height: 10px; border: 1px solid #000; margin-right: 2px; text-align: center; font-size: 7pt; line-height: 10px; }
        .checkbox.checked { background: #333; color: #fff; }

        /* Causale */
        .causale-row { display: table; width: 100%; margin-bottom: 4mm; border: 1px solid #999; }
        .causale-row td { padding: 2mm 3mm; font-size: 8pt; }

        /* Tabella articoli */
        .intro-text { font-size: 7.5pt; font-style: italic; margin-bottom: 3mm; }
        .rif-ord { font-size: 7.5pt; margin-bottom: 1mm; }

        table.articoli { width: 100%; border-collapse: collapse; margin-bottom: 2mm; }
        table.articoli th {
            border: 1px solid #999; padding: 2mm; font-size: 8pt;
            text-align: center; background: #f5f5f5; font-weight: bold;
        }
        table.articoli td {
            border: 1px solid #999; padding: 1.5mm 2mm; font-size: 8pt;
        }
        table.articoli td.desc { text-align: left; }
        table.articoli td.rif { text-align: center; width: 14%; font-size: 7.5pt; }
        table.articoli td.um { text-align: center; width: 10%; }
        table.articoli td.qta { text-align: right; width: 14%; }

        /* Note */
        .note-text { font-size: 7pt; line-height: 1.4; margin-top: 3mm; color: #333; }
        .disclaimer { font-size: 6.5pt; line-height: 1.4; margin-top: 4mm; color: #555; }

        /* Footer */
        .footer { margin-top: 8mm; }
        .footer-table { width: 100%; border-collapse: collapse; }
        .footer-table td {
            border: 1px solid #999; padding: 2mm 3mm; font-size: 7pt;
            vertical-align: top; height: 12mm;
        }
        .footer-label { font-size: 6pt; color: #666; letter-spacing: 1px; }
        .footer-value { font-size: 9pt; font-weight: bold; margin-top: 1mm; }

        .vettore-row { margin-top: 3mm; }
        .vettore-table { width: 100%; border-collapse: collapse; }
        .vettore-table td { border: 1px solid #999; padding: 2mm; font-size: 7pt; height: 10mm; }
    </style>
</head>
<body>
<div class="page">

    {{-- CLIENTE (posizionato a destra, l'intestazione è pre-stampata sulla carta) --}}
    <div style="text-align: right; margin-bottom: 3mm;">
        <div class="cliente-label">Spett.le</div>
        <div class="cliente-box" style="display: inline-block; text-align: left; min-width: 40%; max-width: 50%;">
            <div class="cliente-nome">{{ $testa->ClienteNome }}</div>
            <div class="cliente-indirizzo">
                {{ $testa->ClienteIndirizzo }}<br>
                {{ $testa->ClienteCitta }} {{ $testa->ClienteCap }} {{ $testa->ClienteProvincia }}<br>
                {{ $nazione }}
            </div>
        </div>
    </div>

    {{-- Destinazione --}}
    @if($coda && $coda->DestNome)
    <div style="text-align: right; margin-bottom: 3mm;">
        <div class="dest-label">DESTINAZIONE (se l'indirizzo è diverso da quello del destinatario)</div>
        <div class="dest-box" style="display: inline-block; text-align: left; min-width: 55%;">
            <div class="cliente-nome">{{ $coda->DestNome }}</div>
            <div class="cliente-indirizzo">
                {{ $coda->DestIndirizzo }}<br>
                {{ $coda->DestCap }} {{ $coda->DestCitta }} {{ $coda->DestProvincia }}
            </div>
        </div>
    </div>
    @endif

    {{-- BLOCCO DDT --}}
    <table class="ddt-info">
        <tr>
            <td style="width:35%">
                <span class="label">DOCUMENTO DI TRASPORTO N.</span><br>
                <span class="value">{{ $numeroDdt }}</span>
            </td>
            <td style="width:5%; text-align:center; font-weight:bold;">DEL</td>
            <td style="width:15%">
                <span class="value">{{ $dataDdt }}</span>
            </td>
            <td style="width:45%; text-align:right;">
                <span class="label">PARTITA IVA</span> &nbsp;&nbsp;/&nbsp;&nbsp; <span class="label">CODICE FISCALE</span><br>
                <span class="value" style="font-size:8.5pt">{{ $testa->ClientePIVA }}</span>
                &nbsp;&nbsp;&nbsp;&nbsp;
                <span class="value" style="font-size:8.5pt">{{ $testa->ClienteCF }}</span>
            </td>
        </tr>
    </table>

    {{-- TRASPORTO + INIZIO + CAUSALE (una riga) --}}
    <table style="width:100%; border-collapse:collapse; margin-bottom:2mm;">
        <tr>
            <td style="width:35%; vertical-align:top; border:1px solid #999; padding:2mm 3mm;">
                <span class="label">TRASPORTO A CURA DEL</span><br>
                <span class="checkbox {{ $trasportoCura === 'Cedente' ? 'checked' : '' }}">{{ $trasportoCura === 'Cedente' ? 'x' : '' }}</span> Cedente
                &nbsp;
                <span class="checkbox {{ $trasportoCura === 'Cessionario' ? 'checked' : '' }}">{{ $trasportoCura === 'Cessionario' ? 'x' : '' }}</span> Cessionario
                &nbsp;
                <span class="checkbox {{ $trasportoCura === 'Vettore' ? 'checked' : '' }}">{{ $trasportoCura === 'Vettore' ? 'x' : '' }}</span> Vettore
            </td>
            <td style="width:35%; vertical-align:top; border:1px solid #999; padding:2mm 3mm;">
                <span class="label">INIZIO DEL TRASPORTO O CONSEGNA</span><br>
                Data <strong>{{ $dataTrasporto }}</strong> &nbsp;&nbsp; Ora <strong>{{ $oraTrasporto }}</strong>
            </td>
            <td style="width:30%; vertical-align:top; border:1px solid #999; padding:2mm 3mm;">
                <span class="label">CAUSALE DEL TRASPORTO</span><br>
                <strong>{{ $causale }}</strong>
            </td>
        </tr>
    </table>

    {{-- INTRO --}}
    <div class="intro-text">
        Ci pregiamo di consegnarVi quanto segue da Voi ordinatoci con riserva di inviarVi regolare fattura.
    </div>

    {{-- TABELLA ARTICOLI --}}
    <table class="articoli">
        <thead>
            <tr>
                <th style="text-align:left;">DESCRIZIONE</th>
                <th>RIF.VS ORD.</th>
                <th>UM</th>
                <th>QUANTITA'</th>
            </tr>
        </thead>
        <tbody>
            @foreach($righeFinali as $riga)
                <tr>
                    <td class="desc">{{ $riga['descrizione'] }}</td>
                    <td class="rif">{{ $riga['rif_ord'] ?? '' }}</td>
                    <td class="um">{{ $riga['um'] }}</td>
                    <td class="qta">{{ number_format($riga['qta'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- NOTE AGGIUNTIVE (righe testo dal DDT) --}}
    @if($noteRighe)
    <div class="note-text">{!! nl2br(e($noteRighe)) !!}</div>
    @endif

    {{-- DISCLAIMER --}}
    <div class="disclaimer">
        Eventuali contestazioni relative a quantità, qualità o conformità della merce del presente documento devono
        essere comunicate per iscritto (PEC, mail, o raccomandata) entro e non oltre 8 giorni dal ricevimento della
        stessa (art. 1495 c.c.). Trascorso tale termine, la merce si intende definitivamente accettata.
    </div>

    {{-- FOOTER --}}
    <div class="footer">
        <table class="footer-table">
            <tr>
                <td style="width:12%">
                    <div class="footer-label">Numero colli</div>
                    <div class="footer-value">{{ $coda->NumeroColli ?? '' }}</div>
                </td>
                <td style="width:23%">
                    <div class="footer-label">Aspetto esteriore dei beni</div>
                    <div class="footer-value">{{ strtoupper($coda->AspettoEsteriore ?? '') }}</div>
                </td>
                <td style="width:32%">
                    <div class="footer-label">Firma del Conducente</div>
                </td>
                <td style="width:33%">
                    <div class="footer-label">Firma del Cessionario</div>
                </td>
            </tr>
        </table>

        <table class="footer-table" style="margin-top:-1px;">
            <tr>
                <td style="width:12%">
                    <div class="footer-label">Kg</div>
                    <div class="footer-value">{{ $coda && $coda->Peso ? number_format($coda->Peso, 0, ',', '.') : '' }}</div>
                </td>
                <td colspan="3">
                    <div class="footer-label">Annotazioni</div>
                    <div style="font-size:8pt;">{{ $noteRighe }}</div>
                </td>
            </tr>
        </table>

        <div class="vettore-row">
            <table class="vettore-table">
                <tr>
                    <td style="width:20%">
                        <div class="footer-label">VETTORE - Ditta</div>
                        @if($coda && $coda->VettoreNome)
                            <div style="font-size:8pt; font-weight:bold;">{{ $coda->VettoreNome }}</div>
                        @endif
                    </td>
                    <td style="width:35%">
                        <div class="footer-label">Residenza domicilio (Comune, via e n.)</div>
                        @if($coda && $coda->VettoreIndirizzo)
                            <div style="font-size:7pt;">{{ $coda->VettoreCitta }} - {{ $coda->VettoreIndirizzo }}</div>
                        @endif
                    </td>
                    <td style="width:15%"><div class="footer-label">Data del ritiro</div></td>
                    <td style="width:15%"><div class="footer-label">Ora del ritiro</div></td>
                    <td style="width:15%"><div class="footer-label">Firma</div></td>
                </tr>
            </table>
        </div>
    </div>

</div>
</body>
</html>
