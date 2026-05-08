<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Storico Attivita Prinect</h2>
        <div class="d-flex gap-2">
            <a href="<?php echo e(route('mes.prinect')); ?>" class="btn btn-outline-secondary btn-sm">Prinect</a>
            <a href="<?php echo e(route('mes.prinect.jobs')); ?>" class="btn btn-outline-secondary btn-sm">Lista Job</a>
            <a href="<?php echo e(route('owner.dashboard')); ?>" class="btn btn-dark btn-sm">Dashboard</a>
        </div>
    </div>

    
    <div class="card mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label mb-0 small">Job ID</label>
                    <input type="text" name="job" class="form-control form-control-sm" value="<?php echo e(request('job')); ?>" placeholder="es. 66455">
                </div>
                <div class="col-auto">
                    <label class="form-label mb-0 small">Tipo</label>
                    <select name="tipo" class="form-select form-select-sm">
                        <option value="">Tutti</option>
                        <option value="Avviamento" <?php if(request('tipo') === 'Avviamento'): echo 'selected'; endif; ?>>Avviamento</option>
                        <option value="Produzione fogli buoni" <?php if(request('tipo') === 'Produzione fogli buoni'): echo 'selected'; endif; ?>>Produzione</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label mb-0 small">Data da</label>
                    <input type="date" name="da" class="form-control form-control-sm" value="<?php echo e(request('da')); ?>">
                </div>
                <div class="col-auto">
                    <label class="form-label mb-0 small">Data a</label>
                    <input type="date" name="a" class="form-control form-control-sm" value="<?php echo e(request('a')); ?>">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary btn-sm">Filtra</button>
                    <a href="<?php echo e(route('mes.prinect.attivita')); ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </div>

    
    <?php if($riepilogoJobs->isNotEmpty()): ?>
    <div class="card mb-3">
        <div class="card-header">
            <strong>Riepilogo per Job</strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Job ID</th>
                            <th>Descrizione</th>
                            <th>Commessa</th>
                            <th>Fogli buoni</th>
                            <th>Fogli scarto</th>
                            <th>% Scarto</th>
                            <th>N. Attivita</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $riepilogoJobs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $job): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($job->prinect_job_id); ?></td>
                                <td><?php echo e($job->prinect_job_name); ?></td>
                                <td>
                                    <?php if($job->commessa_gestionale): ?>
                                        <a href="<?php echo e(route('mes.prinect.report', $job->commessa_gestionale)); ?>" class="fw-bold"><?php echo e($job->commessa_gestionale); ?></a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td class="text-success fw-bold"><?php echo e(number_format($job->total_good)); ?></td>
                                <td class="text-danger"><?php echo e(number_format($job->total_waste)); ?></td>
                                <td>
                                    <?php
                                        $totale = $job->total_good + $job->total_waste;
                                        $percentuale = $totale > 0 ? round(($job->total_waste / $totale) * 100, 1) : 0;
                                    ?>
                                    <?php echo e($percentuale); ?>%
                                </td>
                                <td><?php echo e($job->count); ?></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    
    <div class="card">
        <div class="card-header">
            <strong>Dettaglio attivita (<?php echo e($attivita->total()); ?>)</strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Inizio</th>
                            <th>Fine</th>
                            <th>Durata</th>
                            <th>Tipo</th>
                            <th>Job</th>
                            <th>Commessa</th>
                            <th>Workstep</th>
                            <th>Buoni</th>
                            <th>Scarto</th>
                            <th>Operatore</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $attivita; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $att): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr class="<?php if($att->activity_name === 'Avviamento'): ?> table-warning <?php elseif($att->activity_name === 'Produzione fogli buoni'): ?> table-success <?php endif; ?>">
                                <td><?php echo e($att->start_time ? $att->start_time->format('d/m H:i:s') : '-'); ?></td>
                                <td><?php echo e($att->end_time ? $att->end_time->format('d/m H:i:s') : '-'); ?></td>
                                <td>
                                    <?php if($att->start_time && $att->end_time): ?>
                                        <?php
                                            $diff = $att->start_time->diffInSeconds($att->end_time);
                                            $min = floor($diff / 60);
                                            $sec = $diff % 60;
                                        ?>
                                        <?php echo e($min); ?>m <?php echo e($sec); ?>s
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($att->activity_name === 'Avviamento'): ?>
                                        <span class="badge bg-warning text-dark">Avviamento</span>
                                    <?php elseif($att->activity_name === 'Produzione fogli buoni'): ?>
                                        <span class="badge bg-success">Produzione</span>
                                    <?php else: ?>
                                        <?php echo e($att->activity_name ?? '-'); ?>

                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($att->prinect_job_name ?? '-'); ?></td>
                                <td>
                                    <?php if($att->commessa_gestionale): ?>
                                        <a href="<?php echo e(route('mes.prinect.report', $att->commessa_gestionale)); ?>"><?php echo e($att->commessa_gestionale); ?></a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><small><?php echo e($att->workstep_name ?? '-'); ?></small></td>
                                <td>
                                    <?php if($att->good_cycles > 0): ?>
                                        <span class="text-success fw-bold"><?php echo e(number_format($att->good_cycles)); ?></span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($att->waste_cycles > 0): ?>
                                        <span class="text-danger"><?php echo e(number_format($att->waste_cycles)); ?></span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($att->operatore_prinect ?? '-'); ?></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr><td colspan="10" class="text-center">Nessuna attivita trovata. Esegui <code>php artisan prinect:sync-attivita</code> per importare.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-center">
        <?php echo e($attivita->withQueryString()->links('pagination::bootstrap-5')); ?>

    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Giovanni\graficanappa-gestionale\resources\views\mes\prinect_attivita.blade.php ENDPATH**/ ?>