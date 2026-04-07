<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 9pt; color: #000; }
        .page { padding: 15mm 15mm 10mm 15mm; }

        /* Header */
        .header { display: table; width: 100%; margin-bottom: 8mm; }
        .header-left { display: table-cell; width: 55%; vertical-align: top; }
        .header-right { display: table-cell; width: 45%; vertical-align: top; }
        .logo-text { font-size: 16pt; font-weight: bold; letter-spacing: 2px; margin-bottom: 2mm; }
        .logo-text span { color: #e60000; }
        .logo-sub { font-size: 7pt; color: #666; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 4mm; }
        .azienda-info { font-size: 7.5pt; line-height: 1.6; color: #333; }
        .azienda-info strong { font-style: italic; }

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
        table.articoli td.um { text-align: center; width: 12%; }
        table.articoli td.qta { text-align: right; width: 15%; }
        .rif-header {
            font-size: 8pt; font-weight: normal; text-align: left;
            padding: 1.5mm 2mm; border: 1px solid #999; background: none;
        }

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

    {{-- HEADER --}}
    <div class="header">
        <div class="header-left">
            <div class="logo-text">GRAFICA<span>NAPPA</span></div>
            <div class="logo-sub">INDUSTRIA POLIGRAFICA</div>
            <div class="azienda-info">
                <strong>Grafica Nappa srl</strong> <em>industria poligrafica</em><br>
                Via Gramsci, 19 - 81031 Aversa (Ce) - Italia<br>
                tel. +39.081.890.6734 - fax. +39.081.890.6739<br>
                P. IVA IT 00100450618 - CCIAA (CE) REA n. 61760 R.I. di Caserta n. 118273/97<br>
                www.graficanappa.com - info@graficanappa.com
            </div>
        </div>
        <div class="header-right">
            {{-- Cliente --}}
            <div class="cliente-label">Spett.le</div>
            <div class="cliente-box">
                <div class="cliente-nome">{{ $testa->ClienteNome }}</div>
                <div class="cliente-indirizzo">
                    {{ $testa->ClienteIndirizzo }}<br>
                    {{ $testa->ClienteCitta }} {{ $testa->ClienteCap }} {{ $testa->ClienteProvincia }}<br>
                    {{ $testa->ClienteNazione ?? 'Italia' }}
                </div>
            </div>

            {{-- Destinazione --}}
            @if($coda && $coda->DestNome)
            <div class="dest-label">DESTINAZIONE (se l'indirizzo è diverso da quello del destinatario)</div>
            <div class="dest-box">
                <div class="cliente-nome">{{ $coda->DestNome }}</div>
                <div class="cliente-indirizzo">
                    @if($coda->DestTelefono)TEL. {{ $coda->DestTelefono }}<br>@endif
                    {{ $coda->DestIndirizzo }}<br>
                    {{ $coda->DestCap }} {{ $coda->DestCitta }} {{ $coda->DestProvincia }}
                </div>
            </div>
            @endif
        </div>
    </div>

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

    {{-- TRASPORTO --}}
    <table style="width:100%; border-collapse:collapse; margin-bottom:2mm;">
        <tr>
            <td style="width:50%; vertical-align:top; padding-right:2mm;">
                <div class="trasporto-box">
                    <span class="label">TRASPORTO A CURA DEL</span><br>
                    <span class="checkbox {{ $trasportoCura === 'Cedente' ? 'checked' : '' }}">{{ $trasportoCura === 'Cedente' ? 'x' : '' }}</span> Cedente
                    &nbsp;&nbsp;
                    <span class="checkbox {{ $trasportoCura === 'Cessionario' ? 'checked' : '' }}">{{ $trasportoCura === 'Cessionario' ? 'x' : '' }}</span> Cessionario
                    &nbsp;&nbsp;
                    <span class="checkbox {{ $trasportoCura === 'Vettore' ? 'checked' : '' }}">{{ $trasportoCura === 'Vettore' ? 'x' : '' }}</span> Vettore
                </div>
            </td>
            <td style="width:50%; vertical-align:top;">
                <div class="trasporto-box">
                    <span class="label">INIZIO DEL TRASPORTO O CONSEGNA</span><br>
                    Data <strong>{{ $dataTrasporto }}</strong> &nbsp;&nbsp; Ora <strong>{{ $oraTrasporto }}</strong>
                </div>
            </td>
        </tr>
    </table>

    {{-- CAUSALE --}}
    <table class="causale-row">
        <tr>
            <td>
                <span class="label">CAUSALE DEL TRASPORTO</span><br>
                <strong>{{ $coda->CausaleTrasporto ?? 'Vendita' }}</strong>
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
                <th>UM</th>
                <th>QUANTITA'</th>
            </tr>
        </thead>
        <tbody>
            @foreach($righeRaggruppate as $riga)
                @if($riga['tipo'] === 'intestazione')
                    <tr>
                        <td colspan="3" class="rif-header">
                            <strong>Rif. Ord.Cli. {{ $riga['commessa'] }}</strong>
                            @if($riga['rif_ord'])
                                &nbsp;— Rif. Ord. Maxtris: {{ $riga['rif_ord'] }}
                            @endif
                        </td>
                    </tr>
                @else
                    <tr>
                        <td class="desc">{{ $riga['descrizione'] }}</td>
                        <td class="um">{{ $riga['um'] }}</td>
                        <td class="qta">{{ number_format($riga['qta'], 2, ',', '.') }}</td>
                    </tr>
                @endif
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
                    <div style="font-size:8pt;">{{ $annotazioni }}</div>
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
