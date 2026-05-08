<?php $__env->startSection('content'); ?>
<div class="container-fluid mt-3 px-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Storico Commesse</h2>
        <a href="<?php echo e(route('admin.dashboard')); ?>" class="btn btn-sm btn-outline-secondary">Dashboard Admin</a>
    </div>

    
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" action="<?php echo e(route('admin.commesse')); ?>" class="row g-2 align-items-end">
                
                <div class="col-auto">
                    <label class="form-label mb-0" style="font-size:12px;">Stato</label>
                    <select name="filtro" class="form-select form-select-sm" style="min-width:130px;">
                        <option value="tutte" <?php echo e($filtro === 'tutte' ? 'selected' : ''); ?>>Tutte</option>
                        <option value="in_corso" <?php echo e($filtro === 'in_corso' ? 'selected' : ''); ?>>In corso</option>
                        <option value="completate" <?php echo e($filtro === 'completate' ? 'selected' : ''); ?>>Completate</option>
                        <option value="consegnate" <?php echo e($filtro === 'consegnate' ? 'selected' : ''); ?>>Consegnate</option>
                    </select>
                </div>

                
                <div class="col-auto">
                    <label class="form-label mb-0" style="font-size:12px;">Cliente</label>
                    <input type="text" name="cliente" class="form-control form-control-sm" placeholder="Cerca cliente..." value="<?php echo e($ricercaCliente); ?>" style="min-width:180px;">
                </div>

                
                <div class="col-auto">
                    <label class="form-label mb-0" style="font-size:12px;">Consegna da</label>
                    <input type="date" name="data_da" class="form-control form-control-sm" value="<?php echo e($dataDa); ?>">
                </div>

                
                <div class="col-auto">
                    <label class="form-label mb-0" style="font-size:12px;">Consegna a</label>
                    <input type="date" name="data_a" class="form-control form-control-sm" value="<?php echo e($dataA); ?>">
                </div>

                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-dark">Filtra</button>
                    <a href="<?php echo e(route('admin.commesse')); ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header bg-dark text-white">
            <strong>Commesse (<?php echo e($commesse->count()); ?>)</strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm table-striped mb-0" style="font-size:13px;">
                    <thead class="table-dark">
                        <tr>
                            <th>Commessa</th>
                            <th>Cliente</th>
                            <th>Descrizione</th>
                            <th>Consegna</th>
                            <th class="text-center">Fasi tot.</th>
                            <th class="text-center">Completate</th>
                            <th class="text-center" style="min-width:120px;">Avanzamento</th>
                            <th class="text-center">Stato</th>
                            <th class="text-center">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $commesse; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td><strong><?php echo e($c->commessa); ?></strong></td>
                            <td><?php echo e($c->cliente_nome ?: '-'); ?></td>
                            <td style="max-width:300px; white-space:normal;"><?php echo e($c->descrizione ?: '-'); ?></td>
                            <td>
                                <?php if($c->data_prevista_consegna): ?>
                                    <?php echo e(\Carbon\Carbon::parse($c->data_prevista_consegna)->format('d/m/Y')); ?>

                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?php echo e($c->fasi_totali); ?></td>
                            <td class="text-center"><?php echo e($c->fasi_completate); ?></td>
                            <td class="text-center">
                                <div class="progress" style="height:18px; min-width:80px;">
                                    <div class="progress-bar <?php echo e($c->consegnata ? 'bg-secondary' : ($c->completata ? 'bg-success' : 'bg-primary')); ?>"
                                         style="width:<?php echo e($c->percentuale); ?>%">
                                        <?php echo e($c->percentuale); ?>%
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <?php if($c->consegnata): ?>
                                    <span class="badge bg-secondary">Consegnata</span>
                                <?php elseif($c->completata): ?>
                                    <span class="badge bg-success">Completata</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">In corso</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="<?php echo e(route('admin.reportCommessa', $c->commessa)); ?>" class="btn btn-sm btn-outline-primary">Report</a>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-3">Nessuna commessa trovata</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Giovanni\graficanappa-gestionale\resources\views\admin\lista_commesse.blade.php ENDPATH**/ ?>