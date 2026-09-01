<?php

namespace App\Http\Controllers\Admin\Search;

use App\Http\Controllers\Controller;
use App\Models\Client\Client;
use App\Models\Lead\Lead;
use App\Models\Product\Product;
use App\Models\Presentation\Presentation;
use App\Services\Common\AccessService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __construct(private readonly AccessService $access)
    {
    }

    public function __invoke(Request $request): View
    {
        $term = trim((string) $request->query('q', ''));
        $clients = collect();
        $leads = collect();
        $products = collect();
        $presentations = collect();

        if (mb_strlen($term) >= 2) {
            $clients = $this->access
                ->scopeOwned(Client::query())
                ->where(function ($query) use ($term) {
                    $query->where('company_name', 'like', "%{$term}%")
                        ->orWhere('contact_name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                })
                ->limit(10)
                ->get();

            $leads = $this->access
                ->scopeOwned(Lead::query())
                ->where(function ($query) use ($term) {
                    $query->where('title', 'like', "%{$term}%")
                        ->orWhere('source', 'like', "%{$term}%");
                })
                ->limit(10)
                ->get();

            if (! $this->access->isSalesperson()) {
                $products = Product::query()
                    ->where(function ($query) use ($term) {
                        $query->where('name', 'like', "%{$term}%")
                            ->orWhere('category', 'like', "%{$term}%")
                            ->orWhere('short_description', 'like', "%{$term}%");
                    })
                    ->limit(10)
                    ->get();
            }

            $presentations = $this->access
                ->scopeOwned(Presentation::with('client'))
                ->where(function ($query) use ($term) {
                    $query->where('reference_no', 'like', "%{$term}%")
                        ->orWhere('title', 'like', "%{$term}%");
                })
                ->limit(10)
                ->get();
        }

        return view('admin.search.index', [
            'q' => $term,
            'clients' => $clients,
            'leads' => $leads,
            'products' => $products,
            'presentations' => $presentations,
        ]);
    }
}
