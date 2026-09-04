<?php

namespace App\Http\Controllers\Admin\Prospect;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Prospect\ProspectRequest;
use App\Models\SmartLink\IntentScore;
use App\Models\SmartLink\Prospect;
use App\Models\SmartLink\SalesActivity;
use App\Models\SmartLink\SmartPageTemplate;
use App\Models\User;
use App\Services\Common\AccessService;
use App\Services\SmartLink\IntentScoreService;
use App\Services\SmartLink\SmartLinkService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProspectController extends Controller
{
    public function __construct(
        private SmartLinkService $smartLinks,
        private IntentScoreService $intentScore,
        private AccessService $access,
    ) {
    }

    /** Salesperson dashboard: My Prospects (PDF section 12). */
    public function index(Request $request)
    {
        $sort = $request->get('sort', 'last_activity');
        $direction = $request->get('direction') === 'asc' ? 'asc' : 'desc';

        $query = Prospect::with(['intentScore', 'smartLink', 'salesperson'])
            ->withCount('visits')
            ->withMax('events as last_activity_at', 'occurred_at');

        $this->access->scopeOwned($query, 'salesperson_id');

        if ($search = trim((string) $request->get('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('business_name', 'like', "%{$search}%")
                    ->orWhere('contact_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        if ($level = $request->get('level')) {
            $query->whereHas('intentScore', fn ($q) => $q->where('intent_level', $level));
        }

        match ($sort) {
            'intent' => $query->orderBy(
                IntentScore::select('score')->whereColumn('prospect_id', 'prospects.id'),
                $direction
            ),
            'name' => $query->orderBy('business_name', $direction),
            default => $query->orderBy('last_activity_at', $direction),
        };

        return view('admin.prospects.index', [
            'prospects' => $query->paginate(20)->withQueryString(),
            'sort' => $sort,
            'direction' => $direction,
            'alerts' => $this->highIntentAlerts(),
        ]);
    }

    public function create()
    {
        return view('admin.prospects.create', [
            'prospect' => new Prospect,
            'templates' => SmartPageTemplate::where('is_active', true)->get(),
            'users' => User::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    /**
     * One form creates the Prospect, Smart Page, Smart Link and tracking ID
     * in a single step, as described in PDF section 4.
     */
    public function store(ProspectRequest $request)
    {
        $data = $request->validated();
        $template = SmartPageTemplate::findOrFail($data['template_id']);

        $prospect = Prospect::create([
            'business_name' => $data['business_name'],
            'contact_name' => $data['contact_name'] ?? null,
            'website' => $data['website'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'industry' => $data['industry'] ?? null,
            'location' => $data['location'] ?? null,
            'offer' => $data['offer'] ?? null,
            'salesperson_id' => $this->access->enforceOwner($data['salesperson_id'] ?? null) ?: auth()->id(),
            'status' => 'new',
        ]);

        $this->smartLinks->createForProspect($prospect, $template, [
            'personalized_message' => $data['personalized_message'] ?? null,
        ]);

        $this->intentScore->recalculate($prospect);

        return redirect()->route('admin.prospects.show', $prospect)
            ->with('success', 'Smart Link created. Copy the link and send it to your prospect.');
    }

    /** Prospect detail screen (PDF section 13). */
    public function show(Prospect $prospect)
    {
        $this->access->assertOwner($prospect->salesperson_id);

        $prospect->load(['intentScore', 'smartLink', 'smartPage.sections', 'salesperson']);

        $this->markAlertSeen($prospect);

        return view('admin.prospects.show', [
            'prospect' => $prospect,
            'timeline' => $prospect->events()->latest('occurred_at')->limit(200)->get(),
            'visits' => $prospect->visits()->latest('started_at')->limit(20)->get(),
            'activities' => $prospect->salesActivities()->with('user')->latest()->get(),
            'scoreService' => $this->intentScore,
        ]);
    }

    public function edit(Prospect $prospect)
    {
        $this->access->assertOwner($prospect->salesperson_id);

        return view('admin.prospects.create', [
            'prospect' => $prospect,
            'templates' => SmartPageTemplate::where('is_active', true)->get(),
            'users' => User::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function update(ProspectRequest $request, Prospect $prospect)
    {
        $this->access->assertOwner($prospect->salesperson_id);

        $data = $request->validated();
        $prospect->update([
            'business_name' => $data['business_name'],
            'contact_name' => $data['contact_name'] ?? null,
            'website' => $data['website'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'industry' => $data['industry'] ?? null,
            'location' => $data['location'] ?? null,
            'offer' => $data['offer'] ?? null,
            'salesperson_id' => $this->access->enforceOwner($data['salesperson_id'] ?? null) ?: $prospect->salesperson_id,
        ]);

        if ($page = $prospect->smartPage) {
            $page->update(['personalized_message' => $data['personalized_message'] ?? $page->personalized_message]);

            if ((int) $data['template_id'] !== (int) $page->template_id) {
                $page->update(['template_id' => $data['template_id']]);
                $this->smartLinks->applyTemplate($page, SmartPageTemplate::findOrFail($data['template_id']));
            }
        }

        return redirect()->route('admin.prospects.show', $prospect)->with('success', 'Prospect updated.');
    }

    public function destroy(Prospect $prospect)
    {
        $this->access->assertOwner($prospect->salesperson_id);
        $prospect->delete();

        return redirect()->route('admin.prospects.index')->with('success', 'Prospect moved to trash.');
    }

    /** Sales status dropdown on the detail screen (PDF section 13). */
    public function updateStatus(Request $request, Prospect $prospect)
    {
        $this->access->assertOwner($prospect->salesperson_id);

        $data = $request->validate([
            'status' => ['required', Rule::in(Prospect::STATUSES)],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $prospect->update(['status' => $data['status']]);

        SalesActivity::create([
            'prospect_id' => $prospect->id,
            'user_id' => auth()->id(),
            'activity_type' => 'status_changed',
            'notes' => trim('Status set to '.str_replace('_', ' ', $data['status']).'. '.($data['notes'] ?? '')),
        ]);

        return back()->with('success', 'Sales status updated.');
    }

    public function regenerateLink(Prospect $prospect)
    {
        $this->access->assertOwner($prospect->salesperson_id);

        $prospect->smartLink?->update(['slug' => $this->smartLinks->makeSlug()]);

        return back()->with('success', 'A new Smart Link was generated. The old link no longer works.');
    }

    /** In-app HIGH INTENT indication (PDF section 18). */
    private function highIntentAlerts()
    {
        $query = IntentScore::with('prospect')
            ->where('intent_level', 'HIGH INTENT')
            ->where('alert_seen', false);

        if ($this->access->isSalesperson()) {
            $query->whereHas('prospect', fn ($q) => $q->where('salesperson_id', auth()->id()));
        }

        return $query->latest('updated_at')->limit(5)->get();
    }

    private function markAlertSeen(Prospect $prospect): void
    {
        if ($prospect->intentScore && ! $prospect->intentScore->alert_seen) {
            $prospect->intentScore->update(['alert_seen' => true]);
        }
    }
}
