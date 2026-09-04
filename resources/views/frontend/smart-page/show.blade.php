@php
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
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="noindex,nofollow">
<title>{{ $heading }}</title>
<link rel="icon" href="{{ asset('images/favicon.svg') }}" type="image/svg+xml">
<link rel="stylesheet" href="{{ asset('admin-assets/css/remixicon.css') }}">
<link rel="stylesheet" href="{{ asset('css/portal-public.css') }}">
</head>
<body class="client-page" id="smart-page" data-track-url="{{ route('smart.track', $page->smartLink->slug) }}">

<nav class="client-nav">
    <div class="client-nav-inner">
        <a class="client-logo" href="#top"><img src="{{ asset('images/logo.svg') }}" alt="" style="height:30px">{{ $company }}</a>
        <div class="client-nav-links">
            @foreach($ordered as $s)
                @if($s->section_type !== 'cta')
                    <a href="#sec-{{ $s->section_type }}">{{ $s->title ?: \App\Models\SmartLink\SmartPageTemplate::sectionLabel($s->section_type) }}</a>
                @endif
            @endforeach
        </div>
        @if($ctaSection)
            <a class="client-nav-cta btn-client" href="{{ $page->cta_url ?: '#sec-cta' }}" data-track="cta_clicked" data-section="cta" data-label="Header CTA">{{ $page->cta_text }}</a>
        @endif
    </div>
</nav>

<header class="hero" id="top">
    <div class="container-client">
        <div class="section-head" style="max-width:820px;margin-bottom:0">
            <div class="eyebrow">Prepared for you</div>
            <h1>{{ $heading }}</h1>
            <p>{{ $page->subheading ?: 'A few ideas we put together specifically for your business.' }}</p>
        </div>
    </div>
</header>

