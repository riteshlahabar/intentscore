<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Analytics\PresentationEvent;
use App\Models\Analytics\PresentationSession;
use App\Models\Client\Client;
use App\Models\Lead\Lead;
use App\Models\Presentation\Presentation;
use App\Models\Product\Product;
use App\Services\Common\AccessService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly AccessService $access)
    {
    }

    public function index(): View
    {
        $clientQuery = $this->access->scopeOwned(Client::query());
        $leadQuery = $this->access->scopeOwned(Lead::query());
        $presentationQuery = $this->access->scopeOwned(Presentation::query());

        $stats = [
            'clients' => (clone $clientQuery)->count(),
            'leads' => (clone $leadQuery)->whereNotIn('status', ['won', 'lost'])->count(),
            'products' => Product::where('status', 'active')->count(),
            'presentations' => (clone $presentationQuery)->count(),
            'hot' => (clone $presentationQuery)->where('engagement_score', '>=', 70)->count(),
            'won' => (clone $leadQuery)->where('status', 'won')->count(),
        ];

        $hot = $this->access
            ->scopeOwned(Presentation::with(['client', 'product']))
            ->orderByDesc('engagement_score')
            ->limit(8)
            ->get();

        $events = PresentationEvent::with(['presentation.client'])
            ->when(
                $this->access->isSalesperson(),
                fn ($query) => $query->whereHas(
                    'presentation',
                    fn ($presentationQuery) => $presentationQuery->where('owner_id', auth()->id())
                )
            )
            ->latest('occurred_at')
            ->limit(12)
            ->get();

        $live = $this->liveVisitorQuery()
            ->limit(10)
            ->get();

        return view('admin.dashboard.index', compact('stats', 'hot', 'events', 'live'));
    }

    public function live(): JsonResponse
    {
        $visitors = $this->liveVisitorQuery()
            ->limit(20)
            ->get()
            ->map(fn (PresentationSession $session) => [
                'client' => $session->presentation?->client?->company_name,
                'section' => $session->current_section,
                'last_activity' => $session->last_activity_at?->diffForHumans(),
                'device' => $session->device_type,
            ]);

        return response()->json($visitors);
    }

    private function liveVisitorQuery()
    {
        return PresentationSession::with('presentation.client')
            ->when(
                $this->access->isSalesperson(),
                fn ($query) => $query->whereHas(
                    'presentation',
                    fn ($presentationQuery) => $presentationQuery->where('owner_id', auth()->id())
                )
            )
            ->where('last_activity_at', '>=', now()->subSeconds(60))
            ->latest('last_activity_at');
    }
}
