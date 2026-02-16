<h2>Conferma Spedizione Avvenuta</h2>
<p>Si comunica che la seguente spedizione è stata effettuata:</p>

<table cellpadding="8" cellspacing="0" border="0" style="font-size:15px;">
    <tr>
        <td><strong>Commessa</strong></td>
        <td>{{ $fase->ordine->commessa ?? '-' }}</td>
    </tr>
    <tr>
        <td><strong>Cliente</strong></td>
        <td>{{ $fase->ordine->cliente_nome ?? '-' }}</td>
    </tr>
    <tr>
        <td><strong>Descrizione</strong></td>
        <td>{{ $fase->ordine->descrizione ?? '-' }}</td>
    </tr>
    <tr>
        <td><strong>Fase</strong></td>
        <td>{{ $fase->faseCatalogo->nome ?? $fase->fase ?? '-' }}</td>
    </tr>
    <tr>
        <td><strong>Operatore</strong></td>
        <td>{{ $nomeOperatore }}</td>
    </tr>
    <tr>
        <td><strong>Data Spedizione</strong></td>
        <td>{{ now()->format('d/m/Y H:i:s') }}</td>
    </tr>
</table>
