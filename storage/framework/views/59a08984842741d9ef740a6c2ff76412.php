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
    .kpi-accent-blue   { border-left: 4px solid #0d6efd; }
    .kpi-accent-orange  { border-left: 4px solid #fd7e14; }
    .kpi-accent-purple  { border-left: 4px solid #6f42c1; }
    .kpi-accent-green   { border-left: 4px solid #198754; }
    .kpi-accent-teal    { border-left: 4px solid #20c997; }
    .kpi-accent-red     { border-left: 4px solid #dc3545; }
    .header-line { border-bottom: 3px solid #d11317; padding-bottom: 0.5rem; margin-bottom: 1.5rem; }
    .section-title { font-size: 0.95rem; font-weight: 700; margin-bottom: 0.8rem; color: #333; }
    .table-report { font-size: 0.82rem; }
    .table-report th { background: #f8f9fa; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; }
    .btn-periodo.active { font-weight: 700; box-shadow: 0 0 0 2px rgba(13,110,253,0.5); }
    @media print {
        .no-print { display: none !important; }
        .kpi-card { break-inside: avoid; box-shadow: none; border: 1px solid #ddd; }
        .card { break-inside: avoid; box-shadow: none; }
    }
</style>

<div class="container-fluid mt-3 px-4">
    
    <div class="d-flex flex-wrap justify-content-between align-items-center header-line">
        <div>
            <h4 class="mb-0">Report Costi & Margini</h4>
            <small class="text-muted"><?php echo e($labelPeriodo); ?></small>
        </div>
        <div class="d-flex flex-wrap gap-1 align-items-center no-print">
            <?php $__currentLoopData = ['settimana'=>'Settimana','mese'=>'Mese','trimestre'=>'Trimestre','semestre'=>'Semestre','anno'=>'Anno']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="?periodo=<?php echo e($p); ?>" class="btn btn-sm btn-periodo <?php echo e($periodo === $p ? 'btn-primary active' : 'btn-outline-secondary'); ?>"><?php echo e($label); ?></a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <span class="mx-1"></span>
            <a href="<?php echo e(route('admin.costi.reportExcel', ['periodo' => $periodo])); ?>" class="btn btn-sm btn-success">Export XL</a>
            <button onclick="window.print()" class="btn btn-sm btn-outline-dark">Stampa</button>
            <a href="<?php echo e(route('admin.costi.tariffe')); ?>" class="btn btn-sm btn-outline-warning">Config Tariffe</a>
            <a href="<?php echo e(route('admin.dashboard')); ?>" class="btn btn-sm btn-outline-secondary">Dashboard</a>
        </div>
    </div>

    
    <div class="row g-3 mb-4">
        
        <div class="col-6 col-md-2">
            <div class="card kpi-card kpi-accent-blue">
                <div class="card-body">
                    <div class="kpi-value">&euro;<?php echo e(number_format($kpi->totaleValore, 0, ',', '.')); ?></div>
                    <div class="kpi-label">Valore Vendita</div>
                    <?php if($delta->totaleValore !== null): ?>
                        <div class="kpi-delta <?php echo e($delta->totaleValore >= 0 ? 'text-success' : 'text-danger'); ?>">
                            <?php echo e($delta->totaleValore >= 0 ? '&#9650;' : '&#9660;'); ?> <?php echo e(abs($delta->totaleValore)); ?>%
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-6 col-md-2">
            <div class="card kpi-card kpi-accent-orange">
                <div class="card-body">
                    <div class="kpi-value">&euro;<?php echo e(number_format($kpi->costoTotaleLav, 0, ',', '.')); ?></div>
                    <div class="kpi-label">Costo Lavorazione</div>
                    <?php if($delta->costoTotaleLav !== null): ?>
                        <div class="kpi-delta <?php echo e($delta->costoTotaleLav <= 0 ? 'text-success' : 'text-danger'); ?>">
                            <?php echo e($delta->costoTotaleLav >= 0 ? '&#9650;' : '&#9660;'); ?> <?php echo e(abs($delta->costoTotaleLav)); ?>%
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-6 col-md-2">
            <div class="card kpi-card kpi-accent-purple">
                <div class="card-body">
                    <div class="kpi-value">&euro;<?php echo e(number_format($kpi->costoTotaleMat, 0, ',', '.')); ?></div>
                    <div class="kpi-label">Costo Materiali</div>
                    <?php if($delta->costoTotaleMat !== null): ?>
                        <div class="kpi-delta <?php echo e($delta->costoTotaleMat <= 0 ? 'text-success' : 'text-danger'); ?>">
                            <?php echo e($delta->costoTotaleMat >= 0 ? '&#9650;' : '&#9660;'); ?> <?php echo e(abs($delta->costoTotaleMat)); ?>%
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-6 col-md-2">
            <div class="card kpi-card <?php echo e($kpi->margineTotal >= 0 ? 'kpi-accent-green' : 'kpi-accent-red'); ?>">
                <div class="card-body">
                    <div class="kpi-value <?php echo e($kpi->margineTotal < 0 ? 'text-danger' : ''); ?>">&euro;<?php echo e(number_format($kpi->margineTotal, 0, ',', '.')); ?></div>
                    <div class="kpi-label">Margine Lordo</div>
                    <?php if($delta->margineTotal !== null): ?>
                        <div class="kpi-delta <?php echo e($delta->margineTotal >= 0 ? 'text-success' : 'text-danger'); ?>">
                            <?php echo e($delta->margineTotal >= 0 ? '&#9650;' : '&#9660;'); ?> <?php echo e(abs($delta->margineTotal)); ?>%
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-6 col-md-2">
            <div class="card kpi-card kpi-accent-teal">
                <div class="card-body">
                    <div class="kpi-value"><?php echo e($kpi->marginePercMedio); ?>%</div>
                    <div class="kpi-label">Margine % Medio</div>
                    <?php if($delta->marginePercMedio != 0): ?>
                        <div class="kpi-delta <?php echo e($delta->marginePercMedio >= 0 ? 'text-success' : 'text-danger'); ?>">
                            <?php echo e($delta->marginePercMedio >= 0 ? '&#9650;' : '&#9660;'); ?> <?php echo e(abs($delta->marginePercMedio)); ?> pp
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-6 col-md-2">
            <div class="card kpi-card kpi-accent-red">
                <div class="card-body">
                    <div class="kpi-value"><?php echo e($kpi->commesseInPerdita); ?></div>
                    <div class="kpi-label">In Perdita</div>
                    <?php if($delta->commesseInPerdita !== null): ?>
                        <div class="kpi-delta <?php echo e($delta->commesseInPerdita <= 0 ? 'text-success' : 'text-danger'); ?>">
                            <?php echo e($delta->commesseInPerdita >= 0 ? '&#9650;' : '&#9660;'); ?> <?php echo e(abs($delta->commesseInPerdita)); ?>%
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-7">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Trend Ore & Commesse</div>
                    <canvas id="chartTrend" height="130"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-5">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Costi per Reparto</div>
                    <canvas id="chartReparti" height="180"></canvas>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Top 10 Commesse Profittevoli</div>
                    <?php if($kpi->topProfittevoli->count() > 0): ?>
                    <table class="table table-sm table-bordered table-striped table-report mb-0">
                        <thead>
                            <tr>
                                <th>Commessa</th>
                                <th>Cliente</th>
                                <th class="text-end">Valore</th>
                                <th class="text-end">Costo</th>
                                <th class="text-end">Margine</th>
                                <th class="text-end">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $kpi->topProfittevoli; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr class="table-success">
                                <td><strong><?php echo e($c->commessa); ?></strong></td>
                                <td><?php echo e(\Illuminate\Support\Str::limit($c->cliente, 20)); ?></td>
                                <td class="text-end">&euro;<?php echo e(number_format($c->valore_ordine, 0, ',', '.')); ?></td>
                                <td class="text-end">&euro;<?php echo e(number_format($c->costo_totale, 0, ',', '.')); ?></td>
                                <td class="text-end"><strong class="text-success">&euro;<?php echo e(number_format($c->margine, 0, ',', '.')); ?></strong></td>
                                <td class="text-end"><strong><?php echo e($c->margine_pct); ?>%</strong></td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <p class="text-muted">Nessuna commessa con margine calcolabile nel periodo.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Commesse in Perdita</div>
                    <?php if($kpi->topPerdita->count() > 0): ?>
                    <table class="table table-sm table-bordered table-striped table-report mb-0">
                        <thead>
                            <tr>
                                <th>Commessa</th>
                                <th>Cliente</th>
                                <th class="text-end">Valore</th>
                                <th class="text-end">Costo</th>
                                <th class="text-end">Margine</th>
                                <th class="text-end">%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $kpi->topPerdita; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr class="table-danger">
                                <td><strong><?php echo e($c->commessa); ?></strong></td>
                                <td><?php echo e(\Illuminate\Support\Str::limit($c->cliente, 20)); ?></td>
                                <td class="text-end">&euro;<?php echo e(number_format($c->valore_ordine, 0, ',', '.')); ?></td>
                                <td class="text-end">&euro;<?php echo e(number_format($c->costo_totale, 0, ',', '.')); ?></td>
                                <td class="text-end"><strong class="text-danger">&euro;<?php echo e(number_format($c->margine, 0, ',', '.')); ?></strong></td>
                                <td class="text-end"><strong class="text-danger"><?php echo e($c->margine_pct); ?>%</strong></td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <p class="text-muted">Nessuna commessa in perdita nel periodo.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Dettaglio Commesse (<?php echo e($kpi->numCommesse); ?>)</div>
                    <?php if($kpi->dettaglioCommesse->count() > 0): ?>
                    <div class="table-responsive">
                    <table class="table table-sm table-bordered table-striped table-report mb-0">
                        <thead>
                            <tr>
                                <th>Commessa</th>
                                <th>Cliente</th>
                                <th class="text-end">Valore</th>
                                <th class="text-end">Costo Lav.</th>
                                <th class="text-end">Costo Mat.</th>
                                <th class="text-end">Costo Tot.</th>
                                <th class="text-end">Margine</th>
                                <th class="text-end">%</th>
                                <th class="text-end">Ore Stim.</th>
                                <th class="text-end">Ore Eff.</th>
                                <th class="text-end">&#916; Ore</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $kpi->dettaglioCommesse; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $rowClass = '';
                                if ($c->margine !== null && $c->margine < 0) $rowClass = 'table-danger';
                                elseif ($c->margine_pct !== null && $c->margine_pct <= 10 && $c->margine_pct >= 0) $rowClass = 'table-warning';
                                elseif ($c->margine_pct !== null && $c->margine_pct > 25) $rowClass = 'table-success';
                            ?>
                            <tr class="<?php echo e($rowClass); ?>">
                                <td><strong><?php echo e($c->commessa); ?></strong></td>
                                <td><?php echo e(\Illuminate\Support\Str::limit($c->cliente, 18)); ?></td>
                                <td class="text-end"><?php echo e($c->valore_ordine > 0 ? '&euro;' . number_format($c->valore_ordine, 0, ',', '.') : '--'); ?></td>
                                <td class="text-end">&euro;<?php echo e(number_format($c->costo_lav, 0, ',', '.')); ?></td>
                                <td class="text-end"><?php echo e($c->costo_materiali > 0 ? '&euro;' . number_format($c->costo_materiali, 0, ',', '.') : '--'); ?></td>
                                <td class="text-end">&euro;<?php echo e(number_format($c->costo_totale, 0, ',', '.')); ?></td>
                                <td class="text-end">
                                    <?php if($c->margine !== null): ?>
                                        <strong class="<?php echo e($c->margine < 0 ? 'text-danger' : 'text-success'); ?>">&euro;<?php echo e(number_format($c->margine, 0, ',', '.')); ?></strong>
                                    <?php else: ?>
                                        --
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if($c->margine_pct !== null): ?>
                                        <strong class="<?php echo e($c->margine_pct < 0 ? 'text-danger' : ''); ?>"><?php echo e($c->margine_pct); ?>%</strong>
                                    <?php else: ?>
                                        --
                                    <?php endif; ?>
                                </td>
                                <td class="text-end"><?php echo e($c->ore_stimate > 0 ? $c->ore_stimate . 'h' : '--'); ?></td>
                                <td class="text-end"><?php echo e($c->ore_effettive); ?>h</td>
                                <td class="text-end">
                                    <?php if($c->delta_ore_pct !== null): ?>
                                        <span class="<?php echo e($c->delta_ore_pct > 10 ? 'text-danger' : ($c->delta_ore_pct < -10 ? 'text-success' : '')); ?>">
                                            <?php echo e($c->delta_ore_pct > 0 ? '+' : ''); ?><?php echo e($c->delta_ore_pct); ?>%
                                        </span>
                                    <?php else: ?>
                                        --
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                    </div>
                    <?php else: ?>
                        <p class="text-muted">Nessuna commessa completata nel periodo selezionato.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-8">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Costi per Reparto</div>
                    <?php if($kpi->costoPerReparto->count() > 0): ?>
                    <table class="table table-sm table-bordered table-striped table-report mb-0">
                        <thead>
                            <tr>
                                <th>Reparto</th>
                                <th class="text-end">Ore Lavorate</th>
                                <th class="text-end">Tariffa &euro;/h</th>
                                <th class="text-end">Costo Totale</th>
                                <th style="width:25%"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $maxCosto = $kpi->costoPerReparto->max('costo') ?: 1; ?>
                            <?php $__currentLoopData = $kpi->costoPerReparto; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><strong><?php echo e($r->reparto_nome); ?></strong></td>
                                <td class="text-end"><?php echo e($r->ore); ?>h</td>
                                <td class="text-end"><?php echo e(number_format($r->tariffa, 2, ',', '.')); ?> &euro;</td>
                                <td class="text-end"><strong>&euro;<?php echo e(number_format($r->costo, 0, ',', '.')); ?></strong></td>
                                <td>
                                    <div class="progress" style="height:16px;">
                                        <div class="progress-bar bg-primary" style="width: <?php echo e(round(($r->costo / $maxCosto) * 100)); ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                        <p class="text-muted">Nessun dato per il periodo selezionato.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Confronto Periodi</div>
                    <table class="table table-sm table-report mb-0">
                        <thead><tr><th>KPI</th><th class="text-end">Attuale</th><th class="text-end">Prec.</th></tr></thead>
                        <tbody>
                            <tr><td>Valore Vendita</td><td class="text-end">&euro;<?php echo e(number_format($kpi->totaleValore, 0, ',', '.')); ?></td><td class="text-end">&euro;<?php echo e(number_format($kpiPrev->totaleValore, 0, ',', '.')); ?></td></tr>
                            <tr><td>Costo Lavorazione</td><td class="text-end">&euro;<?php echo e(number_format($kpi->costoTotaleLav, 0, ',', '.')); ?></td><td class="text-end">&euro;<?php echo e(number_format($kpiPrev->costoTotaleLav, 0, ',', '.')); ?></td></tr>
                            <tr><td>Costo Materiali</td><td class="text-end">&euro;<?php echo e(number_format($kpi->costoTotaleMat, 0, ',', '.')); ?></td><td class="text-end">&euro;<?php echo e(number_format($kpiPrev->costoTotaleMat, 0, ',', '.')); ?></td></tr>
                            <tr><td>Margine Lordo</td><td class="text-end">&euro;<?php echo e(number_format($kpi->margineTotal, 0, ',', '.')); ?></td><td class="text-end">&euro;<?php echo e(number_format($kpiPrev->margineTotal, 0, ',', '.')); ?></td></tr>
                            <tr><td>Margine % Medio</td><td class="text-end"><?php echo e($kpi->marginePercMedio); ?>%</td><td class="text-end"><?php echo e($kpiPrev->marginePercMedio); ?>%</td></tr>
                            <tr><td>Commesse</td><td class="text-end"><?php echo e($kpi->numCommesse); ?></td><td class="text-end"><?php echo e($kpiPrev->numCommesse); ?></td></tr>
                            <tr><td>In Perdita</td><td class="text-end"><?php echo e($kpi->commesseInPerdita); ?></td><td class="text-end"><?php echo e($kpiPrev->commesseInPerdita); ?></td></tr>
                        </tbody>
                    </table>
                    <small class="text-muted d-block mt-2">Precedente: <?php echo e($labelPrecedente); ?></small>
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
            const p = val.split('-');
            return mesiIt[parseInt(p[1])] + ' ' + p[0];
        } else if (granularita === 'settimana') {
            return 'S' + val.split('-W')[1] + ' ' + val.split('-')[0];
        } else {
            const p = val.split('-');
            return p[2] + '/' + p[1];
        }
    }

    // --- 1. Trend Ore & Commesse ---
    const trend = <?php echo json_encode($kpi->trendMargine, 15, 512) ?>;
    if (trend.length > 0) {
        new Chart(document.getElementById('chartTrend'), {
            data: {
                labels: trend.map(r => formatLabel(r.periodo)),
                datasets: [
                    {
                        type: 'bar',
                        label: 'Commesse',
                        data: trend.map(r => r.n_commesse),
                        backgroundColor: 'rgba(13,110,253,0.6)',
                        borderRadius: 3,
                        yAxisID: 'y',
                        order: 2
                    },
                    {
                        type: 'line',
                        label: 'Ore lavorate',
                        data: trend.map(r => r.ore),
                        borderColor: '#fd7e14',
                        backgroundColor: 'rgba(253,126,20,0.1)',
                        tension: 0.3, pointRadius: 3, fill: true,
                        yAxisID: 'y1',
                        order: 1
                    }
                ]
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    y:  { beginAtZero: true, position: 'left',  title: { display: true, text: 'Commesse' } },
                    y1: { beginAtZero: true, position: 'right', title: { display: true, text: 'Ore' }, grid: { drawOnChartArea: false } }
                },
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }

    // --- 2. Costi per Reparto (ciambella) ---
    const reparti = <?php echo json_encode($kpi->costoPerReparto, 15, 512) ?>;
    if (reparti.length > 0) {
        new Chart(document.getElementById('chartReparti'), {
            type: 'doughnut',
            data: {
                labels: reparti.map(r => r.reparto_nome),
                datasets: [{
                    data: reparti.map(r => r.costo),
                    backgroundColor: colori.slice(0, reparti.length),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                cutout: '55%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ctx.label + ': \u20AC' + ctx.parsed.toLocaleString('it-IT');
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Giovanni\graficanappa-gestionale\resources\views\admin\costi\report_costi.blade.php ENDPATH**/ ?>