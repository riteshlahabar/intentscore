@extends('admin.layouts.app')
@section('title','Smart Links Overview')
@section('page_title','Smart Links Overview')
@section('page_subtitle','Everything happening across all prospects and Smart Pages.')
@section('page_actions')
<a class="btn btn-primary" href="{{ route('admin.prospects.create') }}"><i class="ri-add-line me-1"></i>Create Smart Link</a>
@endsection

@section('content')
<div class="row g-3 mb-3">
    @foreach([
        ['Total Prospects',$stats['prospects'],'ri-user-search-line'],
        ['Total Smart Links',$stats['smart_links'],'ri-links-line'],
        ['Total Visits',$stats['visits'],'ri-eye-line'],
        ['Returning Visitors',$stats['returning'],'ri-repeat-line'],
        ['Interested',$stats['interested'],'ri-thumb-up-line'],
        ['High Intent',$stats['high_intent'],'ri-fire-line'],
    ] as $s)
        <div class="col-6 col-md-4 col-xl-2">
            <div class="metric-card d-flex justify-content-between">
                <div><div class="metric-label">{{ $s[0] }}</div><div class="metric-value">{{ $s[1] }}</div></div>
                <div class="metric-icon"><i class="{{ $s[2] }}"></i></div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-xl-7">
        <div class="card table-card mb-3">
            <div class="card-header"><strong>Highest intent prospects</strong></div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Prospect</th><th>Salesperson</th><th>Intent</th><th>Score</th><th></th></tr></thead>
                    <tbody>
                    @forelse($hot as $row)
                        @if($row->prospect)
                        <tr>
                            <td class="fw-semibold">{{ $row->prospect->business_name }}</td>
                            <td>{{ $row->prospect->salesperson?->name ?: '—' }}</td>
                            <td><span class="badge-soft {{ $row->badgeClass() }}">{{ $row->intent_level }}</span></td>
                            <td class="fw-bold">{{ $row->score }}</td>
                            <td class="text-end"><a class="action-btn" href="{{ route('admin.prospects.show',$row->prospect) }}"><i class="ri-arrow-right-line"></i></a></td>
                        </tr>
                        @endif
                    @empty
                        <tr><td colspan="5"><div class="empty-state">No scored prospects yet.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card table-card">
            <div class="card-header"><strong>Salespeople</strong></div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Salesperson</th><th>Prospects</th><th>Visits</th><th>High Intent</th></tr></thead>
                    <tbody>
                    @forelse($salespeople as $row)
                        <tr>
                            <td><strong>{{ $row->name }}</strong><div class="stat-mini">{{ ucwords(str_replace('_',' ',$row->role)) }}</div></td>
                            <td>{{ $row->prospects }}</td>
                            <td>{{ $row->visits }}</td>
                            <td class="fw-bold">{{ $row->high_intent }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="empty-state">No prospects assigned yet.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header"><strong>Recent activity</strong></div>
            <div class="card-body">
                <div class="timeline">
                    @forelse($recent as $e)
                        <div class="timeline-item">
                            <div class="timeline-time">{{ $e->occurred_at?->format('d M Y, h:i A') }}</div>
                            <div class="timeline-title">{{ $e->prospect?->business_name }} · {{ $e->label() }}</div>
                        </div>
                    @empty
                        <div class="empty-state">No activity yet.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
