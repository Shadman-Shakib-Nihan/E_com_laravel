<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'base_pricing',
        'stock',
        'discount',
        'discount_type',
        'category_id',
    ];

    // ─── Relationships ────────────────────────────────────────

    /**
     * A product belongs to one category
     * products.category_id → product_categories.id
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    /**
     * A product has many sizes (XS, S, M, XL, XXL)
     * product_sizes.product_id → products.id
     */
    public function sizes(): HasMany
    {
        return $this->hasMany(ProductSize::class);
    }

    /**
     * A product has many genders (Men, Woman, Unisex)
     * product_genders.product_id → products.id
     */
    public function genders(): HasMany
    {
        return $this->hasMany(ProductGender::class);
    }

    /**
     * A product has many images
     * product_images.product_id → products.id
     */
    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    // ─── Helpers ──────────────────────────────────────────────

    /**
     * Get only the primary/main display image
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_primary', true);
    }

    /**
     * Get discounted price after applying discount
     */
    public function discountedPrice(): float
    {
        if (! $this->discount) {
            return $this->base_pricing;
        }

        return $this->base_pricing - ($this->base_pricing * $this->discount / 100);
    }

    /**
     * Check if product is in stock
     */
    public function inStock(): bool
    {
        return $this->stock > 0;
    }
}
