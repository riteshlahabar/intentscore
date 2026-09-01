<?php

namespace App\Http\Controllers\Admin\Analytics;

use App\Http\Controllers\Controller;
use App\Models\Analytics\PresentationEvent;
use App\Models\Presentation\Presentation;
use App\Services\Common\AccessService;
use App\Services\ImportExport\CsvService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(
        private CsvService $csv,
        private AccessService $access,
    ) {}

    public function index(Request $request)
    {
        $events = $this->filteredQuery($request)
            ->with(['presentation.client', 'session'])
            ->latest('occurred_at')
            ->paginate(30)
            ->withQueryString();

        $presentations = $this->access->scopeOwned(Presentation::with('client')->latest())->get();
        $types = PresentationEvent::distinct()->orderBy('event_type')->pluck('event_type');
        $sections = PresentationEvent::whereNotNull('section_key')->distinct()->orderBy('section_key')->pluck('section_key');

        return view('admin.analytics.index', compact('events', 'presentations', 'types', 'sections'));
    }

    public function show(Presentation $presentation)
    {
        $this->access->assertOwner($presentation->owner_id);

        $presentation->load([
            'client',
            'product',
            'sessions' => fn ($query) => $query->latest('started_at'),
            'events' => fn ($query) => $query->latest('occurred_at')->limit(250),
        ]);

        $sections = $presentation->events()
            ->where('event_type', 'section_time')
            ->selectRaw('section_key, COUNT(*) views, SUM(duration_ms) duration_ms')
            ->groupBy('section_key')
            ->orderByDesc('duration_ms')
            ->get();

        $urls = $presentation->events()
            ->where('event_type', 'url_opened')
            ->selectRaw('element_key, target_url, COUNT(*) opens')
            ->groupBy('element_key', 'target_url')
            ->orderByDesc('opens')
            ->get();

        return view('admin.analytics.show', compact('presentation', 'sections', 'urls'));
    }

    public function export(Request $request)
    {
        $rows = $this->filteredQuery($request)
            ->with(['presentation.client', 'session'])
            ->latest('occurred_at')
            ->limit(20000)
            ->get()
            ->map(fn (PresentationEvent $event) => [
                $event->occurred_at?->format('Y-m-d H:i:s'),
                $event->presentation?->client?->company_name,
                $event->presentation?->reference_no,
                $event->event_type,
                $event->section_key,
                $event->element_key,
                $event->target_url,
                $event->duration_ms,
                $event->session?->device_type,
                $event->session?->browser,
                $event->session?->ip_address,
            ]);

        return $this->csv->download(
            'analytics-'.now()->format('Ymd-His').'.csv',
            ['date_time', 'client', 'reference_no', 'event', 'section', 'element', 'url', 'duration_ms', 'device', 'browser', 'ip'],
            $rows,
        );
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = PresentationEvent::query();

        if ($this->access->isSalesperson()) {
            $query->whereHas('presentation', fn (Builder $presentation) => $presentation->where('owner_id', auth()->id()));
        }

        if ($request->filled('q')) {
            $term = $request->string('q')->toString();
            $query->where(fn (Builder $builder) => $builder
                ->where('event_type', 'like', "%{$term}%")
                ->orWhere('section_key', 'like', "%{$term}%")
                ->orWhere('element_key', 'like', "%{$term}%")
                ->orWhereHas('presentation.client', fn (Builder $client) => $client->where('company_name', 'like', "%{$term}%")));
        }

        foreach (['event_type', 'section_key', 'presentation_id'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('occurred_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('occurred_at', '<=', $request->date_to);
        }

        return $query;
    }
}
