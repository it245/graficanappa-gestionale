<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<title>Analisi {{ $analisi->nome }}</title>
<style>
@page { margin: 22mm 16mm 18mm 16mm; }
body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; }
.header { border-bottom: 2px solid #1a4d8c; padding-bottom: 8px; margin-bottom: 14px; }
.header table { width: 100%; }
.header img { height: 36px; }
.header .azienda { font-size: 9px; color: #555; text-align: right; line-height: 1.3; }
.header .azienda strong { color: #1a4d8c; font-size: 11px; }

h1 { font-size: 16px; color: #1a4d8c; margin: 14px 0 4px 0; }
.subtitle { font-size: 10px; color: #555; margin-bottom: 12px; }

.kpi-grid { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
.kpi-grid td { width: 25%; padding: 10px 12px; background: #f4f7fb; border: 1px solid #e0e6ed; border-radius: 6px; vertical-align: top; }
.kpi-grid .lbl { font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; }
.kpi-grid .val { font-size: 16px; font-weight: bold; color: #1a4d8c; }

.section-title { font-size: 11px; font-weight: bold; color: #1a4d8c; margin: 14px 0 4px 0; padding-bottom: 3px; border-bottom: 1px solid #1a4d8c; }

table.tbl { width: 100%; border-collapse: collapse; margin-top: 4px; font-size: 9.5px; }
table.tbl th { background: #1a4d8c; color: #fff; padding: 6px 5px; text-align: left; font-size: 9px; }
table.tbl td { border-bottom: 1px solid #e0e6ed; padding: 5px; }
table.tbl .num { text-align: right; font-family: monospace; }
table.tbl tfoot td { background: #1a4d8c; color: #fff; font-weight: bold; padding: 8px 5px; }
.bar-track { width: 60%; height: 8px; background: #e5e7eb; display: inline-block; border-radius: 4px; vertical-align: middle; margin: 0 6px; }
.bar-fill { background: #1a4d8c; height: 100%; border-radius: 4px; display: inline-block; }
</style>
</head>
<body>

<div class="header">
    <table>
        <tr>
            <td style="width:40%;"><img src="{{ public_path('images/logo_graficanappa.png') }}" alt="Grafica Nappa"></td>
            <td class="azienda">
                <strong>GRAFICA NAPPA S.R.L.</strong><br>
                Via Antonio Gramsci 19 — 81031 Aversa (CE)<br>
                Tel. 081 890 6734
            </td>
        </tr>
    </table>
</div>

<h1>📊 Analisi Custom: {{ $analisi->nome }}</h1>
<div class="subtitle">
    {{ $analisi->descrizione ?? '' }}<br>
    Creato da {{ $analisi->autore }} · Generato il {{ now()->format('d/m/Y H:i') }}
</div>

<table class="kpi-grid">
    <tr>
        <td><div class="lbl">Commesse</div><div class="val">{{ count($datiCommesse) }}</div></td>
        <td><div class="lbl">Totale costi</div><div class="val">€ {{ number_format($totaleGenerale, 2, ',', '.') }}</div></td>
        <td><div class="lbl">Costo medio</div><div class="val">€ {{ count($datiCommesse) > 0 ? number_format($totaleGenerale / count($datiCommesse), 2, ',', '.') : '0,00' }}</div></td>
        <td><div class="lbl">Categorie</div><div class="val">{{ count($categorieTot) }}</div></td>
    </tr>
</table>

@if(!empty($categorieTot))
<div class="section-title">Distribuzione costi per categoria</div>
<table class="tbl">
    <thead><tr><th>Categoria</th><th class="num">Importo €</th><th>Distribuzione</th><th class="num">%</th></tr></thead>
    <tbody>
    @foreach($categorieTot as $cat => $val)
    @php $pct = $totaleGenerale > 0 ? $val / $totaleGenerale * 100 : 0; @endphp
    <tr>
        <td><strong>{{ strtoupper($cat) }}</strong></td>
        <td class="num">€ {{ number_format($val, 2, ',', '.') }}</td>
        <td><div class="bar-track"><span class="bar-fill" style="width:{{ min($pct,100) }}%;"></span></div></td>
        <td class="num">{{ number_format($pct, 1, ',', '.') }}%</td>
    </tr>
    @endforeach
    </tbody>
</table>
@endif

<div class="section-title">Commesse incluse ({{ count($datiCommesse) }})</div>
<table class="tbl">
    <thead><tr><th>Commessa</th><th>Cliente / Descrizione</th><th>Etichetta</th><th class="num">Costo €</th></tr></thead>
    <tbody>
    @foreach($datiCommesse as $c)
        <tr>
            <td><strong>{{ $c['commessa'] }}</strong></td>
            <td>{{ $c['cliente'] }}<br><small style="color:#6b7280;">{{ \Illuminate\Support\Str::limit($c['descrizione'], 80) }}</small></td>
            <td>{{ $c['etichetta'] ?? '-' }}</td>
            <td class="num">€ {{ number_format($c['totale'], 2, ',', '.') }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot><tr><td colspan="3" style="text-align:right;">TOTALE ANALISI</td><td class="num">€ {{ number_format($totaleGenerale, 2, ',', '.') }}</td></tr></tfoot>
</table>

</body>
</html>
