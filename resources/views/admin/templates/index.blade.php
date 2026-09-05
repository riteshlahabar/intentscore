@extends('admin.layouts.app')
@section('title','Smart Templates')
@section('page_title','Smart Templates')
@section('page_subtitle','The page designs available when creating a Smart Link. Click a template to see how it looks.')

@section('content')
<div class="row g-3">
    @foreach($templates as $template)
        <div class="col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <strong>{{ $template->name }}</strong>
                        <span class="badge-soft soft-blue">{{ $template->industry }}</span>
                    </div>
                    <p class="text-muted flex-grow-1">{{ $template->description }}</p>
                    @if($template->design)
                        <a class="btn btn-primary" href="{{ route('admin.templates.preview', $template) }}" target="_blank" rel="noopener">
                            <i class="ri-eye-line me-1"></i>Preview design
                        </a>
                    @else
                        <span class="empty-state mb-0">No preview available for this template.</span>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