@foreach($ordered as $section)
    @php($type = $section->section_type)
    @php($title = $section->title ?: \App\Models\SmartLink\SmartPageTemplate::sectionLabel($type))
    @php($alt = $loop->index % 2 === 1)

    @if($type === 'cta')
        @continue
    @endif

    <section class="client-section {{ $alt ? 'alt' : '' }}" id="sec-{{ $type }}" data-section="{{ $type }}">
        <div class="container-client">

            @if($type === 'intro')
                <div class="section-head"><h2>{{ $title }}</h2></div>
                <div class="content-box">{{ $page->personalized_message ?: $section->content }}</div>

            @elseif(in_array($type, ['website_audit','instagram_audit']))
                <div class="section-head"><div class="eyebrow">Audit</div><h2>{{ $title }}</h2></div>
                <div class="grid-3">
                    @foreach(['observation'=>['What we see','ri-search-eye-line'],'problem'=>['The problem','ri-error-warning-line'],'recommendation'=>['What we recommend','ri-lightbulb-flash-line']] as $key=>$meta)
                        @if($section->field($key))
                            <div class="feature-card">
                                <div class="feature-icon"><i class="{{ $meta[1] }}"></i></div>
                                <h3>{{ $meta[0] }}</h3>
                                <p>{{ $section->field($key) }}</p>
                            </div>
                        @endif
                    @endforeach
                </div>
                @if($section->content)<div class="custom-copy mt-3">{{ $section->content }}</div>@endif

            @elseif($type === 'google_audit')
                <div class="section-head"><div class="eyebrow">Audit</div><h2>{{ $title }}</h2></div>
                <div class="grid-2">
                    <div class="feature-card">
                        <div class="detail-row" style="border-top:0"><span>Current rating</span><strong>{{ $section->field('rating','—') }}</strong></div>
                        <div class="detail-row"><span>Review count</span><strong>{{ $section->field('reviews','—') }}</strong></div>
                    </div>
                    <div class="feature-card">
                        @if($section->field('opportunity'))<h3>Opportunity</h3><p>{{ $section->field('opportunity') }}</p>@endif
                        @if($section->field('recommendation'))<h3 class="mt-2">Recommendation</h3><p>{{ $section->field('recommendation') }}</p>@endif
                    </div>
                </div>

            @elseif($type === 'free_tools')
                @php($tools = $section->data['tools'] ?? ['revenue','no_show','profit'])
                <div class="section-head"><div class="eyebrow">Free tools</div><h2>{{ $title }}</h2><p>{{ $section->content ?: 'Run the numbers for your own business. Nothing is submitted anywhere.' }}</p></div>

                @if(in_array('revenue',$tools))
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
                @endif

                @if(in_array('no_show',$tools))
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
                @endif

                @if(in_array('profit',$tools))
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
                @endif

            @elseif($type === 'solution')
                <div class="section-head"><div class="eyebrow">Recommended</div><h2>{{ $title }}</h2></div>
                <div class="grid-2">
                    <div>
                        @if($section->field('what'))<h3 style="font-size:15px">What we recommend</h3><div class="content-box mb-3">{{ $section->field('what') }}</div>@endif
                        @if($section->field('why'))<h3 style="font-size:15px">Why</h3><div class="content-box">{{ $section->field('why') }}</div>@endif
                    </div>
                    @if($section->field('benefits'))
                    <div>
                        <h3 style="font-size:15px">Key benefits</h3>
                        <ul class="list-clean">
                            @foreach(preg_split('/\r\n|\r|\n/', $section->field('benefits')) as $benefit)
                                @if(trim($benefit))<li><i class="ri-checkbox-circle-fill"></i><span>{{ trim($benefit) }}</span></li>@endif
                            @endforeach
                        </ul>
                    </div>
                    @endif
                </div>

            @elseif($type === 'portfolio')
                <div class="section-head"><div class="eyebrow">Our work</div><h2>{{ $title }}</h2>@if($section->content)<p>{{ $section->content }}</p>@endif</div>
                <div class="media-grid">
                    @forelse($section->data['images'] ?? [] as $img)
                        <div class="media-item">
                            <img src="{{ asset($img['path']) }}" alt="{{ $img['caption'] ?? '' }}" loading="lazy">
                            @if($img['caption'] ?? false)<div class="caption">{{ $img['caption'] }}</div>@endif
                        </div>
                    @empty
                        <div class="custom-copy">Examples will be shared on our call.</div>
                    @endforelse
                </div>

            @else
                <div class="section-head"><h2>{{ $title }}</h2></div>
                @if($section->content)<div class="content-box">{{ $section->content }}</div>@endif
            @endif

        </div>
    </section>
@endforeach

@if($ctaSection)
<section class="client-section" id="sec-cta" data-section="cta">
    <div class="container-client">
        <div class="contact-panel">
            <div>
                <h2>{{ $ctaSection->title ?: 'Ready when you are' }}</h2>
                <p>{{ $ctaSection->content ?: 'Happy to walk through any of this in a short call.' }}</p>
            </div>
            <div class="contact-actions">
                <a class="btn-client" href="{{ $page->cta_url ?: '#' }}" data-track="{{ $ctaEvent }}" data-section="cta" data-label="{{ $page->cta_text }}" @if($page->cta_url) target="_blank" rel="noopener" @endif>
                    <i class="ri-chat-3-line"></i>{{ $page->cta_text }}
                </a>
                @if($prospect->salesperson?->phone)
                    <a class="btn-client light" href="tel:{{ $prospect->salesperson->phone }}" data-track="contact_clicked" data-section="cta" data-label="Call">
                        <i class="ri-phone-line"></i>Call us
                    </a>
                @endif
            </div>
        </div>
    </div>
</section>
@endif

<div class="privacy-note">
    {{ $settings['privacy_notice'] ?? 'This page records which sections you view and which tools you use, so we can follow up with what is actually relevant to you.' }}
</div>

<script src="{{ asset('js/smart-page-tracker.js') }}"></script>
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
