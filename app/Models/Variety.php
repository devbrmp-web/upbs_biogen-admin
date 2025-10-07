<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Variety extends Model
{
    use HasFactory;

    protected $fillable = [
        'commodity_id',
        'name',
        'slug',
        'sku',
        'description',
        'price',
        'stock',
        'stock_bs_kg',
        'stock_fs_kg',
        'minimum_limit',
        'status',
        'image',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'decimal:3',
        'stock_bs_kg' => 'decimal:3',
        'stock_fs_kg' => 'decimal:3',
        'minimum_limit' => 'decimal:3',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($variety) {
            if (empty($variety->slug)) {
                $variety->slug = Str::slug($variety->name);
            }
        });

        static::updating(function ($variety) {
            if ($variety->isDirty('name') && empty($variety->slug)) {
                $variety->slug = Str::slug($variety->name);
            }
        });
    }

    /**
     * Get the commodity that owns the variety.
     */
    public function commodity()
    {
        return $this->belongsTo(Commodity::class);
    }

    /**
     * Get the seed lots for the variety.
     */
    public function seedLots()
    {
        return $this->hasMany(SeedLot::class);
    }

    /**
     * Get active seed lots for the variety.
     */
    public function activeSeedLots()
    {
        return $this->hasMany(SeedLot::class)->sellable();
    }

    /**
     * Get dynamic stock status based on stock levels.
     */
    public function getStockStatusAttribute(): string
    {
        $total = $this->total_stock;
        $minimum = $this->minimum_limit ?? 0;

        if ($total <= 0) {
            return 'habis'; // Out of stock
        } elseif ($total <= $minimum) {
            return 'restock'; // Low stock, needs restocking
        } else {
            return 'tersedia'; // Available
        }
    }

    /**
     * Scope a query to only include varieties with stock.
     */
    public function scopeInStock($query)
    {
        return $query->where(function ($q) {
            $q->where('stock', '>', 0)
              ->orWhere('stock_bs_kg', '>', 0)
              ->orWhere('stock_fs_kg', '>', 0);
        });
    }

    /**
     * Get the total stock from all sources.
     */
    public function getTotalStockAttribute(): float
    {
        return ($this->stock ?? 0) + ($this->stock_bs_kg ?? 0) + ($this->stock_fs_kg ?? 0);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
