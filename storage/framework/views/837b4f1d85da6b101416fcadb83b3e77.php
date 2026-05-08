<?php $__env->startSection('content'); ?>
<style>
    .table-tariffe th { background: #f8f9fa; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.5px; }
    .badge-non-config { background: #e9ecef; color: #6c757d; }
    .storico-row { background: #fafafa; }
    .header-line { border-bottom: 3px solid #d11317; padding-bottom: 0.5rem; margin-bottom: 1.5rem; }
</style>

<div class="container-fluid mt-3 px-4">
    
    <div class="d-flex justify-content-between align-items-center header-line">
        <div>
            <h4 class="mb-0">Configurazione Tariffe Orarie</h4>
            <small class="text-muted">Costo orario per reparto/macchina</small>
        </div>
        <div>
            <a href="<?php echo e(route('admin.costi.report')); ?>" class="btn btn-outline-primary btn-sm me-1">Report Costi</a>
            <a href="<?php echo e(route('admin.dashboard')); ?>" class="btn btn-outline-secondary btn-sm">Dashboard</a>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo e(session('success')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        
        <div class="col-12 col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h6 class="mb-3">Tariffe per Reparto</h6>
                    <table class="table table-sm table-bordered table-tariffe mb-0">
                        <thead>
                            <tr>
                                <th>Reparto</th>
                                <th class="text-end">Tariffa Corrente</th>
                                <th>Valido Dal</th>
                                <th>Note</th>
                                <th class="text-center" style="width:120px">Azioni</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $reparti; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rep): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $corrente = $rep->costiOrari->whereNull('valido_al')->first();
                                    $storico = $rep->costiOrari->whereNotNull('valido_al');
                                ?>
                                <tr>
                                    <td><strong><?php echo e($rep->nome); ?></strong></td>
                                    <td class="text-end">
                                        <?php if($corrente): ?>
                                            <strong class="text-success"><?php echo e(number_format($corrente->costo_orario, 2, ',', '.')); ?> &euro;/h</strong>
                                        <?php else: ?>
                                            <span class="badge badge-non-config">Non configurato</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo e($corrente ? $corrente->valido_dal->format('d/m/Y') : '-'); ?></td>
                                    <td><small class="text-muted"><?php echo e($corrente->note ?? ''); ?></small></td>
                                    <td class="text-center">
                                        <?php if($storico->count() > 0): ?>
                                            <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#storico-<?php echo e($rep->id); ?>">
                                                Storico (<?php echo e($storico->count()); ?>)
                                            </button>
                                        <?php endif; ?>
                                        <?php if($corrente): ?>
                                            <form method="POST" action="<?php echo e(route('admin.costi.eliminaTariffa', $corrente->id)); ?>" class="d-inline" onsubmit="return confirm('Eliminare la tariffa corrente?')">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button class="btn btn-outline-danger btn-sm" title="Elimina corrente">X</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php if($storico->count() > 0): ?>
                                    <tr class="collapse storico-row" id="storico-<?php echo e($rep->id); ?>">
                                        <td colspan="5">
                                            <table class="table table-sm mb-0" style="font-size:0.8rem;">
                                                <thead><tr><th>Tariffa</th><th>Dal</th><th>Al</th><th>Note</th><th></th></tr></thead>
                                                <tbody>
                                                    <?php $__currentLoopData = $storico; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                        <tr>
                                                            <td><?php echo e(number_format($s->costo_orario, 2, ',', '.')); ?> &euro;/h</td>
                                                            <td><?php echo e($s->valido_dal->format('d/m/Y')); ?></td>
                                                            <td><?php echo e($s->valido_al->format('d/m/Y')); ?></td>
                                                            <td><?php echo e($s->note ?? ''); ?></td>
                                                            <td>
                                                                <form method="POST" action="<?php echo e(route('admin.costi.eliminaTariffa', $s->id)); ?>" class="d-inline" onsubmit="return confirm('Eliminare?')">
                                                                    <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                                                                    <button class="btn btn-outline-danger btn-sm py-0 px-1">X</button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                                </tbody>
                                            </table>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        
        <div class="col-12 col-lg-4">
            <div class="card border-primary">
                <div class="card-header bg-primary text-white">Nuova Tariffa</div>
                <div class="card-body">
                    <form method="POST" action="<?php echo e(route('admin.costi.salvaTariffa')); ?>">
                        <?php echo csrf_field(); ?>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Reparto</label>
                            <select name="reparto_id" class="form-select" required>
                                <option value="">-- Seleziona --</option>
                                <?php $__currentLoopData = $reparti; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rep): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($rep->id); ?>"><?php echo e($rep->nome); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Costo Orario (&euro;/h)</label>
                            <input type="number" name="costo_orario" class="form-control" step="0.01" min="0" required placeholder="es. 45.00">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Valido Dal</label>
                            <input type="date" name="valido_dal" class="form-control" required value="<?php echo e(date('Y-m-d')); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Valido Al <small class="text-muted">(vuoto = corrente)</small></label>
                            <input type="date" name="valido_al" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Note</label>
                            <input type="text" name="note" class="form-control" maxlength="255" placeholder="opzionale">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Salva Tariffa</button>
                    </form>
                    <small class="text-muted d-block mt-2">
                        Se lasci "Valido Al" vuoto, la tariffa diventa quella corrente e la precedente viene chiusa automaticamente.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Giovanni\graficanappa-gestionale\resources\views\admin\costi\config_tariffe.blade.php ENDPATH**/ ?>