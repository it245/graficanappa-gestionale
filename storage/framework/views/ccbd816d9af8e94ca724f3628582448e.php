<?php $__env->startSection('content'); ?>
<div class="container-fluid px-0">
<style>
    html, body { margin:0; padding:0; overflow-x:hidden; width:100%; }
    h2, h4, p { margin-left:8px; margin-right:8px; }
    .table-wrapper {
        width:100%; max-width:100%; overflow-x:auto; overflow-y:visible; margin: 0 4px;
    }
    table th, table td { white-space:nowrap; font-size:13px; }
    td.desc-col { white-space:normal; min-width:280px; max-width:400px; }
    td.fasi-col { white-space:normal; min-width:180px; max-width:300px; font-size:12px; }
    .search-box {
        max-width:600px; margin:12px 8px; font-size:18px; padding:12px 20px;
        border-radius:10px; border:2px solid #dee2e6; transition:border-color 0.2s;
    }
    .search-box:focus { border-color:#17a2b8; box-shadow:0 0 0 3px rgba(23,162,184,0.15); }
    .row-scaduta { background: #f8d7da !important; }
    .row-warning { background: #fff3cd !important; }
    a.commessa-link { color:#000; font-weight:bold; text-decoration:underline; }
    a.commessa-link:hover { color:#0d6efd; }
    .badge-stato { font-size:11px; }
</style>

<div class="d-flex align-items-center mx-2 mb-2 mt-2">
    <a href="<?php echo e(route('owner.dashboard')); ?>" class="btn btn-outline-secondary btn-sm me-3">&larr; Dashboard</a>
    <h2 class="mb-0" style="color:#17a2b8;">Lavorazioni Esterne (<?php echo e($commesseEsterne->count()); ?>)</h2>
</div>

<input type="text" id="searchBox" class="form-control search-box" placeholder="Cerca commessa, cliente, fornitore, fase...">

<div class="table-wrapper">
    <table class="table table-bordered table-sm table-striped" id="tabEsterne">
        <thead style="background:#17a2b8; color:#fff;">
            <tr>
                <th>Stato</th>
                <th>Commessa</th>
                <th>Cliente</th>
                <th>Fornitore</th>
                <th>Fasi (<?php echo e($commesseEsterne->sum('num_fasi')); ?>)</th>
                <th>Cod. Articolo</th>
                <th>Descrizione</th>
                <th>Data Consegna</th>
                <th>Data Invio</th>
            </tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $commesseEsterne; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $riga): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $rowClass = '';
                    if ($riga->ordine && $riga->ordine->data_prevista_consegna) {
                        $oggi = \Carbon\Carbon::today();
                        $dataPrevista = \Carbon\Carbon::parse($riga->ordine->data_prevista_consegna);
                        $diff = $oggi->diffInDays($dataPrevista, false);
                        if ($diff < -5) $rowClass = 'row-scaduta';
                        elseif ($diff <= 3) $rowClass = 'row-warning';
                    }
                    $statoFase = $riga->stato;
                    $inPausa = is_string($statoFase) && !is_numeric($statoFase);
                ?>
                <tr class="<?php echo e($rowClass); ?> searchable">
                    <td>
                        <?php if($statoFase == 0): ?>
                            <span class="badge bg-secondary badge-stato">Da fare</span>
                        <?php elseif($statoFase == 1): ?>
                            <span class="badge bg-info badge-stato">Pronto</span>
                        <?php elseif($statoFase == 2): ?>
                            <span class="badge bg-primary badge-stato">In corso</span>
                        <?php elseif($inPausa): ?>
                            <span class="badge bg-warning text-dark badge-stato">Pausa: <?php echo e($statoFase); ?></span>
                        <?php endif; ?>
                    </td>
                    <td><a href="<?php echo e(route('owner.dettaglioCommessa', $riga->ordine->commessa ?? '-')); ?>" class="commessa-link"><?php echo e($riga->ordine->commessa ?? '-'); ?></a></td>
                    <td><?php echo e($riga->ordine->cliente_nome ?? '-'); ?></td>
                    <td><strong><?php echo e($riga->fornitore); ?></strong></td>
                    <td class="fasi-col"><?php echo e($riga->fasi); ?> <?php if($riga->num_fasi > 1): ?><span class="badge bg-secondary"><?php echo e($riga->num_fasi); ?></span><?php endif; ?></td>
                    <td><?php echo e($riga->ordine->cod_art ?? '-'); ?></td>
                    <td class="desc-col"><?php echo e($riga->ordine->descrizione ?? '-'); ?></td>
                    <td><?php echo e($riga->ordine->data_prevista_consegna ? \Carbon\Carbon::parse($riga->ordine->data_prevista_consegna)->format('d/m/Y') : '-'); ?></td>
                    <td><?php echo e($riga->data_invio ? \Carbon\Carbon::parse($riga->data_invio)->format('d/m/Y') : '-'); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr>
                    <td colspan="9" class="text-center text-muted py-3">Nessuna lavorazione esterna</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</div>

<script>
document.getElementById('searchBox').addEventListener('input', function() {
    const query = this.value.toLowerCase().trim();
    document.querySelectorAll('tr.searchable').forEach(function(row) {
        const text = row.innerText.toLowerCase();
        row.style.display = (!query || text.includes(query)) ? '' : 'none';
    });
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Giovanni\graficanappa-gestionale\resources\views\owner\esterne.blade.php ENDPATH**/ ?>