<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductGender;
use App\Models\ProductImage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        $products = Product::with(['category', 'sizes', 'genders', 'primaryImage'])
            ->latest()
            ->get()
            ->map(function (Product $product): array {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'base_pricing' => $product->base_pricing,
                    'stock' => $product->stock,
                    'discount' => $product->discount,
                    'discount_type' => $product->discount_type,
                    'category' => $product->category?->name,
                    'sizes' => $product->sizes->pluck('size')->all(),
                    'gender' => $product->genders->first()?->gender,
                    'image' => $product->primaryImage?->url,
                ];
            });

        return Inertia::render('products/index', [
            'products' => $products,
        ]);
    }

    /**
     * Display the storefront catalog.
     *
     * Supports infinite-scroll pagination (?page=N) and optional
     * ?category= / ?gender= filtering, both applied server-side so
     * filtering always runs against the full dataset, not just
     * whatever pages have been loaded into the browser so far.
     */
    public function show(Request $request): Response
    {
        $products = Product::with(['category', 'sizes', 'genders', 'primaryImage', 'images'])
            ->when($request->filled('category'), function ($query) use ($request) {
                $query->whereHas('category', fn ($q) => $q->where('id', $request->string('category')));
            })
            ->when($request->filled('gender'), function ($query) use ($request) {
                $query->whereHas('genders', fn ($q) => $q->where('gender', $request->string('gender')));
            })
            ->latest()
            ->paginate(12)
            ->withQueryString()
            ->through(function (Product $product): array {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'base_pricing' => (float) $product->base_pricing,
                    'price' => (float) $product->discountedPrice(),
                    'discount' => $product->discount,
                    'discount_type' => $product->discount_type,
                    'stock' => $product->stock,
                    'categoryId' => $product->category_id,
                    'category' => $product->category?->name,
                    'sizes' => $product->sizes->pluck('size')->all(),
                    'gender' => $product->genders->first()?->gender,
                    'image' => $product->primaryImage?->url,
                    'images' => $product->images
                        ->sortBy('sort_order')
                        ->values()
                        ->map(fn ($img): array => [
                            'id' => $img->id,
                            'image' => $img->url,
                        ])
                        ->all(),
                    'createdAt' => $product->created_at?->toISOString(),
                ];
            });

        $categories = ProductCategory::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $genders = ProductGender::query()
            ->select('gender')
            ->distinct()
            ->orderBy('gender')
            ->get()
            ->map(fn (ProductGender $gender): array => [
                'id' => $gender->gender,
                'name' => $gender->gender,
            ])
            ->values();

        return Inertia::render('products/show', [
            'products' => $products,
            'categories' => $categories,
            'genders' => $genders,
            'filters' => [
                'category' => $request->string('category')->toString() ?: null,
                'gender' => $request->string('gender')->toString() ?: null,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('products/create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'sizes' => 'required|array|min:1',
            'sizes.*' => 'string|in:XS,S,M,XL,XXL',
            'gender' => 'required|string|in:Men,Woman,Unisex',
            'basePricing' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'discount' => 'nullable|numeric|min:0',
            'discountType' => 'nullable|string|in:fixed,percentage',
            'category' => 'required|string|max:255',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        DB::transaction(function () use ($data, $request): void {
            $category = ProductCategory::firstOrCreate(
                ['slug' => Str::slug($data['category'])],
                ['name' => $data['category']],
            );

            $product = Product::create([
                'name' => $data['name'],
                'description' => $data['description'],
                'base_pricing' => $data['basePricing'],
                'stock' => $data['stock'],
                'discount' => $data['discount'] ?? null,
                'discount_type' => $data['discountType'] ?? null,
                'category_id' => $category->id,
            ]);

            $product->sizes()->createMany(
                collect($data['sizes'])
                    ->map(fn (string $size): array => ['size' => $size])
                    ->all(),
            );

            $product->genders()->create([
                'gender' => $data['gender'],
            ]);

            collect($request->file('images', []))
                ->values()
                ->each(function ($image, int $index) use ($product): void {
                    $product->images()->create([
                        'image_path' => $image->store('products', 'public'),
                        'is_primary' => $index === 0,
                        'sort_order' => $index,
                    ]);
                });
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product created successfully.')]);

        return redirect()->route('dashboard');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product): Response
    {
        $product->load(['category', 'sizes', 'genders', 'images']);

        return Inertia::render('products/edit', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'base_pricing' => $product->base_pricing,
                'stock' => $product->stock,
                'discount' => $product->discount,
                'discount_type' => $product->discount_type,
                'category' => $product->category?->name,
                'sizes' => $product->sizes->pluck('size')->all(),
                'gender' => $product->genders->first()?->gender,
                'images' => $product->images->map(fn ($image): array => [
                    'id' => $image->id,
                    'url' => $image->url,
                    'is_primary' => $image->is_primary,
                ])->all(),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'sizes' => 'required|array|min:1',
            'sizes.*' => 'string|in:XS,S,M,XL,XXL',
            'gender' => 'required|string|in:Men,Woman,Unisex',
            'basePricing' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'discount' => 'nullable|numeric|min:0',
            'discountType' => 'nullable|string|in:fixed,percentage',
            'category' => 'required|string|max:255',
            'deletedImageIds' => 'nullable|array',
            'deletedImageIds.*' => 'integer|exists:product_images,id',
            'primaryImageId' => 'nullable|integer',
            'newImages' => 'nullable|array',
            'newImages.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        DB::transaction(function () use ($data, $request, $product): void {

            // ── 1. Core product fields ────────────────────────────
            $category = ProductCategory::firstOrCreate(
                ['slug' => Str::slug($data['category'])],
                ['name' => $data['category']],
            );

            $product->update([
                'name' => $data['name'],
                'description' => $data['description'],
                'base_pricing' => $data['basePricing'],
                'stock' => $data['stock'],
                'discount' => $data['discount'] ?? null,
                'discount_type' => $data['discountType'] ?? null,
                'category_id' => $category->id,
            ]);

            // ── 2. Sizes ──────────────────────────────────────────
            $product->sizes()->delete();
            $product->sizes()->createMany(
                collect($data['sizes'])
                    ->map(fn (string $size): array => ['size' => $size])
                    ->all(),
            );

            // ── 3. Gender ─────────────────────────────────────────
            $product->genders()->delete();
            $product->genders()->create(['gender' => $data['gender']]);

            // ── 4. Delete removed images from storage + DB ────────
            if (! empty($data['deletedImageIds'])) {
                $product->images()
                    ->whereIn('id', $data['deletedImageIds'])
                    ->each(function (ProductImage $image): void {
                        Storage::disk('public')->delete($image->image_path);
                        $image->delete();
                    });
            }

            // ── 5. Reassign primary image ─────────────────────────
            if (! empty($data['primaryImageId'])) {
                // Clear all, then set the chosen one
                $product->images()->update(['is_primary' => false]);
                $product->images()
                    ->where('id', $data['primaryImageId'])
                    ->update(['is_primary' => true]);
            }

            // ── 6. Store newly uploaded images ────────────────────
            if ($request->hasFile('newImages')) {
                // Auto-assign primary if none exists after deletions
                $needsPrimary = ! $product->images()->where('is_primary', true)->exists();
                $nextSortOrder = (int) $product->images()->max('sort_order') + 1;

                collect($request->file('newImages'))
                    ->values()
                    ->each(function ($image, int $index) use ($product, &$needsPrimary, &$nextSortOrder): void {
                        $product->images()->create([
                            'image_path' => $image->store('products', 'public'),
                            'is_primary' => $needsPrimary && $index === 0,
                            'sort_order' => $nextSortOrder++,
                        ]);

                        $needsPrimary = false;
                    });
            }
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product updated successfully.')]);

        return redirect()->route('dashboard');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product): RedirectResponse
    {
        DB::transaction(function () use ($product): void {
            $product->images->each(function ($image): void {
                Storage::disk('public')->delete($image->image_path);
                $image->delete();
            });

            $product->sizes()->delete();
            $product->genders()->delete();
            $product->delete();
        });

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product deleted successfully.')]);

        return redirect()->route('dashboard');
    }
}
