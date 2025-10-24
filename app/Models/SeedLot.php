<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeedLot extends Model
{
    use HasFactory;

    protected $fillable = [
        'variety_id',
        'seed_class_id',
        'lot_code',
        'production_year',
        'quantity',
        'unit',
        'price_per_unit',
        'is_sellable',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'price_per_unit' => 'decimal:2',
        'is_sellable' => 'boolean',
        'production_year' => 'integer',
    ];

    /**
     * Get the variety that owns the seed lot.
     */
    public function variety()
    {
        return $this->belongsTo(Variety::class);
    }

    /**
     * Get the seed class that owns the seed lot.
     */
    public function seedClass()
    {
        return $this->belongsTo(SeedClass::class);
    }

    /**
     * Scope a query to only include sellable seed lots.
     */
    public function scopeSellable($query)
    {
        return $query->where('is_sellable', true)->where('quantity', '>', 0);
    }

    /**
     * Scope a query to only include seed lots with stock.
     */
    public function scopeInStock($query)
    {
        return $query->where('quantity', '>', 0);
    }

    /**
     * Get the total value of the seed lot.
     */
    public function getTotalValueAttribute(): float
    {
        return $this->quantity * $this->price_per_unit;
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'lot_code';
    }

    /**
     * Boot method to handle cache clearing on model events.
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($seedLot) {
            $seedLot->clearVarietyStockCache();
        });

        static::updated(function ($seedLot) {
            $seedLot->clearVarietyStockCache();
        });

        static::deleted(function ($seedLot) {
            $seedLot->clearVarietyStockCache();
        });
    }

    /**
     * Clear variety stock cache when seed lot changes.
     */
    protected function clearVarietyStockCache(): void
    {
        if ($this->variety) {
            $this->variety->clearStockCache();
        }
    }
}
