@extends('admin.layouts.app')

@section('title', 'Follow-ups')
@section('page_title', 'Follow-ups')
@section('page_subtitle', 'Plan calls, WhatsApp follow-ups, meetings and other next actions.')

@section('page_actions')
    <x-admin.data-tools
        :import-route="route('admin.followups.import')"
        :export-route="route('admin.followups.export', request()->query())"
        :create-route="route('admin.followups.create')"
        create-label="Add follow-up"
    />
@endsection

@section('content')
    <form class="filter-panel" method="get">
        <div class="row g-2">
            <div class="col-lg-3">
                <input class="form-control" name="q" value="{{ request('q') }}" placeholder="Search lead, client or note">
            </div>
            <div class="col-lg-2">
                <select class="form-select" name="status">
                    <option value="">All statuses</option>
                    @foreach(['pending', 'completed', 'cancelled'] as $value)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ ucfirst($value) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <select class="form-select" name="type">
                    <option value="">All types</option>
                    @foreach(['call', 'whatsapp', 'email', 'meeting', 'other'] as $value)
                        <option value="{{ $value }}" @selected(request('type') === $value)>{{ ucfirst($value) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2">
                <input type="date" class="form-control" name="date_from" value="{{ request('date_from') }}" title="From date">
            </div>
            <div class="col-lg-2">
                <input type="date" class="form-control" name="date_to" value="{{ request('date_to') }}" title="To date">
            </div>
            <div class="col-lg-1 d-flex gap-1">
                <button class="btn btn-primary" title="Apply filters"><i class="ri-search-line"></i></button>
                <a class="btn btn-light border" href="{{ route('admin.followups.index') }}" title="Reset"><i class="ri-refresh-line"></i></a>
            </div>
        </div>
    </form>

    <div class="card table-card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date & time</th>
                        <th>Lead / Client</th>
                        <th>Type</th>
                        <th>Assigned</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($followUps as $followUp)
                        @php($isOverdue = $followUp->status === 'pending' && $followUp->follow_up_at?->isPast())
                        <tr>
                            <td>
                                <strong class="{{ $isOverdue ? 'text-danger' : '' }}">{{ $followUp->follow_up_at?->format('d M Y') }}</strong>
                                <div class="stat-mini">{{ $followUp->follow_up_at?->format('h:i A') }}{{ $isOverdue ? ' · Overdue' : '' }}</div>
                            </td>
                            <td>
                                <strong>{{ $followUp->lead?->title ?: '—' }}</strong>
                                <div class="stat-mini">{{ $followUp->lead?->client?->company_name ?: 'No client' }}</div>
                            </td>
                            <td>{{ ucfirst($followUp->type) }}</td>
                            <td>{{ $followUp->user?->name ?: '—' }}</td>
                            <td><x-admin.status-badge :value="$followUp->status" /></td>
                            <td>{{ \Illuminate\Support\Str::limit($followUp->notes, 60) ?: '—' }}</td>
                            <td class="text-end">
                                <a class="action-btn" href="{{ route('admin.followups.edit', $followUp) }}" title="Edit"><i class="ri-edit-line"></i></a>
                                <form class="d-inline" method="post" action="{{ route('admin.followups.destroy', $followUp) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="action-btn" data-confirm="Delete this follow-up?" title="Delete"><i class="ri-delete-bin-line"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="empty-state">No follow-ups match your filters.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-3 pb-2">{{ $followUps->links() }}</div>
    </div>
@endsection
