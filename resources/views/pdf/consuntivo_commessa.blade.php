<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Consuntivo Commessa {{ $commessa }}</title>
<style>
@page { margin: 25mm 18mm 22mm 18mm; }
body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
.header { border-bottom: 2px solid #1a4d8c; padding-bottom: 8px; margin-bottom: 12px; }
.header table { width: 100%; }
.header .logo img { height: 36px; }
.header .azienda { font-size: 9px; color: #555; line-height: 1.3; }
.header .azienda strong { color: #1a4d8c; font-size: 11px; }

h1 { font-size: 14px; color: #1a4d8c; margin: 14px 0 4px 0; }
.subtitle { font-size: 10px; color: #555; margin-bottom: 8px; }
.info-box { background: #f4f7fb; border-left: 3px solid #1a4d8c; padding: 8px 10px; margin-bottom: 12px; }
.info-box table { width: 100%; }
.info-box td { padding: 2px 4px; font-size: 10px; }
.info-box .lbl { color: #555; font-weight: bold; width: 25%; }

table.voci { width: 100%; border-collapse: collapse; margin-top: 6px; font-size: 9.5px; }
table.voci th { background: #1a4d8c; color: #fff; padding: 6px 5px; text-align: left; font-size: 9px; }
table.voci td { border-bottom: 1px solid #e0e6ed; padding: 5px; vertical-align: top; }
table.voci tr:nth-child(even) td { background: #fafbfd; }
table.voci .num { text-align: right; font-family: monospace; }
table.voci .badge { background: #e0e6ed; color: #1a4d8c; padding: 1px 6px; border-radius: 3px; font-size: 8px; font-weight: bold; }
table.voci .override { background: #fff4d6 !important; }
table.voci tfoot td { border-top: 2px solid #1a4d8c; padding-top: 8px; font-weight: bold; font-size: 11px; color: #1a4d8c; background: #f4f7fb; }

.section-title { font-size: 11px; font-weight: bold; color: #1a4d8c; margin: 14px 0 4px 0; padding-bottom: 3px; border-bottom: 1px solid #1a4d8c; }
.sub-table { width: 100%; margin: 4px 0 10px 0; border-collapse: collapse; font-size: 9.5px; }
.sub-table th, .sub-table td { padding: 3px 5px; border-bottom: 1px dotted #ccc; }
.sub-table th { color: #555; font-weight: normal; font-size: 9px; }

.footer-note { margin-top: 18px; padding-top: 10px; border-top: 1px solid #ccc; font-size: 8.5px; color: #777; line-height: 1.4; }
.totale-finale { background: #1a4d8c; color: #fff; padding: 10px 14px; margin-top: 12px; border-radius: 4px; font-size: 14px; }
.totale-finale .lbl { font-size: 11px; opacity: 0.9; }
.totale-finale .val { font-size: 18px; font-weight: bold; }
</style>
</head>
<body>

<div class="header">
    <table>
        <tr>
            <td class="logo" style="width:40%;"><img src="{{ public_path('images/logo_graficanappa.png') }}" alt="Grafica Nappa"></td>
            <td class="azienda" style="text-align:right;">
                <strong>GRAFICA NAPPA S.R.L.</strong><br>
                Via dei Tigli 60 — 80026 Casoria (NA)<br>
                P.IVA 06959301214 — info@graficanappa.com<br>
                Tel. 081 758 7037
            </td>
        </tr>
    </table>
</div>

<h1>Consuntivo Commessa {{ $commessa }}</h1>
<div class="subtitle">Generato il {{ now()->format('d/m/Y H:i') }}</div>

<div class="info-box">
    <table>
        <tr>
            <td class="lbl">Cliente</td><td>{{ $cliente }}</td>
            <td class="lbl">Qta richiesta</td><td>{{ number_format($qta_richiesta, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="lbl">Descrizione</td><td colspan="3">{{ $descrizione }}</td>
        </tr>
        <tr>
            <td class="lbl">Consegna</td><td>{{ $data_consegna ? \Carbon\Carbon::parse($data_consegna)->format('d/m/Y') : '-' }}</td>
            <td class="lbl">Fogli stampati</td><td>{{ number_format($fogliUtilizzati, 0, ',', '.') }}</td>
        </tr>
    </table>
</div>

@if($oreReparto->isNotEmpty())
<div class="section-title">Ore lavorate per reparto</div>
<table class="sub-table">
    <thead><tr><th>Reparto</th><th style="text-align:right;width:80px;">Ore</th></tr></thead>
    <tbody>
    @foreach($oreReparto as $r)
        <tr><td>{{ $r->reparto }}</td><td class="num">{{ $r->ore_hm }}</td></tr>
    @endforeach
    </tbody>
</table>
@endif

<div class="section-title">💰 Costi consuntivo dettagliato</div>
<table class="voci">
    <thead>
        <tr>
            <th style="width:70px;">Categoria</th>
            <th>Descrizione</th>
            <th style="width:60px; text-align:right;">Qta</th>
            <th style="width:30px;">UM</th>
            <th style="width:55px; text-align:right;">€/unit</th>
            <th style="width:70px; text-align:right;">Importo</th>
        </tr>
    </thead>
    <tbody>
    @foreach($vociCosto as $v)
        @if($v['importo'] <= 0) @continue @endif
        <tr class="{{ $v['override_manuale'] ? 'override' : '' }}">
            <td><span class="badge">{{ $v['categoria'] }}</span></td>
            <td>{{ $v['descrizione'] }}{{ $v['override_manuale'] ? ' (M)' : '' }}</td>
            <td class="num">{{ $v['qta'] !== null ? number_format($v['qta'], 2, ',', '.') : '—' }}</td>
            <td>{{ $v['udm'] ?? '' }}</td>
            <td class="num">{{ $v['prezzo_unit'] !== null ? number_format($v['prezzo_unit'], 4, ',', '.') : '—' }}</td>
            <td class="num">€ {{ number_format($v['importo'], 2, ',', '.') }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5" style="text-align:right;">TOTALE</td>
            <td class="num">€ {{ number_format($totaleConsuntivo, 2, ',', '.') }}</td>
        </tr>
    </tfoot>
</table>

<div class="totale-finale">
    <table style="width:100%;">
        <tr>
            <td class="lbl">TOTALE CONSUNTIVO COMMESSA {{ $commessa }}</td>
            <td class="val" style="text-align:right;">€ {{ number_format($totaleConsuntivo, 2, ',', '.') }}</td>
        </tr>
    </table>
</div>

@if($lavorazioniEsterne->isNotEmpty())
<div class="section-title">⚠️ Lavorazioni esterne</div>
<table class="sub-table">
    <thead><tr><th>Fase</th><th>Reparto</th><th>Fornitore</th><th style="text-align:right;">Qta</th><th>Data inizio</th><th>Data fine</th></tr></thead>
    <tbody>
    @foreach($lavorazioniEsterne as $le)
        <tr>
            <td>{{ $le->fase }}</td><td>{{ $le->reparto }}</td>
            <td>{{ $le->fornitore }}</td>
            <td class="num">{{ number_format($le->qta_prod, 0, ',', '.') }}</td>
            <td>{{ $le->data_inizio ? \Carbon\Carbon::parse($le->data_inizio)->format('d/m/Y') : '-' }}</td>
            <td>{{ $le->data_fine ? \Carbon\Carbon::parse($le->data_fine)->format('d/m/Y') : '-' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
@endif

<div class="footer-note">
Documento generato automaticamente dal MES Grafica Nappa. Le voci contrassegnate "(M)" sono state modificate manualmente rispetto al calcolo automatico.
GDPR: documento ad uso interno aziendale. Foro competente: Aversa.
</div>

</body>
</html>
