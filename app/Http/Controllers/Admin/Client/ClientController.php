<?php

namespace App\Http\Controllers\Admin\Client;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Client\ClientRequest;
use App\Models\Client\Client;
use App\Models\User;
use App\Services\Common\AccessService;
use App\Services\ImportExport\CsvService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function __construct(
        private CsvService $csv,
        private AccessService $access,
    ) {}

    public function index(Request $request)
    {
        $clients = $this->filteredQuery($request)
            ->with('owner')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.clients.index', compact('clients'));
    }

    public function create()
    {
        return view('admin.clients.form', [
            'client' => new Client,
            'users' => $this->assignableUsers(),
        ]);
    }

    public function store(ClientRequest $request)
    {
        $data = $request->validated();
        $data['owner_id'] = $this->access->enforceOwner($data['owner_id'] ?? null) ?: auth()->id();

        Client::create($data);

        return redirect()->route('admin.clients.index')->with('success', 'Client created.');
    }

    public function edit(Client $client)
    {
        $this->access->assertOwner($client->owner_id);

        return view('admin.clients.form', [
            'client' => $client,
            'users' => $this->assignableUsers(),
        ]);
    }

    public function update(ClientRequest $request, Client $client)
    {
        $this->access->assertOwner($client->owner_id);

        $data = $request->validated();
        $data['owner_id'] = $this->access->enforceOwner($data['owner_id'] ?? $client->owner_id);
        $client->update($data);

        return redirect()->route('admin.clients.index')->with('success', 'Client updated.');
    }

    public function destroy(Client $client)
    {
        $this->access->assertOwner($client->owner_id);
        $client->delete();

        return back()->with('success', 'Client moved to trash.');
    }

    public function export(Request $request)
    {
        $rows = $this->filteredQuery($request)
            ->orderBy('id')
            ->get()
            ->map(fn (Client $client) => [
                $client->company_name,
                $client->contact_name,
                $client->email,
                $client->phone,
                $client->whatsapp,
                $client->city,
                $client->state,
                $client->country,
                $client->status,
                $client->notes,
            ]);

        return $this->csv->download(
            'clients-'.now()->format('Ymd-His').'.csv',
            ['company_name', 'contact_name', 'email', 'phone', 'whatsapp', 'city', 'state', 'country', 'status', 'notes'],
            $rows,
        );
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:5120']);
        $count = 0;

        foreach ($this->csv->read($request->file('file')) as $row) {
            if (empty($row['company_name'])) {
                continue;
            }

            $data = array_intersect_key($row, array_flip([
                'company_name', 'contact_name', 'email', 'phone', 'whatsapp',
                'city', 'state', 'country', 'notes',
            ]));
            $data['status'] = in_array($row['status'] ?? 'prospect', ['prospect', 'active', 'inactive', 'won', 'lost'], true)
                ? $row['status']
                : 'prospect';
            $data['owner_id'] = auth()->id();

            $identity = ['company_name' => mb_substr($row['company_name'], 0, 255)];
            if (! empty($row['email'])) {
                $identity['email'] = mb_substr($row['email'], 0, 255);
            }
            if ($this->access->isSalesperson()) {
                $identity['owner_id'] = auth()->id();
            }

            Client::updateOrCreate($identity, $data);
            $count++;
        }

        return back()->with('success', "Imported {$count} client records.");
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = $this->access->scopeOwned(Client::query());

        if ($request->filled('q')) {
            $term = $request->string('q')->toString();
            $query->where(fn (Builder $builder) => $builder
                ->where('company_name', 'like', "%{$term}%")
                ->orWhere('contact_name', 'like', "%{$term}%")
                ->orWhere('email', 'like', "%{$term}%")
                ->orWhere('phone', 'like', "%{$term}%"));
        }

        foreach (['status', 'city', 'state', 'owner_id'] as $field) {
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

    private function assignableUsers()
    {
        return $this->access->isSalesperson()
            ? User::whereKey(auth()->id())->get()
            : User::where('status', 'active')->orderBy('name')->get();
    }
}
