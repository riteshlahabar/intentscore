<?php

namespace App\Http\Controllers\Admin\Prospect;

use App\Http\Controllers\Controller;
use App\Models\SmartLink\Prospect;
use App\Models\SmartLink\SmartPageTemplate;
use App\Services\Common\AccessService;
use App\Services\Common\UploadService;
use Illuminate\Http\Request;

/**
 * Content editing for a Smart Page (PDF section 16).
 * Fixed section list, enable/disable and ordering only - no drag-and-drop builder.
 */
class SmartPageController extends Controller
{
    public function __construct(
        private AccessService $access,
        private UploadService $uploads,
    ) {
    }

    public function edit(Prospect $prospect)
    {
        $this->authorize('update', $prospect);

        $page = $prospect->smartPage()->with('sections')->firstOrFail();

        return view('admin.prospects.page', [
            'prospect' => $prospect,
            'page' => $page,
            'templates' => SmartPageTemplate::where('is_active', true)->get(),
        ]);
    }

    public function update(Request $request, Prospect $prospect)
    {
        $this->authorize('update', $prospect);

        $page = $prospect->smartPage()->with('sections')->firstOrFail();

        $data = $request->validate([
            'heading' => ['nullable', 'string', 'max:180'],
            'subheading' => ['nullable', 'string', 'max:250'],
            'personalized_message' => ['nullable', 'string', 'max:5000'],
            'cta_text' => ['required', 'string', 'max:80'],
            'cta_type' => ['required', 'in:whatsapp,email,calendar,contact'],
            'cta_url' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:draft,published'],
            'sections' => ['array'],
            'sections.*.enabled' => ['nullable', 'boolean'],
            'sections.*.title' => ['nullable', 'string', 'max:180'],
            'sections.*.content' => ['nullable', 'string', 'max:8000'],
            'sections.*.display_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'sections.*.data' => ['nullable', 'array'],
        ]);

        $page->update([
            'heading' => $data['heading'] ?? null,
            'subheading' => $data['subheading'] ?? null,
            'personalized_message' => $data['personalized_message'] ?? null,
            'cta_text' => $data['cta_text'],
            'cta_type' => $data['cta_type'],
            'cta_url' => $data['cta_url'] ?? null,
            'status' => $data['status'],
        ]);

        foreach ($data['sections'] ?? [] as $sectionId => $values) {
            $section = $page->sections->firstWhere('id', (int) $sectionId);

            if (! $section) {
                continue;
            }

            $incoming = array_map(
                fn ($value) => is_string($value) ? trim($value) : $value,
                $values['data'] ?? []
            );

            // Unchecking every calculator posts no "tools" key at all, so without this
            // the merge below would silently keep the previous selection.
            if ($section->section_type === 'free_tools' && ! isset($incoming['tools'])) {
                $incoming['tools'] = [];
            }

            $section->update([
                'enabled' => (bool) ($values['enabled'] ?? false),
                'title' => $values['title'] ?? $section->title,
                'content' => $values['content'] ?? null,
                'display_order' => (int) ($values['display_order'] ?? $section->display_order),
                // Merge so keys the form does not post (portfolio images) survive a save.
                'data' => array_merge($section->data ?? [], $incoming),
            ]);
        }

        return redirect()->route('admin.prospects.page.edit', $prospect)
            ->with('success', 'Smart Page saved.');
    }

    public function uploadPortfolio(Request $request, Prospect $prospect)
    {
        $this->authorize('update', $prospect);

        $request->validate([
            'image' => ['required', 'file', 'image', 'max:4096'],
            'caption' => ['nullable', 'string', 'max:150'],
        ]);

        $page = $prospect->smartPage()->with('sections')->firstOrFail();
        $section = $page->sections->firstWhere('section_type', 'portfolio');

        abort_unless($section, 404, 'This page has no portfolio section.');

        $path = $this->uploads->store($request->file('image'), 'smart-pages/'.$prospect->id);

        $data = $section->data ?? [];
        $images = $data['images'] ?? [];
        $images[] = ['path' => $path, 'caption' => $request->string('caption')->toString()];
        $data['images'] = array_slice($images, 0, 12);

        $section->update(['data' => $data]);

        return back()->with('success', 'Portfolio image added.');
    }

    public function deletePortfolio(Request $request, Prospect $prospect)
    {
        $this->authorize('update', $prospect);

        $index = (int) $request->validate(['index' => ['required', 'integer', 'min:0']])['index'];

        $page = $prospect->smartPage()->with('sections')->firstOrFail();
        $section = $page->sections->firstWhere('section_type', 'portfolio');

        abort_unless($section, 404);

        $data = $section->data ?? [];
        $images = $data['images'] ?? [];

        if (isset($images[$index])) {
            $this->uploads->delete($images[$index]['path'] ?? null);
            unset($images[$index]);
            $data['images'] = array_values($images);
            $section->update(['data' => $data]);
        }

        return back()->with('success', 'Portfolio image removed.');
    }
}
