<?php
    $company = $settings['company_name'] ?? 'Smart Links';
    $ordered = $sections->sortBy('display_order');
    $heading = $page->heading ?: $prospect->business_name;
    $ctaSection = $ordered->firstWhere('section_type', 'cta');
    $ctaEvent = match ($page->cta_type) {
        'whatsapp' => 'whatsapp_clicked',
        'email' => 'email_clicked',
        'calendar' => 'calendar_clicked',
        default => 'contact_clicked',
    };
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
<meta name="robots" content="noindex,nofollow">
<title><?php echo e($heading); ?></title>
<link rel="icon" href="<?php echo e(asset('images/favicon.svg')); ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?php echo e(asset('admin-assets/css/remixicon.css')); ?>">
<link rel="stylesheet" href="<?php echo e(asset('css/portal-public.css')); ?>">
</head>
<body class="client-page" id="smart-page" data-track-url="<?php echo e(route('smart.track', $page->smartLink->slug)); ?>">

<nav class="client-nav">
    <div class="client-nav-inner">
        <a class="client-logo" href="#top"><img src="<?php echo e(asset('images/logo.svg')); ?>" alt="" style="height:30px"><?php echo e($company); ?></a>
        <div class="client-nav-links">
            <?php $__currentLoopData = $ordered; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if($s->section_type !== 'cta'): ?>
                    <a href="#sec-<?php echo e($s->section_type); ?>"><?php echo e($s->title ?: \App\Models\SmartLink\SmartPageTemplate::sectionLabel($s->section_type)); ?></a>
                <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php if($ctaSection): ?>
            <a class="client-nav-cta btn-client" href="<?php echo e($page->cta_url ?: '#sec-cta'); ?>" data-track="cta_clicked" data-section="cta" data-label="Header CTA"><?php echo e($page->cta_text); ?></a>
        <?php endif; ?>
    </div>
</nav>

<header class="hero" id="top">
    <div class="container-client">
        <div class="section-head" style="max-width:820px;margin-bottom:0">
            <div class="eyebrow">Prepared for you</div>
            <h1><?php echo e($heading); ?></h1>
            <p><?php echo e($page->subheading ?: 'A few ideas we put together specifically for your business.'); ?></p>
        </div>
    </div>
</header>

