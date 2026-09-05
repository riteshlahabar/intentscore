<?php

namespace App\Http\Controllers\Frontend\SmartPage;

use App\Http\Controllers\Controller;
use App\Models\Setting\Setting;
use App\Models\SmartLink\SmartLinkModel;
use App\Models\SmartLink\SmartPageTemplate;
use App\Services\SmartLink\SmartTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicSmartPageController extends Controller
{
    public function show(string $slug): View
    {
        $page = $this->resolve($slug);

        $design = $page->template?->design;
        $view = in_array($design, SmartPageTemplate::DESIGNS, true)
            ? "frontend.smart-page.designs.{$design}"
            : 'frontend.smart-page.show';

        return view($view, [
            'page' => $page,
            'prospect' => $page->prospect,
            'sections' => $page->sections->where('enabled', true),
            'settings' => Setting::pluck('value', 'key'),
        ]);
    }

    public function track(Request $request, string $slug, SmartTrackingService $tracking): JsonResponse
    {
        $page = $this->resolve($slug);

        $data = $request->validate([
            'session_id' => ['required', 'uuid'],
            'visitor_id' => ['nullable', 'uuid'],
            'event_type' => ['required', 'string', 'max:60'],
            'section_type' => ['nullable', 'string', 'max:60'],
            'duration_ms' => ['nullable', 'integer', 'min:0', 'max:1800000'],
            'metadata' => ['nullable', 'array'],
        ]);

        $tracking->track($page, $request, $data);

        return response()->json(['ok' => true]);
    }

    private function resolve(string $slug)
    {
        $link = SmartLinkModel::where('slug', $slug)->firstOrFail();

        abort_unless($link->isActive(), 410, 'This link is no longer active.');

        $page = $link->smartPage()->with(['prospect', 'sections', 'template'])->firstOrFail();

        abort_unless($page->status === 'published', 404);

        return $page;
    }
}
