<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Test Tracking BRT</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background: #f5f5f5; padding: 30px; }
        .card { max-width: 900px; margin: 0 auto; }
        .evento-row td { font-size: 14px; }
        .badge-stato { font-size: 16px; }
    </style>
</head>
<body>

<div class="card shadow">
    <div class="card-header text-white" style="background:#d4380d;">
        <h4 class="mb-0">Test Tracking BRT</h4>
    </div>
    <div class="card-body">
        <form method="GET" action="<?php echo e(route('spedizione.trackingTest')); ?>" class="row g-3 mb-4">
            <div class="col-md-8">
                <input type="text" name="segnacollo" class="form-control form-control-lg"
                       placeholder="Inserisci segnacollo BRT (es. 067138050411341)"
                       value="<?php echo e($segnacollo ?? ''); ?>" autofocus required>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-lg w-100 text-white" style="background:#d4380d;">Cerca</button>
            </div>
        </form>

        <?php if($data): ?>
            <?php
                $resp = $data['ttParcelIdResponse'] ?? null;
                $errCode = $resp['executionMessage']['code'] ?? -1;
            ?>

            <?php if(!$resp || $errCode !== 0 || isset($data['error'])): ?>
                <div class="alert alert-danger">
                    <strong>Errore:</strong>
                    <?php echo e($data['message'] ?? ($resp['executionMessage']['codeDesc'] ?? 'Risposta non valida')); ?>

                    (code: <?php echo e($errCode); ?>)
                </div>
            <?php else: ?>
                <?php
                    $bolla = $resp['bolla'];
                    $sped = $bolla['dati_spedizione'];
                    $cons = $bolla['dati_consegna'];
                    $rif = $bolla['riferimenti'];
                    $mitt = $bolla['mittente'];
                    $dest = $bolla['destinatario'];
                    $merce = $bolla['merce'];
                    $eventi = collect($resp['lista_eventi'])->filter(fn($e) => !empty($e['evento']['descrizione']))->values();
                    $ultimoEvento = $eventi->first()['evento']['descrizione'] ?? '-';
                ?>

                
                <div class="text-center mb-4">
                    <?php if(str_contains($ultimoEvento, 'CONSEGNATA')): ?>
                        <span class="badge bg-success badge-stato"><?php echo e($ultimoEvento); ?></span>
                    <?php elseif(str_contains($ultimoEvento, 'CONSEGNA') || str_contains($ultimoEvento, 'PARTITA')): ?>
                        <span class="badge bg-warning text-dark badge-stato"><?php echo e($ultimoEvento); ?></span>
                    <?php else: ?>
                        <span class="badge bg-info badge-stato"><?php echo e($ultimoEvento); ?></span>
                    <?php endif; ?>
                </div>

                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr><th>N. Spedizione</th><td><?php echo e($sped['spedizione_id']); ?></td></tr>
                            <tr><th>Data spedizione</th><td><?php echo e($sped['spedizione_data']); ?></td></tr>
                            <tr><th>Servizio</th><td><?php echo e($sped['servizio']); ?> (<?php echo e($sped['porto']); ?>)</td></tr>
                            <tr><th>Filiale arrivo</th><td><?php echo e($sped['filiale_arrivo']); ?></td></tr>
                            <tr><th>Rif. mittente</th><td><?php echo e($rif['riferimento_mittente_numerico']); ?> / <?php echo e($rif['riferimento_mittente_alfabetico']); ?></td></tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr><th>Mittente</th><td><?php echo e($mitt['ragione_sociale'] ?: $mitt['localita']); ?> (<?php echo e($mitt['sigla_area']); ?>)</td></tr>
                            <tr><th>Destinatario</th><td><?php echo e($dest['ragione_sociale'] ?: $dest['localita']); ?> (<?php echo e($dest['sigla_provincia']); ?>)</td></tr>
                            <tr><th>Colli</th><td><?php echo e($merce['colli']); ?></td></tr>
                            <tr><th>Peso (kg)</th><td><?php echo e($merce['peso_kg']); ?></td></tr>
                            <tr><th>Data consegna</th><td><?php echo e($cons['data_consegna_merce'] ?: '-'); ?> <?php echo e($cons['ora_consegna_merce']); ?></td></tr>
                        </table>
                    </div>
                </div>

                
                <h5>Eventi (<?php echo e($eventi->count()); ?>)</h5>
                <table class="table table-bordered table-sm table-striped">
                    <thead class="table-dark">
                        <tr><th>Data</th><th>Ora</th><th>Descrizione</th><th>Filiale</th></tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $eventi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr class="evento-row">
                                <td><?php echo e($item['evento']['data']); ?></td>
                                <td><?php echo e($item['evento']['ora']); ?></td>
                                <td><strong><?php echo e($item['evento']['descrizione']); ?></strong></td>
                                <td><?php echo e($item['evento']['filiale']); ?></td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php elseif($segnacollo): ?>
            <div class="alert alert-warning">Nessuna risposta ricevuta.</div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
<?php /**PATH C:\Users\Giovanni\graficanappa-gestionale\resources\views\spedizione\tracking-test.blade.php ENDPATH**/ ?>