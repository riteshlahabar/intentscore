{{--
    Publishes the configurable header logo height as a CSS custom property.

    Goes in <head>. Setting it on :root rather than on the <img> matters: the fallback
    page's nav bar has a fixed height that has to grow with the logo, and a property
    set on the image itself cannot be read by its parent. The stylesheets own the
    tablet/mobile scaling, so one number in Settings drives all three breakpoints.
--}}
@php
    // Clamped to the range the settings form validates, in case an older row or a
    // hand-edited value falls outside it.
    $logoHeight = max(24, min(160, (int) ($settings['header_logo_height'] ?? 72)));
@endphp
<style>:root{--brand-logo-h:{{ $logoHeight }}px}</style>
