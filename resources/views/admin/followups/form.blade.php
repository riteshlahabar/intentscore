@extends('admin.layouts.app')

@section('title', $followUp->exists ? 'Edit Follow-up' : 'Add Follow-up')
@section('page_title', $followUp->exists ? 'Edit Follow-up' : 'Add Follow-up')
@section('page_subtitle', 'Keep the next sales action clear and assigned.')

@section('content')
    <form class="card" method="post" action="{{ $followUp->exists ? route('admin.followups.update', $followUp) : route('admin.followups.store') }}">
        <div class="card-body">
            @csrf
            @if($followUp->exists) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label required">Lead</label>
                    <select class="form-select" name="lead_id" required>
                        <option value="">Select lead</option>
                        @foreach($leads as $lead)
                            <option value="{{ $lead->id }}" @selected(old('lead_id', $followUp->lead_id) == $lead->id)>
                                {{ $lead->title }}{{ $lead->client ? ' — '.$lead->client->company_name : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label required">Date & time</label>
                    <input
                        type="datetime-local"
                        class="form-control"
                        name="follow_up_at"
                        value="{{ old('follow_up_at', $followUp->follow_up_at?->format('Y-m-d\TH:i')) }}"
                        required
                    >
                </div>
                <div class="col-md-3">
                    <label class="form-label required">Type</label>
                    <select class="form-select" name="type" required>
                        @foreach(['call', 'whatsapp', 'email', 'meeting', 'other'] as $value)
                            <option value="{{ $value }}" @selected(old('type', $followUp->type ?: 'call') === $value)>{{ ucfirst($value) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Assigned to</label>
                    <select class="form-select" name="user_id">
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected(old('user_id', $followUp->user_id ?: auth()->id()) == $user->id)>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label required">Status</label>
                    <select class="form-select" name="status" required>
                        @foreach(['pending', 'completed', 'cancelled'] as $value)
                            <option value="{{ $value }}" @selected(old('status', $followUp->status ?: 'pending') === $value)>{{ ucfirst($value) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" name="notes" rows="5" maxlength="10000">{{ old('notes', $followUp->notes) }}</textarea>
                </div>
            </div>

            <div class="text-end mt-3">
                <a class="btn btn-light border" href="{{ route('admin.followups.index') }}">Cancel</a>
                <button class="btn btn-primary">Save follow-up</button>
            </div>
        </div>
    </form>
@endsection
