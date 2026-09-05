@php
    $company = $settings['company_name'] ?? 'Smart Links';
    $ordered = $sections->sortBy('display_order');
    $navSections = $ordered->where('section_type', '!=', 'cta')->values();
    $navInline = $navSections->take(4);
    $navMore = $navSections->slice(4);
    $heading = $page->heading ?: $prospect->business_name;
    $ctaSection = $ordered->firstWhere('section_type', 'cta');
    $introSection = $ordered->firstWhere('section_type', 'intro');
    $portfolioSection = $ordered->firstWhere('section_type', 'portfolio');
    $freeToolsSection = $ordered->firstWhere('section_type', 'free_tools');
    $solutionSection = $ordered->firstWhere('section_type', 'solution');
    $auditSections = $ordered->whereIn('section_type', ['website_audit', 'google_audit', 'instagram_audit']);
    $ctaEvent = match ($page->cta_type) {
        'whatsapp' => 'whatsapp_clicked',
        'email' => 'email_clicked',
        'calendar' => 'calendar_clicked',
        default => 'contact_clicked',
    };
    $benefitLines = $solutionSection
        ? array_values(array_filter(preg_split('/\r\n|\r|\n/', $solutionSection->field('benefits')), fn ($l) => trim($l) !== ''))
        : [];
    $benefitIcons = ['monitor', 'heart', 'eye', 'bold', 'feather', 'code', 'user-check', 'git-merge', 'settings'];
    $auditIcons = ['observation' => 'search', 'problem' => 'alert-triangle', 'recommendation' => 'zap' ];
    $preview = $preview ?? false;
    $trackUrl = $preview ? '#' : route('smart.track', $page->smartLink->slug);
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
<link href="{{ asset('smart-templates/assets/libs/tiny-slider/tiny-slider.css') }}" rel="stylesheet">
<link href="{{ asset('smart-templates/assets/libs/tobii/css/tobii.min.css') }}" rel="stylesheet">
<link href="{{ asset('smart-templates/assets/css/bootstrap.min.css') }}" rel="stylesheet">
<link href="{{ asset('smart-templates/assets/libs/@mdi/font/css/materialdesignicons.min.css') }}" rel="stylesheet">
<link href="{{ asset('smart-templates/assets/libs/@iconscout/unicons/css/line.css') }}" rel="stylesheet">
<link href="{{ asset('smart-templates/assets/css/style.min.css') }}" rel="stylesheet">
</head>
<body id="smart-page" data-track-url="{{ $trackUrl }}">

<header id="topnav" class="defaultscroll sticky">
    <div class="container">
        <a class="logo" href="#home">
            <span class="h4 text-primary fw-bold mb-0">{{ $company }}</span>
        </a>

        <div class="menu-extras">
            <div class="menu-item">
                <a class="navbar-toggle" id="isToggle" onclick="toggleMenu()">
                    <div class="lines"><span></span><span></span><span></span></div>
                </a>
            </div>
        </div>

        @if($ctaSection)
        <ul class="buy-button list-inline mb-0 d-none d-md-inline-block">
            <li class="list-inline-item mb-0">
                <a class="btn btn-primary" href="{{ $page->cta_url ?: '#sec-cta' }}" data-track="cta_clicked" data-section="cta" data-label="Header CTA">{{ $page->cta_text }}</a>
            </li>
        </ul>
        @endif

        <div id="navigation">
            <ul class="navigation-menu">
                <li><a href="#home">Home</a></li>
                @foreach($navInline as $s)
                    <li><a href="#sec-{{ $s->section_type }}">{{ \App\Models\SmartLink\SmartPageTemplate::navLabel($s->section_type) }}</a></li>
                @endforeach
                @if($navMore->isNotEmpty())
                <li class="has-submenu">
                    <a href="javascript:void(0)">More</a><span class="menu-arrow"></span>
                    <ul class="submenu">
                        @foreach($navMore as $s)
                            <li><a href="#sec-{{ $s->section_type }}" class="sub-menu-item">{{ \App\Models\SmartLink\SmartPageTemplate::navLabel($s->section_type) }}</a></li>
                        @endforeach
                    </ul>
                </li>
                @endif
            </ul>
        </div>
    </div>
</header>

