@extends('admin.layouts.app')
@section('title',$prospect->exists?'Edit Prospect':'Create Smart Link')
@section('page_title',$prospect->exists?'Edit Prospect':'Create Smart Link')
@section('page_subtitle','One form creates the prospect, the Smart Page and a unique Smart Link.')
@section('page_actions')
<a class="btn btn-light border" href="{{ route('admin.prospects.index') }}"><i class="ri-arrow-left-line me-1"></i>Back</a>
@endsection

@section('content')
@php($page = $prospect->exists ? $prospect->smartPage : null)
<form method="post" action="{{ $prospect->exists ? route('admin.prospects.update',$prospect) : route('admin.prospects.store') }}" class="card">
    <div class="card-body">
        @csrf
        @if($prospect->exists)@method('PUT')@endif

        <div class="form-section">
            <h6>Prospect information</h6>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label required">Business name</label><input class="form-control" name="business_name" value="{{ old('business_name',$prospect->business_name) }}" required></div>
                <div class="col-md-6"><label class="form-label">Contact name</label><input class="form-control" name="contact_name" value="{{ old('contact_name',$prospect->contact_name) }}"></div>
                <div class="col-md-6"><label class="form-label">Website</label><input class="form-control" name="website" value="{{ old('website',$prospect->website) }}" placeholder="https://"></div>
                <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email" value="{{ old('email',$prospect->email) }}"></div>
                <div class="col-md-4"><label class="form-label">Phone</label><input class="form-control" name="phone" value="{{ old('phone',$prospect->phone) }}" placeholder="Country code + number"></div>
                <div class="col-md-4"><label class="form-label">Industry</label><input class="form-control" name="industry" value="{{ old('industry',$prospect->industry) }}" placeholder="Pet Grooming"></div>
                <div class="col-md-4"><label class="form-label">Location</label><input class="form-control" name="location" value="{{ old('location',$prospect->location) }}" placeholder="City, State"></div>
            </div>
        </div>

        <div class="form-section">
            <h6>Sales information</h6>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Offer</label><input class="form-control" name="offer" value="{{ old('offer',$prospect->offer) }}" placeholder="What you are pitching"></div>
                <div class="col-md-6">
                    <label class="form-label">Salesperson</label>
                    <select class="form-select" name="salesperson_id" @disabled(auth()->user()->role==='salesperson')>
                        <option value="">Me ({{ auth()->user()->name }})</option>
                        @foreach($users as $u)<option value="{{ $u->id }}" @selected(old('salesperson_id',$prospect->salesperson_id)==$u->id)>{{ $u->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Short personalized message</label>
                    <textarea class="form-control" rows="4" name="personalized_message" placeholder="This appears at the top of their Smart Page.">{{ old('personalized_message',$page?->personalized_message) }}</textarea>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h6>Smart Page template</h6>
            <div class="row g-3">
                @foreach($templates as $t)
                <div class="col-md-6">
                    <label class="section-sort-row w-100" style="grid-template-columns:28px 1fr;cursor:pointer">
                        <input type="radio" name="template_id" value="{{ $t->id }}" class="form-check-input" @checked(old('template_id',$page?->template_id ?? $templates->first()->id)==$t->id) required>
                        <span>
                            <span class="section-name d-block">{{ $t->name }}</span>
                            <span class="stat-mini">{{ $t->description }}</span>
                            <span class="stat-mini d-block mt-1">{{ collect($t->sections)->map(fn($s)=>\App\Models\SmartLink\SmartPageTemplate::sectionLabel($s))->join(' · ') }}</span>
                        </span>
                    </label>
                </div>
                @endforeach
            </div>
            @if($prospect->exists)<div class="stat-mini mt-2">Changing the template enables a different set of sections. Content you already wrote is kept.</div>@endif
        </div>

        <div class="text-end">
            <button class="btn btn-primary px-4">{{ $prospect->exists ? 'Save changes' : 'Create Smart Link' }}</button>
        </div>
    </div>
</form>
@endsection
