<?php

namespace App\Http\Controllers\Frontend\Presentation;

use App\Http\Controllers\Controller;
use App\Models\Presentation\Presentation;
use App\Models\Setting\Setting;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class PublicPresentationController extends Controller
{
    public function show(string $token): View|Response
    {
        $presentation = Presentation::with([
            'client',
            'owner',
            'product.features' => fn ($query) => $query->where('is_active', true),
            'product.demoLinks' => fn ($query) => $query->where('is_active', true),
            'product.media' => fn ($query) => $query->where('is_active', true),
            'sections' => fn ($query) => $query
                ->where('is_enabled', true)
                ->orderBy('sort_order'),
        ])
            ->where('public_token', $token)
            ->firstOrFail();

        if (! $presentation->isPubliclyAvailable()) {
            return response()->view(
                'frontend.presentation.expired',
                ['presentation' => $presentation],
                410
            );
        }

        return view('frontend.presentation.show', [
            'presentation' => $presentation,
            'settings' => Setting::pluck('value', 'key'),
        ]);
    }

    public function track(
        Request $request,
        string $token,
        AnalyticsService $analytics
    ): JsonResponse {
        $presentation = Presentation::where('public_token', $token)->firstOrFail();
        abort_unless($presentation->isPubliclyAvailable(), 410);

        $data = $request->validate([
            'session_uuid' => ['required', 'uuid'],
            'visitor_uuid' => ['nullable', 'uuid'],
            'event_type' => ['required', 'string', 'max:60'],
            'section_key' => ['nullable', 'string', 'max:60'],
            'element_key' => ['nullable', 'string', 'max:120'],
            'target_url' => ['nullable', 'string', 'max:2000'],
            'duration_ms' => ['nullable', 'integer', 'min:0', 'max:1800000'],
            'referrer' => ['nullable', 'string', 'max:2000'],
            'source' => ['nullable', 'string', 'max:60'],
            'meta' => ['nullable', 'array'],
        ]);

        $analytics->track($presentation, $request, $data);

        return response()->json(['ok' => true]);
    }
}