<section class="bg-half-170 d-table w-100 border-bottom" id="home">
    <div class="container">
        <div class="row align-items-center mt-md-5">
            <div class="col-lg-7 col-md-6">
                <div class="title-heading mt-4 me-lg-4">
                    <div class="text-primary fw-bold text-uppercase small mb-2">Prepared for you</div>
                    <h1 class="heading mb-3">{{ $heading }}</h1>
                    <p class="para-desc text-muted">{{ $page->subheading ?: 'A few ideas we put together specifically for your business.' }}</p>
                    @if($ctaSection)
                    <div class="mt-4 pt-2">
                        <a href="{{ $page->cta_url ?: '#sec-cta' }}" class="btn btn-primary m-1" data-track="cta_clicked" data-section="cta" data-label="Hero CTA">{{ $page->cta_text }} <i class="uil uil-angle-right-b"></i></a>
                    </div>
                    @endif
                </div>
            </div><!--end col-->

            <div class="col-lg-5 col-md-6 mt-4 pt-2 mt-sm-0 pt-sm-0">
                <img src="{{ asset('smart-templates/modern-business/images/illustrator/Startup_SVG.svg') }}" class="img-fluid" alt="">
            </div><!--end col-->
        </div><!--end row-->
    </div><!--end container-->
</section><!--end section-->

@if($introSection)
<section class="section" id="sec-intro" data-section="intro">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-5 col-md-5">
                <div class="position-relative">
                    <img src="{{ asset('smart-templates/modern-business/images/company/about.jpg') }}" class="rounded img-fluid mx-auto d-block" alt="">
                </div>
            </div><!--end col-->

            <div class="col-lg-7 col-md-7 mt-4 pt-2 mt-sm-0 pt-sm-0">
                <div class="section-title ms-lg-4">
                    <h4 class="title mb-4">{{ $introSection->title ?: 'A Few Ideas For You' }}</h4>
                    <p class="text-muted">{{ $page->personalized_message ?: $introSection->content }}</p>
                </div>
            </div><!--end col-->
        </div><!--end row-->
    </div><!--end container-->
</section><!--end section-->
@endif

@foreach($auditSections as $section)
    @php($type = $section->section_type)
    @php($title = $section->title ?: \App\Models\SmartLink\SmartPageTemplate::sectionLabel($type))
    <section class="section {{ $loop->even ? 'bg-light' : '' }}" id="sec-{{ $type }}" data-section="{{ $type }}">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 text-center">
                    <div class="section-title mb-4 pb-2">
                        <h4 class="title mb-4">{{ $title }}</h4>
                    </div>
                </div>
            </div><!--end row-->

            @if($type === 'google_audit')
                <div class="row justify-content-center text-center">
                    <div class="col-md-3 col-6">
                        <h2 class="text-primary fw-bold">{{ $section->field('rating', '—') }}</h2>
                        <p class="text-muted">Current rating</p>
                    </div>
                    <div class="col-md-3 col-6">
                        <h2 class="text-primary fw-bold">{{ $section->field('reviews', '—') }}</h2>
                        <p class="text-muted">Review count</p>
                    </div>
                </div>
                <div class="row justify-content-center mt-4">
                    @if($section->field('opportunity'))
                    <div class="col-md-5 mt-4 pt-2">
                        <div class="features feature-primary text-center">
                            <div class="content"><h5>Opportunity</h5><p class="text-muted mb-0">{{ $section->field('opportunity') }}</p></div>
                        </div>
                    </div>
                    @endif
                    @if($section->field('recommendation'))
                    <div class="col-md-5 mt-4 pt-2">
                        <div class="features feature-primary text-center">
                            <div class="content"><h5>Recommendation</h5><p class="text-muted mb-0">{{ $section->field('recommendation') }}</p></div>
                        </div>
                    </div>
                    @endif
                </div>
            @else
                <div class="row">
                    @foreach(['observation' => 'What we see', 'problem' => 'The problem', 'recommendation' => 'What we recommend'] as $key => $label)
                        @if($section->field($key))
                        <div class="col-md-4 col-12 mt-4 mt-md-0">
                            <div class="features feature-primary text-center">
                                <div class="image position-relative d-inline-block">
                                    <i data-feather="{{ $auditIcons[$key] }}" class="fea icon-lg text-primary"></i>
                                </div>
                                <div class="content mt-4">
                                    <h5>{{ $label }}</h5>
                                    <p class="text-muted mb-0">{{ $section->field($key) }}</p>
                                </div>
                            </div>
                        </div>
                        @endif
                    @endforeach
                </div>
                @if($section->content)<p class="text-muted mt-4">{{ $section->content }}</p>@endif
            @endif
        </div><!--end container-->
    </section><!--end section-->
