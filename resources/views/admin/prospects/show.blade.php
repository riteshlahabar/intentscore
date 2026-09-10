@extends('admin.layouts.app')
@section('title',$prospect->business_name)
@section('page_title',$prospect->business_name)
@section('page_subtitle',collect([$prospect->industry,$prospect->location])->filter()->join(' · ') ?: 'Prospect detail')
@section('page_actions')
<div class="toolbar-actions">
    <a class="btn btn-light border" href="{{ route('admin.prospects.index') }}"><i class="ri-arrow-left-line me-1"></i>Back</a>
    <a class="btn btn-light border" href="{{ route('admin.prospects.edit',$prospect) }}"><i class="ri-edit-line me-1"></i>Edit</a>
    <a class="btn btn-primary" href="{{ route('admin.prospects.page.edit',$prospect) }}"><i class="ri-layout-4-line me-1"></i>Edit Smart Page</a>
</div>
@endsection

@section('content')
@php($score = $prospect->intentScore)
@php($link = $prospect->smartLink)

<div class="row g-3">
    <div class="col-xl-4">

        <div class="card mb-3">
            <div class="card-body text-center">
                <div class="metric-label">Intent</div>
                <div style="font-size:30px;font-weight:750;line-height:1.15" class="mt-1">{{ $score?->intent_level ?: 'LOW' }}</div>
                <div class="text-brand fw-bold" style="font-size:16px">Score {{ $score?->score ?: 0 }}</div>
                <div class="stat-mini mt-2">LOW 0–10 · ENGAGED 11–25 · INTERESTED 26–50 · HIGH INTENT 51+</div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><strong>Smart Link</strong></div>
            <div class="card-body">
                @if($link)
                    <div class="link-public d-block text-break mb-2">{{ $link->publicUrl() }}</div>
                    <div class="toolbar-actions">
                        <button type="button" class="btn btn-primary btn-sm" data-copy="{{ $link->publicUrl() }}"><i class="ri-file-copy-line me-1"></i>Copy Link</button>
                        <a class="btn btn-light border btn-sm" href="{{ $link->publicUrl() }}" target="_blank" rel="noopener"><i class="ri-external-link-line me-1"></i>View Smart Page</a>
                    </div>
                    <form method="post" action="{{ route('admin.prospects.regenerate',$prospect) }}" class="mt-2">@csrf
                        <button class="btn btn-light border btn-sm w-100" data-confirm="Generate a new link? The current link will stop working."><i class="ri-refresh-line me-1"></i>Regenerate link</button>
                    </form>
                @else
                    <div class="stat-mini">No Smart Link generated.</div>
                @endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><strong>Basic information</strong></div>
            <div class="card-body">
                @foreach([
                    'Business'=>$prospect->business_name,
                    'Contact'=>$prospect->contact_name,
                    'Website'=>$prospect->website,
                    'Phone'=>$prospect->phone,
                    'Email'=>$prospect->email,
                    'Offer'=>$prospect->offer,
                    'Salesperson'=>$prospect->salesperson?->name,
                ] as $label=>$value)
                    <div class="d-flex justify-content-between gap-3 py-2 border-bottom">
                        <span class="stat-mini">{{ $label }}</span>
                        <span class="text-end text-break" style="font-size:12.5px">{{ $value ?: '—' }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><strong>Sales status</strong></div>
            <div class="card-body">
                <form method="post" action="{{ route('admin.prospects.status',$prospect) }}">@csrf @method('PUT')
                    <select class="form-select mb-2" name="status">
                        @foreach(\App\Models\SmartLink\Prospect::STATUSES as $st)
                            <option value="{{ $st }}" @selected($prospect->status===$st)>{{ ucwords(str_replace('_',' ',$st)) }}</option>
                        @endforeach
                    </select>
                    <textarea class="form-control mb-2" rows="2" name="notes" placeholder="Optional note"></textarea>
                    <button class="btn btn-primary btn-sm w-100">Update status</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><strong>Visits</strong></div>
            <div class="card-body">
                <div class="timeline-scroll">
                    @forelse($visits as $v)
                        <div class="d-flex justify-content-between gap-2 py-2 border-bottom">
                            <div>
                                <div style="font-size:12.5px">{{ $v->started_at?->format('d M Y, h:i A') }}</div>
                                <div class="stat-mini">{{ collect([$v->device_type,$v->browser,$v->operating_system])->filter()->join(' · ') }}</div>
                            </div>
                            <div class="text-end">
                                @if($v->is_return_visit)<span class="badge-soft soft-amber">Return</span>@endif
                                <div class="stat-mini mt-1">{{ $v->active_seconds }}s</div>
                            </div>
                        </div>
                    @empty
                        <div class="stat-mini">No visits recorded yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Website Audit</strong>
                <div class="toolbar-actions">
                    @foreach(['mobile' => 'smartphone', 'desktop' => 'computer'] as $auditStrategy => $auditIcon)
                        <form method="post" action="{{ route('admin.prospects.audit',$prospect) }}" data-audit-form="{{ $auditStrategy }}">@csrf
                            <input type="hidden" name="strategy" value="{{ $auditStrategy }}">
                            <button class="btn btn-primary btn-sm" @if(!$prospect->website) disabled @endif>
                                <i class="ri-{{ $auditIcon }}-line me-1"></i>Run {{ ucfirst($auditStrategy) }} Audit
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
            <div class="card-body">
                <div id="audit-progress" class="mb-3" hidden>
                    <div class="progress" style="height:6px">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" style="width:100%"></div>
                    </div>
                    <div class="stat-mini mt-1" id="audit-progress-text">Running PageSpeed audit — this can take up to two minutes…</div>
                </div>
                @if(!$prospect->website)
                    <div class="empty-state"><i class="ri-global-line"></i><div class="mt-2">Add a website URL to this prospect to run a PageSpeed audit.</div></div>
                @else
                    <div class="row g-3">
                        @foreach(['mobile' => $latestMobileAudit, 'desktop' => $latestDesktopAudit] as $strategy => $audit)
                            <div class="col-md-6">
                                <div class="psi-col h-100">
                                    <div class="psi-col-head">
                                        <i class="ri-{{ $strategy === 'mobile' ? 'smartphone' : 'computer' }}-line me-1"></i>{{ ucfirst($strategy) }}
                                    </div>
                                    @if(!$audit)
                                        <div class="stat-mini">No {{ $strategy }} audit run yet.</div>
                                    @elseif($audit->status === 'failed')
                                        <div class="psi-error">
                                            <i class="ri-error-warning-line me-1"></i>Could not fetch {{ $strategy }} data
                                            <div class="mt-1">{{ $audit->error_message }}</div>
                                        </div>
                                        <div class="stat-mini mt-2">Attempted {{ $audit->created_at->format('d M Y, h:i A') }}</div>
                                    @else
                                        <div class="psi-gauges">
                                            @foreach([
                                                ['Performance',$audit->performance_score],
                                                ['Accessibility',$audit->accessibility_score],
                                                ['Best Practices',$audit->best_practices_score],
                                                ['SEO',$audit->seo_score],
                                            ] as [$auditLabel,$auditScore])
                                                @php($auditTier = $auditScore === null ? 'na' : ($auditScore >= 90 ? 'good' : ($auditScore >= 50 ? 'ok' : 'poor')))
                                                @php($auditColor = ['good'=>'#0cce6b','ok'=>'#ffa400','poor'=>'#ff4e42','na'=>'#c7ccd1'][$auditTier])
                                                <div class="psi-gauge-wrap">
                                                    <div class="psi-gauge" style="--psi-s:{{ $auditScore ?? 0 }};--psi-c:{{ $auditColor }}">
                                                        <div class="psi-gauge-hole">{{ $auditScore ?? '—' }}</div>
                                                    </div>
                                                    <div class="psi-gauge-label">{{ $auditLabel }}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="psi-vitals">
                                            @foreach([
                                                ['LCP',$audit->lcp_ms !== null ? number_format($audit->lcp_ms / 1000, 1).'s' : '—'],
                                                ['FCP',$audit->fcp_ms !== null ? number_format($audit->fcp_ms / 1000, 1).'s' : '—'],
                                                ['CLS',$audit->cls !== null ? number_format($audit->cls, 3) : '—'],
                                                ['TBT',$audit->tbt_ms !== null ? $audit->tbt_ms.'ms' : '—'],
                                                ['Speed Index',$audit->speed_index_ms !== null ? number_format($audit->speed_index_ms / 1000, 1).'s' : '—'],
                                            ] as [$vitalLabel,$vitalValue])
                                                <div>
                                                    <div class="stat-mini">{{ $vitalLabel }}</div>
                                                    <div style="font-size:12px;font-weight:650">{{ $vitalValue }}</div>
                                                </div>
                                            @endforeach
                                        </div>
                                        @if($audit->screenshot)
                                            <img src="{{ $audit->screenshot }}" alt="{{ ucfirst($strategy) }} screenshot" class="psi-shot {{ $strategy === 'mobile' ? 'psi-shot-mobile' : '' }} mt-3">
                                        @endif
                                        <div class="stat-mini psi-audited mt-2">Audited {{ $audit->created_at->format('d M Y, h:i A') }}</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>Instagram Audit</strong>
                <form method="post" action="{{ route('admin.prospects.instagram',$prospect) }}" class="toolbar-actions" data-instagram-form>@csrf
                    <input type="text" name="instagram" class="form-control form-control-sm" style="width:230px"
                           placeholder="instagram.com/username" value="{{ old('instagram', $latestInstagramAudit?->username) }}"
                           @if(!$instagramReady) disabled @endif>
                    <button class="btn btn-primary btn-sm" @if(!$instagramReady) disabled @endif>
                        <i class="ri-instagram-line me-1"></i>Fetch Profile
                    </button>
                </form>
            </div>
            <div class="card-body">
                <div id="instagram-progress" class="mb-3" hidden>
                    <div class="progress" style="height:6px">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" style="width:100%"></div>
                    </div>
                    <div class="stat-mini mt-1">Reading the public Instagram profile…</div>
                </div>
                @if(!$instagramReady)
                    <div class="empty-state">
                        <i class="ri-instagram-line"></i>
                        <div class="mt-2">Instagram audits are not switched on yet.</div>
                        <div class="stat-mini mt-2">
                            Instagram refuses this server's hosting IP, so audits run from the worker PC instead. Set
                            <code>INSTAGRAM_SOURCE=worker</code> and <code>INSTAGRAM_WORKER_TOKEN</code> in your
                            <code>.env</code>, run <code>php artisan config:clear</code>, and start the script in
                            <code>local_pc_instagram_script</code>.
                        </div>
                    </div>
                @elseif(!$latestInstagramAudit && !$pendingInstagramAudit)
                    <div class="empty-state"><i class="ri-instagram-line"></i><div class="mt-2">Paste the prospect's Instagram profile link above to pull their public profile.</div></div>
                @elseif(!$latestInstagramAudit)
                    <div class="empty-state">
                        <i class="ri-time-line"></i>
                        <div class="mt-2">Waiting for the worker PC to fetch <strong>{{ '@'.$pendingInstagramAudit->username }}</strong>.</div>
                        <div class="stat-mini mt-2">
                            Queued {{ $pendingInstagramAudit->created_at->diffForHumans() }}. Refresh this page in a
                            minute. If it stays here, the worker PC is switched off or its script is not running.
                        </div>
                    </div>
                @elseif($latestInstagramAudit->status === 'failed')
                    <div class="psi-error">
                        <i class="ri-error-warning-line me-1"></i>Could not read this Instagram profile
                        <div class="mt-1">{{ $latestInstagramAudit->error_message }}</div>
                    </div>
                    <div class="stat-mini mt-2">Attempted {{ $latestInstagramAudit->created_at->format('d M Y, h:i A') }}</div>
                @else
                    @php($ig = $latestInstagramAudit)
                    <div class="row g-3">
                        <div class="col-md-5">
                            <div class="psi-col h-100">
                                <div class="ig-head">
                                    @if($ig->profile_pic)
                                        <img src="{{ $ig->profile_pic }}" alt="{{ $ig->username }}" class="ig-avatar">
                                    @else
                                        <div class="ig-avatar ig-avatar-blank"><i class="ri-user-line"></i></div>
                                    @endif
                                    <div>
                                        <div class="ig-handle">
                                            {{ $ig->username }}
                                            @if($ig->is_verified)<i class="ri-verified-badge-fill text-primary"></i>@endif
                                        </div>
                                        @if($ig->full_name)<div class="ig-name">{{ $ig->full_name }}</div>@endif
                                    </div>
                                </div>
                                <div class="ig-counts">
                                    @foreach([
                                        ['posts', $ig->posts_count],
                                        ['followers', $ig->followers],
                                        ['following', $ig->following],
                                    ] as [$igLabel,$igCount])
                                        <div><strong>{{ $igCount !== null ? number_format($igCount) : '—' }}</strong> {{ $igLabel }}</div>
                                    @endforeach
                                </div>
                                @if($ig->category)<div class="stat-mini mt-2">{{ $ig->category }}</div>@endif
                                @if($ig->biography)<div class="ig-bio">{{ $ig->biography }}</div>@endif
                                @if($ig->business_address)<div class="stat-mini mt-2"><i class="ri-map-pin-line me-1"></i>{{ $ig->business_address }}</div>@endif
                                @if($ig->external_url)
                                    <div class="mt-2"><a href="{{ $ig->external_url }}" target="_blank" rel="noopener" class="text-brand" style="font-size:12px;word-break:break-all"><i class="ri-links-line me-1"></i>{{ $ig->external_url }}</a></div>
                                @endif
                                <div class="stat-mini psi-audited mt-3">
                                    <a href="{{ $ig->profile_url }}" target="_blank" rel="noopener" class="text-brand">Open profile</a>
                                    · Fetched {{ $ig->created_at->format('d M Y, h:i A') }}
                                    @if($pendingInstagramAudit)
                                        <div class="mt-1"><i class="ri-time-line me-1"></i>A refresh is queued - the worker PC will update these numbers.</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="psi-col h-100">
                                <div class="psi-col-head">Audit score · {{ $ig->overallScore() ?? '—' }}/100</div>
                                <div class="psi-gauges">
                                    @foreach([
                                        ['Profile',$ig->profile_score],
                                        ['Audience',$ig->audience_score],
                                        ['Engagement',$ig->engagement_score],
                                        ['Consistency',$ig->consistency_score],
                                    ] as [$igScoreLabel,$igScore])
                                        @php($igTier = $igScore === null ? 'na' : ($igScore >= 90 ? 'good' : ($igScore >= 50 ? 'ok' : 'poor')))
                                        @php($igColor = ['good'=>'#0cce6b','ok'=>'#ffa400','poor'=>'#ff4e42','na'=>'#c7ccd1'][$igTier])
                                        <div class="psi-gauge-wrap">
                                            <div class="psi-gauge" style="--psi-s:{{ $igScore ?? 0 }};--psi-c:{{ $igColor }}">
                                                <div class="psi-gauge-hole">{{ $igScore ?? '—' }}</div>
                                            </div>
                                            <div class="psi-gauge-label">{{ $igScoreLabel }}</div>
                                        </div>
                                    @endforeach
                                </div>
                                <div class="psi-vitals">
                                    @foreach([
                                        ['Engagement rate',$ig->engagement_rate !== null ? number_format($ig->engagement_rate, 2).'%' : '—'],
                                        ['Avg likes',$ig->avg_likes !== null ? number_format($ig->avg_likes) : '—'],
                                        ['Avg comments',$ig->avg_comments !== null ? number_format($ig->avg_comments) : '—'],
                                        ['Follower/following',$ig->follow_ratio !== null ? number_format($ig->follow_ratio, 2).'x' : '—'],
                                        ['Posts / month',$ig->posts_per_month !== null ? number_format($ig->posts_per_month, 1) : '—'],
                                        ['Last post',$ig->days_since_last_post !== null ? $ig->days_since_last_post.'d ago' : '—'],
                                    ] as [$igVitalLabel,$igVitalValue])
                                        <div>
                                            <div class="stat-mini">{{ $igVitalLabel }}</div>
                                            <div style="font-size:12px;font-weight:650">{{ $igVitalValue }}</div>
                                        </div>
                                    @endforeach
                                </div>
                                @if($ig->is_private)
                                    <div class="stat-mini mt-3"><span class="badge-soft soft-amber">Private account</span> Post data is hidden, so engagement and consistency cannot be measured.</div>
                                @elseif($ig->status === 'partial')
                                    {{-- Counts came from the profile page because Instagram answered the fuller
                                         request with 429; the account type is unknown here, so do not guess at it.
                                         Keyed off the status rather than a null engagement rate, so the reason
                                         recorded on the row is what gets shown. --}}
                                    <div class="stat-mini mt-3"><span class="badge-soft soft-amber">Limited data</span> {{ $ig->error_message ?: 'Instagram limited the detailed request, so only the public counts were read.' }}</div>
                                @elseif(!$ig->is_business)
                                    <div class="stat-mini mt-3"><span class="badge-soft soft-gray">Personal account</span> No business category or address is published on personal profiles.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between">
                <strong>Activity timeline</strong>
                <span class="text-muted" style="font-size:11px">{{ $timeline->count() }} events</span>
            </div>
            <div class="card-body">
                <div class="timeline timeline-scroll">
                    @forelse($timeline as $e)
                        @php($points = $scoreService->pointsFor($e))
                        <div class="timeline-item">
                            <div class="timeline-time">{{ $e->occurred_at?->format('d M Y, h:i A') }}</div>
                            <div class="timeline-title">
                                {{ $e->label() }}
                                @if($points > 0)<span class="badge-soft soft-green ms-1">+{{ $points }}</span>@endif
                            </div>
                            @if($e->metadata['label'] ?? false)<div class="stat-mini">{{ $e->metadata['label'] }}</div>@endif
                        </div>
                    @empty
                        <div class="empty-state"><i class="ri-time-line"></i><div class="mt-2">Nothing yet. Activity appears here as soon as the prospect opens the Smart Page.</div></div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><strong>Sales activity log</strong></div>
            <div class="card-body">
                @forelse($activities as $a)
                    <div class="py-2 border-bottom">
                        <div class="stat-mini">{{ $a->created_at->format('d M Y, h:i A') }} · {{ $a->user?->name ?: 'System' }}</div>
                        <div style="font-size:12.5px">{{ $a->notes ?: ucwords(str_replace('_',' ',$a->activity_type)) }}</div>
                    </div>
                @empty
                    <div class="stat-mini">No sales activity logged yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
