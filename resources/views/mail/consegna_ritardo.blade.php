<h2>Consegne BRT in ritardo</h2>
<p>Le seguenti spedizioni BRT risultano ritirate da <strong>3 o più giorni</strong> e non ancora consegnate:</p>

<table cellpadding="8" cellspacing="0" border="1" style="border-collapse:collapse; font-size:14px; width:100%;">
    <thead>
        <tr style="background-color:#f2f2f2;">
            <th style="text-align:left;">DDT</th>
            <th style="text-align:left;">Cliente</th>
            <th style="text-align:left;">Commesse</th>
            <th style="text-align:left;">Destinatario</th>
            <th style="text-align:left;">Località</th>
            <th style="text-align:center;">Colli</th>
            <th style="text-align:center;">Data Ritiro</th>
            <th style="text-align:center;">Giorni</th>
            <th style="text-align:left;">Stato BRT</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($ritardi as $r)
            <tr>
                <td>{{ $r['numero_ddt'] }}</td>
                <td>{{ $r['cliente'] }}</td>
                <td>{{ $r['commesse'] }}</td>
                <td>{{ $r['destinatario'] }}</td>
                <td>{{ $r['localita'] }}</td>
                <td style="text-align:center;">{{ $r['colli'] }}</td>
                <td style="text-align:center;">{{ $r['data_ritiro'] }}</td>
                <td style="text-align:center; font-weight:bold; color:#c0392b;">{{ $r['giorni_ritardo'] }}</td>
                <td>{{ $r['stato_brt'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<p style="margin-top:16px; font-size:13px; color:#888;">
    Controllo automatico del {{ now()->format('d/m/Y H:i') }}
</p>
