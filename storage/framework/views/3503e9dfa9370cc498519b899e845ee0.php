<?php $__env->startSection('content'); ?>
<style>
.kpi-card { border-radius: 12px; transition: transform 0.15s; }
.kpi-card:hover { transform: translateY(-2px); }
.kpi-value { font-size: 2rem; font-weight: 800; line-height: 1.1; }
.kpi-label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; opacity: 0.7; }
.kpi-sub { font-size: 0.85rem; }
.status-badge { font-size: 1rem; padding: 6px 16px; border-radius: 20px; font-weight: 700; }
.status-Running { background: #198754; color: #fff; }
.status-Idle { background: #ffc107; color: #000; }
.status-Stopped, .status-Error { background: #dc3545; color: #fff; }
.live-dot { display: inline-block; width: 10px; height: 10px; border-radius: 50%; margin-right: 6px; animation: pulse 1.5s infinite; }
.live-dot.green { background: #198754; }
.live-dot.yellow { background: #ffc107; }
.live-dot.red { background: #dc3545; }
@keyframes pulse { 0%,100% { opacity:1; } 50% { opacity:0.4; } }
.op-table td, .op-table th { font-size: 13px; padding: 6px 10px; }
.chart-card { border-radius: 12px; }
</style>

<div class="container-fluid px-3">
    
    <div class="d-flex justify-content-between align-items-center mb-3 mt-2">
        <div>
            <h2 class="mb-0">Dashboard Prinect <span class="live-dot green" id="liveDot"></span></h2>
            <small class="text-muted">XL 106 - Aggiornamento automatico ogni 10s</small>
        </div>
        <div class="d-flex gap-2">
            <a href="<?php echo e(route('mes.prinect.attivita')); ?>" class="btn btn-outline-primary btn-sm">Storico Attivita</a>
            <a href="<?php echo e(route('mes.prinect.jobs')); ?>" class="btn btn-outline-secondary btn-sm">Lista Job</a>
            <a href="<?php echo e(route('owner.dashboard')); ?>" class="btn btn-dark btn-sm">Dashboard</a>
        </div>
    </div>

    
    <?php if(!empty($alerts)): ?>
    <div class="mb-3">
        <?php $__currentLoopData = $alerts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $alert): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="alert alert-<?php echo e($alert['tipo']); ?> py-2 mb-2 d-flex align-items-center" style="font-size:14px;">
            <strong class="me-2"><?php echo e($alert['tipo'] === 'danger' ? 'ALERT' : 'ATTENZIONE'); ?></strong> <?php echo e($alert['msg']); ?>

        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php endif; ?>

    
    <div class="card mb-3 border-0 shadow-sm" style="border-radius:12px;">
        <div class="card-body py-3">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <div class="kpi-label mb-1">Stato Macchina</div>
                    <span class="status-badge status-<?php echo e($device['deviceStatus']['status'] ?? 'Idle'); ?>" id="liveStatus">
                        <?php echo e($device['deviceStatus']['status'] ?? '-'); ?>

                    </span>
                </div>
                <div class="col-md-2 text-center">
                    <div class="kpi-label mb-1">Velocita</div>
                    <div class="kpi-value text-primary" id="liveSpeed"><?php echo e(number_format($device['deviceStatus']['speed'] ?? 0)); ?></div>
                    <div class="kpi-sub">fogli/h</div>
                </div>
                <div class="col-md-3">
                    <div class="kpi-label mb-1">Job Corrente</div>
                    <div class="fw-bold" id="liveJob" style="font-size:0.95rem;"><?php echo e($device['deviceStatus']['workstep']['job']['name'] ?? '-'); ?></div>
                    <div class="text-muted small" id="liveWorkstep"><?php echo e($device['deviceStatus']['workstep']['name'] ?? '-'); ?></div>
                </div>
                <div class="col-md-2 text-center">
                    <div class="kpi-label mb-1">Prodotti / Scarto</div>
                    <div>
                        <span class="fw-bold text-success" id="liveProduced"><?php echo e(number_format($device['deviceStatus']['workstep']['amountProduced'] ?? 0)); ?></span>
                        <span class="text-muted">/</span>
                        <span class="fw-bold text-danger" id="liveWaste"><?php echo e(number_format($device['deviceStatus']['workstep']['wasteProduced'] ?? 0)); ?></span>
                    </div>
                </div>
                <div class="col-md-2 text-center">
                    <div class="kpi-label mb-1">Totalizzatore</div>
                    <div class="fw-bold" id="liveTotalizer" style="font-size:1.1rem;"><?php echo e(number_format($device['deviceStatus']['totalizer'] ?? 0)); ?></div>
                </div>
                <div class="col-md-1 text-center">
                    <div class="kpi-label mb-1">Operatore</div>
                    <div class="small fw-bold" id="liveOperatori">
                        <?php $__currentLoopData = $device['deviceStatus']['employees'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $emp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php echo e($emp['firstName'] ?? ''); ?> <?php echo e($emp['name'] ?? ''); ?><br>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row g-3 mb-2">
        <div class="col-md-3">
            <div class="card kpi-card border-0 shadow-sm h-100 bg-success bg-opacity-10">
                <div class="card-body text-center py-3">
                    <div class="kpi-label text-success">Fogli buoni oggi</div>
                    <div class="kpi-value text-success"><?php echo e(number_format($totBuoniOggi)); ?></div>
                    <div class="kpi-sub text-success" data-live-produced><span class="live-dot green" style="width:6px;height:6px;"></span>+<?php echo e(number_format($liveProduced)); ?> in corso</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card kpi-card border-0 shadow-sm h-100 bg-danger bg-opacity-10">
                <div class="card-body text-center py-3">
                    <div class="kpi-label text-danger">Scarto oggi</div>
                    <div class="kpi-value text-danger"><?php echo e(number_format($totScartoOggi)); ?></div>
                    <div class="kpi-sub text-danger" data-live-waste><span class="live-dot red" style="width:6px;height:6px;"></span>+<?php echo e(number_format($liveWaste)); ?> in corso</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card kpi-card border-0 shadow-sm h-100 bg-warning bg-opacity-10">
                <div class="card-body text-center py-3">
                    <div class="kpi-label text-warning">Avviamento oggi</div>
                    <div class="kpi-value text-warning"><?php echo e(floor($secAvvOggi/3600)); ?>h<?php echo e(floor(($secAvvOggi%3600)/60)); ?>m</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card kpi-card border-0 shadow-sm h-100 bg-primary bg-opacity-10">
                <div class="card-body text-center py-3">
                    <div class="kpi-label text-primary">Produzione oggi</div>
                    <div class="kpi-value text-primary"><?php echo e(floor($secProdOggi/3600)); ?>h<?php echo e(floor(($secProdOggi%3600)/60)); ?>m</div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card kpi-card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="kpi-label">Fogli 7gg</div>
                    <div class="kpi-value"><?php echo e(number_format($totFogli7gg)); ?></div>
                    <div class="kpi-sub text-muted">buoni: <?php echo e(number_format($totBuoni7gg)); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card kpi-card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="kpi-label">Lastre 7gg</div>
                    <div class="kpi-value"><?php echo e(number_format($cambiLastra)); ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card kpi-card border-0 shadow-sm h-100 bg-secondary bg-opacity-10">
                <div class="card-body text-center py-3">
                    <div class="kpi-label">Cambio lastre</div>
                    <div class="kpi-value"><?php echo e(number_format($mediaLastreCommessa, 1, ',', '.')); ?></div>
                    <div class="kpi-sub text-muted">media per commessa</div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card kpi-card border-0 shadow-sm h-100" style="background: <?php echo e($oee >= 60 ? 'rgba(25,135,84,0.1)' : ($oee >= 40 ? 'rgba(255,193,7,0.1)' : 'rgba(220,53,69,0.1)')); ?>;">
                <div class="card-body text-center py-3">
                    <div class="kpi-label">OEE 7 giorni</div>
                    <div class="kpi-value" style="font-size:2.5rem; color: <?php echo e($oee >= 60 ? '#198754' : ($oee >= 40 ? '#e67e22' : '#dc3545')); ?>;"><?php echo e($oee); ?>%</div>
                    <div class="kpi-sub text-muted">Overall Equipment Effectiveness</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card kpi-card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="kpi-label">Disponibilita</div>
                    <div class="kpi-value text-info"><?php echo e($oeeDisp); ?>%</div>
                    <div class="kpi-sub text-muted">Tempo operativo / pianificato</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card kpi-card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="kpi-label">Performance</div>
                    <div class="kpi-value text-primary"><?php echo e($oeePerf); ?>%</div>
                    <div class="kpi-sub text-muted">Velocita reale / nominale</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card kpi-card border-0 shadow-sm h-100">
                <div class="card-body text-center py-3">
                    <div class="kpi-label">Qualita</div>
                    <div class="kpi-value text-success"><?php echo e($oeeQual); ?>%</div>
                    <div class="kpi-sub text-muted">Fogli buoni / totali</div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row g-3 mb-3">
        <div class="col-md-8">
            <div class="card chart-card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0"><strong>Produzione ultimi 7 giorni</strong></div>
                <div class="card-body">
                    <canvas id="chartProd7gg" height="220"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card chart-card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0"><strong>Tempo oggi: Avviamento vs Produzione</strong></div>
                <div class="card-body d-flex align-items-center justify-content-center">
                    <canvas id="chartTempoOggi" style="max-height:220px"></canvas>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row g-3 mb-3">
        <div class="col-md-6">
            <div class="card chart-card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0"><strong>Performance operatori (7gg)</strong></div>
                <div class="card-body">
                    <canvas id="chartOperatori" height="200"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card chart-card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0"><strong>% Scarto per giorno</strong></div>
                <div class="card-body">
                    <canvas id="chartScartoGiorno" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    
    <div class="card chart-card border-0 shadow-sm mb-3">
        <div class="card-header bg-white border-0"><strong>Timeline attivita oggi</strong></div>
        <div class="card-body">
            <canvas id="chartTimeline" style="height:180px"></canvas>
        </div>
    </div>

    
    <div class="row g-3 mb-3">
        
        <div class="col-md-5">
            <div class="card chart-card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0"><strong>Operatori (7gg)</strong></div>
                <div class="card-body p-0">
                    <table class="table table-sm op-table mb-0">
                        <thead class="table-light">
                            <tr><th>Operatore</th><th class="text-center">Buoni</th><th class="text-center">Scarto</th><th class="text-center">% Scarto</th><th class="text-center">T. Avv</th><th class="text-center">T. Prod</th></tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $perOperatore->sortByDesc('buoni'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $nome => $dati): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php $tot = $dati->buoni + $dati->scarto; $perc = $tot > 0 ? round(($dati->scarto/$tot)*100,1) : 0; ?>
                            <tr>
                                <td class="fw-bold"><?php echo e($nome); ?></td>
                                <td class="text-center text-success fw-bold"><?php echo e(number_format($dati->buoni)); ?></td>
                                <td class="text-center text-danger"><?php echo e(number_format($dati->scarto)); ?></td>
                                <td class="text-center"><?php echo e($perc); ?>%</td>
                                <td class="text-center"><?php echo e(floor($dati->sec_avv/3600)); ?>h<?php echo e(floor(($dati->sec_avv%3600)/60)); ?>m</td>
                                <td class="text-center"><?php echo e(floor($dati->sec_prod/3600)); ?>h<?php echo e(floor(($dati->sec_prod%3600)/60)); ?>m</td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-7">
            <div class="card chart-card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0"><strong>Top commesse (7gg)</strong></div>
                <div class="card-body p-0">
                    <table class="table table-sm op-table mb-0">
                        <thead class="table-light">
                            <tr><th>Commessa</th><th>Job</th><th class="text-center">Buoni</th><th class="text-center">Scarto</th><th class="text-center">% Scarto</th><th class="text-center">Attivita</th></tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $topCommesse; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php $tot = $c->buoni + $c->scarto; $perc = $tot > 0 ? round(($c->scarto/$tot)*100,1) : 0; ?>
                            <tr>
                                <td><a href="<?php echo e(route('mes.prinect.report', $c->commessa)); ?>" class="fw-bold"><?php echo e($c->commessa); ?></a></td>
                                <td class="small"><?php echo e($c->job_name); ?></td>
                                <td class="text-center text-success fw-bold"><?php echo e(number_format($c->buoni)); ?></td>
                                <td class="text-center text-danger"><?php echo e(number_format($c->scarto)); ?></td>
                                <td class="text-center"><?php echo e($perc); ?>%</td>
                                <td class="text-center"><?php echo e($c->n_attivita); ?></td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    
    <div class="card chart-card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
            <strong>Attivita oggi (<?php echo e($attivitaOggiPerCommessa->count()); ?> commesse, <?php echo e($attivitaOggi->count()); ?> attivita)</strong>
            <a href="<?php echo e(route('mes.prinect.attivita')); ?>" class="btn btn-sm btn-outline-primary">Vedi tutto lo storico</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm op-table mb-0">
                    <thead class="table-light">
                        <tr><th>Ultima att.</th><th>Tempo tot.</th><th>Stato</th><th>Commessa</th><th>Job</th><th>Workstep</th><th class="text-center">Buoni</th><th class="text-center">Scarto</th><th class="text-center">Att.</th><th>Operatore</th></tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $attivitaOggiPerCommessa; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $att): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $jId = $att->prinect_job_id ?? null;
                            $comm = ($jId && is_numeric($jId)) ? str_pad($jId, 7, '0', STR_PAD_LEFT) . '-' . date('y') : null;
                            $sec = $att->sec_totali ?? 0;
                        ?>
                        <tr class="<?php if($att->activity_name === 'Avviamento'): ?> table-warning <?php else: ?> table-success <?php endif; ?>">
                            <td><?php echo e($att->start_time ? $att->start_time->format('H:i:s') : '-'); ?></td>
                            <td><?php if($sec > 0): ?><?php echo e(floor($sec/3600) > 0 ? floor($sec/3600).'h ' : ''); ?><?php echo e(floor(($sec%3600)/60)); ?>m <?php else: ?> - <?php endif; ?></td>
                            <td>
                                <?php if($att->activity_name === 'Avviamento'): ?>
                                    <span class="badge bg-warning text-dark">Avv</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Prod</span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-bold"><?php if($comm): ?><a href="<?php echo e(route('mes.prinect.report', $comm)); ?>"><?php echo e($comm); ?></a><?php else: ?> - <?php endif; ?></td>
                            <td class="small"><?php echo e($att->prinect_job_name ?? '-'); ?></td>
                            <td class="small"><?php echo e($att->workstep_name ?? '-'); ?></td>
                            <td class="text-center"><?php if($att->good_cycles > 0): ?><span class="text-success fw-bold"><?php echo e(number_format($att->good_cycles)); ?></span><?php else: ?> - <?php endif; ?></td>
                            <td class="text-center"><?php if($att->waste_cycles > 0): ?><span class="text-danger"><?php echo e(number_format($att->waste_cycles)); ?></span><?php else: ?> - <?php endif; ?></td>
                            <td class="text-center"><span class="badge bg-secondary"><?php echo e($att->n_attivita); ?></span></td>
                            <td class="small"><?php echo e($att->operatore_prinect ?? '-'); ?></td>
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
// === HELPERS ===
function fmt(n){ return n.toLocaleString('it-IT'); }
function fmtMin(sec){ const h=Math.floor(sec/3600); const m=Math.floor((sec%3600)/60); return h>0 ? h+'h '+m+'m' : m+'m'; }

// === 1. PRODUZIONE 7GG (Bar chart) ===
const prod7gg = <?php echo json_encode($prodPerGiorno, 15, 512) ?>;
const giorni = Object.keys(prod7gg);
const giorniLabel = giorni.map(g => { const d=new Date(g); return d.getDate()+'/'+(d.getMonth()+1); });

new Chart(document.getElementById('chartProd7gg'), {
    type: 'bar',
    data: {
        labels: giorniLabel,
        datasets: [
            { label:'Fogli buoni', data: giorni.map(g => prod7gg[g].buoni), backgroundColor:'#198754', borderRadius:4 },
            { label:'Scarto', data: giorni.map(g => prod7gg[g].scarto), backgroundColor:'#dc3545', borderRadius:4 }
        ]
    },
    options: {
        responsive:true, maintainAspectRatio:false,
        scales:{ x:{ stacked:true }, y:{ stacked:true, beginAtZero:true } },
        plugins:{ legend:{ position:'bottom' } }
    }
});

// === 2. TEMPO OGGI (Doughnut) ===
const avvSec = <?php echo e($secAvvOggi); ?>;
const prodSec = <?php echo e($secProdOggi); ?>;
new Chart(document.getElementById('chartTempoOggi'), {
    type: 'doughnut',
    data: {
        labels: ['Avviamento ('+fmtMin(avvSec)+')', 'Produzione ('+fmtMin(prodSec)+')'],
        datasets: [{ data:[Math.round(avvSec/60), Math.round(prodSec/60)], backgroundColor:['#ffc107','#0d6efd'] }]
    },
    options: { responsive:true, plugins:{ legend:{ position:'bottom' } } }
});

// === 3. OPERATORI (Horizontal bar) ===
const opData = <?php echo json_encode($perOperatore, 15, 512) ?>;
const opNomi = Object.keys(opData);
new Chart(document.getElementById('chartOperatori'), {
    type: 'bar',
    data: {
        labels: opNomi,
        datasets: [
            { label:'Buoni', data: opNomi.map(n => opData[n].buoni), backgroundColor:'#198754', borderRadius:4 },
            { label:'Scarto', data: opNomi.map(n => opData[n].scarto), backgroundColor:'#dc3545', borderRadius:4 }
        ]
    },
    options: {
        responsive:true, maintainAspectRatio:false,
        indexAxis:'y',
        scales:{ x:{ stacked:true, beginAtZero:true }, y:{ stacked:true } },
        plugins:{ legend:{ position:'bottom' } }
    }
});

// === 4. % SCARTO PER GIORNO (Line) ===
const scartoPerc = giorni.map(g => {
    const tot = prod7gg[g].buoni + prod7gg[g].scarto;
    return tot > 0 ? Math.round((prod7gg[g].scarto / tot) * 1000) / 10 : 0;
});
new Chart(document.getElementById('chartScartoGiorno'), {
    type: 'line',
    data: {
        labels: giorniLabel,
        datasets: [{
            label:'% Scarto', data: scartoPerc,
            borderColor:'#dc3545', backgroundColor:'rgba(220,53,69,0.1)',
            fill:true, tension:0.3, pointRadius:5, pointBackgroundColor:'#dc3545'
        }]
    },
    options: {
        responsive:true, maintainAspectRatio:false,
        scales:{ y:{ beginAtZero:true, title:{ display:true, text:'%' } } },
        plugins:{ legend:{ display:false } }
    }
});

// === 5. TIMELINE OGGI ===
const timeline = <?php echo json_encode($timelineOggi, 15, 512) ?>;
if (timeline.length > 0) {
    const tlLabels = timeline.map(t => {
        const d = new Date(t.start);
        return d.getHours()+':'+String(d.getMinutes()).padStart(2,'0');
    });
    new Chart(document.getElementById('chartTimeline'), {
        type: 'bar',
        data: {
            labels: tlLabels,
            datasets: [{
                label:'Durata (min)', data: timeline.map(t => t.durata_min),
                backgroundColor: timeline.map(t => t.tipo === 'Avviamento' ? '#ffc107' : '#198754'),
                borderWidth:0, borderRadius:2
            }]
        },
        options: {
            responsive:true, maintainAspectRatio:false,
            scales:{
                x:{ ticks:{ maxRotation:90, font:{size:9} } },
                y:{ beginAtZero:true, title:{ display:true, text:'Minuti' } }
            },
            plugins:{
                legend:{ display:false },
                tooltip:{ callbacks:{ afterLabel: function(ctx){
                    const t = timeline[ctx.dataIndex];
                    return t.tipo+'\n'+t.workstep+'\nBuoni: '+t.buoni+' | Scarto: '+t.scarto+'\n'+t.operatore;
                }}}
            }
        }
    });
}

// === LIVE UPDATE (ogni 10 secondi) ===
setInterval(() => {
    fetch('<?php echo e(route("mes.prinect.apiStatus")); ?>')
    .then(r => r.json())
    .then(d => {
        if (d.error) return;
        // Status badge
        const statusEl = document.getElementById('liveStatus');
        statusEl.textContent = d.status;
        statusEl.className = 'status-badge status-' + d.status;

        // Dot color
        const dot = document.getElementById('liveDot');
        dot.className = 'live-dot ' + (d.status === 'Running' ? 'green' : d.status === 'Idle' ? 'yellow' : 'red');

        // Values
        document.getElementById('liveSpeed').textContent = fmt(d.speed);
        document.getElementById('liveJob').textContent = d.job_name;
        document.getElementById('liveWorkstep').textContent = d.workstep + ' (' + d.ws_status + ')';
        document.getElementById('liveProduced').textContent = fmt(d.produced);
        document.getElementById('liveWaste').textContent = fmt(d.waste);
        document.getElementById('liveTotalizer').textContent = fmt(d.totalizer);
        document.getElementById('liveOperatori').innerHTML = d.operatori.replace(/, /g, '<br>');

        // Aggiorna "in corso" nei KPI oggi
        const inCorsoEls = document.querySelectorAll('[data-live-produced]');
        inCorsoEls.forEach(el => el.textContent = '+' + fmt(d.produced) + ' in corso');
        const inCorsoWaste = document.querySelectorAll('[data-live-waste]');
        inCorsoWaste.forEach(el => el.textContent = '+' + fmt(d.waste) + ' in corso');
    })
    .catch(() => {
        document.getElementById('liveDot').className = 'live-dot red';
    });
}, 10000);
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Giovanni\graficanappa-gestionale\resources\views\mes\prinect_dashboard.blade.php ENDPATH**/ ?>