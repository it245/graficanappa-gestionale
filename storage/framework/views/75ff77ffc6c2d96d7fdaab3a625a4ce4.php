<?php
    $rowClass = '';
    if ($fase->ordine && $fase->ordine->data_prevista_consegna) {
        $oggi = \Carbon\Carbon::today();
        $dataPrevista = \Carbon\Carbon::parse($fase->ordine->data_prevista_consegna);
        $diff = $oggi->diffInDays($dataPrevista, false);
        if ($diff < -5) $rowClass = 'scaduta';
        elseif ($diff <= 3) $rowClass = 'warning-strong';
        elseif ($diff <= 5) $rowClass = 'warning-light';
    }
    $statoBg = [0 => '#e9ecef', 1 => '#cfe2ff', 2 => '#fff3cd', 3 => '#d1e7dd'];
?>

<tr id="fase-<?php echo e($fase->id); ?>" class="<?php echo e($rowClass); ?>">
    <td><?php echo e($fase->priorita !== null ? number_format($fase->priorita, 2) : '-'); ?></td>
    <td id="operatore-<?php echo e($fase->id); ?>">
        <?php $__currentLoopData = $fase->operatori; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $op): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php echo e($op->nome); ?> (<?php echo e($op->pivot->data_inizio ? \Carbon\Carbon::parse($op->pivot->data_inizio)->format('d/m/Y H:i:s') : '-'); ?>)<br>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </td>
    <td><?php echo e($fase->faseCatalogo->nome_display ?? '-'); ?></td>
    <td id="stato-<?php echo e($fase->id); ?>" style="background:<?php echo e($statoBg[$fase->stato] ?? '#e9ecef'); ?>;font-weight:bold;text-align:center;"><?php echo e($fase->stato); ?></td>

    
    <td>
        <a href="<?php echo e(route('commesse.show', $fase->ordine->commessa)); ?>?fase=<?php echo e($fase->id); ?>" class="commessa-link"
           style="font-weight:bold">
           <?php echo e($fase->ordine->commessa); ?>

        </a>
        <?php $repNomeEtichetta = strtolower(optional(optional($fase->faseCatalogo)->reparto)->nome ?? ''); ?>
        <?php if(!in_array($repNomeEtichetta, ['digitale', 'finitura digitale'])): ?>
        <a href="<?php echo e(route('operatore.etichetta', $fase->ordine->id)); ?>" class="ms-1"
           title="Stampa etichetta" style="text-decoration:none;"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="#6c757d" viewBox="0 0 16 16"><path d="M2 1a1 1 0 0 0-1 1v4.586a1 1 0 0 0 .293.707l7 7a1 1 0 0 0 1.414 0l4.586-4.586a1 1 0 0 0 0-1.414l-7-7A1 1 0 0 0 6.586 1H2zm4 3.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0z"/></svg></a>
        <?php endif; ?>
    </td>

    <td><?php echo e($fase->ordine->data_registrazione ? \Carbon\Carbon::parse($fase->ordine->data_registrazione)->format('d/m/Y') : '-'); ?></td>
    <td><?php echo e($fase->ordine->cliente_nome ?? '-'); ?></td>
    <td><?php echo e($fase->ordine->cod_art ?? '-'); ?></td>
    <?php if($showColori ?? false): ?><td><?php echo e($fase->colori ?? '-'); ?></td><?php endif; ?>
    <td><?php echo e($fase->fustella_codice ?? '-'); ?></td>
    <?php if($showEsterno ?? false): ?><td><?php echo e($fase->fornitore_esterno ?? '-'); ?></td><?php endif; ?>
    <td class="descrizione"><?php echo e($fase->ordine->descrizione ?? '-'); ?></td>
    <td><?php echo e($fase->ordine->qta_richiesta ?? '-'); ?></td>
    <td><?php echo e($fase->ordine->um ?? '-'); ?></td>
    <td><?php echo e($fase->ordine->data_prevista_consegna ? \Carbon\Carbon::parse($fase->ordine->data_prevista_consegna)->format('d/m/Y') : '-'); ?></td>
    <td><?php echo e($fase->qta_prod ?? '-'); ?></td>
    <td><?php echo e($fase->ordine->cod_carta ?? '-'); ?></td>
    <td><?php echo e($fase->ordine->carta ?? '-'); ?></td>
    <td><?php echo e($fase->ordine->qta_carta ?? '-'); ?></td>
    <td><?php echo e($fase->ordine->UM_carta ?? '-'); ?></td>
    <td><?php echo e($fase->note_pulita ?? $fase->note ?? '-'); ?></td>
    <td id="timeout-<?php echo e($fase->id); ?>"><?php echo e($fase->timeout ?? '-'); ?></td>
</tr>
<?php /**PATH C:\Users\Giovanni\graficanappa-gestionale\resources\views\operatore\_fase_row.blade.php ENDPATH**/ ?>