@endforeach

@if($freeToolsSection)
<section class="section bg-light" id="sec-free_tools" data-section="free_tools">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 text-center">
                <div class="section-title mb-4 pb-2">
                    <h4 class="title mb-4">{{ $freeToolsSection->title ?: 'Free Tools' }}</h4>
                    <p class="text-muted para-desc mx-auto mb-0">{{ $freeToolsSection->content ?: 'Run the numbers for your own business. Nothing is submitted anywhere.' }}</p>
                </div>
            </div>
        </div><!--end row-->
        <div class="row justify-content-center">
            <div class="col-lg-10">
                @include('frontend.smart-page.partials.free-tools', ['section' => $freeToolsSection])
            </div>
        </div><!--end row-->
    </div><!--end container-->
</section><!--end section-->
@endif

@if($solutionSection)
<section class="section" id="sec-solution" data-section="solution">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-5 col-md-6">
                <img src="{{ asset('smart-templates/modern-business/images/illustrator/Startup_SVG.svg') }}" class="img-fluid" alt="">
            </div><!--end col-->

            <div class="col-lg-7 col-md-6 mt-4 mt-sm-0 pt-2 pt-sm-0">
                <div class="section-title ms-lg-4">
                    <h4 class="title mb-4">{{ $solutionSection->title ?: 'Recommended Solution' }}</h4>
                    @if($solutionSection->field('what'))<p class="text-muted">{{ $solutionSection->field('what') }}</p>@endif
                    @if($solutionSection->field('why'))<p class="text-muted">{{ $solutionSection->field('why') }}</p>@endif
                </div>
            </div><!--end col-->
        </div><!--end row-->

        @if($benefitLines)
        <div class="row mt-100 mt-60">
            @foreach($benefitLines as $i => $benefit)
                <div class="col-lg-4 col-md-6 mt-4 pt-2">
                    <div class="d-flex features feature-primary key-feature align-items-center p-3 rounded shadow">
                        <div class="icon text-center rounded-circle me-3">
                            <i data-feather="{{ $benefitIcons[$i % count($benefitIcons)] }}" class="fea icon-ex-md"></i>
                        </div>
                        <div class="flex-1"><h4 class="title mb-0">{{ trim($benefit) }}</h4></div>
                    </div>
                </div>
            @endforeach
        </div>
        @endif
    </div><!--end container-->
</section><!--end section-->
@endif

@if($portfolioSection)
<section class="section bg-light" id="sec-portfolio" data-section="portfolio">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 text-center">
                <div class="section-title mb-4 pb-2">
                    <h4 class="title mb-4">{{ $portfolioSection->title ?: 'Our Work' }}</h4>
                    @if($portfolioSection->content)<p class="text-muted para-desc mx-auto mb-0">{{ $portfolioSection->content }}</p>@endif
                </div>
            </div>
        </div><!--end row-->
        <div class="row">
            @forelse($portfolioSection->data['images'] ?? [] as $img)
                <div class="col-lg-4 col-md-6 mt-4 pt-2">
                    <div class="card blog blog-primary rounded border-0 shadow">
                        <div class="position-relative">
                            <img src="{{ asset($img['path']) }}" class="card-img-top rounded-top" alt="{{ $img['caption'] ?? '' }}" loading="lazy">
                        </div>
                        @if($img['caption'] ?? false)
                        <div class="card-body content"><h5 class="card-title title text-dark mb-0">{{ $img['caption'] }}</h5></div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="col-12 text-center text-muted">Examples will be shared on our call.</div>
            @endforelse
        </div><!--end row-->
    </div><!--end container-->
</section><!--end section-->
@endif