<?php $__currentLoopData = $ordered; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $section): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php ($type = $section->section_type); ?>
    <?php ($title = $section->title ?: \App\Models\SmartLink\SmartPageTemplate::sectionLabel($type)); ?>
    <?php ($alt = $loop->index % 2 === 1); ?>

    <?php if($type === 'cta'): ?>
        <?php continue; ?>
    <?php endif; ?>

    <section class="client-section <?php echo e($alt ? 'alt' : ''); ?>" id="sec-<?php echo e($type); ?>" data-section="<?php echo e($type); ?>">
        <div class="container-client">

            <?php if($type === 'intro'): ?>
                <div class="section-head"><h2><?php echo e($title); ?></h2></div>
                <div class="content-box"><?php echo e($page->personalized_message ?: $section->content); ?></div>

            <?php elseif(in_array($type, ['website_audit','instagram_audit'])): ?>
                <div class="section-head"><div class="eyebrow">Audit</div><h2><?php echo e($title); ?></h2></div>
                <div class="grid-3">
                    <?php $__currentLoopData = ['observation'=>['What we see','ri-search-eye-line'],'problem'=>['The problem','ri-error-warning-line'],'recommendation'=>['What we recommend','ri-lightbulb-flash-line']]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$meta): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if($section->field($key)): ?>
                            <div class="feature-card">
                                <div class="feature-icon"><i class="<?php echo e($meta[1]); ?>"></i></div>
                                <h3><?php echo e($meta[0]); ?></h3>
                                <p><?php echo e($section->field($key)); ?></p>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <?php if($section->content): ?><div class="custom-copy mt-3"><?php echo e($section->content); ?></div><?php endif; ?>

            <?php elseif($type === 'google_audit'): ?>
                <div class="section-head"><div class="eyebrow">Audit</div><h2><?php echo e($title); ?></h2></div>
                <div class="grid-2">
                    <div class="feature-card">
                        <div class="detail-row" style="border-top:0"><span>Current rating</span><strong><?php echo e($section->field('rating','—')); ?></strong></div>
                        <div class="detail-row"><span>Review count</span><strong><?php echo e($section->field('reviews','—')); ?></strong></div>
                    </div>
                    <div class="feature-card">
                        <?php if($section->field('opportunity')): ?><h3>Opportunity</h3><p><?php echo e($section->field('opportunity')); ?></p><?php endif; ?>
                        <?php if($section->field('recommendation')): ?><h3 class="mt-2">Recommendation</h3><p><?php echo e($section->field('recommendation')); ?></p><?php endif; ?>
                    </div>
                </div>

            <?php elseif($type === 'free_tools'): ?>
                <?php ($tools = $section->data['tools'] ?? ['revenue','no_show','profit']); ?>
                <div class="section-head"><div class="eyebrow">Free tools</div><h2><?php echo e($title); ?></h2><p><?php echo e($section->content ?: 'Run the numbers for your own business. Nothing is submitted anywhere.'); ?></p></div>

                <?php if(in_array('revenue',$tools)): ?>
                <div class="calc" data-tool="revenue">
                    <h3>Grooming Revenue Calculator</h3>
                    <div class="calc-inputs">
                        <label>Number of groomers<input type="number" min="0" step="1" value="2" data-in="groomers"></label>
                        <label>Appointments per day<input type="number" min="0" step="1" value="6" data-in="appts"></label>
                        <label>Average ticket (₹)<input type="number" min="0" step="1" value="1200" data-in="ticket"></label>
                        <label>Working days / month<input type="number" min="0" max="31" step="1" value="26" data-in="days"></label>
                    </div>
                    <button type="button" class="btn-client" data-calc>Calculate</button>
                    <div class="calc-out" hidden>
                        <div class="detail-row"><span>Estimated daily revenue</span><strong data-out="daily"></strong></div>
                        <div class="detail-row"><span>Monthly revenue</span><strong data-out="monthly"></strong></div>
                        <div class="detail-row"><span>Annual revenue</span><strong data-out="annual"></strong></div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if(in_array('no_show',$tools)): ?>
                <div class="calc" data-tool="no_show">
                    <h3>No-Show Calculator</h3>
                    <div class="calc-inputs">
                        <label>Monthly appointments<input type="number" min="0" step="1" value="300" data-in="appts"></label>
                        <label>Average ticket (₹)<input type="number" min="0" step="1" value="1200" data-in="ticket"></label>
                        <label>No-show %<input type="number" min="0" max="100" step="0.1" value="8" data-in="rate"></label>
                    </div>
                    <button type="button" class="btn-client" data-calc>Calculate</button>
                    <div class="calc-out" hidden>
                        <div class="detail-row"><span>Estimated monthly revenue lost</span><strong data-out="monthly"></strong></div>
                        <div class="detail-row"><span>Estimated annual revenue lost</span><strong data-out="annual"></strong></div>
                    </div>
                </div>
                <?php endif; ?>

                <?php if(in_array('profit',$tools)): ?>
                <div class="calc" data-tool="profit">
                    <h3>Grooming Business Profit Calculator</h3>
                    <div class="calc-inputs">
                        <label>Monthly revenue (₹)<input type="number" min="0" step="1" value="200000" data-in="revenue"></label>
                        <label>Payroll (₹)<input type="number" min="0" step="1" value="80000" data-in="payroll"></label>
                        <label>Rent (₹)<input type="number" min="0" step="1" value="30000" data-in="rent"></label>
                        <label>Supplies (₹)<input type="number" min="0" step="1" value="20000" data-in="supplies"></label>
                        <label>Software (₹)<input type="number" min="0" step="1" value="5000" data-in="software"></label>
                        <label>Marketing (₹)<input type="number" min="0" step="1" value="10000" data-in="marketing"></label>
                        <label>Other expenses (₹)<input type="number" min="0" step="1" value="8000" data-in="other"></label>
                    </div>
                    <button type="button" class="btn-client" data-calc>Calculate</button>
                    <div class="calc-out" hidden>
                        <div class="detail-row"><span>Estimated monthly profit</span><strong data-out="monthly"></strong></div>
                        <div class="detail-row"><span>Profit margin</span><strong data-out="margin"></strong></div>
                        <div class="detail-row"><span>Annual profit</span><strong data-out="annual"></strong></div>
                    </div>
                </div>
                <?php endif; ?>

            <?php elseif($type === 'solution'): ?>
                <div class="section-head"><div class="eyebrow">Recommended</div><h2><?php echo e($title); ?></h2></div>
                <div class="grid-2">
                    <div>
                        <?php if($section->field('what')): ?><h3 style="font-size:15px">What we recommend</h3><div class="content-box mb-3"><?php echo e($section->field('what')); ?></div><?php endif; ?>
                        <?php if($section->field('why')): ?><h3 style="font-size:15px">Why</h3><div class="content-box"><?php echo e($section->field('why')); ?></div><?php endif; ?>
                    </div>
                    <?php if($section->field('benefits')): ?>
                    <div>
                        <h3 style="font-size:15px">Key benefits</h3>
                        <ul class="list-clean">
                            <?php $__currentLoopData = preg_split('/\r\n|\r|\n/', $section->field('benefits')); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $benefit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if(trim($benefit)): ?><li><i class="ri-checkbox-circle-fill"></i><span><?php echo e(trim($benefit)); ?></span></li><?php endif; ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>

            <?php elseif($type === 'portfolio'): ?>
                <div class="section-head"><div class="eyebrow">Our work</div><h2><?php echo e($title); ?></h2><?php if($section->content): ?><p><?php echo e($section->content); ?></p><?php endif; ?></div>
                <div class="media-grid">
                    <?php $__empty_1 = true; $__currentLoopData = $section->data['images'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $img): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="media-item">
                            <img src="<?php echo e(asset($img['path'])); ?>" alt="<?php echo e($img['caption'] ?? ''); ?>" loading="lazy">
                            <?php if($img['caption'] ?? false): ?><div class="caption"><?php echo e($img['caption']); ?></div><?php endif; ?>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="custom-copy">Examples will be shared on our call.</div>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <div class="section-head"><h2><?php echo e($title); ?></h2></div>
                <?php if($section->content): ?><div class="content-box"><?php echo e($section->content); ?></div><?php endif; ?>
            <?php endif; ?>

        </div>
    </section>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

