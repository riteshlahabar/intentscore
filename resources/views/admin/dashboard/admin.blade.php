@extends('admin.layouts.app')
@section('title','Dashboard')
@section('page_title','Dashboard')
@section('page_subtitle','Pipeline health and quick actions across the whole team.')
@section('page_actions')
<a class="btn btn-primary" href="{{ route('admin.prospects.create') }}"><i class="ri-add-line me-1"></i>Create Smart Link</a>
@endsection

@php
    $statusMeta = [
        'new' => ['New', 'soft-blue'],
        'contacted' => ['Contacted', 'soft-amber'],
        'follow_up' => ['Follow-up', 'soft-amber'],
        'meeting' => ['Meeting', 'soft-blue'],
        'won' => ['Won', 'soft-green'],
        'lost' => ['Lost', 'soft-red'],
    ];
@endphp

@section('content')
<div class="row g-3 mb-3">
    @foreach([
        ['Total Prospects',$stats['prospects'],'ri-user-search-line'],
        ['New This Week',$stats['new_this_week'],'ri-calendar-event-line'],
        ['Total Smart Links',$stats['smart_links'],'ri-links-line'],
        ['Unseen High-Intent Alerts',$stats['unseen_alerts'],'ri-fire-line'],
    ] as $s)
        <div class="col-6 col-md-3">
            <div class="metric-card d-flex justify-content-between">
                <div><div class="metric-label">{{ $s[0] }}</div><div class="metric-value">{{ $s[1] }}</div></div>
                <div class="metric-icon"><i class="{{ $s[2] }}"></i></div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-xl-4">
        <div class="card table-card mb-3">
            <div class="card-header"><strong>Pipeline by status</strong></div>
            <div class="table-responsive">
                <table class="table">
                    <tbody>
                    @foreach(\App\Models\SmartLink\Prospect::STATUSES as $status)
                        @php([$label, $badge] = $statusMeta[$status])
                        <tr>
                            <td><span class="badge-soft {{ $badge }}">{{ $label }}</span></td>
                            <td class="text-end fw-bold">{{ $statusCounts[$status] ?? 0 }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card table-card">
            <div class="card-header"><strong>Top salespeople</strong></div>
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Name</th><th>Prospects</th><th>Won</th><th>High Intent</th></tr></thead>
                    <tbody>
                    @forelse($leaderboard as $row)
                        <tr>
                            <td><strong>{{ $row->name }}</strong></td>
                            <td>{{ $row->prospects }}</td>
                            <td class="fw-bold">{{ $row->won }}</td>
                            <td>{{ $row->high_intent }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4"><div class="empty-state">No prospects assigned yet.</div></td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header"><strong>High-intent alerts</strong></div>
            <div class="card-body">
                <div class="timeline">
                    @forelse($alerts as $alert)
                        @if($alert->prospect)
                        <div class="timeline-item">
                            <div class="timeline-time">Score {{ $alert->score }}</div>
                            <div class="timeline-title">{{ $alert->prospect->business_name }} · {{ $alert->prospect->salesperson?->name ?: '—' }}</div>
                        </div>
                        @endif
                    @empty
                        <div class="empty-state">No unseen alerts.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header"><strong>Recent sales activity</strong></div>
            <div class="card-body">
                <div class="timeline">
                    @forelse($activity as $a)
                        <div class="timeline-item">
                            <div class="timeline-time">{{ $a->created_at?->format('d M Y, h:i A') }}</div>
                            <div class="timeline-title">{{ $a->prospect?->business_name }} · {{ ucwords(str_replace('_',' ',$a->activity_type)) }} · {{ $a->user?->name ?: '—' }}</div>
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
