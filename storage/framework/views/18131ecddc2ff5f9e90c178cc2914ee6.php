<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'percentuale' => 0,
    'avviate' => 0,
    'totale' => 0,
    'terminate' => 0,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'percentuale' => 0,
    'avviate' => 0,
    'totale' => 0,
    'terminate' => 0,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
$pct = (int) $percentuale;
$avv = (int) $avviate;
?>

<div class="mes-progress" title="<?php echo e($terminate); ?>/<?php echo e($totale); ?> terminate" <?php echo e($attributes); ?>>
    <div class="mes-progress-track">
        <?php if($pct > 0): ?>
            <div class="mes-progress-fill mes-progress-done" style="width:<?php echo e($pct); ?>%"></div>
        <?php endif; ?>
        <?php if($avv > 0): ?>
            <div class="mes-progress-fill mes-progress-active" style="width:<?php echo e($pct + $avv); ?>%"></div>
        <?php endif; ?>
    </div>
    <span class="mes-progress-text"><?php echo e($pct); ?>%</span>
</div>
<?php /**PATH C:\Users\Giovanni\graficanappa-gestionale\resources\views/components/mes/progress-bar.blade.php ENDPATH**/ ?>