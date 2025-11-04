<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'shipping_method',
        'status',
        'courier_name',
        'tracking_number',
        'ready_for_pickup_at',
        'shipped_at',
        'delivered_at',
    ];

    protected $casts = [
        'ready_for_pickup_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    // Shipment status constants
    const STATUS_PENDING = 'pending';
    const STATUS_READY_FOR_PICKUP = 'ready_for_pickup';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';
    
    // Courier constants
    const COURIER_POS_INDONESIA = 'Pos Indonesia';
    const COURIER_INDAH_CARGO = 'Indah Cargo';

    // Shipping method constants (same as Order)
    const SHIPPING_PICKUP = 'pickup';
    const SHIPPING_DELIVERY = 'delivery';

    /**
     * Relationships
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Scopes
     */
    public function scopeForPickup($query)
    {
        return $query->where('shipping_method', self::SHIPPING_PICKUP);
    }

    public function scopeForDelivery($query)
    {
        return $query->where('shipping_method', self::SHIPPING_DELIVERY);
    }

    public function scopeReadyForPickup($query)
    {
        return $query->where('status', self::STATUS_READY_FOR_PICKUP);
    }

    public function scopeShipped($query)
    {
        return $query->where('status', self::STATUS_SHIPPED);
    }

    /**
     * Accessors
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_READY_FOR_PICKUP => 'Ready for Pickup',
            self::STATUS_SHIPPED => 'Shipped',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_CANCELLED => 'Cancelled',
            default => 'Unknown'
        };
    }

    public function getShippingMethodLabelAttribute(): string
    {
        return match($this->shipping_method) {
            self::SHIPPING_PICKUP => 'Ambil di Kantor',
            self::SHIPPING_DELIVERY => 'Pengiriman',
            default => 'Unknown'
        };
    }

    public function getIsPickupAttribute(): bool
    {
        return $this->shipping_method === self::SHIPPING_PICKUP;
    }

    public function getIsDeliveryAttribute(): bool
    {
        return $this->shipping_method === self::SHIPPING_DELIVERY;
    }

    /**
     * Business Logic Methods
     */
    public function markAsReadyForPickup(): void
    {
        $this->update([
            'status' => self::STATUS_READY_FOR_PICKUP,
            'ready_for_pickup_at' => now(),
        ]);

        // Update order status
        $this->order->markAsShipped();
    }

    public function markAsPickedUp(): void
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);

        // Update order status
        $this->order->markAsCompleted();
    }

    public function markAsShipped(array $shippingData = []): void
    {
        $updateData = [
            'status' => self::STATUS_SHIPPED,
            'shipped_at' => now(),
        ];

        if (!empty($shippingData['courier_name'])) {
            $updateData['courier_name'] = $shippingData['courier_name'];
        }
        if (!empty($shippingData['tracking_number'])) {
            $updateData['tracking_number'] = $shippingData['tracking_number'];
        }

        $this->update($updateData);

        // Update order status
        $this->order->markAsShipped($shippingData);
    }

    public function markAsDelivered(): void
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);

        // Update order status
        $this->order->markAsCompleted();
    }

    public function markAsCancelled(string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    /**
     * Create shipment for order
     */
    public static function createForOrder(Order $order): self
    {
        return static::create([
            'order_id' => $order->id,
            'shipping_method' => $order->shipping_method,
            'status' => self::STATUS_PENDING,
        ]);
    }

    /**
     * Get call center instructions
     */
    public function getCallCenterInstructions(): string
    {
        if ($this->is_pickup) {
            return 'Customer will pick up at office. Prepare order for pickup.';
        }

        return 'Customer needs shipping coordination. Contact call center to arrange delivery.';
    }
}
