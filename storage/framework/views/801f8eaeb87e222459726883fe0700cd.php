<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Report Operatori - Reparti - Fasi</title>
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 11px; }
            .operatore-card { page-break-inside: avoid; }
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            margin: 20px 30px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #333;
            padding-bottom: 15px;
        }
        .header h1 { margin: 0; font-size: 22px; }
        .header p { margin: 5px 0 0; color: #666; font-size: 13px; }
        .btn-print {
            background: #0d6efd; color: #fff; border: none; padding: 10px 25px;
            border-radius: 5px; font-size: 14px; cursor: pointer; margin-bottom: 20px;
        }
        .btn-print:hover { background: #0b5ed7; }
        .btn-back {
            background: #6c757d; color: #fff; border: none; padding: 10px 25px;
            border-radius: 5px; font-size: 14px; cursor: pointer; margin-bottom: 20px;
            text-decoration: none; display: inline-block;
        }
        .operatore-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        .operatore-header {
            background: #343a40;
            color: #fff;
            padding: 10px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .operatore-header .nome { font-size: 16px; font-weight: bold; }
        .operatore-header .codice {
            background: rgba(255,255,255,0.2);
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 13px;
        }
        .reparto-section {
            border-top: 1px solid #dee2e6;
            padding: 10px 15px;
        }
        .reparto-nome {
            font-weight: bold;
            font-size: 14px;
            color: #0d6efd;
            margin-bottom: 6px;
            text-transform: uppercase;
        }
        .fasi-list {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        .fase-badge {
            background: #e9ecef;
            border: 1px solid #ced4da;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 12px;
        }
        .no-fasi { color: #999; font-style: italic; font-size: 12px; }
        .riepilogo {
            margin-top: 30px;
            border-top: 3px solid #333;
            padding-top: 15px;
        }
        .riepilogo h2 { font-size: 18px; margin-bottom: 15px; }
        table.riepilogo-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        table.riepilogo-table th, table.riepilogo-table td {
            border: 1px solid #dee2e6;
            padding: 6px 10px;
            text-align: left;
        }
        table.riepilogo-table th {
            background: #343a40;
            color: #fff;
        }
        table.riepilogo-table tr:nth-child(even) { background: #f8f9fa; }
    </style>
</head>
<body>

<div class="no-print" style="margin-bottom:15px;">
    <a href="<?php echo e(route('admin.dashboard')); ?>" class="btn-back">&larr; Dashboard Admin</a>
    <button class="btn-print" onclick="window.print()">Stampa / Salva PDF</button>
</div>

<div class="header">
    <h1>Grafica Nappa - Report Operatori</h1>
    <p>Operatori attivi con reparti e fasi associate &mdash; <?php echo e(now()->format('d/m/Y H:i')); ?></p>
</div>

<?php $__currentLoopData = $operatori; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $op): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
<div class="operatore-card">
    <div class="operatore-header">
        <span class="nome"><?php echo e($op->cognome); ?> <?php echo e($op->nome); ?></span>
        <span class="codice"><?php echo e($op->codice_operatore); ?></span>
    </div>
    <?php if($op->reparti->isEmpty()): ?>
        <div class="reparto-section">
            <span class="no-fasi">Nessun reparto assegnato</span>
        </div>
    <?php else: ?>
        <?php $__currentLoopData = $op->reparti->sortBy('nome'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $reparto): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="reparto-section">
            <div class="reparto-nome"><?php echo e($reparto->nome); ?></div>
            <?php
                $fasi = $fasiPerReparto->get($reparto->id, collect());
            ?>
            <?php if($fasi->isEmpty()): ?>
                <span class="no-fasi">Nessuna fase in catalogo</span>
            <?php else: ?>
                <ul class="fasi-list">
                    <?php $__currentLoopData = $fasi; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fase): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li class="fase-badge"><?php echo e($fase->nome); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php endif; ?>
</div>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

<div class="riepilogo">
    <h2>Riepilogo per Reparto</h2>
    <table class="riepilogo-table">
        <thead>
            <tr>
                <th>Reparto</th>
                <th>Operatori</th>
                <th>Fasi</th>
            </tr>
        </thead>
        <tbody>
            <?php
                $repartiTutti = \App\Models\Reparto::orderBy('nome')->get();
            ?>
            <?php $__currentLoopData = $repartiTutti; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rep): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><strong><?php echo e(strtoupper($rep->nome)); ?></strong></td>
                <td>
                    <?php
                        $opsReparto = $operatori->filter(fn($o) => $o->reparti->contains('id', $rep->id));
                    ?>
                    <?php if($opsReparto->isEmpty()): ?>
                        <em style="color:#999;">-</em>
                    <?php else: ?>
                        <?php echo e($opsReparto->map(fn($o) => $o->cognome . ' ' . $o->nome)->implode(', ')); ?>

                    <?php endif; ?>
                </td>
                <td>
                    <?php $fasiRep = $fasiPerReparto->get($rep->id, collect()); ?>
                    <?php if($fasiRep->isEmpty()): ?>
                        <em style="color:#999;">-</em>
                    <?php else: ?>
                        <?php echo e($fasiRep->pluck('nome')->implode(', ')); ?>

                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
</div>

<div style="margin-top:20px; text-align:center; color:#999; font-size:11px;">
    Generato il <?php echo e(now()->format('d/m/Y')); ?> alle <?php echo e(now()->format('H:i')); ?> &mdash; Grafica Nappa Gestionale
</div>

</body>
</html>
<?php /**PATH C:\Users\Giovanni\graficanappa-gestionale\resources\views\admin\report_operatori.blade.php ENDPATH**/ ?>