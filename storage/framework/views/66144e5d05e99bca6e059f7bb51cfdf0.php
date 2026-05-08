<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Statistiche Operatori</h2>
        <a href="<?php echo e(route('admin.dashboard')); ?>" class="btn btn-sm btn-outline-secondary">Dashboard Admin</a>
    </div>

    
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-primary h-100">
                <div class="card-body text-center py-2">
                    <div class="text-muted small">Operatori attivi</div>
                    <div class="fs-2 fw-bold text-primary"><?php echo e($operatori->count()); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success h-100">
                <div class="card-body text-center py-2">
                    <div class="text-muted small">Fasi completate (totale)</div>
                    <div class="fs-2 fw-bold text-success"><?php echo e($operatori->sum('stat_fasi_completate')); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning h-100">
                <div class="card-body text-center py-2">
                    <div class="text-muted small">Fasi in corso</div>
                    <div class="fs-2 fw-bold text-warning"><?php echo e($operatori->sum('stat_fasi_in_corso')); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info h-100">
                <div class="card-body text-center py-2">
                    <div class="text-muted small">Ore lavorate (totale)</div>
                    <div class="fs-2 fw-bold text-info"><?php echo e(number_format($operatori->sum('stat_sec_totale') / 3600, 1)); ?>h</div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header"><strong>Fasi completate per operatore</strong></div>
                <div class="card-body">
                    <canvas id="chartFasiOperatore" style="height:280px"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header"><strong>Ore lavorate per operatore</strong></div>
                <div class="card-body">
                    <canvas id="chartOreOperatore" style="height:280px"></canvas>
                </div>
            </div>
        </div>
    </div>

    
    <div class="card">
        <div class="card-header"><strong>Dettaglio operatori</strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Codice</th>
                            <th>Nome</th>
                            <th>Reparti</th>
                            <th class="text-center">Fasi completate</th>
                            <th class="text-center">Fasi in corso</th>
                            <th class="text-center">Tempo totale</th>
                            <th class="text-center">Tempo medio/fase</th>
                            <th class="text-center">Qta prodotta</th>
                            <th class="text-center">Ultimi 7gg</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $operatori; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $op): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><strong><?php echo e($op->codice_operatore); ?></strong></td>
                                <td><?php echo e($op->nome); ?> <?php echo e($op->cognome); ?></td>
                                <td><small><?php echo e($op->stat_reparti ?: '-'); ?></small></td>
                                <td class="text-center">
                                    <span class="badge bg-success"><?php echo e($op->stat_fasi_completate); ?></span>
                                </td>
                                <td class="text-center">
                                    <?php if($op->stat_fasi_in_corso > 0): ?>
                                        <span class="badge bg-warning text-dark"><?php echo e($op->stat_fasi_in_corso); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if($op->stat_sec_totale > 0): ?>
                                        <?php echo e(floor($op->stat_sec_totale / 3600)); ?>h <?php echo e(floor(($op->stat_sec_totale % 3600) / 60)); ?>m
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if($op->stat_sec_medio > 0): ?>
                                        <?php echo e(floor($op->stat_sec_medio / 3600)); ?>h <?php echo e(floor(($op->stat_sec_medio % 3600) / 60)); ?>m
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?php if($op->stat_qta_prod > 0): ?>
                                        <?php echo e(number_format($op->stat_qta_prod)); ?>

                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-info"><?php echo e($op->stat_fasi_recenti); ?> fasi</span>
                                    <?php if($op->stat_sec_recenti > 0): ?>
                                        <br><small><?php echo e(floor($op->stat_sec_recenti / 3600)); ?>h <?php echo e(floor(($op->stat_sec_recenti % 3600) / 60)); ?>m</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
const operatori = <?php echo json_encode($operatori->values(), 15, 512) ?>;
const nomi = operatori.map(o => o.nome + ' ' + o.cognome);
const colori = ['#0d6efd','#198754','#dc3545','#ffc107','#0dcaf0','#6f42c1','#fd7e14','#20c997','#6610f2','#d63384'];

// Grafico fasi completate
new Chart(document.getElementById('chartFasiOperatore'), {
    type: 'bar',
    data: {
        labels: nomi,
        datasets: [{
            label: 'Fasi completate',
            data: operatori.map(o => o.stat_fasi_completate),
            backgroundColor: colori.slice(0, operatori.length)
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        scales: { x: { beginAtZero: true } },
        plugins: { legend: { display: false } }
    }
});

// Grafico ore lavorate
new Chart(document.getElementById('chartOreOperatore'), {
    type: 'bar',
    data: {
        labels: nomi,
        datasets: [{
            label: 'Ore lavorate',
            data: operatori.map(o => Math.round(o.stat_sec_totale / 3600 * 10) / 10),
            backgroundColor: colori.slice(0, operatori.length)
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        indexAxis: 'y',
        scales: { x: { beginAtZero: true, ticks: { callback: v => v + 'h' } } },
        plugins: { legend: { display: false } }
    }
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Giovanni\graficanappa-gestionale\resources\views\admin\statistiche_operatori.blade.php ENDPATH**/ ?>