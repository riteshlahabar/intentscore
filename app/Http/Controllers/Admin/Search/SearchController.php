<?php

namespace App\Http\Controllers\Admin\Search;

use App\Http\Controllers\Controller;
use App\Models\SmartLink\Prospect;
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
        $prospects = collect();

        if (mb_strlen($term) >= 2) {
            $prospects = $this->access
                ->scopeOwned(Prospect::query(), 'salesperson_id')
                ->where(function ($query) use ($term) {
                    $query->where('business_name', 'like', "%{$term}%")
                        ->orWhere('contact_name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('phone', 'like', "%{$term}%");
                })
                ->limit(20)
                ->get();
        }

        return view('admin.search.index', [
            'q' => $term,
            'prospects' => $prospects,
        ]);
    }
}
