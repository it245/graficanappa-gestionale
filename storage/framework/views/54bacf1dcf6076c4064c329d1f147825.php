<h2>Conferma Spedizione Avvenuta</h2>
<p>Si comunica che la seguente spedizione è stata effettuata:</p>

<table cellpadding="8" cellspacing="0" border="0" style="font-size:15px;">
    <tr>
        <td><strong>Commessa</strong></td>
        <td><?php echo e($fase->ordine->commessa ?? '-'); ?></td>
    </tr>
    <tr>
        <td><strong>Cliente</strong></td>
        <td><?php echo e($fase->ordine->cliente_nome ?? '-'); ?></td>
    </tr>
    <tr>
        <td><strong>Descrizione</strong></td>
        <td><?php echo e($fase->ordine->descrizione ?? '-'); ?></td>
    </tr>
    <tr>
        <td><strong>Fase</strong></td>
        <td><?php echo e($fase->faseCatalogo->nome ?? $fase->fase ?? '-'); ?></td>
    </tr>
    <tr>
        <td><strong>Operatore</strong></td>
        <td><?php echo e($nomeOperatore); ?></td>
    </tr>
    <tr>
        <td><strong>Data Spedizione</strong></td>
        <td><?php echo e(now()->format('d/m/Y H:i:s')); ?></td>
    </tr>
</table>
<?php /**PATH C:\Users\Giovanni\graficanappa-gestionale\resources\views\mail\spedizione_completata.blade.php ENDPATH**/ ?>