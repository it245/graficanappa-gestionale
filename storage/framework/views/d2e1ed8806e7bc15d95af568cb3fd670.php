<?php $__env->startSection('content'); ?>
<div class="container mt-3" style="max-width:600px;">
    <h2><?php echo e($operatore ? 'Modifica Operatore' : 'Nuovo Operatore'); ?></h2>

    <?php if($errors->any()): ?>
        <div class="alert alert-danger mt-2">
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div><?php echo e($error); ?></div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo e($operatore ? route('admin.operatore.aggiorna', $operatore->id) : route('admin.operatore.salva')); ?>" class="mt-3">
        <?php echo csrf_field(); ?>

        <div class="mb-3">
            <label class="form-label">Nome</label>
            <input type="text" name="nome" class="form-control" value="<?php echo e(old('nome', $operatore->nome ?? '')); ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Cognome</label>
            <input type="text" name="cognome" class="form-control" value="<?php echo e(old('cognome', $operatore->cognome ?? '')); ?>" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Ruolo</label>
            <select name="ruolo" class="form-select" required>
                <option value="operatore" <?php echo e(old('ruolo', $operatore->ruolo ?? '') === 'operatore' ? 'selected' : ''); ?>>Operatore</option>
                <option value="owner" <?php echo e(old('ruolo', $operatore->ruolo ?? '') === 'owner' ? 'selected' : ''); ?>>Owner</option>
                <option value="admin" <?php echo e(old('ruolo', $operatore->ruolo ?? '') === 'admin' ? 'selected' : ''); ?>>Admin</option>
            </select>
        </div>

        <?php if($operatore): ?>
            <div class="mb-3">
                <label class="form-label">Codice operatore</label>
                <input type="text" name="codice_operatore" class="form-control" value="<?php echo e(old('codice_operatore', $operatore->codice_operatore)); ?>" required>
            </div>
        <?php endif; ?>

        <div class="mb-3">
            <label class="form-label">Reparto Principale</label>
            <select name="reparto_principale" class="form-select" required>
                <?php $__currentLoopData = $reparti; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $rep): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($id); ?>" <?php echo e(old('reparto_principale', $operatore?->reparti->first()?->id) == $id ? 'selected' : ''); ?>><?php echo e($rep); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Reparto Secondario (facoltativo)</label>
            <select name="reparto_secondario" class="form-select">
                <option value="">-- Nessuno --</option>
                <?php $__currentLoopData = $reparti; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $rep): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($id); ?>" <?php echo e(old('reparto_secondario', $operatore?->reparti->skip(1)->first()?->id) == $id ? 'selected' : ''); ?>><?php echo e($rep); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Password <?php echo e($operatore ? '(lascia vuoto per non modificare)' : '(facoltativa)'); ?></label>
            <input type="password" name="password" class="form-control" autocomplete="new-password">
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success"><?php echo e($operatore ? 'Salva modifiche' : 'Crea operatore'); ?></button>
            <a href="<?php echo e(route('admin.dashboard')); ?>" class="btn btn-secondary">Annulla</a>
        </div>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\Giovanni\graficanappa-gestionale\resources\views\admin\operatore_form.blade.php ENDPATH**/ ?>