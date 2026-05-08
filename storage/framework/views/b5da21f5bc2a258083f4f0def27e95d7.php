<?php $__env->startSection('content'); ?>
<style>
    @media print {
        .no-print { display: none !important; }
        body { font-size: 11px; }
        .container-fluid { padding: 0 !important; margin: 0 !important; }
        .card { border: none !important; box-shadow: none !important; }
        .card-header { background: #fff !important; color: #000 !important; border-bottom: 2px solid #000 !important; }
        .table th { background: #eee !important; color: #000 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .progress { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .progress-bar { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .bg-success { background-color: #28a745 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .bg-danger { background-color: #dc3545 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .barra-delta { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        @page { margin: 1cm; }
    }
</style>

<div class="container-fluid mt-3 px-3">
    
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2 class="mb-1">Report Commessa: <?php echo e($commessa); ?></h2>
            <p class="text-muted mb-0">
                <strong>Cliente:</strong> <?php echo e($cliente ?: '-'); ?> |
                <strong>Descrizione:</strong> <?php echo e($descrizione ?: '-'); ?> |
                <strong>Consegna:</strong> <?php echo e($consegna ? \Carbon\Carbon::parse($consegna)->format('d/m/Y') : '-'); ?> |
                <strong>Stato:</strong>
                <?php if($completata): ?>
                    <span class="badge bg-success">Completata</span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark">In corso</span>
                <?php endif; ?>
            </p>
        </div>
        <div class="no-print">
            <button onclick="window.print()" class="btn btn-sm btn-dark me-1">Stampa PDF</button>
            <a href="<?php echo e(route('admin.commesse')); ?>" class="btn btn-sm btn-outline-secondary">Lista commesse</a>
        </div>
    </div>

    
    <div class="card mb-3">
        <div class="card-header bg-dark text-white">
            <strong>Dettaglio fasi (<?php echo e($fasi->count()); ?>)</strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-sm table-striped mb-0" style="font-size:12px;">
                    <thead class="table-dark">
                        <tr>
                            <th>Fase</th>
                            <th>Reparto</th>
                            <th>Operatore</th>
                            <th class="text-center">Qta</th>
                            <th class="text-center">Ore stimate</th>
                            <th class="text-center">Ore effettive</th>
                            <th class="text-center">Delta</th>
                            <th class="text-center">%</th>
                            <th style="min-width:120px">Scostamento</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $fasi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $f): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td>
                                <strong><?php echo e($f->fase); ?></strong>
                                <?php if($f->stato == 4): ?>
                                    <span class="badge bg-dark ms-1" style="font-size:9px">Consegnato</span>
                                <?php elseif($f->stato == 3): ?>
                                    <span class="badge bg-success ms-1" style="font-size:9px">Terminato</span>
                                <?php elseif($f->stato == 2): ?>
                                    <span class="badge bg-info ms-1" style="font-size:9px">Avviato</span>
                                <?php elseif($f->stato == 1): ?>
                                    <span class="badge bg-warning text-dark ms-1" style="font-size:9px">Pronto</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary ms-1" style="font-size:9px">Caricato</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e(ucfirst($f->reparto)); ?></td>
                            <td><?php echo e($f->operatore); ?></td>
                            <td class="text-center"><?php echo e(number_format($f->qta)); ?></td>
                            <td class="text-center"><?php echo e($f->ore_stimate); ?>h</td>
                            <td class="text-center">
                                <?php if($f->ore_effettive > 0): ?>
                                    <?php echo e($f->ore_effettive); ?>h
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center fw-bold <?php echo e($f->delta > 0 ? 'text-danger' : ($f->delta < 0 ? 'text-success' : '')); ?>">
                                <?php if($f->ore_effettive > 0): ?>
                                    <?php echo e($f->delta > 0 ? '+' : ''); ?><?php echo e($f->delta); ?>h
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td class="text-center fw-bold <?php echo e($f->percentuale > 0 ? 'text-danger' : ($f->percentuale < 0 ? 'text-success' : '')); ?>">
                                <?php if($f->ore_effettive > 0): ?>
                                    <?php echo e($f->percentuale > 0 ? '+' : ''); ?><?php echo e($f->percentuale); ?>%
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($f->ore_effettive > 0 && $f->ore_stimate > 0): ?>
                                    <?php
                                        $ratio = $f->ore_effettive / $f->ore_stimate;
                                        $barWidth = min($ratio * 100, 200);
                                        $barColor = $ratio <= 1 ? '#28a745' : '#dc3545';
                                    ?>
                                    <div class="barra-delta" style="background:#e9ecef; border-radius:4px; height:14px; position:relative; overflow:hidden;">
                                        <div style="width:<?php echo e(min($barWidth, 100)); ?>%; height:100%; background:<?php echo e($barColor); ?>; border-radius:4px;"></div>
                                        <?php if($ratio <= 1): ?>
                                            <div style="position:absolute; left:<?php echo e($barWidth); ?>%; top:0; width:2px; height:100%; background:#000;"></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr class="fw-bold" style="font-size:13px;">
                            <td colspan="4" class="text-end">TOTALE</td>
                            <td class="text-center"><?php echo e($totaleOreStimate); ?>h</td>
                            <td class="text-center"><?php echo e($totaleOreEffettive); ?>h</td>
                            <td class="text-center <?php echo e($deltaComplessivo > 0 ? 'text-danger' : ($deltaComplessivo < 0 ? 'text-success' : '')); ?>">
                                <?php echo e($deltaComplessivo > 0 ? '+' : ''); ?><?php echo e($deltaComplessivo); ?>h
                            </td>
                            <td class="text-center <?php echo e($percentualeComplessiva > 0 ? 'text-danger' : ($percentualeComplessiva < 0 ? 'text-success' : '')); ?>">
                                <?php echo e($percentualeComplessiva > 0 ? '+' : ''); ?><?php echo e($percentualeComplessiva); ?>%
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card border-primary h-100">
                <div class="card-body text-center py-2">
                    <div class="text-muted small">Ore stimate</div>
                    <div class="fs-3 fw-bold text-primary"><?php echo e($totaleOreStimate); ?>h</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info h-100">
                <div class="card-body text-center py-2">
                    <div class="text-muted small">Ore effettive</div>
                    <div class="fs-3 fw-bold text-info"><?php echo e($totaleOreEffettive); ?>h</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card <?php echo e($deltaComplessivo > 0 ? 'border-danger' : 'border-success'); ?> h-100">
                <div class="card-body text-center py-2">
                    <div class="text-muted small">Delta complessivo</div>
                    <div class="fs-3 fw-bold <?php echo e($deltaComplessivo > 0 ? 'text-danger' : 'text-success'); ?>">
                        <?php echo e($deltaComplessivo > 0 ? '+' : ''); ?><?php echo e($deltaComplessivo); ?>h
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card <?php echo e($percentualeComplessiva > 0 ? 'border-danger' : 'border-success'); ?> h-100">
                <div class="card-body text-center py-2">
                    <div class="text-muted small">Scostamento %</div>
                    <div class="fs-3 fw-bold <?php echo e($percentualeComplessiva > 0 ? 'text-danger' : 'text-success'); ?>">
                        <?php echo e($percentualeComplessiva > 0 ? '+' : ''); ?><?php echo e($percentualeComplessiva); ?>%
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="text-muted small text-center mb-3">
        Report generato il <?php echo e(now()->format('d/m/Y H:i')); ?> | Verde = sotto la stima | Rosso = sopra la stima
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Giovanni\graficanappa-gestionale\resources\views\admin\report_commessa.blade.php ENDPATH**/ ?>