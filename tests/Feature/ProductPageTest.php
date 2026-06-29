<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guests are redirected to the login page from products', function () {
    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the product page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('products/index')
        );
});

test('authenticated users can create a product', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $image = UploadedFile::fake()->image('shirt.jpg');

    $response = $this->actingAs($user)->post(route('product.store'), [
        'name' => 'Linen Shirt',
        'description' => 'A breathable summer shirt.',
        'sizes' => ['S', 'M'],
        'gender' => 'Men',
        'basePricing' => '49.99',
        'stock' => 12,
        'discount' => 10,
        'discountType' => 'percentage',
        'category' => 'T-Shirt',
        'images' => [$image],
    ]);

    $response->assertRedirect(route('dashboard'));

    $product = Product::with(['category', 'sizes', 'genders', 'images'])->first();

    expect($product)->not->toBeNull()
        ->and($product->name)->toBe('Linen Shirt')
        ->and((float) $product->base_pricing)->toBe(49.99)
        ->and($product->category->name)->toBe('T-Shirt')
        ->and($product->sizes->pluck('size')->all())->toBe(['S', 'M'])
        ->and($product->genders->pluck('gender')->all())->toBe(['Men'])
        ->and($product->images)->toHaveCount(1)
        ->and($product->images->first()->is_primary)->toBeTruthy();

    Storage::disk('public')->assertExists($product->images->first()->image_path);
});

test('authenticated users can visit the product edit page', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $image = UploadedFile::fake()->image('shirt.jpg');

    $this->actingAs($user)->post(route('product.store'), [
        'name' => 'Edit Test',
        'description' => 'Product for editing.',
        'sizes' => ['S', 'M'],
        'gender' => 'Men',
        'basePricing' => '49.99',
        'stock' => 10,
        'category' => 'T-Shirt',
        'images' => [$image],
    ]);

    $product = Product::first();

    $this->actingAs($user)
        ->get(route('product.edit', $product))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('products/edit')
            ->has('product')
            ->where('product.id', $product->id)
        );
});

test('authenticated users can update a product', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $image = UploadedFile::fake()->image('shirt.jpg');

    $this->actingAs($user)->post(route('product.store'), [
        'name' => 'Before Update',
        'description' => 'Will be updated.',
        'sizes' => ['S'],
        'gender' => 'Men',
        'basePricing' => '29.99',
        'stock' => 5,
        'category' => 'T-Shirt',
        'images' => [$image],
    ]);

    $product = Product::first();

    $response = $this->actingAs($user)->put(route('product.update', $product), [
        'name' => 'Updated Product',
        'description' => 'Updated description.',
        'sizes' => ['M', 'XL'],
        'gender' => 'Woman',
        'basePricing' => '79.99',
        'stock' => 25,
        'discount' => 15,
        'discountType' => 'percentage',
        'category' => 'Jacket',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('dashboard'));

    $product->refresh();

    expect($product->name)->toBe('Updated Product')
        ->and((float) $product->base_pricing)->toBe(79.99)
        ->and($product->stock)->toBe(25)
        ->and($product->category->name)->toBe('Jacket')
        ->and($product->sizes->pluck('size')->sort()->values()->all())->toBe(['M', 'XL'])
        ->and($product->genders->pluck('gender')->all())->toBe(['Woman']);
});

test('authenticated users can delete a product', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $image = UploadedFile::fake()->image('shirt.jpg');

    $this->actingAs($user)->post(route('product.store'), [
        'name' => 'To Delete',
        'description' => 'Will be deleted.',
        'sizes' => ['S'],
        'gender' => 'Men',
        'basePricing' => '29.99',
        'stock' => 5,
        'category' => 'T-Shirt',
        'images' => [$image],
    ]);

    $product = Product::first();

    $this->actingAs($user)->delete(route('product.destroy', $product));

    expect(Product::find($product->id))->toBeNull()
        ->and(Product::count())->toBe(0);
});
