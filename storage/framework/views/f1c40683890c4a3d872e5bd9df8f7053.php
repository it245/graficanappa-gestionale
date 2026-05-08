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
    .kpi-card .kpi-value { font-size: 2rem; font-weight: 700; line-height: 1.1; }
    .kpi-card .kpi-label { font-size: 0.82rem; color: #6c757d; text-transform: uppercase; letter-spacing: 0.5px; }
    .kpi-accent-blue   { border-left: 4px solid #0d6efd; }
    .kpi-accent-green  { border-left: 4px solid #198754; }
    .kpi-accent-red    { border-left: 4px solid #dc3545; }
    .kpi-accent-orange { border-left: 4px solid #fd7e14; }
    .kpi-accent-purple { border-left: 4px solid #6f42c1; }
    .kpi-accent-teal   { border-left: 4px solid #20c997; }
    .kpi-accent-cyan   { border-left: 4px solid #0dcaf0; }
    .section-title { font-size: 1.05rem; font-weight: 600; margin-bottom: 0.75rem; }
    .table-cruscotto { font-size: 13px; }
    .table-cruscotto th { white-space: nowrap; }
    .header-line { border-bottom: 3px solid #d11317; margin-bottom: 1.5rem; padding-bottom: 0.75rem; }

    @media print {
        .btn, .no-print { display: none !important; }
        .kpi-card { box-shadow: none !important; border: 1px solid #dee2e6; }
        .card { break-inside: avoid; }
        canvas { max-height: 250px !important; }
    }
</style>

<div class="container-fluid mt-3 px-4">
    
    <div class="d-flex justify-content-between align-items-center header-line">
        <div>
            <h3 class="mb-0">Cruscotto Direzionale</h3>
            <small class="text-muted">Aggiornato: <?php echo e(now()->format('d/m/Y H:i')); ?></small>
        </div>
        <div class="no-print">
            <a href="<?php echo e(route('admin.dashboard')); ?>" class="btn btn-outline-secondary btn-sm me-1">Dashboard Admin</a>
            <button onclick="window.print()" class="btn btn-outline-dark btn-sm">Stampa</button>
        </div>
    </div>

    
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card kpi-card kpi-accent-blue">
                <div class="card-body">
                    <div class="kpi-value text-primary"><?php echo e($ordiniAttivi); ?></div>
                    <div class="kpi-label">Ordini Attivi</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card kpi-card kpi-accent-green">
                <div class="card-body">
                    <div class="kpi-value text-success"><?php echo e($commesseCompletate30gg); ?></div>
                    <div class="kpi-label">Completate (30gg)</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card kpi-card kpi-accent-red">
                <div class="card-body">
                    <div class="kpi-value text-danger"><?php echo e($commesseInRitardo->count()); ?></div>
                    <div class="kpi-label">In Ritardo</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card kpi-card kpi-accent-orange">
                <div class="card-body">
                    <div class="kpi-value" style="color:#fd7e14"><?php echo e($tassoPuntualita); ?>%</div>
                    <div class="kpi-label">Tasso Puntualita</div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4">
            <div class="card kpi-card kpi-accent-purple">
                <div class="card-body">
                    <div class="kpi-value" style="color:#6f42c1"><?php echo e($oreLavorateMese); ?>h</div>
                    <div class="kpi-label">Ore Lavorate (30gg)</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card kpi-card kpi-accent-teal">
                <div class="card-body">
                    <div class="kpi-value" style="color:#20c997"><?php echo e($fasiCompletate30gg); ?></div>
                    <div class="kpi-label">Fasi Completate (30gg)</div>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card kpi-card kpi-accent-cyan">
                <div class="card-body">
                    <div class="d-flex align-items-baseline gap-2">
                        <div class="kpi-value" style="color:#0dcaf0"><?php echo e(number_format($prinectStats->fogli_buoni)); ?></div>
                        <small class="text-muted">buoni</small>
                    </div>
                    <div class="kpi-label">Prinect Offset (7gg) &mdash; Scarto: <?php echo e($prinectStats->scarto); ?> (<?php echo e($prinectStats->percentuale_scarto); ?>%)</div>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-8">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Trend Produzione &mdash; Ultimi 30 giorni</div>
                    <canvas id="chartTrend" height="110"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Top Clienti Attivi</div>
                    <canvas id="chartClienti" height="180"></canvas>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Carico Reparti</div>
                    <canvas id="chartReparti" height="140"></canvas>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Motivi Pausa &mdash; Ultimi 30 giorni</div>
                    <canvas id="chartPause" height="140"></canvas>
                </div>
            </div>
        </div>
    </div>

    
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-7">
            <div class="card">
                <div class="card-body">
                    <div class="section-title">Scadenze Prossimi 7 Giorni</div>
                    <?php if($scadenzeImminenti->isEmpty()): ?>
                        <p class="text-muted mb-0">Nessuna scadenza imminente.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-cruscotto mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Commessa</th>
                                    <th>Cliente</th>
                                    <th>Avanzamento</th>
                                    <th class="text-end">Giorni</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $scadenzeImminenti; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><strong><?php echo e($s->commessa); ?></strong></td>
                                    <td><?php echo e($s->cliente_nome ?? '-'); ?></td>
                                    <td style="min-width:120px">
                                        <div class="progress" style="height:18px">
                                            <div class="progress-bar <?php echo e($s->avanzamento >= 80 ? 'bg-success' : ($s->avanzamento >= 50 ? 'bg-warning' : 'bg-danger')); ?>"
                                                 style="width:<?php echo e($s->avanzamento); ?>%"><?php echo e($s->avanzamento); ?>%</div>
                                        </div>
                                    </td>
                                    <td class="text-end">
                                        <span class="badge <?php echo e($s->giorni_mancanti <= 1 ? 'bg-danger' : ($s->giorni_mancanti <= 3 ? 'bg-warning text-dark' : 'bg-info')); ?>">
                                            <?php echo e($s->giorni_mancanti); ?>gg
                                        </span>
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
                    <div class="section-title">Top 5 Operatori (30gg)</div>
                    <?php if($topOperatoriMese->isEmpty()): ?>
                        <p class="text-muted mb-0">Nessun dato disponibile.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-cruscotto mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Operatore</th>
                                    <th class="text-end">Fasi</th>
                                    <th class="text-end">Qta Prodotta</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $topOperatoriMese; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $op): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><?php echo e($i + 1); ?></td>
                                    <td><?php echo e($op->nome); ?> <?php echo e($op->cognome); ?></td>
                                    <td class="text-end"><strong><?php echo e($op->fasi_completate); ?></strong></td>
                                    <td class="text-end"><?php echo e(number_format($op->qta_prodotta ?? 0)); ?></td>
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

    
    <?php if($commesseInRitardo->count() > 0): ?>
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card border-danger">
                <div class="card-body">
                    <div class="section-title text-danger">Commesse in Ritardo (<?php echo e($commesseInRitardo->count()); ?>)</div>
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-cruscotto mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Commessa</th>
                                    <th>Cliente</th>
                                    <th>Data Consegna</th>
                                    <th class="text-end">Ritardo</th>
                                    <th>Avanzamento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $commesseInRitardo; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr>
                                    <td><strong><?php echo e($r->commessa); ?></strong></td>
                                    <td><?php echo e($r->cliente_nome ?? '-'); ?></td>
                                    <td><?php echo e($r->data_prevista_consegna ? \Carbon\Carbon::parse($r->data_prevista_consegna)->format('d/m/Y') : '-'); ?></td>
                                    <td class="text-end"><span class="badge bg-danger"><?php echo e($r->giorni_ritardo); ?>gg</span></td>
                                    <td style="min-width:120px">
                                        <div class="progress" style="height:18px">
                                            <div class="progress-bar bg-danger" style="width:<?php echo e($r->avanzamento); ?>%"><?php echo e($r->avanzamento); ?>%</div>
                                        </div>
                                    </td>
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

    // --- Trend Produzione (linea + barre) ---
    const trendLabels = <?php echo json_encode(array_keys($trendGiornaliero), 15, 512) ?>;
    const trendFasi = <?php echo json_encode(array_values($trendGiornaliero), 15, 512) ?>;
    const trendOre = <?php echo json_encode(array_values($oreTrendGiornaliero), 15, 512) ?>;

    new Chart(document.getElementById('chartTrend'), {
        data: {
            labels: trendLabels.map(d => {
                const parts = d.split('-');
                return parts[2] + '/' + parts[1];
            }),
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

    // --- Top Clienti (ciambella) ---
    const clientiLabels = <?php echo json_encode($topClienti->pluck('cliente_nome'), 15, 512) ?>;
    const clientiData = <?php echo json_encode($topClienti->pluck('totale'), 15, 512) ?>;
    const colori = ['#0d6efd','#198754','#dc3545','#fd7e14','#6f42c1','#20c997','#0dcaf0','#ffc107'];

    new Chart(document.getElementById('chartClienti'), {
        type: 'doughnut',
        data: {
            labels: clientiLabels,
            datasets: [{
                data: clientiData,
                backgroundColor: colori.slice(0, clientiLabels.length),
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            cutout: '55%',
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
            }
        }
    });

    // --- Carico Reparti (barre orizzontali impilate) ---
    const repartiLabels = <?php echo json_encode($caricoReparti->pluck('nome'), 15, 512) ?>;
    const repartiAttesa = <?php echo json_encode($caricoReparti->pluck('attesa'), 15, 512) ?>;
    const repartiCorso = <?php echo json_encode($caricoReparti->pluck('in_corso'), 15, 512) ?>;
    const repartiDone = <?php echo json_encode($caricoReparti->pluck('completate'), 15, 512) ?>;

    new Chart(document.getElementById('chartReparti'), {
        type: 'bar',
        data: {
            labels: repartiLabels,
            datasets: [
                { label: 'In attesa',    data: repartiAttesa, backgroundColor: '#ffc107' },
                { label: 'In corso',     data: repartiCorso,  backgroundColor: '#0d6efd' },
                { label: 'Completate',   data: repartiDone,   backgroundColor: '#198754' }
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

    // --- Motivi Pausa (barre orizzontali) ---
    const pauseLabels = <?php echo json_encode($motiviPausa->pluck('motivo'), 15, 512) ?>;
    const pauseData = <?php echo json_encode($motiviPausa->pluck('totale'), 15, 512) ?>;

    new Chart(document.getElementById('chartPause'), {
        type: 'bar',
        data: {
            labels: pauseLabels,
            datasets: [{
                label: 'Occorrenze',
                data: pauseData,
                backgroundColor: '#dc3545',
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

});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Giovanni\graficanappa-gestionale\resources\views\admin\cruscotto.blade.php ENDPATH**/ ?>