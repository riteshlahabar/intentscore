<?php

namespace App\Http\Controllers\Admin\FollowUp;

use App\Http\Controllers\Controller;
use App\Models\Lead\FollowUp;
use App\Models\Lead\Lead;
use App\Models\User;
use App\Services\Common\AccessService;
use App\Services\ImportExport\CsvService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FollowUpController extends Controller
{
    public function __construct(
        private readonly CsvService $csv,
        private readonly AccessService $access,
    ) {
    }

    public function index(Request $request): View
    {
        $followUps = $this->filteredQuery($request)
            ->with(['lead.client', 'lead.product', 'user'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('follow_up_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.followups.index', compact('followUps') + $this->formOptions());
    }

    public function create(Request $request): View
    {
        $followUp = new FollowUp;
        if ($request->filled('lead_id')) {
            $lead = $this->accessibleLead((int) $request->lead_id);
            $followUp->lead_id = $lead->id;
        }

        return view('admin.followups.form', compact('followUp') + $this->formOptions());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $lead = $this->accessibleLead((int) $data['lead_id']);
        $data['user_id'] = $this->access->enforceOwner($data['user_id'] ?? null) ?: auth()->id();

        $followUp = FollowUp::create($data);
        $this->syncLeadNextFollowUp($lead);

        return redirect()->route('admin.followups.index')
            ->with('success', 'Follow-up scheduled.');
    }

    public function edit(FollowUp $followUp): View
    {
        $this->assertFollowUpAccess($followUp);

        return view('admin.followups.form', compact('followUp') + $this->formOptions());
    }

    public function update(Request $request, FollowUp $followUp): RedirectResponse
    {
        $this->assertFollowUpAccess($followUp);
        $oldLead = $followUp->lead;
        $data = $this->validateData($request);
        $newLead = $this->accessibleLead((int) $data['lead_id']);
        $data['user_id'] = $this->access->enforceOwner($data['user_id'] ?? $followUp->user_id) ?: auth()->id();

        $followUp->update($data);
        $this->syncLeadNextFollowUp($oldLead);
        if ($newLead->id !== $oldLead->id) {
            $this->syncLeadNextFollowUp($newLead);
        }

        return redirect()->route('admin.followups.index')
            ->with('success', 'Follow-up updated.');
    }

    public function destroy(FollowUp $followUp): RedirectResponse
    {
        $this->assertFollowUpAccess($followUp);
        $lead = $followUp->lead;
        $followUp->delete();
        $this->syncLeadNextFollowUp($lead);

        return back()->with('success', 'Follow-up deleted.');
    }

    public function export(Request $request)
    {
        $rows = $this->filteredQuery($request)
            ->with(['lead.client', 'user'])
            ->get()
            ->map(fn (FollowUp $followUp) => [
                $followUp->lead?->title,
                $followUp->lead?->client?->company_name,
                $followUp->user?->email,
                $followUp->follow_up_at?->format('Y-m-d H:i:s'),
                $followUp->type,
                $followUp->status,
                $followUp->notes,
            ]);

        return $this->csv->download(
            'follow-ups-'.now()->format('Ymd-His').'.csv',
            ['lead', 'client', 'assigned_email', 'follow_up_at', 'type', 'status', 'notes'],
            $rows,
        );
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);
        $count = 0;

        foreach ($this->csv->read($request->file('file')) as $row) {
            if (empty($row['lead']) || empty($row['follow_up_at'])) {
                continue;
            }

            $leadQuery = $this->access->scopeOwned(Lead::query());
            $lead = $leadQuery->where('title', $row['lead'])->first();
            if (! $lead) {
                continue;
            }

            try {
                $date = now()->parse($row['follow_up_at']);
            } catch (\Throwable) {
                continue;
            }

            $type = in_array($row['type'] ?? 'call', ['call', 'whatsapp', 'email', 'meeting', 'other'], true)
                ? $row['type']
                : 'call';
            $status = in_array($row['status'] ?? 'pending', ['pending', 'completed', 'cancelled'], true)
                ? $row['status']
                : 'pending';

            $userId = auth()->id();
            if (! $this->access->isSalesperson() && ! empty($row['assigned_email'])) {
                $userId = User::where('email', $row['assigned_email'])
                    ->where('status', 'active')
                    ->value('id') ?: auth()->id();
            }

            FollowUp::create([
                'lead_id' => $lead->id,
                'user_id' => $userId,
                'follow_up_at' => $date,
                'type' => $type,
                'status' => $status,
                'notes' => isset($row['notes']) ? mb_substr($row['notes'], 0, 10000) : null,
            ]);

            $this->syncLeadNextFollowUp($lead);
            $count++;
        }

        return back()->with('success', "Imported {$count} follow-ups.");
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = FollowUp::query();

        if ($this->access->isSalesperson()) {
            $query->whereHas('lead', fn (Builder $lead) => $lead->where('owner_id', auth()->id()));
        }

        if ($request->filled('q')) {
            $term = $request->string('q')->toString();
            $query->where(function (Builder $builder) use ($term) {
                $builder->where('notes', 'like', "%{$term}%")
                    ->orWhereHas('lead', fn (Builder $lead) => $lead->where('title', 'like', "%{$term}%"))
                    ->orWhereHas('lead.client', fn (Builder $client) => $client->where('company_name', 'like', "%{$term}%"));
            });
        }

        foreach (['status', 'type', 'user_id', 'lead_id'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('follow_up_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('follow_up_at', '<=', $request->date_to);
        }

        return $query;
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'lead_id' => ['required', 'exists:leads,id'],
            'user_id' => ['nullable', 'exists:users,id'],
            'follow_up_at' => ['required', 'date'],
            'type' => ['required', 'in:call,whatsapp,email,meeting,other'],
            'status' => ['required', 'in:pending,completed,cancelled'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ]);
    }

    private function accessibleLead(int $leadId): Lead
    {
        return $this->access->scopeOwned(Lead::query())->findOrFail($leadId);
    }

    private function assertFollowUpAccess(FollowUp $followUp): void
    {
        $followUp->loadMissing('lead');
        $this->access->assertOwner($followUp->lead?->owner_id);
    }

    private function syncLeadNextFollowUp(Lead $lead): void
    {
        $next = $lead->followUps()
            ->where('status', 'pending')
            ->where('follow_up_at', '>=', now())
            ->orderBy('follow_up_at')
            ->value('follow_up_at');

        $lead->updateQuietly(['next_follow_up_at' => $next ? now()->parse($next)->toDateString() : null]);
    }

    private function formOptions(): array
    {
        return [
            'leads' => $this->access->scopeOwned(Lead::with('client')->orderBy('title'))->get(),
            'users' => $this->access->isSalesperson()
                ? User::whereKey(auth()->id())->get()
                : User::where('status', 'active')->orderBy('name')->get(),
        ];
    }
}
