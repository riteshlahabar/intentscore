
<?php
    $logo = $settings['company_logo'] ?? null;
    $mode = $settings['header_display'] ?? 'both';

    // "Logo only" with nothing uploaded would leave an empty header, so the name
    // always stands in. That also covers the template preview, which passes a
    // settings array with no logo at all.
    $showLogo = $mode !== 'name' && ! empty($logo);
    $showName = $mode !== 'logo' || ! $showLogo;
?>
<?php if($style === 'topnav'): ?>
    <?php if($showLogo): ?><img src="<?php echo e(asset($logo)); ?>" alt="<?php echo e($company); ?>" class="brand-logo"><?php endif; ?>
    <?php if($showName): ?><span class="h4 text-primary fw-bold mb-0"><?php echo e($company); ?></span><?php endif; ?>
<?php else: ?>
    <?php if($showLogo): ?>
        <img src="<?php echo e(asset($logo)); ?>" alt="<?php echo e($company); ?>" class="brand-logo">
    <?php elseif($showName): ?>
        <span class="client-logo-mark"><?php echo e(strtoupper(substr($company, 0, 1))); ?></span>
    <?php endif; ?>
    <?php if($showName): ?><?php echo e($company); ?><?php endif; ?>
<?php endif; ?>
<?php /**PATH C:\All Project\IntentScore Internal Project\intentscore\resources\views/frontend/smart-page/partials/brand.blade.php ENDPATH**/ ?>