<?php if($ctaSection): ?>
<section class="client-section" id="sec-cta" data-section="cta">
    <div class="container-client">
        <div class="contact-panel">
            <div>
                <h2><?php echo e($ctaSection->title ?: 'Ready when you are'); ?></h2>
                <p><?php echo e($ctaSection->content ?: 'Happy to walk through any of this in a short call.'); ?></p>
            </div>
            <div class="contact-actions">
                <a class="btn-client" href="<?php echo e($page->cta_url ?: '#'); ?>" data-track="<?php echo e($ctaEvent); ?>" data-section="cta" data-label="<?php echo e($page->cta_text); ?>" <?php if($page->cta_url): ?> target="_blank" rel="noopener" <?php endif; ?>>
                    <i class="ri-chat-3-line"></i><?php echo e($page->cta_text); ?>

                </a>
                <?php if($prospect->salesperson?->phone): ?>
                    <a class="btn-client light" href="tel:<?php echo e($prospect->salesperson->phone); ?>" data-track="contact_clicked" data-section="cta" data-label="Call">
                        <i class="ri-phone-line"></i>Call us
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<div class="privacy-note">
    <?php echo e($settings['privacy_notice'] ?? 'This page records which sections you view and which tools you use, so we can follow up with what is actually relevant to you.'); ?>

</div>

<script src="<?php echo e(asset('js/smart-page-tracker.js')); ?>"></script>
<script>
(function () {
    var money = function (value) {
        return '₹' + Math.round(value).toLocaleString('en-IN');
    };

    var formulas = {
        revenue: function (v) {
            var daily = v.groomers * v.appts * v.ticket;
            return {
                daily: money(daily),
                monthly: money(daily * v.days),
                annual: money(daily * v.days * 12),
                summary: money(daily * v.days) + ' per month'
            };
        },
        no_show: function (v) {
            var monthly = v.appts * (v.rate / 100) * v.ticket;
            return {
                monthly: money(monthly),
                annual: money(monthly * 12),
                summary: money(monthly) + ' lost per month'
            };
        },
        profit: function (v) {
            var costs = v.payroll + v.rent + v.supplies + v.software + v.marketing + v.other;
            var profit = v.revenue - costs;
            var margin = v.revenue > 0 ? (profit / v.revenue) * 100 : 0;
            return {
                monthly: money(profit),
                margin: margin.toFixed(1) + '%',
                annual: money(profit * 12),
                summary: money(profit) + ' per month'
            };
        }
    };

    document.querySelectorAll('[data-tool]').forEach(function (calc) {
        var tool = calc.dataset.tool;

        calc.querySelector('[data-calc]').addEventListener('click', function () {
            var values = {};
            calc.querySelectorAll('[data-in]').forEach(function (input) {
                values[input.dataset.in] = parseFloat(input.value) || 0;
            });

            var result = formulas[tool](values);
            var output = calc.querySelector('.calc-out');

            output.querySelectorAll('[data-out]').forEach(function (el) {
                el.textContent = result[el.dataset.out] || '';
            });
            output.hidden = false;

            if (window.smartPageCalculated) {
                window.smartPageCalculated(tool, result.summary);
            }
        });
    });
})();
</script>
</body>
</html>
<?php /**PATH C:\All Project\IntentScore Internal Project\intentscore\resources\views/frontend/smart-page/show.blade.php ENDPATH**/ ?>