@extends('admin.layouts.app')
@section('title','My Prospects')
@section('page_title','My Prospects')
@section('page_subtitle','Prospects ranked by what they actually did on their Smart Page.')
@section('page_actions')
<a class="btn btn-primary" href="{{ route('admin.prospects.create') }}"><i class="ri-add-line me-1"></i>Create Smart Link</a>
@endsection

@section('content')

@foreach($alerts as $alert)
    @if($alert->prospect)
    <div class="alert alert-warning d-flex align-items-center justify-content-between py-2">
        <div><i class="ri-fire-fill me-1 text-brand"></i><strong>{{ $alert->prospect->business_name }}</strong> is showing HIGH INTENT ({{ $alert->score }}).</div>
        <a class="btn btn-sm btn-primary" href="{{ route('admin.prospects.show',$alert->prospect) }}">Open</a>
    </div>
    @endif
@endforeach

<form class="filter-panel" method="get">
    <div class="row g-2">
        <div class="col-lg-4"><input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search business, contact or email"></div>
        <div class="col-lg-2">
            <select class="form-select" name="level">
                <option value="">All intent levels</option>
                @foreach(array_keys(\App\Models\SmartLink\IntentScore::LEVELS) as $lvl)
                    <option value="{{ $lvl }}" @selected(request('level')===$lvl)>{{ $lvl }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2">
            <select class="form-select" name="status">
                <option value="">All statuses</option>
                @foreach(\App\Models\SmartLink\Prospect::STATUSES as $st)
                    <option value="{{ $st }}" @selected(request('status')===$st)>{{ ucwords(str_replace('_',' ',$st)) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2">
            <select class="form-select" name="sort">
                @foreach(['last_activity'=>'Sort: Last activity','intent'=>'Sort: Intent','name'=>'Sort: Prospect name'] as $key=>$label)
                    <option value="{{ $key }}" @selected($sort===$key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 d-flex gap-1">
            <button class="btn btn-primary flex-fill">Filter</button>
            <a class="btn btn-light border" href="{{ route('admin.prospects.index') }}">Reset</a>
        </div>
    </div>
</form>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table">
            <thead><tr><th>Prospect</th><th>Intent</th><th>Score</th><th>Last activity</th><th>Visits</th><th>Status</th><th>Smart Link</th><th></th></tr></thead>
            <tbody>
            @forelse($prospects as $p)
                <tr>
                    <td>
                        <a class="fw-semibold text-decoration-none text-brand" href="{{ route('admin.prospects.show',$p) }}">{{ $p->business_name }}</a>
                        <div class="stat-mini">{{ $p->contact_name ?: '—' }}@if($p->salesperson) · {{ $p->salesperson->name }}@endif</div>
                    </td>
                    <td><span class="badge-soft {{ $p->intentScore?->badgeClass() ?: 'soft-gray' }}">{{ $p->intentScore?->intent_level ?: 'LOW' }}</span></td>
                    <td class="fw-bold">{{ $p->intentScore?->score ?: 0 }}</td>
                    <td>{{ $p->last_activity_at ? \Illuminate\Support\Carbon::parse($p->last_activity_at)->diffForHumans() : 'No activity yet' }}</td>
                    <td>{{ $p->visits_count }}</td>
                    <td><x-admin.status-badge :value="$p->status"/></td>
                    <td>
                        @if($p->smartLink)
                            <button type="button" class="action-btn" title="Copy Smart Link" data-copy="{{ $p->smartLink->publicUrl() }}"><i class="ri-file-copy-line"></i></button>
                            <a class="action-btn" href="{{ $p->smartLink->publicUrl() }}" target="_blank" rel="noopener" title="Open page"><i class="ri-external-link-line"></i></a>
                        @else — @endif
                    </td>
                    <td class="text-end"><a class="action-btn" href="{{ route('admin.prospects.show',$p) }}"><i class="ri-arrow-right-line"></i></a></td>
                </tr>
            @empty
                <tr><td colspan="8"><div class="empty-state"><i class="ri-links-line"></i><div class="mt-2">No prospects yet. Create your first Smart Link.</div></div></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="px-3 pb-2">{{ $prospects->links() }}</div>
</div>
@endsection
