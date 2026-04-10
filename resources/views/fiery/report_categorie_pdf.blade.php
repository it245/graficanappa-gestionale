<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Report Scatti Canon iPR V900 — {{ \Carbon\Carbon::parse($da)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($a)->format('d/m/Y') }}</title>
    <style>
        @page { size: A4 portrait; margin: 2cm; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #000;
            padding: 30px;
            max-width: 600px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }
        .header h1 {
            font-size: 18pt;
            margin-bottom: 5px;
        }
        .header .sub {
            font-size: 11pt;
            color: #444;
        }
        .header .periodo {
            font-size: 13pt;
            font-weight: bold;
            margin-top: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 12pt;
        }
        th, td {
            border: 1px solid #000;
            padding: 10px 14px;
        }
        th {
            background: #e5e5e5;
            text-align: left;
            font-weight: bold;
        }
        td.right {
            text-align: right;
            font-family: 'Courier New', monospace;
            font-size: 13pt;
        }
        tr.totale td {
            background: #fff9c0;
            font-weight: bold;
            font-size: 14pt;
        }

        .footer {
            margin-top: 30px;
            font-size: 9pt;
            color: #666;
            text-align: center;
            border-top: 1px solid #ccc;
            padding-top: 10px;
        }

        .stampa-btn {
            text-align: center;
            margin: 20px 0;
        }
        .stampa-btn button {
            padding: 10px 30px;
            font-size: 14pt;
            cursor: pointer;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 6px;
        }

        @media print {
            .stampa-btn { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>

<div class="stampa-btn">
    <button onclick="window.print()">Stampa / Salva come PDF</button>
</div>

<div class="header">
    <h1>Report Scatti Canon imagePRESS V900</h1>
    <div class="sub">Grafica Nappa S.r.l.</div>
    <div class="periodo">
        Periodo: {{ \Carbon\Carbon::parse($da)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($a)->format('d/m/Y') }}
    </div>
    @if(!empty($reportCategorie['lettura_iniziale_at']) && !empty($reportCategorie['lettura_finale_at']))
    <div style="font-size:9pt; color:#666; margin-top:4px;">
        Lettura iniziale: {{ $reportCategorie['lettura_iniziale_at'] }} —
        Lettura finale: {{ $reportCategorie['lettura_finale_at'] }}
    </div>
    @endif
</div>

<table>
    <thead>
        <tr>
            <th>Contatore</th>
            <th style="text-align:right;">Scatti</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>B/N A4</td>
            <td class="right">{{ number_format($reportCategorie['bn_a4'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Colore A4</td>
            <td class="right">{{ number_format($reportCategorie['colore_a4'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>B/N A3</td>
            <td class="right">{{ number_format($reportCategorie['bn_a3'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Colore A3</td>
            <td class="right">{{ number_format($reportCategorie['colore_a3'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Banner</td>
            <td class="right">{{ number_format($reportCategorie['banner'], 0, ',', '.') }}</td>
        </tr>
        <tr class="totale">
            <td>TOTALE</td>
            <td class="right">{{ number_format($reportCategorie['totale'], 0, ',', '.') }}</td>
        </tr>
    </tbody>
</table>

<div class="footer">
    Report generato il {{ now()->format('d/m/Y H:i') }} dal MES Grafica Nappa<br>
    Fonte dati: Fiery Accounting API — Canon imagePRESS V900
</div>

</body>
</html>
