<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductCategory;
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
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        //
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
        ]);

        DB::transaction(function () use ($data, $product): void {
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

            $product->sizes()->delete();
            $product->sizes()->createMany(
                collect($data['sizes'])
                    ->map(fn (string $size): array => ['size' => $size])
                    ->all(),
            );

            $product->genders()->delete();
            $product->genders()->create([
                'gender' => $data['gender'],
            ]);
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
