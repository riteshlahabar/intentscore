@php
    // Only the profile half of the audit is rendered here, and that is deliberate.
    // Instagram answers the per-post request with HTTP 429 for this account, so the
    // worker no longer sends it at all: engagement rate, average likes and comments,
    // posts per month and the engagement/consistency scores are always null. Showing
    // them to a client would be a row of dashes, so the card carries only the figures
    // that always arrive - follower, following and post counts, bio, category, picture.
    //
    // A 'failed' row is skipped as well as a missing one. latestInstagramAudit() returns
    // failed rows so the admin can see what went wrong, but a client should never be
    // shown a broken fetch; the section then falls back to its typed copy alone.
    $ig = $instagramAudit ?? null;
    $igShow = $ig && in_array($ig->status, ['completed', 'partial'], true) && $ig->followers !== null;
@endphp

@if($igShow)
    <style>
        .ig-audit{margin-top:24px;max-width:720px;margin-left:auto;margin-right:auto;text-align:left;border:1px solid #e7ecea;border-radius:12px;background:#fff;padding:20px}
        .ig-audit-head{display:flex;align-items:center;gap:14px}
        .ig-audit-avatar{width:66px;height:66px;border-radius:50%;object-fit:cover;border:1px solid #e7ecea;flex:none;background:#f4f6f7}
        .ig-audit-avatar-blank{display:grid;place-items:center;color:#b9c1c8;font-size:26px}
        .ig-audit-handle{font-size:16px;font-weight:750;line-height:1.25;color:#18212b;word-break:break-all}
        .ig-audit-name{font-size:12.5px;color:#67727e;margin-top:2px}
        .ig-audit-tags{display:flex;flex-wrap:wrap;gap:6px;margin-top:6px}
        .ig-audit-tag{font-size:10px;font-weight:700;padding:3px 8px;border-radius:20px;background:#f1ecfd;color:#6a3ad6}
        .ig-audit-counts{display:flex;flex-wrap:wrap;gap:26px;margin-top:18px;padding-top:16px;border-top:1px solid #f1f3f4}
        .ig-audit-count-value{font-size:18px;font-weight:800;color:#18212b;line-height:1.2}
        .ig-audit-count-label{font-size:10px;color:#67727e;margin-top:3px}
        .ig-audit-meta{margin-top:16px;padding-top:14px;border-top:1px solid #f1f3f4}
        .ig-audit-category{font-size:11.5px;font-weight:700;color:#6a3ad6}
        .ig-audit-bio{font-size:12.5px;color:#46515d;white-space:pre-line;margin-top:6px}
        .ig-audit-line{font-size:12px;color:#67727e;margin-top:8px;word-break:break-all}
        .ig-audit-line a{color:#6a3ad6;text-decoration:none}
        .ig-audit-foot{font-size:11px;color:#8b949e;margin-top:16px;padding-top:12px;border-top:1px solid #f1f3f4}
        .ig-audit-foot a{color:#6a3ad6;text-decoration:none;font-weight:650}
        @media(max-width:520px){.ig-audit{padding:16px}.ig-audit-counts{gap:18px}}
    </style>
    <div class="ig-audit">
        <div class="ig-audit-head">
            @if($ig->profile_pic)
                <img src="{{ $ig->profile_pic }}" alt="{{ $ig->username }}" class="ig-audit-avatar" loading="lazy">
            @else
                <div class="ig-audit-avatar ig-audit-avatar-blank"><i data-feather="instagram"></i></div>
            @endif

            <div>
                <div class="ig-audit-handle">&#64;{{ $ig->username }}</div>

                @if($ig->full_name)
                    <div class="ig-audit-name">{{ $ig->full_name }}</div>
                @endif

                @if($ig->is_verified || $ig->is_business || $ig->is_private)
                    <div class="ig-audit-tags">
                        @if($ig->is_verified)
                            <span class="ig-audit-tag">Verified</span>
                        @endif

                        @if($ig->is_business)
                            <span class="ig-audit-tag">Business</span>
                        @endif

                        @if($ig->is_private)
                            <span class="ig-audit-tag">Private</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <div class="ig-audit-counts">
            @foreach([
                ['Posts', $ig->posts_count],
                ['Followers', $ig->followers],
                ['Following', $ig->following],
            ] as [$igLabel, $igValue])
                <div>
                    <div class="ig-audit-count-value">{{ $igValue !== null ? number_format($igValue) : '—' }}</div>
                    <div class="ig-audit-count-label">{{ $igLabel }}</div>
                </div>
            @endforeach
        </div>

        @if($ig->category || $ig->biography || $ig->business_address || $ig->external_url)
            <div class="ig-audit-meta">
                @if($ig->category)
                    <div class="ig-audit-category">{{ $ig->category }}</div>
                @endif

                @if($ig->biography)
                    <div class="ig-audit-bio">{{ $ig->biography }}</div>
                @endif

                @if($ig->business_address)
                    <div class="ig-audit-line">{{ $ig->business_address }}</div>
                @endif

                @if($ig->external_url)
                    <div class="ig-audit-line">
                        <a href="{{ $ig->external_url }}" target="_blank" rel="noopener nofollow"
                           data-track="section_clicked" data-section="instagram_audit" data-label="Instagram bio link">{{ $ig->external_url }}</a>
                    </div>
                @endif
            </div>
        @endif

        <div class="ig-audit-foot">
            <a href="{{ $ig->profile_url }}" target="_blank" rel="noopener"
               data-track="section_clicked" data-section="instagram_audit" data-label="Open Instagram profile">Open profile</a>
            · Reviewed {{ $ig->created_at->format('d M Y') }}
        </div>
    </div>
@endif
