<?php $__env->startSection('content'); ?>
<style>
.ms-pipeline { display:flex; gap:3px; align-items:center; flex-wrap:wrap; }
.ms-step { font-size:10px; padding:2px 6px; border-radius:4px; white-space:nowrap; }
.ms-done { background:#198754; color:#fff; }
.ms-partial { background:#ffc107; color:#000; }
.ms-todo { background:#e9ecef; color:#999; }
.info-label { font-size:11px; text-transform:uppercase; letter-spacing:0.5px; color:#6c757d; margin-bottom:2px; }
.info-value { font-size:14px; font-weight:600; }
.plate-badge { font-size:10px; padding:2px 6px; border-radius:4px; margin:1px; display:inline-block; }
.plate-AVAILABLE, .plate-IMAGED { background:#198754; color:#fff; }
.plate-PENDING { background:#ffc107; color:#000; }
.plate-UNAVAILABLE { background:#e9ecef; color:#999; }
.plate-TO_BE_APPROVED { background:#0d6efd; color:#fff; }
.plate-APPROVED { background:#198754; color:#fff; }
.plate-REJECTED { background:#dc3545; color:#fff; }
</style>

<div class="container-fluid px-3">
    
    <div class="d-flex justify-content-between align-items-center mb-3 mt-2">
        <div>
            <h2 class="mb-0">Job <?php echo e($jobId); ?> <?php if($job): ?> - <?php echo e($job['name']); ?> <?php endif; ?></h2>
            <small class="text-muted">Commessa: <strong><?php echo e($commessa); ?></strong></small>
        </div>
        <div class="d-flex gap-2">
            <?php if($attivitaDB->isNotEmpty()): ?>
                <a href="<?php echo e(route('mes.prinect.report', $commessa)); ?>" class="btn btn-outline-success btn-sm">Report Stampa</a>
            <?php endif; ?>
            <a href="<?php echo e(route('mes.prinect.jobs')); ?>" class="btn btn-outline-secondary btn-sm">Lista Job</a>
            <a href="<?php echo e(route('mes.prinect')); ?>" class="btn btn-outline-secondary btn-sm">Prinect</a>
            <a href="<?php echo e(route('owner.dashboard')); ?>" class="btn btn-dark btn-sm">Dashboard</a>
        </div>
    </div>

    <?php if($job): ?>
    
    <div class="row g-3 mb-3">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
                <div class="card-body py-3">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="info-label">Creazione</div>
                            <div class="info-value"><?php echo e(isset($job['creationDate']) ? \Carbon\Carbon::parse($job['creationDate'])->format('d/m/Y H:i') : '-'); ?></div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-label">Ultima modifica</div>
                            <div class="info-value"><?php echo e(isset($job['lastModified']) ? \Carbon\Carbon::parse($job['lastModified'])->format('d/m/Y H:i') : '-'); ?></div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-label">Stato</div>
                            <div class="info-value">
                                <?php
                                    $gs = $job['jobStatus']['globalStatus'] ?? '-';
                                    $gsClass = match($gs) { 'ACTIVE'=>'bg-primary', 'RUNNING'=>'bg-info', 'FINISHED'=>'bg-success', 'SETUP'=>'bg-warning text-dark', default=>'bg-secondary' };
                                ?>
                                <span class="badge <?php echo e($gsClass); ?>"><?php echo e($gs); ?></span>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-label">Pagine</div>
                            <div class="info-value"><?php echo e($job['numberPlannedPages'] ?? '-'); ?></div>
                        </div>
                        <div class="col-md-2">
                            <div class="info-label">Autore</div>
                            <div class="info-value" style="font-size:12px;"><?php echo e($job['author'] ?? '-'); ?></div>
                        </div>
                    </div>
                    <?php if($job['description']): ?>
                    <div class="mt-2">
                        <div class="info-label">Descrizione</div>
                        <div><?php echo e($job['description']); ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <div class="info-label mb-1">Pipeline produzione</div>
                        <div class="ms-pipeline">
                            <?php $__currentLoopData = $job['jobStatus']['milestones'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $mName = $milestoneMap[$m['milestoneDefId']] ?? '?';
                                    $mProgress = $m['calculatedProgress'] ?? 0;
                                    $mStatus = $m['status'] ?? 'NORMAL';
                                    $mClass = ($mStatus === 'PROGRESS_FINISHED' || $mStatus === 'USER_FINISHED') ? 'ms-done'
                                        : ($mProgress > 0 ? 'ms-partial' : 'ms-todo');
                                ?>
                                <span class="ms-step <?php echo e($mClass); ?>"><?php echo e($mName); ?> <?php echo e($mProgress > 0 ? $mProgress.'%' : ''); ?></span>
                                <?php if(!$loop->last): ?><span style="color:#ccc;">&#9654;</span><?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
                <div class="card-header bg-white border-0"><strong>Anteprima foglio</strong></div>
                <div class="card-body text-center p-2">
                    <?php if($preview): ?>
                        <img src="data:<?php echo e($preview['mimeType']); ?>;base64,<?php echo e($preview['data']); ?>"
                             alt="Preview" style="max-width:100%; max-height:250px; border-radius:8px; border:1px solid #dee2e6; cursor:pointer;"
                             onclick="document.getElementById('previewModal').style.display='flex'">
                    <?php else: ?>
                        <div class="text-muted py-4">Nessuna anteprima disponibile</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    
    <?php if(!empty($elements['pressSheets'])): ?>
    <div class="row g-3 mb-3">
        <?php $__currentLoopData = $elements['pressSheets']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sheet): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm" style="border-radius:12px;">
                <div class="card-header bg-white border-0">
                    <strong><?php echo e($sheet['sheetName'] ?? '-'); ?></strong>
                    <span class="badge bg-secondary ms-1">Foglio stampa</span>
                </div>
                <div class="card-body py-2">
                    <div class="row">
                        <div class="col-4">
                            <div class="info-label">Carta</div>
                            <div class="info-value" style="font-size:13px;"><?php echo e($sheet['brand'] ?? 'N/D'); ?></div>
                        </div>
                        <div class="col-4">
                            <div class="info-label">Grammatura</div>
                            <div class="info-value" style="font-size:13px;"><?php echo e($sheet['weight'] ?? '-'); ?> g/m&sup2;</div>
                        </div>
                        <div class="col-4">
                            <div class="info-label">Formato (cm)</div>
                            <?php
                                $wCm = isset($sheet['width']) ? round($sheet['width'] / 72 * 2.54, 1) : '-';
                                $hCm = isset($sheet['height']) ? round($sheet['height'] / 72 * 2.54, 1) : '-';
                            ?>
                            <div class="info-value" style="font-size:13px;"><?php echo e($wCm); ?> x <?php echo e($hCm); ?></div>
                        </div>
                    </div>
                    <?php if(!empty($sheet['surfaces'])): ?>
                    <div class="mt-2">
                        <?php $__currentLoopData = $sheet['surfaces']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $surf): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div>
                            <span class="info-label"><?php echo e($surf['name']); ?>:</span>
                            <?php $__currentLoopData = $surf['colors'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $color): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $cn = strtolower($color);
                                    $cc = match(true) {
                                        str_contains($cn, 'cyan') => '#00bcd4',
                                        str_contains($cn, 'magenta') => '#e91e63',
                                        str_contains($cn, 'yellow') => '#ffc107',
                                        str_contains($cn, 'black') => '#333',
                                        default => '#9e9e9e'
                                    };
                                ?>
                                <span class="badge" style="background:<?php echo e($cc); ?>; font-size:9px;"><?php echo e($color); ?></span>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php endif; ?>

    
    <?php if(!empty($elements['plates'])): ?>
    <div class="card border-0 shadow-sm mb-3" style="border-radius:12px;">
        <div class="card-header bg-white border-0">
            <strong>Lastre (<?php echo e(count($elements['plates'])); ?>)</strong>
        </div>
        <div class="card-body py-2">
            <?php $__currentLoopData = $elements['plates']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $plate): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $ps = $plate['plateStatus'] ?? 'UNAVAILABLE';
                ?>
                <span class="plate-badge plate-<?php echo e($ps); ?>" title="<?php echo e($plate['sheetName'] ?? ''); ?> - <?php echo e($plate['separationName'] ?? ''); ?> - <?php echo e($ps); ?>">
                    <?php echo e($plate['color'] ?? '?'); ?> / <?php echo e($plate['surfaceName'] ?? '?'); ?>

                    <?php if($ps !== 'UNAVAILABLE'): ?> &#10003; <?php endif; ?>
                </span>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    
    <?php $wsConInk = collect($worksteps)->filter(fn($ws) => !empty($ws['ink'])); ?>

    <?php if($wsConInk->isNotEmpty()): ?>
    <div class="row g-3 mb-3">
        <?php $__currentLoopData = $wsConInk; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ws): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm" style="border-radius:12px;">
                <div class="card-header bg-white border-0">
                    <strong><?php echo e($ws['name'] ?? '-'); ?></strong>
                    <span class="badge <?php echo e(($ws['status'] ?? '') === 'RUNNING' ? 'bg-primary' : (($ws['status'] ?? '') === 'COMPLETED' ? 'bg-success' : 'bg-warning text-dark')); ?> ms-1"><?php echo e($ws['status'] ?? '-'); ?></span>
                </div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-3 text-center">
                            <div class="info-label">Prodotti</div>
                            <div class="fw-bold text-success" style="font-size:18px;"><?php echo e(number_format($ws['amountProduced'] ?? 0)); ?></div>
                        </div>
                        <div class="col-3 text-center">
                            <div class="info-label">Scarto</div>
                            <div class="fw-bold text-danger" style="font-size:18px;"><?php echo e(number_format($ws['wasteProduced'] ?? 0)); ?></div>
                        </div>
                        <div class="col-3 text-center">
                            <div class="info-label">Avviamento</div>
                            <?php $at = collect($ws['actualTimes'] ?? [])->firstWhere('timeTypeName', 'Tempo di avviamento'); ?>
                            <div class="fw-bold" style="font-size:14px;"><?php echo e($at ? floor($at['duration']/3600).'h'.floor(($at['duration']%3600)/60).'m' : '-'); ?></div>
                        </div>
                        <div class="col-3 text-center">
                            <div class="info-label">Produzione</div>
                            <?php $pt = collect($ws['actualTimes'] ?? [])->firstWhere('timeTypeName', 'Tempo di esecuzione'); ?>
                            <div class="fw-bold" style="font-size:14px;"><?php echo e($pt ? floor($pt['duration']/3600).'h'.floor(($pt['duration']%3600)/60).'m' : '-'); ?></div>
                        </div>
                    </div>


                </div>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php endif; ?>

    
    <?php if($wsConInk->isNotEmpty()): ?>
    <div class="card border-0 shadow-sm mb-3" style="border-radius:12px;">
        <div class="card-header bg-white border-0"><strong>Consumo inchiostro totale (kg/1000 fogli)</strong></div>
        <div class="card-body">
            <div style="position:relative; height:200px;">
                <canvas id="inkChartTotal"></canvas>
            </div>
            <div id="inkTotalsTable" class="mt-3"></div>
        </div>
    </div>
    <?php endif; ?>

    
    <div class="card border-0 shadow-sm mb-3" style="border-radius:12px;">
        <div class="card-header bg-white border-0"><strong>Tutti i workstep (<?php echo e(count($worksteps)); ?>)</strong></div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0" style="font-size:13px;">
                    <thead class="table-light">
                        <tr>
                            <th>Nome</th>
                            <th>Tipo</th>
                            <th>Stato</th>
                            <th class="text-center">Pianificati</th>
                            <th class="text-center">Prodotti</th>
                            <th class="text-center">Scarto</th>
                            <th>Inizio</th>
                            <th>Fine</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $worksteps; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ws): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $statusClass = match($ws['status'] ?? '') {
                                'COMPLETED' => 'bg-success',
                                'WAITING' => 'bg-warning text-dark',
                                'RUNNING' => 'bg-primary',
                                'SUSPENDED' => 'bg-secondary',
                                'ABORTED' => 'bg-danger',
                                default => 'bg-secondary'
                            };
                        ?>
                        <tr>
                            <td class="fw-bold"><?php echo e($ws['name'] ?? '-'); ?></td>
                            <td><small><?php echo e(implode(', ', array_slice($ws['types'] ?? [], 0, 2))); ?></small></td>
                            <td><span class="badge <?php echo e($statusClass); ?>"><?php echo e($ws['status'] ?? '-'); ?></span></td>
                            <td class="text-center"><?php echo e(number_format($ws['amountPlanned'] ?? 0)); ?></td>
                            <td class="text-center text-success fw-bold"><?php echo e(number_format($ws['amountProduced'] ?? 0)); ?></td>
                            <td class="text-center text-danger"><?php echo e(number_format($ws['wasteProduced'] ?? 0)); ?></td>
                            <td><small><?php echo e(isset($ws['start']) ? \Carbon\Carbon::parse($ws['start'])->format('d/m H:i') : '-'); ?></small></td>
                            <td><small><?php echo e(isset($ws['end']) ? \Carbon\Carbon::parse($ws['end'])->format('d/m H:i') : '-'); ?></small></td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    
    <?php if($attivitaDB->isNotEmpty()): ?>
    <div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
        <div class="card-header bg-white border-0">
            <strong>Attivita stampa registrate (<?php echo e($attivitaDB->count()); ?>)</strong>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0" style="font-size:13px;">
                    <thead class="table-light">
                        <tr><th>Data</th><th>Tipo</th><th>Workstep</th><th class="text-center">Buoni</th><th class="text-center">Scarto</th><th>Durata</th><th>Operatore</th></tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $attivitaDB->take(30); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $att): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr class="<?php echo e($att->activity_name === 'Avviamento' ? 'table-warning' : 'table-success'); ?>">
                            <td><?php echo e($att->start_time ? $att->start_time->format('d/m H:i') : '-'); ?></td>
                            <td><span class="badge <?php echo e($att->activity_name === 'Avviamento' ? 'bg-warning text-dark' : 'bg-success'); ?>"><?php echo e($att->activity_name === 'Avviamento' ? 'Avv' : 'Prod'); ?></span></td>
                            <td class="small"><?php echo e($att->workstep_name ?? '-'); ?></td>
                            <td class="text-center text-success fw-bold"><?php echo e($att->good_cycles > 0 ? number_format($att->good_cycles) : '-'); ?></td>
                            <td class="text-center text-danger"><?php echo e($att->waste_cycles > 0 ? number_format($att->waste_cycles) : '-'); ?></td>
                            <td>
                                <?php if($att->start_time && $att->end_time): ?>
                                    <?php $d=$att->start_time->diffInSeconds($att->end_time); ?>
                                    <?php echo e(floor($d/60)); ?>m <?php echo e($d%60); ?>s
                                <?php else: ?> - <?php endif; ?>
                            </td>
                            <td class="small"><?php echo e($att->operatore_prinect ?? '-'); ?></td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if($preview): ?>
<div id="previewModal" onclick="this.style.display='none'" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.85); z-index:9999; align-items:center; justify-content:center; cursor:pointer;">
    <img src="data:<?php echo e($preview['mimeType']); ?>;base64,<?php echo e($preview['data']); ?>" alt="Preview" style="max-width:95vw; max-height:95vh; border-radius:8px;">
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
<?php if(isset($wsConInk) && $wsConInk->isNotEmpty()): ?>
(function(){
    // Somma consumi inchiostro di tutti i workstep
    const allInks = [
        <?php $__currentLoopData = $wsConInk; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ws): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php if(!empty($ws['ink']['inkConsumptions'])): ?>
                ...<?php echo json_encode($ws['ink']['inkConsumptions'], 15, 512) ?>,
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    ];

    const totals = {};
    allInks.forEach(i => {
        const color = i.color || '?';
        totals[color] = (totals[color] || 0) + (i.estimatedConsumption || 0);
    });

    const labels = Object.keys(totals);
    const values = Object.values(totals).map(v => Math.round(v * 1000) / 1000);
    const colors = labels.map(l => {
        const n = l.toLowerCase();
        if (n.includes('cyan')) return '#00bcd4';
        if (n.includes('magenta')) return '#e91e63';
        if (n.includes('yellow')) return '#ffc107';
        if (n.includes('black') || n.includes('nero')) return '#333';
        return '#9e9e9e';
    });

    const el = document.getElementById('inkChartTotal');
    if (el) {
        new Chart(el, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'kg/1000',
                    data: values,
                    backgroundColor: colors,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                animation: false,
                scales: { y: { beginAtZero: true, title: { display: true, text: 'kg/1000 fogli' } } },
                plugins: { legend: { display: false } }
            }
        });

        // Tabella riepilogo kg precisi
        const totalKg = values.reduce((s, v) => s + v, 0);
        let html = '<table class="table table-sm mb-0" style="font-size:13px;"><thead class="table-light"><tr><th>Colore</th><th class="text-end">kg/1000 fogli</th></tr></thead><tbody>';
        labels.forEach((l, i) => {
            html += '<tr><td><span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:' + colors[i] + ';vertical-align:middle;margin-right:6px;"></span>' + l + '</td><td class="text-end fw-bold">' + values[i].toFixed(3) + '</td></tr>';
        });
        html += '<tr style="border-top:2px solid #333;"><td class="fw-bold">Totale</td><td class="text-end fw-bold">' + totalKg.toFixed(3) + '</td></tr>';
        html += '</tbody></table>';
        document.getElementById('inkTotalsTable').innerHTML = html;
    }
})();
<?php endif; ?>
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Giovanni\graficanappa-gestionale\resources\views\mes\prinect_job_detail.blade.php ENDPATH**/ ?>