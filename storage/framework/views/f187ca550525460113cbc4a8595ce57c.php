<?php $__env->startSection('content'); ?>
<style>
    .kpi-card {
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        transition: transform 0.15s;
        overflow: hidden;
    }
    .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.12); }
    .kpi-card .card-body { padding: 1rem 1.2rem; }
    .kpi-card .kpi-value { font-size: 1.8rem; font-weight: 700; line-height: 1.1; }
    .kpi-card .kpi-label { font-size: 0.78rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; }
    .kpi-card .kpi-delta { font-size: 0.82rem; font-weight: 600; margin-top: 0.3rem; }
    .kpi-accent-green  { border-left: 4px solid #198754; }
    .kpi-accent-red    { border-left: 4px solid #dc3545; }
    .kpi-accent-orange { border-left: 4px solid #fd7e14; }
    .kpi-accent-blue   { border-left: 4px solid #0d6efd; }
    .kpi-accent-purple { border-left: 4px solid #6f42c1; }
    .kpi-accent-teal   { border-left: 4px solid #20c997; }
    .section-title { font-size: 1.05rem; font-weight: 600; margin-bottom: 0.75rem; }
    .table-report { font-size: 13px; }
    .table-report th { white-space: nowrap; }
    .header-line { border-bottom: 3px solid #d11317; margin-bottom: 1.5rem; padding-bottom: 0.75rem; }
    .delta-up { color: #198754; }
    .delta-down { color: #dc3545; }
    .btn-periodo { min-width: 100px; }
    .btn-periodo.active { font-weight: 700; }
    .oee-bar { height: 30px; border-radius: 6px; overflow: hidden; background: #e9ecef; }
    .oee-fill { height: 100%; display: flex; align-items: center; justify-content: center; color: #fff; font-weight: 700; font-size: 13px; }

    @media print {
        .btn, .no-print { display: none !important; }
        .kpi-card { box-shadow: none !important; border: 1px solid #dee2e6; }
        .card { break-inside: avoid; }
        canvas { max-height: 250px !important; }
    }
</style>

<div class="container-fluid mt-3 px-4">
    
    <div class="d-flex flex-wrap justify-content-between align-items-center header-line">
        <div>
            <h3 class="mb-0">Report Prinect Offset</h3>
            <small class="text-muted"><?php echo e($labelPeriodo); ?> &mdash; XL 106</small>
        </div>
        <div class="no-print d-flex flex-wrap align-items-center gap-2 mt-2 mt-md-0">
            <?php $periodi = ['settimana'=>'Settimana','mese'=>'Mese','trimestre'=>'Trimestre','semestre'=>'Semestre','anno'=>'Anno']; ?>
            <?php $__currentLoopData = $periodi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route('admin.reportPrinect', ['periodo' => $key])); ?>"
                   class="btn btn-sm btn-periodo <?php echo e($periodo === $key ? 'btn-dark active' : 'btn-outline-dark'); ?>"><?php echo e($label); ?></a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <span class="mx-1"></span>
            <a href="<?php echo e(route('admin.reportPrinectExcel', ['periodo' => $periodo])); ?>" class="btn btn-sm btn-success">Export Excel</a>
            <button onclick="window.print()" class="btn btn-sm btn-outline-dark">Stampa</button>
            <a href="<?php echo e(route('admin.dashboard')); ?>" class="btn btn-sm btn-outline-secondary">Dashboard</a>
        </div>
    </div>

    
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-2">
            <div class="card kpi-card kpi-accent-green">
                <div class="card-body">
                    <div class="kpi-value text-success"><?php echo e(number_format($kpi->goodCycles)); ?></div>
                    <div class="kpi-label">Fogli Buoni</div>
                    <?php if($delta->goodCycles !== null): ?>
                        <div class="kpi-delta <?php echo e($delta->goodCycles >= 0 ? 'delta-up' : 'delta-down'); ?>">
                            <?php echo $delta->goodCycles >= 0 ? '&#9650;' : '&#9660;'; ?> <?php echo e(abs($delta->goodCycles)); ?>%
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card kpi-card kpi-accent-red">
                <div class="card-body">
                    <div class="kpi-value text-danger"><?php echo e($kpi->scartoPerc); ?>%</div>
                    <div class="kpi-label">Scarto</div>
                    <div class="kpi-delta <?php echo e($delta->scartoPerc <= 0 ? 'delta-up' : 'delta-down'); ?>">
                        <?php echo $delta->scartoPerc >= 0 ? '&#9650;' : '&#9660;'; ?> <?php echo e(abs($delta->scartoPerc)); ?> pp
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card kpi-card kpi-accent-blue">
                <div class="card-body">
                    <div class="kpi-value text-primary"><?php echo e($kpi->oreTotali); ?>h</div>
                    <div class="kpi-label">Ore Macchina</div>
                    <?php if($delta->oreTotali !== null): ?>
                        <div class="kpi-delta <?php echo e($delta->oreTotali >= 0 ? 'delta-up' : 'delta-down'); ?>">
                            <?php echo $delta->oreTotali >= 0 ? '&#9650;' : '&#9660;'; ?> <?php echo e(abs($delta->oreTotali)); ?>%
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card kpi-card kpi-accent-orange">
                <div class="card-body">
                    <div class="kpi-value" style="color:#fd7e14"><?php echo e($kpi->oee); ?>%</div>
                    <div class="kpi-label">OEE</div>
                    <div class="kpi-delta <?php echo e($delta->oee >= 0 ? 'delta-up' : 'delta-down'); ?>">
                        <?php echo $delta->oee >= 0 ? '&#9650;' : '&#9660;'; ?> <?php echo e(abs($delta->oee)); ?> pp
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card kpi-card kpi-accent-purple">
                <div class="card-body">
                    <div class="kpi-value" style="color:#6f42c1"><?php echo e($kpi->nCommesse); ?></div>
                    <div class="kpi-label">Commesse</div>
                    <?php if($delta->nCommesse !== null): ?>
                        <div class="kpi-delta <?php echo e($delta->nCommesse >= 0 ? 'delta-up' : 'delta-down'); ?>">
                            <?php echo $delta->nCommesse >= 0 ? '&#9650;' : '&#9660;'; ?> <?php echo e(abs($delta->nCommesse)); ?>%
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-2">
            <div class="card kpi-card kpi-accent-teal">
                <div class="card-body">
                    <div class="kpi-value" style="color:#20c997"><?php echo e($kpi->rapportoAvvProd); ?>%</div>
                    <div class="kpi-label">Avviamento/Tot</div>
                    <div class="kpi-delta <?php echo e(($kpi->rapportoAvvProd - $kpiPrev->rapportoAvvProd) <= 0 ? 'delta-up' : 'delta-down'); ?>">
                        <?php echo ($kpi->rapportoAvvProd - $kpiPrev->rapportoAvvProd) >= 0 ? '&#9650;' : '&#9660;'; ?>

                        <?php echo e(abs(round($kpi->rapportoAvvProd - $kpiPrev->rapportoAvvProd, 1))); ?> pp
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-5">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">OEE Breakdown</div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1"><small>Disponibilita</small><strong><?php echo e($kpi->oeeDisp); ?>%</strong></div>
                        <div class="oee-bar"><div class="oee-fill" style="width:<?php echo e($kpi->oeeDisp); ?>%; background:#0d6efd;"><?php echo e($kpi->oeeDisp); ?>%</div></div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1"><small>Performance</small><strong><?php echo e($kpi->oeePerf); ?>%</strong></div>
                        <div class="oee-bar"><div class="oee-fill" style="width:<?php echo e($kpi->oeePerf); ?>%; background:#fd7e14;"><?php echo e($kpi->oeePerf); ?>%</div></div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1"><small>Qualita</small><strong><?php echo e($kpi->oeeQual); ?>%</strong></div>
                        <div class="oee-bar"><div class="oee-fill" style="width:<?php echo e($kpi->oeeQual); ?>%; background:#198754;"><?php echo e($kpi->oeeQual); ?>%</div></div>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between mb-1"><small><strong>OEE Complessivo</strong></small><strong><?php echo e($kpi->oee); ?>%</strong></div>
                        <div class="oee-bar"><div class="oee-fill" style="width:<?php echo e($kpi->oee); ?>%; background:<?php echo e($kpi->oee >= 60 ? '#198754' : ($kpi->oee >= 40 ? '#fd7e14' : '#dc3545')); ?>;"><?php echo e($kpi->oee); ?>%</div></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-7">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Confronto Periodi</div>
                    <table class="table table-sm table-report mb-0">
                        <thead class="table-dark">
                            <tr><th>KPI</th><th class="text-end">Attuale</th><th class="text-end">Precedente</th></tr>
                        </thead>
                        <tbody>
                            <tr><td>Fogli Buoni</td><td class="text-end"><strong><?php echo e(number_format($kpi->goodCycles)); ?></strong></td><td class="text-end"><?php echo e(number_format($kpiPrev->goodCycles)); ?></td></tr>
                            <tr><td>Fogli Scarto</td><td class="text-end"><strong><?php echo e(number_format($kpi->wasteCycles)); ?></strong></td><td class="text-end"><?php echo e(number_format($kpiPrev->wasteCycles)); ?></td></tr>
                            <tr><td>Scarto %</td><td class="text-end"><strong><?php echo e($kpi->scartoPerc); ?>%</strong></td><td class="text-end"><?php echo e($kpiPrev->scartoPerc); ?>%</td></tr>
                            <tr><td>Ore Totali</td><td class="text-end"><strong><?php echo e($kpi->oreTotali); ?>h</strong></td><td class="text-end"><?php echo e($kpiPrev->oreTotali); ?>h</td></tr>
                            <tr><td>Ore Avviamento</td><td class="text-end"><strong><?php echo e($kpi->oreAvviamento); ?>h</strong></td><td class="text-end"><?php echo e($kpiPrev->oreAvviamento); ?>h</td></tr>
                            <tr><td>Ore Produzione</td><td class="text-end"><strong><?php echo e($kpi->oreProduzione); ?>h</strong></td><td class="text-end"><?php echo e($kpiPrev->oreProduzione); ?>h</td></tr>
                            <tr><td>Commesse</td><td class="text-end"><strong><?php echo e($kpi->nCommesse); ?></strong></td><td class="text-end"><?php echo e($kpiPrev->nCommesse); ?></td></tr>
                            <tr><td>OEE</td><td class="text-end"><strong><?php echo e($kpi->oee); ?>%</strong></td><td class="text-end"><?php echo e($kpiPrev->oee); ?>%</td></tr>
                        </tbody>
                    </table>
                    <small class="text-muted d-block mt-2">Precedente: <?php echo e($labelPrecedente); ?></small>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-7">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Trend Produzione Giornaliera</div>
                    <canvas id="chartProd" height="120"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-5">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Trend Scarto %</div>
                    <canvas id="chartScarto" height="120"></canvas>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-7">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Tempo Avviamento vs Produzione</div>
                    <canvas id="chartTempi" height="120"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-5">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Ripartizione Tempo</div>
                    <canvas id="chartTempoPie" height="180"></canvas>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-7">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Performance Operatori Prinect</div>
                    <?php if($kpi->perOperatore->isEmpty()): ?>
                        <p class="text-muted mb-0">Nessun dato.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-report mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Operatore</th>
                                    <th class="text-end">Buoni</th>
                                    <th class="text-end">Scarto</th>
                                    <th class="text-end">%</th>
                                    <th class="text-end">Ore Avv.</th>
                                    <th class="text-end">Ore Prod.</th>
                                    <th class="text-end">Att.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $kpi->perOperatore; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $op): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><strong><?php echo e($op->nome); ?></strong></td>
                                    <td class="text-end"><?php echo e(number_format($op->buoni)); ?></td>
                                    <td class="text-end"><?php echo e(number_format($op->scarto)); ?></td>
                                    <td class="text-end"><?php echo e(($op->buoni + $op->scarto) > 0 ? round(($op->scarto / ($op->buoni + $op->scarto)) * 100, 1) : 0); ?>%</td>
                                    <td class="text-end"><?php echo e(round($op->sec_avv / 3600, 1)); ?>h</td>
                                    <td class="text-end"><?php echo e(round($op->sec_prod / 3600, 1)); ?>h</td>
                                    <td class="text-end"><?php echo e($op->n_attivita); ?></td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-5">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Fogli per Operatore</div>
                    <canvas id="chartOperatori" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-7">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Top Commesse per Produzione</div>
                    <?php if($kpi->perCommessa->isEmpty()): ?>
                        <p class="text-muted mb-0">Nessun dato.</p>
                    <?php else: ?>
                    <div class="table-responsive" style="max-height:400px; overflow-y:auto;">
                        <table class="table table-sm table-striped table-report mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Commessa</th>
                                    <th>Job</th>
                                    <th class="text-end">Buoni</th>
                                    <th class="text-end">Scarto</th>
                                    <th class="text-end">%</th>
                                    <th class="text-end">Ore Avv.</th>
                                    <th class="text-end">Ore Prod.</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $kpi->perCommessa; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><strong><?php echo e($c->commessa); ?></strong></td>
                                    <td><small><?php echo e(\Illuminate\Support\Str::limit($c->job_name, 25)); ?></small></td>
                                    <td class="text-end"><?php echo e(number_format($c->buoni)); ?></td>
                                    <td class="text-end"><?php echo e(number_format($c->scarto)); ?></td>
                                    <td class="text-end"><?php echo e($c->scarto_pct); ?>%</td>
                                    <td class="text-end"><?php echo e(round($c->sec_avv / 3600, 1)); ?>h</td>
                                    <td class="text-end"><?php echo e(round($c->sec_prod / 3600, 1)); ?>h</td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-5">
            <div class="card">
                <div class="card-body">
                    <div class="section-title text-danger">Top 5 Commesse per Scarto</div>
                    <?php if($kpi->topScarto->isEmpty()): ?>
                        <p class="text-muted mb-0">Nessun dato.</p>
                    <?php else: ?>
                    <table class="table table-sm table-striped table-report mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>Commessa</th>
                                <th>Job</th>
                                <th class="text-end">Buoni</th>
                                <th class="text-end">Scarto</th>
                                <th class="text-end">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $kpi->topScarto; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><strong><?php echo e($c->commessa); ?></strong></td>
                                <td><small><?php echo e(\Illuminate\Support\Str::limit($c->job_name, 20)); ?></small></td>
                                <td class="text-end"><?php echo e(number_format($c->buoni)); ?></td>
                                <td class="text-end"><?php echo e(number_format($c->scarto)); ?></td>
                                <td class="text-end"><strong class="text-danger"><?php echo e($c->scarto_pct); ?>%</strong></td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const colori = ['#0d6efd','#198754','#dc3545','#fd7e14','#6f42c1','#20c997','#0dcaf0','#ffc107','#6610f2','#d63384'];
    const granularita = <?php echo json_encode($kpi->granularita, 15, 512) ?>;
    const mesiIt = ['','Gen','Feb','Mar','Apr','Mag','Giu','Lug','Ago','Set','Ott','Nov','Dic'];

    function formatLabel(val) {
        if (granularita === 'mese') {
            // formato: 2026-02
            const p = val.split('-');
            return mesiIt[parseInt(p[1])] + ' ' + p[0];
        } else if (granularita === 'settimana') {
            // formato: 2026-W07
            return 'S' + val.split('-W')[1] + ' ' + val.split('-')[0];
        } else {
            // formato: 2026-02-18
            const p = val.split('-');
            return p[2] + '/' + p[1];
        }
    }

    // --- 1. Trend Produzione (barre impilate buoni + scarto) ---
    const trend = <?php echo json_encode($kpi->trendGiornaliero, 15, 512) ?>;
    new Chart(document.getElementById('chartProd'), {
        type: 'bar',
        data: {
            labels: trend.map(r => formatLabel(r.giorno)),
            datasets: [
                { label: 'Buoni', data: trend.map(r => r.good), backgroundColor: '#198754', borderRadius: 2 },
                { label: 'Scarto', data: trend.map(r => r.waste), backgroundColor: '#dc3545', borderRadius: 2 }
            ]
        },
        options: {
            responsive: true,
            scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } },
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // --- 2. Trend Scarto % (linea) ---
    new Chart(document.getElementById('chartScarto'), {
        type: 'line',
        data: {
            labels: trend.map(r => formatLabel(r.giorno)),
            datasets: [{
                label: 'Scarto %',
                data: trend.map(r => r.scarto_pct),
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220,53,69,0.1)',
                tension: 0.3, pointRadius: 3, fill: true
            }]
        },
        options: {
            responsive: true,
            scales: { y: { beginAtZero: true, title: { display: true, text: '%' } } },
            plugins: { legend: { display: false } }
        }
    });

    // --- 3. Tempo Avviamento vs Produzione (barre impilate) ---
    const tempi = <?php echo json_encode($kpi->trendTempi, 15, 512) ?>;
    new Chart(document.getElementById('chartTempi'), {
        type: 'bar',
        data: {
            labels: tempi.map(r => formatLabel(r.periodo)),
            datasets: [
                { label: 'Avviamento (min)', data: tempi.map(r => Math.round(r.sec_avv / 60)), backgroundColor: '#ffc107', borderRadius: 2 },
                { label: 'Produzione (min)', data: tempi.map(r => Math.round(r.sec_prod / 60)), backgroundColor: '#0d6efd', borderRadius: 2 }
            ]
        },
        options: {
            responsive: true,
            scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, title: { display: true, text: 'Minuti' } } },
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // --- 4. Ripartizione Tempo (ciambella) ---
    new Chart(document.getElementById('chartTempoPie'), {
        type: 'doughnut',
        data: {
            labels: ['Avviamento', 'Produzione'],
            datasets: [{
                data: [<?php echo e($kpi->oreAvviamento); ?>, <?php echo e($kpi->oreProduzione); ?>],
                backgroundColor: ['#ffc107', '#0d6efd'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true, cutout: '55%',
            plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } }
        }
    });

    // --- 5. Operatori (barre orizzontali impilate) ---
    const opData = <?php echo json_encode($kpi->perOperatore, 15, 512) ?>;
    if (opData.length > 0) {
        new Chart(document.getElementById('chartOperatori'), {
            type: 'bar',
            data: {
                labels: opData.map(o => o.nome),
                datasets: [
                    { label: 'Buoni', data: opData.map(o => o.buoni), backgroundColor: '#198754' },
                    { label: 'Scarto', data: opData.map(o => o.scarto), backgroundColor: '#dc3545' }
                ]
            },
            options: {
                indexAxis: 'y', responsive: true,
                scales: { x: { stacked: true, beginAtZero: true }, y: { stacked: true } },
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Giovanni\graficanappa-gestionale\resources\views\admin\report_prinect.blade.php ENDPATH**/ ?>