@extends('admin.layouts.app')
@section('title','Smart Page')
@section('page_title','Smart Page')
@section('page_subtitle','Edit what '.$prospect->business_name.' sees. Turn sections on or off — no page builder needed.')
@section('page_actions')
<div class="toolbar-actions">
    <a class="btn btn-light border" href="{{ route('admin.prospects.show',$prospect) }}"><i class="ri-arrow-left-line me-1"></i>Back</a>
    @if($page->smartLink)<a class="btn btn-light border" href="{{ $page->smartLink->publicUrl() }}" target="_blank" rel="noopener"><i class="ri-eye-line me-1"></i>Preview</a>@endif
</div>
@endsection

@php
$fields = [
    'website_audit' => ['observation'=>'Current observation','problem'=>'Problem','recommendation'=>'Recommendation'],
    'instagram_audit' => ['observation'=>'Current observation','problem'=>'Problem','recommendation'=>'Recommendation'],
    'google_audit' => ['rating'=>'Current rating','reviews'=>'Review count','opportunity'=>'Opportunity','recommendation'=>'Recommendation'],
    'solution' => ['what'=>'What we recommend','why'=>'Why','benefits'=>'Key benefits (one per line)'],
];
@endphp

@section('content')
<form method="post" action="{{ route('admin.prospects.page.update',$prospect) }}" class="card">
    <div class="card-body">
        @csrf @method('PUT')

        <div class="form-section">
            <h6>Header</h6>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Heading</label><input class="form-control" name="heading" value="{{ old('heading',$page->heading) }}"></div>
                <div class="col-md-6"><label class="form-label">Sub-heading</label><input class="form-control" name="subheading" value="{{ old('subheading',$page->subheading) }}"></div>
                <div class="col-12"><label class="form-label">Personalized message</label><textarea class="form-control" rows="4" name="personalized_message">{{ old('personalized_message',$page->personalized_message) }}</textarea></div>
                <div class="col-md-4">
                    <label class="form-label">Page status</label>
                    <select class="form-select" name="status">
                        <option value="published" @selected($page->status==='published')>Published</option>
                        <option value="draft" @selected($page->status==='draft')>Draft (link shows 404)</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h6>Call to action</h6>
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label required">CTA text</label><input class="form-control" name="cta_text" value="{{ old('cta_text',$page->cta_text) }}" required></div>
                <div class="col-md-4">
                    <label class="form-label">CTA type</label>
                    <select class="form-select" name="cta_type">
                        @foreach(['whatsapp'=>'WhatsApp','email'=>'Email','calendar'=>'Calendar URL','contact'=>'Contact form / other'] as $k=>$v)
                            <option value="{{ $k }}" @selected($page->cta_type===$k)>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4"><label class="form-label">CTA destination URL</label><input class="form-control" name="cta_url" value="{{ old('cta_url',$page->cta_url) }}" placeholder="https://wa.me/91..."></div>
            </div>
        </div>

        <div class="form-section">
            <h6>Sections</h6>
            @foreach($page->sections as $section)
                @php($type = $section->section_type)
                <div class="card mb-2">
                    <div class="card-header d-flex align-items-center gap-3">
                        <div class="form-check form-switch m-0">
                            <input type="hidden" name="sections[{{ $section->id }}][enabled]" value="0">
                            <input class="form-check-input" type="checkbox" name="sections[{{ $section->id }}][enabled]" value="1" @checked($section->enabled)>
                        </div>
                        <strong class="flex-fill">{{ \App\Models\SmartLink\SmartPageTemplate::sectionLabel($type) }}</strong>
                        <div style="width:90px"><input class="form-control form-control-sm" type="number" name="sections[{{ $section->id }}][display_order]" value="{{ $section->display_order }}" title="Display order"></div>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label">Section title</label><input class="form-control" name="sections[{{ $section->id }}][title]" value="{{ $section->title }}"></div>

                            @foreach($fields[$type] ?? [] as $key=>$label)
                                <div class="col-md-6"><label class="form-label">{{ $label }}</label>
                                    @if(in_array($key,['rating','reviews']))
                                        <input class="form-control" name="sections[{{ $section->id }}][data][{{ $key }}]" value="{{ $section->field($key) }}">
                                    @else
                                        <textarea class="form-control" rows="3" name="sections[{{ $section->id }}][data][{{ $key }}]">{{ $section->field($key) }}</textarea>
                                    @endif
                                </div>
                            @endforeach

                            @if($type === 'free_tools')
                                <div class="col-12">
                                    <label class="form-label">Calculators shown on this page</label>
                                    @php($enabledTools = $section->data['tools'] ?? ['revenue','no_show','profit'])
                                    <div class="d-flex gap-3 flex-wrap">
                                        @foreach(['revenue'=>'Grooming Revenue Calculator','no_show'=>'No-Show Calculator','profit'=>'Profit Calculator'] as $k=>$v)
                                            <label class="form-check">
                                                <input class="form-check-input" type="checkbox" name="sections[{{ $section->id }}][data][tools][]" value="{{ $k }}" @checked(in_array($k,$enabledTools))>
                                                <span class="form-check-label" style="font-size:12.5px">{{ $v }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if($type === 'portfolio')
                                <div class="col-12">
                                    <label class="form-label">Portfolio images</label>
                                    <div class="d-flex gap-2 flex-wrap mb-2">
                                        @forelse($section->data['images'] ?? [] as $i=>$img)
                                            <div class="text-center">
                                                <img class="preview-cover" src="{{ asset($img['path']) }}" alt="">
                                                <div class="stat-mini">{{ $img['caption'] ?? '' }}</div>
                                            </div>
                                        @empty
                                            <div class="stat-mini">No images added yet.</div>
                                        @endforelse
                                    </div>
                                    <div class="stat-mini">Use the upload box below the form to add images.</div>
                                </div>
                            @endif

                            <div class="col-12">
                                <label class="form-label">{{ $type==='intro' ? 'Intro text' : 'Additional text (optional)' }}</label>
                                <textarea class="form-control" rows="3" name="sections[{{ $section->id }}][content]">{{ $section->content }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="text-end"><button class="btn btn-primary px-4">Save Smart Page</button></div>
    </div>
</form>

@php($portfolio = $page->sections->firstWhere('section_type','portfolio'))
@if($portfolio)
<div class="card mt-3">
    <div class="card-header"><strong>Portfolio images</strong></div>
    <div class="card-body">
        <form method="post" action="{{ route('admin.prospects.page.portfolio.store',$prospect) }}" enctype="multipart/form-data" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-5"><label class="form-label">Image</label><input class="form-control" type="file" name="image" accept="image/*" required></div>
            <div class="col-md-5"><label class="form-label">Caption</label><input class="form-control" name="caption"></div>
            <div class="col-md-2"><button class="btn btn-primary w-100">Upload</button></div>
        </form>

        <div class="d-flex gap-3 flex-wrap mt-3">
            @foreach($portfolio->data['images'] ?? [] as $i=>$img)
                <div class="text-center">
                    <img class="preview-cover" src="{{ asset($img['path']) }}" alt="">
                    <div class="stat-mini">{{ $img['caption'] ?? '' }}</div>
                    <form method="post" action="{{ route('admin.prospects.page.portfolio.destroy',$prospect) }}">@csrf @method('DELETE')
                        <input type="hidden" name="index" value="{{ $i }}">
                        <button class="btn btn-light border btn-sm mt-1" data-confirm="Remove this image?"><i class="ri-delete-bin-line"></i></button>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endif
@endsection
