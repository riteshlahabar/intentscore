@extends('admin.layouts.app')
@section('title','Dashboard')
@section('page_title','Dashboard')
@section('page_subtitle','Your prospects, alerts and recent activity at a glance.')
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
        ['My Prospects',$stats['prospects'],'ri-user-search-line'],
        ['Won',$stats['won'],'ri-trophy-line'],
        ['High Intent',$stats['high_intent'],'ri-fire-line'],
        ['Total Visits',$stats['visits'],'ri-eye-line'],
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
        <div class="card table-card">
            <div class="card-header"><strong>My pipeline by status</strong></div>
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
    </div>

    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header"><strong>My high-intent alerts</strong></div>
            <div class="card-body">
                <div class="timeline">
                    @forelse($alerts as $alert)
                        @if($alert->prospect)
                        <div class="timeline-item">
                            <div class="timeline-time">Score {{ $alert->score }}</div>
                            <div class="timeline-title"><a href="{{ route('admin.prospects.show',$alert->prospect) }}">{{ $alert->prospect->business_name }}</a></div>
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
            <div class="card-header"><strong>My recent activity</strong></div>
            <div class="card-body">
                <div class="timeline">
                    @forelse($activity as $a)
                        <div class="timeline-item">
                            <div class="timeline-time">{{ $a->created_at?->format('d M Y, h:i A') }}</div>
                            <div class="timeline-title">{{ $a->prospect?->business_name }} · {{ ucwords(str_replace('_',' ',$a->activity_type)) }}</div>
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
