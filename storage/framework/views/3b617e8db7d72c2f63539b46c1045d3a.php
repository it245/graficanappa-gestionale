<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'value' => '0',
    'label' => '',
    'color' => 'accent',
    'subtitle' => null,
    'href' => null,
    'id' => null,
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
    'value' => '0',
    'label' => '',
    'color' => 'accent',
    'subtitle' => null,
    'href' => null,
    'id' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<div class="kpi-card"
     <?php if($id): ?> id="<?php echo e($id); ?>" <?php endif; ?>
     <?php if($href): ?> onclick="window.location='<?php echo e($href); ?>'" style="cursor:pointer" <?php else: ?> style="cursor:default" <?php endif; ?>
     <?php echo e($attributes); ?>>
    <div class="kpi-border" style="background:var(--<?php echo e($color); ?>)"></div>
    <div class="kpi-body">
        <span class="kpi-label"><?php echo e($label); ?></span>
        <span class="kpi-value"><?php echo e($value); ?></span>
        <?php if($subtitle): ?>
            <span class="kpi-subtitle"><?php echo e($subtitle); ?></span>
        <?php endif; ?>
    </div>
</div>
<?php /**PATH C:\Users\Giovanni\graficanappa-gestionale\resources\views/components/mes/kpi-card.blade.php ENDPATH**/ ?>