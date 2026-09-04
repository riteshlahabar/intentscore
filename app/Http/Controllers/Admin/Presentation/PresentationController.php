<?php

namespace App\Http\Controllers\Admin\Presentation;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Presentation\PresentationRequest;
use App\Models\Client\Client;
use App\Models\Presentation\Presentation;
use App\Models\Product\Product;
use App\Models\User;
use App\Services\Common\AccessService;
use App\Services\ImportExport\CsvService;
use App\Services\Presentation\PresentationBuilderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PresentationController extends Controller
{
    public function __construct(
        private PresentationBuilderService $builder,
        private CsvService $csv,
        private AccessService $access,
    ) {}

    public function index(Request $request)
    {
        $presentations = $this->filteredQuery($request)
            ->with(['client', 'product', 'owner'])
            ->withCount(['sessions', 'events'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.presentations.index', compact('presentations') + $this->formOptions());
    }

    public function create()
    {
        return view('admin.presentations.form', ['presentation' => new Presentation] + $this->formOptions());
    }

    public function store(PresentationRequest $request)
    {
        $data = $request->validated();
        $this->assertRelatedClientAccess((int) $data['client_id']);
        $data['reference_no'] = $this->builder->makeReference();
        $data['public_token'] = $this->builder->makeToken();
        $data['owner_id'] = $this->access->enforceOwner($data['owner_id'] ?? null) ?: auth()->id();
        $data['published_at'] = $data['status'] === 'published' ? now() : null;

        $presentation = Presentation::create($data);
        $this->builder->createDefaults($presentation);

        return redirect()->route('admin.presentations.edit', $presentation)
            ->with('success', 'Presentation created. Configure sections below.');
    }

    public function edit(Presentation $presentation)
    {
        $this->authorize('update', $presentation);
        $presentation->load(['sections', 'client', 'product.features', 'product.demoLinks', 'product.media']);

        return view('admin.presentations.form', compact('presentation') + $this->formOptions() + [
            'sectionLabels' => PresentationBuilderService::SECTIONS,
        ]);
    }

    public function update(PresentationRequest $request, Presentation $presentation)
    {
        $this->authorize('update', $presentation);

        $data = $request->validated();
        $this->assertRelatedClientAccess((int) $data['client_id']);
        $data['owner_id'] = $this->access->enforceOwner($data['owner_id'] ?? $presentation->owner_id);
        if ($data['status'] === 'published' && ! $presentation->published_at) {
            $data['published_at'] = now();
        }

        $presentation->update($data);

        return back()->with('success', 'Presentation updated.');
    }

    public function updateSections(Request $request, Presentation $presentation)
    {
        $this->authorize('update', $presentation);

        $data = $request->validate([
            'sections' => 'required|array',
            'sections.*.is_enabled' => 'nullable|boolean',
            'sections.*.sort_order' => 'required|integer|min:0|max:1000',
            'sections.*.custom_title' => 'nullable|string|max:255',
            'sections.*.custom_content' => 'nullable|string|max:15000',
        ]);

        foreach ($data['sections'] as $id => $row) {
            $section = $presentation->sections()->findOrFail($id);
            $section->update([
                'is_enabled' => (bool) ($row['is_enabled'] ?? false),
                'sort_order' => $row['sort_order'],
                'custom_title' => $row['custom_title'] ?? null,
                'custom_content' => $row['custom_content'] ?? null,
            ]);
        }

        return back()->with('success', 'Presentation sections updated.');
    }

    public function regenerateToken(Presentation $presentation)
    {
        $this->authorize('update', $presentation);
        $presentation->update(['public_token' => $this->builder->makeToken()]);

        return back()->with('success', 'Public link regenerated. Old link no longer works.');
    }

    public function destroy(Presentation $presentation)
    {
        $this->authorize('delete', $presentation);
        $presentation->delete();

        return back()->with('success', 'Presentation moved to trash.');
    }

    public function export(Request $request)
    {
        $rows = $this->filteredQuery($request)
            ->with(['client', 'product'])
            ->get()
            ->map(fn (Presentation $presentation) => [
                $presentation->reference_no,
                $presentation->client?->company_name,
                $presentation->product?->name,
                $presentation->title,
                $presentation->status,
                $presentation->price,
                $presentation->currency,
                $presentation->valid_until?->format('Y-m-d'),
                $presentation->engagement_score,
                route('presentation.public', $presentation->public_token),
            ]);

        return $this->csv->download(
            'presentations-'.now()->format('Ymd-His').'.csv',
            ['reference_no', 'client', 'product', 'title', 'status', 'price', 'currency', 'valid_until', 'engagement_score', 'public_url'],
            $rows,
        );
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:5120']);
        $count = 0;

        foreach ($this->csv->read($request->file('file')) as $row) {
            if (empty($row['client']) || empty($row['product'])) {
                continue;
            }

            $clientIdentity = ['company_name' => mb_substr($row['client'], 0, 255)];
            if ($this->access->isSalesperson()) {
                $clientIdentity['owner_id'] = auth()->id();
            }
            $client = Client::firstOrCreate($clientIdentity, ['status' => 'prospect', 'owner_id' => auth()->id()]);
            $product = Product::where('name', $row['product'])->first();
            if (! $product) {
                continue;
            }

            $status = in_array($row['status'] ?? 'draft', ['draft', 'published', 'viewed', 'engaged', 'negotiation', 'won', 'lost', 'expired'], true)
                ? $row['status']
                : 'draft';

            $presentation = Presentation::create([
                'client_id' => $client->id,
                'product_id' => $product->id,
                'owner_id' => auth()->id(),
                'reference_no' => $this->builder->makeReference(),
                'public_token' => $this->builder->makeToken(),
                'title' => mb_substr($row['title'] ?? ($product->name.' for '.$client->company_name), 0, 220),
                'status' => $status,
                'price' => is_numeric($row['price'] ?? null) ? $row['price'] : $product->base_price,
                'currency' => mb_substr($row['currency'] ?? 'INR', 0, 8),
                'valid_until' => ! empty($row['valid_until']) ? $row['valid_until'] : null,
                'published_at' => $status === 'published' ? now() : null,
            ]);

            $this->builder->createDefaults($presentation);
            $count++;
        }

        return back()->with('success', "Imported {$count} presentations.");
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = $this->access->scopeOwned(Presentation::query());

        if ($request->filled('q')) {
            $term = $request->string('q')->toString();
            $query->where(fn (Builder $builder) => $builder
                ->where('reference_no', 'like', "%{$term}%")
                ->orWhere('title', 'like', "%{$term}%")
                ->orWhereHas('client', fn (Builder $client) => $client->where('company_name', 'like', "%{$term}%")));
        }

        foreach (['status', 'client_id', 'product_id', 'owner_id'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        return $query;
    }

    private function assertRelatedClientAccess(int $clientId): void
    {
        $client = Client::findOrFail($clientId);
        $this->authorize('update', $client);
    }

    private function formOptions(): array
    {
        return [
            'clients' => $this->access->scopeOwned(Client::orderBy('company_name'))->get(),
            'products' => Product::where('status', 'active')->orderBy('name')->get(),
            'users' => $this->access->isSalesperson()
                ? User::whereKey(auth()->id())->get()
                : User::where('status', 'active')->orderBy('name')->get(),
        ];
    }
}
