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
    .kpi-accent-green  { border-left: 4px solid #198754; }
    .kpi-accent-red    { border-left: 4px solid #dc3545; }
    .kpi-accent-orange { border-left: 4px solid #fd7e14; }
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
    .bottleneck-danger { background-color: #f8d7da !important; }

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
            <h3 class="mb-0">Report Direzione</h3>
            <small class="text-muted"><?php echo e($labelPeriodo); ?></small>
        </div>
        <div class="no-print d-flex flex-wrap align-items-center gap-2 mt-2 mt-md-0">
            
            <?php
                $periodi = ['settimana' => 'Settimana', 'mese' => 'Mese', 'trimestre' => 'Trimestre', 'semestre' => 'Semestre', 'anno' => 'Anno'];
            ?>
            <?php $__currentLoopData = $periodi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route('admin.reportDirezione', ['periodo' => $key])); ?>"
                   class="btn btn-sm btn-periodo <?php echo e($periodo === $key ? 'btn-dark active' : 'btn-outline-dark'); ?>">
                    <?php echo e($label); ?>

                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <span class="mx-1"></span>
            <a href="<?php echo e(route('admin.reportDirezioneExcel', ['periodo' => $periodo])); ?>" class="btn btn-sm btn-success">Export Excel</a>
            <button onclick="window.print()" class="btn btn-sm btn-outline-dark">Stampa</button>
            <a href="<?php echo e(route('admin.dashboard')); ?>" class="btn btn-sm btn-outline-secondary">Dashboard</a>
        </div>
    </div>

    
    <div class="row g-3 mb-4">
        
        <div class="col-6 col-md-2">
            <div class="card kpi-card kpi-accent-blue">
                <div class="card-body">
                    <div class="kpi-value text-primary"><?php echo e(number_format($kpi->fasiCompletate)); ?></div>
                    <div class="kpi-label">Fasi Completate</div>
                    <?php if($delta->fasiCompletate !== null): ?>
                        <div class="kpi-delta <?php echo e($delta->fasiCompletate >= 0 ? 'delta-up' : 'delta-down'); ?>">
                            <?php echo $delta->fasiCompletate >= 0 ? '&#9650;' : '&#9660;'; ?> <?php echo e(abs($delta->fasiCompletate)); ?>%
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-6 col-md-2">
            <div class="card kpi-card kpi-accent-purple">
                <div class="card-body">
                    <div class="kpi-value" style="color:#6f42c1"><?php echo e($kpi->oreLavorate); ?>h</div>
                    <div class="kpi-label">Ore Lavorate</div>
                    <?php if($delta->oreLavorate !== null): ?>
                        <div class="kpi-delta <?php echo e($delta->oreLavorate >= 0 ? 'delta-up' : 'delta-down'); ?>">
                            <?php echo $delta->oreLavorate >= 0 ? '&#9650;' : '&#9660;'; ?> <?php echo e(abs($delta->oreLavorate)); ?>%
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-6 col-md-2">
            <div class="card kpi-card kpi-accent-green">
                <div class="card-body">
                    <div class="kpi-value text-success"><?php echo e($kpi->numCommesseCompletate); ?></div>
                    <div class="kpi-label">Commesse Completate</div>
                    <?php if($delta->commesseCompletate !== null): ?>
                        <div class="kpi-delta <?php echo e($delta->commesseCompletate >= 0 ? 'delta-up' : 'delta-down'); ?>">
                            <?php echo $delta->commesseCompletate >= 0 ? '&#9650;' : '&#9660;'; ?> <?php echo e(abs($delta->commesseCompletate)); ?>%
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-6 col-md-2">
            <div class="card kpi-card kpi-accent-red">
                <div class="card-body">
                    <div class="kpi-value text-danger"><?php echo e($kpi->numCommesseInRitardo); ?></div>
                    <div class="kpi-label">In Ritardo</div>
                    <?php if($delta->commesseInRitardo !== null): ?>
                        
                        <div class="kpi-delta <?php echo e($delta->commesseInRitardo <= 0 ? 'delta-up' : 'delta-down'); ?>">
                            <?php echo $delta->commesseInRitardo >= 0 ? '&#9650;' : '&#9660;'; ?> <?php echo e(abs($delta->commesseInRitardo)); ?>%
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-6 col-md-2">
            <div class="card kpi-card kpi-accent-orange">
                <div class="card-body">
                    <div class="kpi-value" style="color:#fd7e14"><?php echo e($kpi->tassoPuntualita); ?>%</div>
                    <div class="kpi-label">Tasso Puntualita</div>
                    <div class="kpi-delta <?php echo e($delta->tassoPuntualita >= 0 ? 'delta-up' : 'delta-down'); ?>">
                        <?php echo $delta->tassoPuntualita >= 0 ? '&#9650;' : '&#9660;'; ?> <?php echo e(abs($delta->tassoPuntualita)); ?> pp
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-6 col-md-2">
            <div class="card kpi-card kpi-accent-teal">
                <div class="card-body">
                    <div class="kpi-value" style="color:#20c997"><?php echo e($kpi->scartoPercentuale); ?>%</div>
                    <div class="kpi-label">Scarto Prinect</div>
                    
                    <div class="kpi-delta <?php echo e($delta->scartoPercentuale <= 0 ? 'delta-up' : 'delta-down'); ?>">
                        <?php echo $delta->scartoPercentuale >= 0 ? '&#9650;' : '&#9660;'; ?> <?php echo e(abs($delta->scartoPercentuale)); ?> pp
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-7">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Colli di Bottiglia Reparti</div>
                    <?php if($kpi->colliBottiglia->isEmpty()): ?>
                        <p class="text-muted mb-0">Nessun dato disponibile.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-report mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Reparto</th>
                                    <th class="text-end">Coda</th>
                                    <th class="text-end">In Corso</th>
                                    <th class="text-end">Completate</th>
                                    <th class="text-end">T. Medio</th>
                                    <th class="text-end">Indice BN</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $kpi->colliBottiglia; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr class="<?php echo e($r->indice_bottleneck > 2 ? 'bottleneck-danger' : ''); ?>">
                                    <td><strong><?php echo e($r->nome); ?></strong></td>
                                    <td class="text-end"><?php echo e($r->coda); ?></td>
                                    <td class="text-end"><?php echo e($r->in_corso); ?></td>
                                    <td class="text-end"><?php echo e($r->completate_periodo); ?></td>
                                    <td class="text-end"><?php echo e($r->tempo_medio_sec > 0 ? round($r->tempo_medio_sec / 60, 1) . ' min' : '-'); ?></td>
                                    <td class="text-end">
                                        <strong class="<?php echo e($r->indice_bottleneck > 2 ? 'text-danger' : ''); ?>"><?php echo e($r->indice_bottleneck); ?></strong>
                                    </td>
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
                    <div class="section-title">Distribuzione Reparti</div>
                    <canvas id="chartReparti" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-8">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Trend Produzione</div>
                    <canvas id="chartTrend" height="110"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Confronto Periodi</div>
                    <table class="table table-sm table-report mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>KPI</th>
                                <th class="text-end">Attuale</th>
                                <th class="text-end">Precedente</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Fasi Completate</td>
                                <td class="text-end"><strong><?php echo e(number_format($kpi->fasiCompletate)); ?></strong></td>
                                <td class="text-end"><?php echo e(number_format($kpiPrev->fasiCompletate)); ?></td>
                            </tr>
                            <tr>
                                <td>Ore Lavorate</td>
                                <td class="text-end"><strong><?php echo e($kpi->oreLavorate); ?>h</strong></td>
                                <td class="text-end"><?php echo e($kpiPrev->oreLavorate); ?>h</td>
                            </tr>
                            <tr>
                                <td>Commesse Completate</td>
                                <td class="text-end"><strong><?php echo e($kpi->numCommesseCompletate); ?></strong></td>
                                <td class="text-end"><?php echo e($kpiPrev->numCommesseCompletate); ?></td>
                            </tr>
                            <tr>
                                <td>In Ritardo</td>
                                <td class="text-end"><strong><?php echo e($kpi->numCommesseInRitardo); ?></strong></td>
                                <td class="text-end"><?php echo e($kpiPrev->numCommesseInRitardo); ?></td>
                            </tr>
                            <tr>
                                <td>Puntualita</td>
                                <td class="text-end"><strong><?php echo e($kpi->tassoPuntualita); ?>%</strong></td>
                                <td class="text-end"><?php echo e($kpiPrev->tassoPuntualita); ?>%</td>
                            </tr>
                            <tr>
                                <td>Scarto Prinect</td>
                                <td class="text-end"><strong><?php echo e($kpi->scartoPercentuale); ?>%</strong></td>
                                <td class="text-end"><?php echo e($kpiPrev->scartoPercentuale); ?>%</td>
                            </tr>
                        </tbody>
                    </table>
                    <small class="text-muted d-block mt-2">Precedente: <?php echo e($labelPrecedente); ?></small>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Performance Operatori</div>
                    <?php if($kpi->operatoriPerf->isEmpty()): ?>
                        <p class="text-muted mb-0">Nessun dato disponibile.</p>
                    <?php else: ?>
                    <div class="row">
                        <div class="col-12 col-lg-7">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped table-report mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>#</th>
                                            <th>Operatore</th>
                                            <th>Reparti</th>
                                            <th class="text-end">Fasi</th>
                                            <th class="text-end">Ore</th>
                                            <th class="text-end">Qta</th>
                                            <th class="text-end">T.Medio</th>
                                            <th class="text-end">Fasi/gg</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $__currentLoopData = $kpi->operatoriPerf; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $op): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td><?php echo e($i + 1); ?></td>
                                            <td><strong><?php echo e($op->nome); ?> <?php echo e($op->cognome); ?></strong></td>
                                            <td><?php echo e($op->reparti ?: '-'); ?></td>
                                            <td class="text-end"><?php echo e($op->fasi_completate); ?></td>
                                            <td class="text-end"><?php echo e($op->ore_lavorate); ?>h</td>
                                            <td class="text-end"><?php echo e(number_format($op->qta_prodotta ?? 0)); ?></td>
                                            <td class="text-end"><?php echo e($op->tempo_medio_sec > 0 ? round($op->tempo_medio_sec / 60, 1) . 'm' : '-'); ?></td>
                                            <td class="text-end"><?php echo e($op->fasi_giorno); ?></td>
                                        </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="col-12 col-lg-5">
                            <div class="section-title">Top 10 Operatori</div>
                            <canvas id="chartOperatori" height="220"></canvas>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Analisi Pause</div>
                    <?php if($kpi->motiviPausa->isEmpty()): ?>
                        <p class="text-muted mb-0">Nessuna pausa nel periodo.</p>
                    <?php else: ?>
                    <div class="row">
                        <div class="col-7">
                            <table class="table table-sm table-striped table-report mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Motivo</th>
                                        <th class="text-end">N.</th>
                                        <th class="text-end">%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $__currentLoopData = $kpi->motiviPausa; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td><?php echo e($m->motivo); ?></td>
                                        <td class="text-end"><?php echo e($m->totale); ?></td>
                                        <td class="text-end"><?php echo e($m->percentuale); ?>%</td>
                                    </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="col-5">
                            <canvas id="chartPause" height="180"></canvas>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Scarto Prinect</div>
                    <canvas id="chartScarto" height="120"></canvas>
                    <?php if($kpi->topScartoCommesse->isNotEmpty()): ?>
                    <div class="mt-3">
                        <small class="text-muted fw-bold">Top 5 Commesse per Scarto</small>
                        <table class="table table-sm table-striped table-report mb-0 mt-1">
                            <thead class="table-dark">
                                <tr>
                                    <th>Commessa</th>
                                    <th class="text-end">Buoni</th>
                                    <th class="text-end">Scarto</th>
                                    <th class="text-end">%</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $kpi->topScartoCommesse; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><?php echo e($s->commessa_gestionale); ?></td>
                                    <td class="text-end"><?php echo e(number_format($s->good)); ?></td>
                                    <td class="text-end"><?php echo e(number_format($s->waste)); ?></td>
                                    <td class="text-end"><strong><?php echo e($s->scarto_pct); ?>%</strong></td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Top Clienti</div>
                    <?php if($kpi->topClienti->isEmpty()): ?>
                        <p class="text-muted mb-0">Nessun dato disponibile.</p>
                    <?php else: ?>
                    <table class="table table-sm table-striped table-report mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Cliente</th>
                                <th class="text-end">Commesse</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $kpi->topClienti; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td><?php echo e($i + 1); ?></td>
                                <td><?php echo e($c->cliente_nome); ?></td>
                                <td class="text-end"><strong><?php echo e($c->commesse); ?></strong></td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Commesse Completate (<?php echo e($kpi->numCommesseCompletate); ?>)</div>
                    <?php if($kpi->dettaglioCompletate->isEmpty()): ?>
                        <p class="text-muted mb-0">Nessuna commessa completata nel periodo.</p>
                    <?php else: ?>
                    <div class="table-responsive" style="max-height:350px; overflow-y:auto;">
                        <table class="table table-sm table-striped table-report mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Commessa</th>
                                    <th>Cliente</th>
                                    <th class="text-end">Fasi</th>
                                    <th class="text-end">Ore</th>
                                    <th>Consegna</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $kpi->dettaglioCompletate; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><strong><?php echo e($c->commessa); ?></strong></td>
                                    <td><?php echo e($c->cliente); ?></td>
                                    <td class="text-end"><?php echo e($c->fasi_totali); ?></td>
                                    <td class="text-end"><?php echo e($c->ore_totali); ?>h</td>
                                    <td><?php echo e($c->data_consegna ? \Carbon\Carbon::parse($c->data_consegna)->format('d/m/Y') : '-'); ?></td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    
    <?php if($kpi->commesseInRitardo->count() > 0): ?>
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card border-danger">
                <div class="card-body">
                    <div class="section-title text-danger">Commesse in Ritardo (<?php echo e($kpi->commesseInRitardo->count()); ?>)</div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-report mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Commessa</th>
                                    <th>Cliente</th>
                                    <th>Data Consegna</th>
                                    <th class="text-end">Ritardo</th>
                                    <th>Avanzamento</th>
                                    <th>Fasi Mancanti</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $kpi->commesseInRitardo; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><strong><?php echo e($r->commessa); ?></strong></td>
                                    <td><?php echo e($r->cliente_nome ?? '-'); ?></td>
                                    <td><?php echo e($r->data_prevista_consegna ? \Carbon\Carbon::parse($r->data_prevista_consegna)->format('d/m/Y') : '-'); ?></td>
                                    <td class="text-end"><span class="badge bg-danger"><?php echo e($r->giorni_ritardo); ?>gg</span></td>
                                    <td style="min-width:120px">
                                        <div class="progress" style="height:18px">
                                            <div class="progress-bar <?php echo e($r->avanzamento >= 80 ? 'bg-warning' : 'bg-danger'); ?>"
                                                 style="width:<?php echo e($r->avanzamento); ?>%"><?php echo e($r->avanzamento); ?>%</div>
                                        </div>
                                    </td>
                                    <td><small><?php echo e($r->fasi_mancanti); ?></small></td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const colori = ['#0d6efd','#198754','#dc3545','#fd7e14','#6f42c1','#20c997','#0dcaf0','#ffc107','#6610f2','#d63384'];

    // Granularita adattiva per etichette
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

    // --- 1. Trend Produzione (linea + barre) ---
    const trendLabels = <?php echo json_encode(array_keys($kpi->trendGiornaliero), 15, 512) ?>;
    const trendFasi = <?php echo json_encode(array_values($kpi->trendGiornaliero), 15, 512) ?>;
    const trendOre = <?php echo json_encode(array_values($kpi->oreTrendGiornaliero), 15, 512) ?>;

    new Chart(document.getElementById('chartTrend'), {
        data: {
            labels: trendLabels.map(d => formatLabel(d)),
            datasets: [
                {
                    type: 'bar',
                    label: 'Fasi completate',
                    data: trendFasi,
                    backgroundColor: 'rgba(13,110,253,0.6)',
                    borderRadius: 3,
                    yAxisID: 'y',
                    order: 2
                },
                {
                    type: 'line',
                    label: 'Ore lavorate',
                    data: trendOre,
                    borderColor: '#fd7e14',
                    backgroundColor: 'rgba(253,126,20,0.1)',
                    tension: 0.3,
                    pointRadius: 3,
                    fill: true,
                    yAxisID: 'y1',
                    order: 1
                }
            ]
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            scales: {
                y:  { beginAtZero: true, position: 'left',  title: { display: true, text: 'Fasi' } },
                y1: { beginAtZero: true, position: 'right', title: { display: true, text: 'Ore' }, grid: { drawOnChartArea: false } }
            },
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // --- 2. Reparti (barre orizzontali impilate) ---
    const repLabels = <?php echo json_encode($kpi->colliBottiglia->pluck('nome'), 15, 512) ?>;
    const repCoda = <?php echo json_encode($kpi->colliBottiglia->pluck('coda'), 15, 512) ?>;
    const repCorso = <?php echo json_encode($kpi->colliBottiglia->pluck('in_corso'), 15, 512) ?>;
    const repDone = <?php echo json_encode($kpi->colliBottiglia->pluck('completate_periodo'), 15, 512) ?>;

    new Chart(document.getElementById('chartReparti'), {
        type: 'bar',
        data: {
            labels: repLabels,
            datasets: [
                { label: 'In coda',     data: repCoda,  backgroundColor: '#ffc107' },
                { label: 'In corso',    data: repCorso, backgroundColor: '#0d6efd' },
                { label: 'Completate',  data: repDone,  backgroundColor: '#198754' }
            ]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            scales: {
                x: { stacked: true, beginAtZero: true },
                y: { stacked: true }
            },
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // --- 3. Top 10 Operatori (barre orizzontali) ---
    const opData = <?php echo json_encode($kpi->operatoriPerf->take(10)->values(), 15, 512) ?>;
    if (opData.length > 0) {
        new Chart(document.getElementById('chartOperatori'), {
            type: 'bar',
            data: {
                labels: opData.map(o => o.nome + ' ' + o.cognome),
                datasets: [{
                    label: 'Fasi completate',
                    data: opData.map(o => o.fasi_completate),
                    backgroundColor: colori.slice(0, opData.length),
                    borderRadius: 3
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                scales: { x: { beginAtZero: true } },
                plugins: { legend: { display: false } }
            }
        });
    }

    // --- 4. Pause (ciambella) ---
    const pauseLabels = <?php echo json_encode($kpi->motiviPausa->pluck('motivo'), 15, 512) ?>;
    const pauseData = <?php echo json_encode($kpi->motiviPausa->pluck('totale'), 15, 512) ?>;

    if (pauseLabels.length > 0) {
        new Chart(document.getElementById('chartPause'), {
            type: 'doughnut',
            data: {
                labels: pauseLabels,
                datasets: [{
                    data: pauseData,
                    backgroundColor: colori.slice(0, pauseLabels.length),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                cutout: '55%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } }
                }
            }
        });
    }

    // --- 5. Scarto Prinect trend (linea) ---
    const scartoTrend = <?php echo json_encode($kpi->prinectTrend, 15, 512) ?>;
    if (scartoTrend.length > 0) {
        new Chart(document.getElementById('chartScarto'), {
            type: 'line',
            data: {
                labels: scartoTrend.map(r => formatLabel(r.periodo)),
                datasets: [{
                    label: 'Scarto %',
                    data: scartoTrend.map(r => r.scarto_pct),
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220,53,69,0.1)',
                    tension: 0.3,
                    pointRadius: 3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Scarto %' } }
                },
                plugins: { legend: { display: false } }
            }
        });
    }

});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Giovanni\graficanappa-gestionale\resources\views\admin\report_direzione.blade.php ENDPATH**/ ?>