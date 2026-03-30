<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="utf-8">
<title>Report Commesse per Percorso Produttivo — MES Grafica Nappa</title>
<style>
    @page { margin: 1cm; size: A4 landscape; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 10px; color: #222; line-height: 1.4; padding: 15px; }

    .header { text-align: center; border-bottom: 3px solid #d11317; padding-bottom: 12px; margin-bottom: 15px; }
    .header h1 { color: #d11317; font-size: 18px; margin-bottom: 2px; }
    .header .subtitle { color: #555; font-size: 12px; }
    .header .date { color: #888; font-size: 10px; margin-top: 4px; }

    .summary { display: flex; gap: 12px; margin-bottom: 15px; flex-wrap: wrap; }
    .summary-card {
        flex: 1; min-width: 140px; padding: 8px 14px;
        border-radius: 8px; border-left: 4px solid; font-size: 11px;
    }
    .summary-card .count { font-size: 22px; font-weight: 700; }
    .summary-card .label { font-size: 10px; color: #555; }

    .group-title {
        font-size: 13px; font-weight: 700; padding: 6px 12px;
        border-radius: 6px; margin: 12px 0 6px 0; page-break-after: avoid;
    }

    table { width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 9px; }
    th { background: #333; color: #fff; padding: 5px 6px; text-align: left; font-size: 9px; font-weight: 600; }
    td { padding: 4px 6px; border-bottom: 1px solid #e0e0e0; vertical-align: top; }
    tr:nth-child(even) td { background: inherit; }

    .desc { max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .fasi-col { max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 8px; color: #666; }
    .center { text-align: center; }
    .right { text-align: right; }
    .bold { font-weight: 700; }

    .progress-bar { display: inline-block; background: #e9ecef; border-radius: 4px; height: 12px; width: 60px; position: relative; vertical-align: middle; }
    .progress-fill { height: 100%; border-radius: 4px; background: #28a745; }

    .footer { margin-top: 15px; padding-top: 8px; border-top: 2px solid #e0e0e0; text-align: center; font-size: 9px; color: #888; }
    .empty-group { color: #999; font-style: italic; padding: 6px 12px; font-size: 10px; }

    @media print {
        body { padding: 0; }
        .no-print { display: none !important; }
        .group-title { page-break-after: avoid; }
        table { page-break-inside: auto; }
        tr { page-break-inside: avoid; }
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
</style>
</head>
<body>

<div class="no-print" style="text-align:center; margin-bottom:12px;">
    <button onclick="window.print()" style="padding:8px 24px; font-size:14px; background:#d11317; color:#fff; border:none; border-radius:6px; cursor:pointer; font-weight:700;">
        Stampa / Salva PDF
    </button>
</div>

<div class="header">
    <h1>Report Commesse per Percorso Produttivo</h1>
    <div class="subtitle">MES Grafica Nappa srl</div>
    <div class="date">Generato il {{ now()->format('d/m/Y H:i') }} &mdash; Commesse attive: {{ $totale }}</div>
</div>

<div class="summary">
@foreach($gruppi as $key => $g)
    <div class="summary-card" style="background: {{ $g['color'] }}; border-color: {{ $g['border'] }};">
        <div class="count">{{ count($g['ordini']) }}</div>
        <div class="label">{{ $g['label'] }}</div>
    </div>
@endforeach
</div>

@foreach($gruppi as $key => $g)
    <div class="group-title" style="background: {{ $g['color'] }}; border-left: 4px solid {{ $g['border'] }};">
        {{ $g['label'] }} ({{ count($g['ordini']) }} commesse)
    </div>

    @if(empty($g['ordini']))
        <div class="empty-group">Nessuna commessa in questo percorso.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th>Commessa</th>
                    <th>Cliente</th>
                    <th>Cod. Art.</th>
                    <th>Descrizione</th>
                    <th class="right">Qta</th>
                    <th class="center">Consegna</th>
                    <th class="center">Progresso</th>
                    <th>Fasi</th>
                </tr>
            </thead>
            <tbody>
            @foreach($g['ordini'] as $o)
                @php
                    [$done, $tot] = explode('/', $o['progresso']);
                    $pct = $tot > 0 ? round(($done / $tot) * 100) : 0;
                @endphp
                <tr style="background: {{ $g['color'] }} !important;">
                    <td class="bold">{{ $o['commessa'] }}</td>
                    <td>{{ $o['cliente'] }}</td>
                    <td>{{ $o['cod_art'] }}</td>
                    <td class="desc" title="{{ $o['descrizione'] }}">{{ $o['descrizione'] }}</td>
                    <td class="right">{{ $o['qta'] }}</td>
                    <td class="center">{{ $o['consegna'] }}</td>
                    <td class="center">
                        <div class="progress-bar">
                            <div class="progress-fill" style="width:{{ $pct }}%"></div>
                        </div>
                        <span style="font-size:8px; margin-left:2px;">{{ $o['progresso'] }}</span>
                    </td>
                    <td class="fasi-col" title="{{ $o['fasi'] }}">{{ $o['fasi'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
@endforeach

<div class="footer">
    MES Grafica Nappa v2.0 &mdash; Report generato automaticamente &mdash; {{ now()->format('d/m/Y H:i') }}
</div>

</body>
</html>
