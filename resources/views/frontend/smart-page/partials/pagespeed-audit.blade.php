@php
    $psiCards = collect(['mobile' => $mobileAudit ?? null, 'desktop' => $desktopAudit ?? null])
        ->filter(fn ($audit) => $audit && $audit->status === 'completed');
@endphp
@if($psiCards->isNotEmpty())
    <style>
        .psi-audit{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:16px;margin-top:24px;text-align:left}
        .psi-audit-card{border:1px solid #e7ecea;border-radius:11px;padding:16px;background:#fff}
        .psi-audit-device{font-size:11px;font-weight:750;text-transform:uppercase;letter-spacing:.06em;color:#67727e;margin-bottom:10px}
        .psi-audit-scores{display:grid;grid-template-columns:repeat(4,1fr);gap:8px}
        .psi-audit-pill{border-radius:9px;padding:10px 4px;text-align:center}
        .psi-audit-value{display:block;font-size:18px;font-weight:800;line-height:1.2}
        .psi-audit-label{display:block;font-size:9px;color:#67727e;margin-top:3px}
        .psi-audit-pill.psi-good{background:#e7f7f3}.psi-audit-pill.psi-good .psi-audit-value{color:#0b836e}
        .psi-audit-pill.psi-ok{background:#fff5da}.psi-audit-pill.psi-ok .psi-audit-value{color:#9a6a00}
        .psi-audit-pill.psi-poor{background:#fdeaea}.psi-audit-pill.psi-poor .psi-audit-value{color:#b42318}
        .psi-audit-pill.psi-na{background:#eef1f4}.psi-audit-pill.psi-na .psi-audit-value{color:#66717d}
        .psi-audit-screenshot{width:100%;border-radius:8px;border:1px solid #e7ecea;margin-top:12px;display:block}
        @media(max-width:480px){.psi-audit-scores{grid-template-columns:repeat(2,1fr)}}
    </style>
    <div class="psi-audit">
        @foreach($psiCards as $strategy => $audit)
            <div class="psi-audit-card">
                <div class="psi-audit-device">{{ ucfirst($strategy) }} score</div>
                <div class="psi-audit-scores">
                    @foreach([
                        ['Performance', $audit->performance_score],
                        ['Accessibility', $audit->accessibility_score],
                        ['Best Practices', $audit->best_practices_score],
                        ['SEO', $audit->seo_score],
                    ] as [$psiLabel, $psiScore])
                        @php
                            $psiTier = $psiScore === null ? 'na' : ($psiScore >= 90 ? 'good' : ($psiScore >= 50 ? 'ok' : 'poor'));
                        @endphp
                        <div class="psi-audit-pill psi-{{ $psiTier }}">
                            <span class="psi-audit-value">{{ $psiScore ?? '—' }}</span>
                            <span class="psi-audit-label">{{ $psiLabel }}</span>
                        </div>
                    @endforeach
                </div>
                @if($audit->screenshot)
                    <img src="{{ $audit->screenshot }}" alt="{{ ucfirst($strategy) }} screenshot" class="psi-audit-screenshot" loading="lazy">
                @endif
            </div>
        @endforeach
    </div>
@endif