@if($ctaSection)
<section class="section bg-cta" style="background: url('{{ asset('smart-templates/modern-business/images/2.jpg') }}') center center;" id="sec-cta" data-section="cta">
    <div class="bg-overlay"></div>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <div class="section-title">
                    <h4 class="title title-dark text-white mb-4">{{ $ctaSection->title ?: 'Ready when you are' }}</h4>
                    <p class="text-white-50 para-dark para-desc mx-auto">{{ $ctaSection->content ?: 'Happy to walk through any of this in a short call.' }}</p>
                    <div class="mt-4 d-flex justify-content-center gap-2 flex-wrap">
                        <a class="btn btn-light" href="{{ $page->cta_url ?: '#' }}" data-track="{{ $ctaEvent }}" data-section="cta" data-label="{{ $page->cta_text }}" @if($page->cta_url) target="_blank" rel="noopener" @endif>{{ $page->cta_text }}</a>
                        @if($prospect->salesperson?->phone)
                        <a class="btn btn-outline-light" href="tel:{{ $prospect->salesperson->phone }}" data-track="contact_clicked" data-section="cta" data-label="Call">Call us</a>
                        @endif
                    </div>
                </div>
            </div>
        </div><!--end row-->
    </div><!--end container-->
</section><!--end section-->
@endif

<footer class="footer footer-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 col-12 mb-4 mb-md-0">
                <span class="h5 fw-bold d-block mb-3">{{ $company }}</span>
                @if($settings['company_about'] ?? false)<p class="mt-2">{{ $settings['company_about'] }}</p>@endif
                <ul class="list-unstyled footer-list mt-3">
                    @if($settings['company_phone'] ?? false)<li><i class="uil uil-phone me-1"></i> {{ $settings['company_phone'] }}</li>@endif
                    @if($settings['company_email'] ?? false)<li class="mt-2"><i class="uil uil-envelope me-1"></i> {{ $settings['company_email'] }}</li>@endif
                </ul>
            </div><!--end col-->

            <div class="col-lg-4 col-md-6 col-12 mt-4 mt-sm-0">
                <h5 class="footer-head">Quick Links</h5>
                <ul class="list-unstyled footer-list mt-4">
                    @foreach($ordered as $s)
                        @if($s->section_type !== 'cta')
                        <li><a href="#sec-{{ $s->section_type }}" class="text-foot"><i class="uil uil-angle-right-b me-1"></i> {{ $s->title ?: \App\Models\SmartLink\SmartPageTemplate::sectionLabel($s->section_type) }}</a></li>
                        @endif
                    @endforeach
                </ul>
            </div><!--end col-->

            <div class="col-lg-4 col-md-6 col-12 mt-4 mt-sm-0">
                <h5 class="footer-head">Get In Touch</h5>
                @if($ctaSection)
                <p class="mt-4">{{ $ctaSection->content ?: 'Happy to walk through any of this in a short call.' }}</p>
                <a href="{{ $page->cta_url ?: '#sec-cta' }}" class="btn btn-soft-primary" data-track="{{ $ctaEvent }}" data-section="cta" data-label="Footer CTA">{{ $page->cta_text }}</a>
                @endif
            </div><!--end col-->
        </div><!--end row-->
    </div><!--end container-->

    <div class="footer-py-30 border-top">
        <div class="container text-center">
            <p class="mb-0">© <script>document.write(new Date().getFullYear())</script> {{ $company }}.</p>
            <p class="mb-0 small mt-2">{{ $settings['privacy_notice'] ?? 'This page records which sections you view and which tools you use, so we can follow up with what is actually relevant to you.' }}</p>
        </div>
    </div>
</footer><!--end footer-->

<script src="{{ asset('smart-templates/assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('smart-templates/assets/libs/tiny-slider/min/tiny-slider.js') }}"></script>
<script src="{{ asset('smart-templates/assets/libs/tobii/js/tobii.min.js') }}"></script>
<script src="{{ asset('smart-templates/assets/libs/feather-icons/feather.min.js') }}"></script>
<script src="{{ asset('smart-templates/assets/js/plugins.init.js') }}"></script>
<script src="{{ asset('smart-templates/assets/js/app.js') }}"></script>
@unless($preview)
<script src="{{ asset('js/smart-page-tracker.js') }}"></script>
@endunless
@include('frontend.smart-page.partials.free-tools-script')
</body>
</html>
