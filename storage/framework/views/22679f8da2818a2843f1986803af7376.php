<?php $__env->startSection('content'); ?>
<h1>Simulazione MES - Produzione</h1>

<?php $__currentLoopData = $ordini; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ordine): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
        <h3>Ordine: <?php echo e($ordine->commessa); ?> - <?php echo e($ordine->cliente_nome); ?></h3>

        <p>
            Articolo: <?php echo e($ordine->cod_art); ?> - <?php echo e($ordine->descrizione); ?>

        </p>

        <p>
            Quantità richiesta: <?php echo e($ordine->qta_richiesta); ?>

            |
            Quantità prodotta: <?php echo e($ordine->qta_prodotta); ?>

        </p>

        <h4>Fasi del tuo reparto</h4>
        <ul>
            <?php $__currentLoopData = $ordine->fasi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fase): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li>
                    <strong><?php echo e($fase->fase); ?></strong>
                    (<?php echo e($fase->reparto); ?>)
                    —
                    Stato:
                    <?php if($fase->stato == 0): ?> Non avviata
                    <?php elseif($fase->stato == 1): ?> In lavorazione
                    <?php else: ?> Terminata
                    <?php endif; ?>
                    |
                    Qta prodotta: <?php echo e($fase->qta_prod); ?>

                </li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Giovanni\graficanappa-gestionale\resources\views\dashboard\operator.blade.php ENDPATH**/ ?>