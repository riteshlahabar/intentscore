@php
    $psiCards = collect(['mobile' => $mobileAudit ?? null, 'desktop' => $desktopAudit ?? null])
        ->filter(fn ($audit) => $audit && $audit->status === 'completed');
    $psiTierOf = fn (?int $score) => $score === null ? 'na' : ($score >= 90 ? 'good' : ($score >= 50 ? 'ok' : 'poor'));
    $psiUid = 'psi-'.uniqid();
@endphp
@if($psiCards->isNotEmpty())
    <style>
        .psi-audit{margin-top:24px;max-width:900px;margin-left:auto;margin-right:auto;text-align:left;border:1px solid #e7ecea;border-radius:12px;overflow:hidden;background:#fff}
        .psi-tabs{display:flex;gap:4px;border-bottom:1px solid #e7ecea;padding:10px 14px 0}
        .psi-tabs label{padding:9px 14px;font-size:12.5px;font-weight:700;color:#67727e;cursor:pointer;border-bottom:2px solid transparent;user-select:none}
        .psi-panels{padding:20px}
        .psi-panel{display:none;grid-template-columns:1fr auto;gap:24px;align-items:flex-start}
        .psi-gauges{display:flex;flex-wrap:wrap;gap:18px}
        .psi-gauge{width:74px;height:74px;border-radius:50%;display:grid;place-items:center;background:conic-gradient(var(--psi-c) calc(var(--psi-s)*1%), #e7ecea 0)}
        .psi-gauge-hole{width:56px;height:56px;border-radius:50%;background:#fff;display:grid;place-items:center;font-size:16px;font-weight:800;color:#18212b}
        .psi-gauge-wrap{text-align:center}
        .psi-gauge-label{font-size:10px;color:#67727e;margin-top:7px;font-weight:650}
        .psi-shot{width:190px;max-width:100%;border-radius:8px;border:1px solid #e7ecea;display:block}
        .psi-panel-mobile .psi-shot{width:auto;height:auto;max-height:150px;margin:0 auto}
        .psi-vitals{display:flex;flex-wrap:wrap;gap:16px;margin-top:18px;padding-top:16px;border-top:1px solid #f1f3f4}
        .psi-vital-label{font-size:10px;color:#67727e}
        .psi-vital-value{font-size:12.5px;font-weight:700;margin-top:2px}
        #{{ $psiUid }}-mobile:checked ~ .psi-audit .psi-panel-mobile,
        #{{ $psiUid }}-desktop:checked ~ .psi-audit .psi-panel-desktop{display:grid}
        #{{ $psiUid }}-mobile:checked ~ .psi-audit .psi-tabs label[for="{{ $psiUid }}-mobile"],
        #{{ $psiUid }}-desktop:checked ~ .psi-audit .psi-tabs label[for="{{ $psiUid }}-desktop"]{color:#ee7b1d;border-color:#ee7b1d}
        @media(max-width:520px){.psi-panel{grid-template-columns:1fr}.psi-shot{width:100%}.psi-panel-mobile .psi-shot{width:auto;max-height:220px}}
    </style>
    @if($psiCards->has('mobile'))<input type="radio" name="{{ $psiUid }}" id="{{ $psiUid }}-mobile" hidden checked>@endif
    @if($psiCards->has('desktop'))<input type="radio" name="{{ $psiUid }}" id="{{ $psiUid }}-desktop" hidden {{ !$psiCards->has('mobile') ? 'checked' : '' }}>@endif
    <div class="psi-audit">
        @if($psiCards->count() > 1)
            <div class="psi-tabs">
                <label for="{{ $psiUid }}-mobile">Mobile</label>
                <label for="{{ $psiUid }}-desktop">Desktop</label>
            </div>
        @endif
        <div class="psi-panels">
            @foreach($psiCards as $strategy => $audit)
                <div class="psi-panel psi-panel-{{ $strategy }}" @if($psiCards->count() === 1) style="display:grid" @endif>
                    <div>
                        <div class="psi-gauges">
                            @foreach([
                                ['Performance', $audit->performance_score],
                                ['Accessibility', $audit->accessibility_score],
                                ['Best Practices', $audit->best_practices_score],
                                ['SEO', $audit->seo_score],
                            ] as [$psiLabel, $psiScore])
                                @php($psiTier = $psiTierOf($psiScore))
                                @php($psiColor = ['good' => '#0cce6b', 'ok' => '#ffa400', 'poor' => '#ff4e42', 'na' => '#c7ccd1'][$psiTier])
                                <div class="psi-gauge-wrap">
                                    <div class="psi-gauge" style="--psi-s:{{ $psiScore ?? 0 }};--psi-c:{{ $psiColor }}">
                                        <div class="psi-gauge-hole">{{ $psiScore ?? '—' }}</div>
                                    </div>
                                    <div class="psi-gauge-label">{{ $psiLabel }}</div>
                                </div>
                            @endforeach
                        </div>
                        <div class="psi-vitals">
                            @foreach([
                                ['LCP', $audit->lcp_ms !== null ? number_format($audit->lcp_ms / 1000, 1).'s' : '—'],
                                ['FCP', $audit->fcp_ms !== null ? number_format($audit->fcp_ms / 1000, 1).'s' : '—'],
                                ['CLS', $audit->cls ?? '—'],
                                ['TBT', $audit->tbt_ms !== null ? $audit->tbt_ms.'ms' : '—'],
                            ] as [$vLabel, $vVal])
                                <div>
                                    <div class="psi-vital-label">{{ $vLabel }}</div>
                                    <div class="psi-vital-value">{{ $vVal }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @if($audit->screenshot)
                        <img src="{{ $audit->screenshot }}" alt="{{ ucfirst($strategy) }} screenshot" class="psi-shot" loading="lazy">
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif
