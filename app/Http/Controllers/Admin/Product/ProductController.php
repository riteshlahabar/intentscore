<?php

namespace App\Http\Controllers\Admin\Product;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\ProductRequest;
use App\Models\Product\Product;
use App\Models\Product\ProductDemoLink;
use App\Models\Product\ProductFeature;
use App\Models\Product\ProductMedia;
use App\Services\Common\UploadService;
use App\Services\ImportExport\CsvService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function __construct(
        private CsvService $csv,
        private UploadService $upload,
    ) {}

    public function index(Request $request)
    {
        $products = $this->filteredQuery($request)
            ->withCount(['features', 'demoLinks'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $categories = Product::whereNotNull('category')->distinct()->orderBy('category')->pluck('category');

        return view('admin.products.index', compact('products', 'categories'));
    }

    public function create()
    {
        return view('admin.products.form', ['product' => new Product]);
    }

    public function store(ProductRequest $request)
    {
        $data = $request->validated();
        unset($data['cover_image']);

        $data['slug'] = $this->uniqueSlug($data['name']);
        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $this->upload->store($request->file('cover_image'), 'products');
        }

        $product = Product::create($data);

        return redirect()->route('admin.products.edit', $product)
            ->with('success', 'Product created. Add features, media and demo links below.');
    }

    public function edit(Product $product)
    {
        $product->load(['features', 'demoLinks', 'media']);

        return view('admin.products.form', compact('product'));
    }

    public function update(ProductRequest $request, Product $product)
    {
        $data = $request->validated();
        unset($data['cover_image']);

        if ($request->hasFile('cover_image')) {
            $this->upload->delete($product->cover_image);
            $data['cover_image'] = $this->upload->store($request->file('cover_image'), 'products');
        }

        $product->update($data);

        return back()->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return back()->with('success', 'Product moved to trash.');
    }

    public function addFeature(Request $request, Product $product)
    {
        $data = $request->validate([
            'title' => 'required|string|max:180',
            'description' => 'nullable|string|max:2000',
            'icon' => 'nullable|string|max:80',
            'sort_order' => 'nullable|integer|min:0|max:10000',
        ]);

        $product->features()->create($data + ['is_active' => true]);

        return back()->with('success', 'Feature added.');
    }

    public function deleteFeature(Product $product, ProductFeature $feature)
    {
        abort_unless($feature->product_id === $product->id, 404);
        $feature->delete();

        return back()->with('success', 'Feature removed.');
    }

    public function addDemo(Request $request, Product $product)
    {
        $data = $request->validate([
            'label' => 'required|string|max:180',
            'url' => 'required|url:http,https|max:2000',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:255',
            'type' => 'required|in:website,admin,app,video,other',
            'sort_order' => 'nullable|integer|min:0|max:10000',
        ]);

        $product->demoLinks()->create($data + ['is_active' => true]);

        return back()->with('success', 'Demo link added securely.');
    }

    public function deleteDemo(Product $product, ProductDemoLink $demo)
    {
        abort_unless($demo->product_id === $product->id, 404);
        $demo->delete();

        return back()->with('success', 'Demo link removed.');
    }

    public function addMedia(Request $request, Product $product)
    {
        $data = $request->validate([
            'title' => 'nullable|string|max:180',
            'type' => 'required|in:image,video,external',
            'file' => 'nullable|file|mimes:jpg,jpeg,png,webp,gif,mp4,webm|max:20480',
            'external_url' => 'nullable|url:http,https|max:2000',
            'sort_order' => 'nullable|integer|min:0|max:10000',
        ]);

        if ($data['type'] === 'external' && empty($data['external_url'])) {
            return back()->withErrors(['external_url' => 'Provide an external URL for external media.']);
        }

        if (in_array($data['type'], ['image', 'video'], true) && ! $request->hasFile('file')) {
            return back()->withErrors(['file' => 'Upload a file for image or video media.']);
        }

        if ($request->hasFile('file')) {
            $mime = (string) $request->file('file')->getMimeType();
            $matchesType = ($data['type'] === 'image' && str_starts_with($mime, 'image/'))
                || ($data['type'] === 'video' && str_starts_with($mime, 'video/'));

            if (! $matchesType) {
                return back()->withErrors(['file' => 'Uploaded file does not match the selected media type.']);
            }
        }

        $path = $request->hasFile('file')
            ? $this->upload->store($request->file('file'), 'products/media')
            : null;

        $product->media()->create([
            'title' => $data['title'] ?? null,
            'type' => $data['type'],
            'file_path' => $path,
            'external_url' => $data['external_url'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => true,
        ]);

        return back()->with('success', 'Product media added.');
    }

    public function deleteMedia(Product $product, ProductMedia $media)
    {
        abort_unless($media->product_id === $product->id, 404);
        $this->upload->delete($media->file_path);
        $media->delete();

        return back()->with('success', 'Product media removed.');
    }

    public function export(Request $request)
    {
        $rows = $this->filteredQuery($request)->get()->map(fn (Product $product) => [
            $product->name,
            $product->category,
            $product->tagline,
            $product->summary,
            $product->base_price,
            $product->currency,
            $product->default_timeline_days,
            $product->status,
        ]);

        return $this->csv->download(
            'products-'.now()->format('Ymd-His').'.csv',
            ['name', 'category', 'tagline', 'summary', 'base_price', 'currency', 'default_timeline_days', 'status'],
            $rows,
        );
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:5120']);
        $count = 0;

        foreach ($this->csv->read($request->file('file')) as $row) {
            if (empty($row['name'])) {
                continue;
            }

            $product = Product::firstOrNew(['name' => mb_substr($row['name'], 0, 180)]);
            $product->fill([
                'name' => mb_substr($row['name'], 0, 180),
                'category' => isset($row['category']) ? mb_substr($row['category'], 0, 120) : null,
                'tagline' => isset($row['tagline']) ? mb_substr($row['tagline'], 0, 255) : null,
                'summary' => $row['summary'] ?? null,
                'base_price' => is_numeric($row['base_price'] ?? null) ? $row['base_price'] : null,
                'currency' => mb_substr($row['currency'] ?? 'INR', 0, 8),
                'default_timeline_days' => is_numeric($row['default_timeline_days'] ?? null)
                    ? min(3650, max(1, (int) $row['default_timeline_days']))
                    : null,
                'status' => in_array($row['status'] ?? 'active', ['active', 'inactive'], true) ? $row['status'] : 'active',
            ]);

            if (! $product->slug) {
                $product->slug = $this->uniqueSlug($row['name']);
            }
            $product->save();
            $count++;
        }

        return back()->with('success', "Imported {$count} products.");
    }

    private function filteredQuery(Request $request): Builder
    {
        $query = Product::query();

        if ($request->filled('q')) {
            $term = $request->string('q')->toString();
            $query->where(fn (Builder $builder) => $builder
                ->where('name', 'like', "%{$term}%")
                ->orWhere('category', 'like', "%{$term}%")
                ->orWhere('tagline', 'like', "%{$term}%"));
        }

        foreach (['status', 'category'] as $field) {
            if ($request->filled($field)) {
                $query->where($field, $request->input($field));
            }
        }

        return $query;
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $counter = 2;

        while (Product::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId, fn (Builder $query) => $query->where('id', '!=', $ignoreId))
            ->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
