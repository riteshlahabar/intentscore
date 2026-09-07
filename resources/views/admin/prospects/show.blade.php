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
                <form method="post" action="{{ route('admin.prospects.audit',$prospect) }}" id="audit-form">@csrf
                    <button class="btn btn-primary btn-sm" id="audit-run-btn" @if(!$prospect->website) disabled @endif><i class="ri-speed-up-line me-1"></i>Run audit</button>
                </form>
            </div>
            <div class="card-body">
                <div id="audit-progress" class="mb-3" hidden>
                    <div class="progress" style="height:6px">
                        <div class="progress-bar progress-bar-striped progress-bar-animated bg-warning" style="width:100%"></div>
                    </div>
                    <div class="stat-mini mt-1">Running PageSpeed audit for mobile and desktop — this can take up to a minute…</div>
                </div>
                @if(!$prospect->website)
                    <div class="empty-state"><i class="ri-global-line"></i><div class="mt-2">Add a website URL to this prospect to run a PageSpeed audit.</div></div>
                @elseif(!$latestMobileAudit && !$latestDesktopAudit)
                    <div class="empty-state"><i class="ri-speed-up-line"></i><div class="mt-2">No audit run yet for {{ $prospect->website }}.</div></div>
                @else
                    @if($latestMobileAudit && $latestDesktopAudit)
                        <input type="radio" name="psi-admin" id="psi-admin-mobile" hidden checked>
                        <input type="radio" name="psi-admin" id="psi-admin-desktop" hidden>
                        <div class="psi-tabs">
                            <label for="psi-admin-mobile">Mobile</label>
                            <label for="psi-admin-desktop">Desktop</label>
                        </div>
                    @endif
                    @foreach(['mobile' => $latestMobileAudit, 'desktop' => $latestDesktopAudit] as $strategy => $audit)
                        @continue(!$audit)
                        <div class="psi-panel psi-panel-{{ $strategy }}" @if(!$latestMobileAudit || !$latestDesktopAudit) style="display:grid" @endif>
                            @if($audit->status === 'failed')
                                <div class="stat-mini" style="color:var(--danger)">{{ ucfirst($strategy) }} audit failed: {{ $audit->error_message }}</div>
                            @else
                                <div>
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
                                            ['CLS',$audit->cls ?? '—'],
                                            ['TBT',$audit->tbt_ms !== null ? $audit->tbt_ms.'ms' : '—'],
                                            ['Speed Index',$audit->speed_index_ms !== null ? number_format($audit->speed_index_ms / 1000, 1).'s' : '—'],
                                        ] as [$vitalLabel,$vitalValue])
                                            <div>
                                                <div class="stat-mini">{{ $vitalLabel }}</div>
                                                <div style="font-size:12px;font-weight:650">{{ $vitalValue }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="stat-mini mt-2">{{ $audit->created_at->format('d M Y, h:i A') }}</div>
                                </div>
                                @if($audit->screenshot)
                                    <img src="{{ $audit->screenshot }}" alt="{{ ucfirst($strategy) }} screenshot" class="psi-shot">
                                @endif
                            @endif
                        </div>
                    @endforeach
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
