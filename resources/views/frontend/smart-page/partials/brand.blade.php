{{--
    Smart Page header brand — logo, company name, or both.

    Driven by the `header_display` setting (Settings -> Company Settings). The rule
    lives here rather than in each design so all five headers stay in step.

    $style picks the markup: 'topnav' for the four Landrick designs, 'client' for the
    fallback show.blade.php, which has its own classes and an initial-letter mark.
--}}
@php
    $logo = $settings['company_logo'] ?? null;
    $mode = $settings['header_display'] ?? 'both';

    // "Logo only" with nothing uploaded would leave an empty header, so the name
    // always stands in. That also covers the template preview, which passes a
    // settings array with no logo at all.
    $showLogo = $mode !== 'name' && ! empty($logo);
    $showName = $mode !== 'logo' || ! $showLogo;
@endphp
@if($style === 'topnav')
    @if($showLogo)<img src="{{ asset($logo) }}" alt="{{ $company }}" class="brand-logo">@endif
    @if($showName)<span class="h4 text-primary fw-bold mb-0">{{ $company }}</span>@endif
@else
    @if($showLogo)
        <img src="{{ asset($logo) }}" alt="{{ $company }}" class="brand-logo">
    @elseif($showName)
        <span class="client-logo-mark">{{ strtoupper(substr($company, 0, 1)) }}</span>
    @endif
    @if($showName){{ $company }}@endif
@endif
