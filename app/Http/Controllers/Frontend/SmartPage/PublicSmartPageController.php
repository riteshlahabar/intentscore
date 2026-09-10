<?php

namespace App\Http\Controllers\Frontend\SmartPage;

use App\Http\Controllers\Controller;
use App\Models\Setting\Setting;
use App\Models\SmartLink\SmartLinkModel;
use App\Models\SmartLink\SmartPageTemplate;
use App\Services\SmartLink\SmartTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicSmartPageController extends Controller
{
    /**
     * Public Smart Page at /{link}/{name}.
     *
     * The name segment is cosmetic — it is not used to find the record. If it does not
     * match the prospect's current business name (renamed prospect, hand-edited URL,
     * link shared after a rename) the visitor is sent to the canonical address instead
     * of being shown a 404, so links already in a client's inbox keep working.
     */
    public function show(SmartLinkModel $link, string $name): View|RedirectResponse
    {
        $page = $this->pageFor($link);

        if ($name !== $link->nameSlug()) {
            return redirect()->to($link->publicUrl(), 301);
        }

        $design = $page->template?->design;
        $view = in_array($design, SmartPageTemplate::DESIGNS, true)
            ? "frontend.smart-page.designs.{$design}"
            : 'frontend.smart-page.show';

        return view($view, [
            'page' => $page,
            'prospect' => $page->prospect,
            'sections' => $page->sections->where('enabled', true),
            'settings' => Setting::pluck('value', 'key'),
            'mobileAudit' => $page->prospect->latestAuditFor('mobile'),
            'desktopAudit' => $page->prospect->latestAuditFor('desktop'),
        ]);
    }

    /**
     * Legacy /s/{slug} address, kept because links in that form are already with
     * clients. Redirects permanently to the /{id}/{name} form.
     */
    public function legacy(string $slug): RedirectResponse
    {
        $link = SmartLinkModel::with('prospect')->where('slug', $slug)->firstOrFail();

        return redirect()->to($link->publicUrl(), 301);
    }

    public function track(Request $request, SmartLinkModel $link, SmartTrackingService $tracking): JsonResponse
    {
        $page = $this->pageFor($link);

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

    private function pageFor(SmartLinkModel $link)
    {
        abort_unless($link->isActive(), 410, 'This link is no longer active.');

        $page = $link->smartPage()->with(['prospect', 'sections', 'template'])->firstOrFail();

        abort_unless($page->status === 'published', 404);

        return $page;
    }
}
