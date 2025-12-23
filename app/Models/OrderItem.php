<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'variety_id',
        'variety_name',
        'variety_sku',
        'unit_price',
        'price_at_order',
        'quantity',
        'total_price',
        'seed_lot_id',
        'seed_class',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'price_at_order' => 'decimal:2',
        'total_price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    /**
     * Relationships
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function variety(): BelongsTo
    {
        return $this->belongsTo(Variety::class);
    }

    public function seedLot(): BelongsTo
    {
        return $this->belongsTo(SeedLot::class);
    }

    /**
     * Boot method to calculate total price automatically
     */
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($orderItem) {
            $orderItem->total_price = (float) $orderItem->unit_price * (int) $orderItem->quantity;
        });
    }

    /**
     * Create order item from variety with snapshot data
     */
    public static function createFromVariety(Order $order, Variety $variety, int $quantity, SeedLot $seedLot = null): self
    {
        $unitPrice = $seedLot ? (float) $seedLot->price_per_unit : (float) $variety->price;

        return static::create([
            'order_id' => $order->id,
            'variety_id' => $variety->id,
            'variety_name' => $variety->name,
            'variety_sku' => $variety->sku,
            'unit_price' => $unitPrice,
            'price_at_order' => $unitPrice,
            'quantity' => $quantity,
            'seed_lot_id' => $seedLot?->id,
            'seed_class' => $seedLot?->seedClass?->code,
        ]);
    }

    /**
     * Get formatted unit price
     */
    public function getFormattedUnitPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->unit_price, 0, ',', '.');
    }

    /**
     * Get formatted total price
     */
    public function getFormattedTotalPriceAttribute(): string
    {
        return 'Rp ' . number_format($this->total_price, 0, ',', '.');
    }

    /**
     * Get product name (alias for variety_name)
     */
    public function getProductNameAttribute(): string
    {
        return $this->variety_name;
    }

    /**
     * Get product SKU (alias for variety_sku)
     */
    public function getProductSkuAttribute(): string
    {
        return $this->variety_sku;
    }
}
