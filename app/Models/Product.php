<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'category_id', 
        'name', 
        'slug', 
        'sku', 
        'price', 
        'stock', 
        'status', 
        'description', 
        'image_path',
        'stock_bs_kg',
        'stock_fs_kg',
        'minimum_limit',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function productNsBatches(): HasMany
    {
        return $this->hasMany(ProductNsBatch::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            // Auto-generate slug if not provided, ensure uniqueness
            $base = $product->slug ?: Str::slug($product->name);
            $slug = $base;
            $i = 2;
            while (static::query()->where('slug', $slug)->exists()) {
                $slug = $base.'-'.$i;
                $i++;
            }
            $product->slug = $slug;

            // Ensure default numeric values when missing
            $product->price = $product->price ?? 0;
            $product->stock_bs_kg = $product->stock_bs_kg ?? 0;
            $product->stock_fs_kg = $product->stock_fs_kg ?? 0;
            $product->minimum_limit = $product->minimum_limit ?? 0;
        });
    }

    public function getTotalStockAttribute(): float
    {
        return (float)($this->stock ?? 0)
            + (float)($this->stock_bs_kg ?? 0)
            + (float)($this->stock_fs_kg ?? 0);
    }

    public function getStockStatusAttribute(): string
    {
        $total = $this->total_stock;
        $min   = (float)($this->minimum_limit ?? 0);
        if ($total <= 0) return 'Habis';
        if ($total < $min) return 'Restock';
        return 'Tersedia';
    }
}
