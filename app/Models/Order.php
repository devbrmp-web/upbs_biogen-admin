<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use App\Traits\Auditable;

class Order extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'order_code',
        'snap_token',
        'customer_name',
        'customer_address',
        'customer_phone',
        'customer_email',
        'status',
        'shipping_method',
        'subtotal',
        'shipping_cost',
        'service_fee',
        'app_fee',
        'total_amount',
        'gross_amount',
        'pnbp_receipt_no',
        'payment_type',
        'transaction_id',
        'transaction_status',
        'paid_at',
        'settlement_time',
        'courier_name',
        'courier_service',
        'tracking_number',
        'completed_at',
        'notes',
        'payment_deadline',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'service_fee' => 'decimal:2',
        'app_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'settlement_time' => 'datetime',
        'completed_at' => 'datetime',
        'payment_deadline' => 'datetime',
        'notes' => 'array',
    ];

    // Shipping method constants
    const SHIPPING_PICKUP = 'pickup';
    const SHIPPING_DELIVERY = 'delivery';

    // Order status constants
    const STATUS_AWAITING_PAYMENT = 'awaiting_payment';
    const STATUS_PAID = 'paid';
    const STATUS_PROCESSING = 'processing';
    const STATUS_PICKUP_READY = 'pickup_ready';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Boot method to generate order code automatically
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($order) {
            if (empty($order->order_code)) {
                $order->order_code = static::generateOrderCode();
            }
        });
    }

    /**
     * Generate unique order code
     */
    public static function generateOrderCode(): string
    {
        do {
            $code = 'WUB-' . date('Ymd') . '-' . strtoupper(Str::random(6));
        } while (static::where('order_code', $code)->exists());
        
        return $code;
    }

    /**
     * Relationships
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'record_id')->where('table_name', 'orders');
    }

    /**
     * Scopes
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByShippingMethod($query, string $method)
    {
        return $query->where('shipping_method', $method);
    }

    public function scopeAwaitingPayment($query)
    {
        return $query->where('status', self::STATUS_AWAITING_PAYMENT);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeForPickup($query)
    {
        return $query->where('shipping_method', self::SHIPPING_PICKUP);
    }

    public function scopeForDelivery($query)
    {
        return $query->where('shipping_method', self::SHIPPING_DELIVERY);
    }

    /**
     * Accessors & Mutators
     */
    public function getShippingMethodLabelAttribute(): string
    {
        return match($this->shipping_method) {
            self::SHIPPING_PICKUP => 'Pickup at BRMP',
            self::SHIPPING_DELIVERY => 'Delivery',
            default => 'Unknown'
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_AWAITING_PAYMENT => 'Awaiting Payment',
            self::STATUS_PAID => 'Paid',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_PICKUP_READY => 'Ready for Pickup',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            default => 'Unknown'
        };
    }

    /**
     * Get status label as method (for backward compatibility)
     */
    public function getStatusLabel(): string
    {
        return $this->status_label;
    }

    public function getIsPickupAttribute(): bool
    {
        return $this->shipping_method === self::SHIPPING_PICKUP;
    }

    public function getIsDeliveryAttribute(): bool
    {
        return $this->shipping_method === self::SHIPPING_DELIVERY;
    }

    public function getIsPaidAttribute(): bool
    {
        return !is_null($this->paid_at);
    }



    public function getIsCompletedAttribute(): bool
    {
        return !is_null($this->completed_at);
    }

    /**
     * Business Logic Methods
     */
    public function markAsPaid(string $pnbpReceiptNo = null): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
            'pnbp_receipt_no' => $pnbpReceiptNo,
        ]);
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
        ]);
    }

    public function markAsPickupReady(): void
    {
        if (!$this->is_pickup) {
            throw new \InvalidArgumentException('Order must be pickup method to mark as pickup ready');
        }
        
        $this->update([
            'status' => self::STATUS_PICKUP_READY,
        ]);
    }


    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    /**
     * Status transition validation
     * Simplified flow:
     * - Delivery: Processing -> Completed
     * - Pickup: Ready for Pickup -> Completed
     */
    public function canTransitionTo(string $newStatus): bool
    {
        $validTransitions = [
            self::STATUS_AWAITING_PAYMENT => [self::STATUS_PAID, self::STATUS_CANCELLED],
            self::STATUS_PAID => [self::STATUS_PROCESSING, self::STATUS_CANCELLED],
            self::STATUS_PROCESSING => $this->is_pickup 
                ? [self::STATUS_PICKUP_READY, self::STATUS_CANCELLED]
                : [self::STATUS_COMPLETED, self::STATUS_CANCELLED],
            self::STATUS_PICKUP_READY => [self::STATUS_COMPLETED, self::STATUS_CANCELLED],
            self::STATUS_COMPLETED => [],
            self::STATUS_CANCELLED => [],
        ];

        return in_array($newStatus, $validTransitions[$this->status] ?? []);
    }

    public function getNextValidStatuses(): array
    {
        $validTransitions = [
            self::STATUS_AWAITING_PAYMENT => [self::STATUS_PAID, self::STATUS_CANCELLED],
            self::STATUS_PAID => [self::STATUS_PROCESSING, self::STATUS_CANCELLED],
            self::STATUS_PROCESSING => $this->is_pickup 
                ? [self::STATUS_PICKUP_READY, self::STATUS_CANCELLED]
                : [self::STATUS_COMPLETED, self::STATUS_CANCELLED],
            self::STATUS_PICKUP_READY => [self::STATUS_COMPLETED, self::STATUS_CANCELLED],
            self::STATUS_COMPLETED => [],
            self::STATUS_CANCELLED => [],
        ];

        return $validTransitions[$this->status] ?? [];
    }

    /**
     * Calculate totals
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->items->sum('total_price');
        $serviceFee = round($subtotal * 0.01);
        $appFee = 4000;
        
        $totalAmount = $subtotal + $serviceFee + $appFee;
        
        $this->update([
            'subtotal' => $subtotal,
            'service_fee' => $serviceFee,
            'app_fee' => $appFee,
            'total_amount' => $totalAmount,
        ]);
    }

    /**
     * Get shipping instructions based on method
     */
    public function getShippingInstructions(): string
    {
        if ($this->is_pickup) {
            return 'Your order is ready for pickup at BRMP Biogen office (Lobi). Office hours: Monday-Friday 08:00-16:00. Please bring a valid ID and your order code: ' . $this->order_code;
        }
        
        return 'For shipping arrangements, please contact our Call Center/WhatsApp: +62-XXX-XXXX-XXXX (Office hours: Monday-Friday 08:00-16:00). They will coordinate the delivery with you directly. No automatic shipping calculation is applied.';
    }

    /**
     * Check if order needs call center coordination
     */
    public function needsCallCenterCoordination(): bool
    {
        return $this->is_delivery && in_array($this->status, [
            self::STATUS_PAID, 
            self::STATUS_PROCESSING
        ]);
    }

    /**
     * Get call center contact information
     */
    public function getCallCenterContact(): array
    {
        return [
            'phone' => '+62-XXX-XXXX-XXXX',
            'whatsapp' => '+62-XXX-XXXX-XXXX',
            'hours' => 'Monday-Friday 08:00-16:00',
            'message' => 'Please contact Call Center/WhatsApp for delivery coordination. No automatic shipping calculation is applied in the system.'
        ];
    }
}
