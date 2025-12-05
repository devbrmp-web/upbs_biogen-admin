<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'payment_method',
        'gateway_transaction_id',
        'gateway_reference',
        'snap_token',
        'redirect_url',
        'pnbp_receipt_no',
        'amount',
        'status',
        'gateway_status',
        'transaction_status',
        'fraud_status',
        'paid_at',
        'expires_at',
        'gateway_response',
        'signature_verification',
        'gateway_signature',
        'payment_ip',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
        'settlement_time' => 'datetime',
        'gateway_response' => 'array',
    ];

    // Payment status constants
    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_EXPIRED = 'expired';

    // Payment method constants
    const METHOD_VA_BCA = 'va_bca';
    const METHOD_VA_BNI = 'va_bni';
    const METHOD_VA_BRI = 'va_bri';
    const METHOD_VA_MANDIRI = 'va_mandiri';
    const METHOD_QRIS = 'qris';
    const METHOD_BANK_TRANSFER = 'bank_transfer';

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
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_EXPIRED);
    }

    /**
     * Accessors
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PAID => 'Paid',
            self::STATUS_FAILED => 'Failed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_EXPIRED => 'Expired',
            default => 'Unknown'
        };
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return match($this->payment_method) {
            self::METHOD_VA_BCA => 'Virtual Account BCA',
            self::METHOD_VA_BNI => 'Virtual Account BNI',
            self::METHOD_VA_BRI => 'Virtual Account BRI',
            self::METHOD_VA_MANDIRI => 'Virtual Account Mandiri',
            self::METHOD_QRIS => 'QRIS',
            self::METHOD_BANK_TRANSFER => 'Bank Transfer',
            default => ucfirst(str_replace('_', ' ', $this->payment_method))
        };
    }

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Business Logic Methods
     */
    public function markAsPaid(?string $pnbpReceiptNo = null, array $gatewayResponse = []): void
    {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
            'pnbp_receipt_no' => $pnbpReceiptNo,
            'gateway_response' => array_merge($this->gateway_response ?? [], $gatewayResponse),
        ]);

        // Update the related order
        $this->order->markAsPaid($pnbpReceiptNo);
    }

    public function markAsFailed(array $gatewayResponse = []): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'gateway_response' => array_merge($this->gateway_response ?? [], $gatewayResponse),
        ]);
    }

    public function markAsExpired(): void
    {
        $this->update([
            'status' => self::STATUS_EXPIRED,
        ]);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    /**
     * Create payment for order
     */
    public static function createForOrder(Order $order, string $paymentMethod, array $gatewayData = []): self
    {
        return static::create([
            'order_id' => $order->id,
            'payment_method' => $paymentMethod,
            'amount' => $order->total_amount,
            'gateway_transaction_id' => $gatewayData['transaction_id'] ?? null,
            'gateway_reference' => $gatewayData['reference'] ?? null,
            'gateway_status' => $gatewayData['gateway_status'] ?? null,
            'fraud_status' => $gatewayData['fraud_status'] ?? null,
            'expires_at' => $gatewayData['expires_at'] ?? now()->addHours(24),
            'gateway_response' => $gatewayData,
            'payment_ip' => request()->ip(),
        ]);
    }

    /**
     * Apply Midtrans status mapping and persist.
     */
    public function applyMidtransStatus(array $midtrans): void
    {
        $this->gateway_status = $midtrans['transaction_status'] ?? ($midtrans['status'] ?? null);
        $this->transaction_status = $midtrans['transaction_status'] ?? $this->transaction_status;
        $this->fraud_status = $midtrans['fraud_status'] ?? null;
        $this->gateway_signature = $midtrans['signature_key'] ?? null;

        // Merge gateway response snapshot
        $mergedResponse = array_merge($this->gateway_response ?? [], $midtrans);

        // Map to internal payment status
        $mapped = match ($this->gateway_status) {
            'settlement', 'capture' => self::STATUS_PAID,
            'pending' => self::STATUS_PENDING,
            'expire' => self::STATUS_EXPIRED,
            'deny', 'cancel' => self::STATUS_FAILED,
            default => $this->status,
        };

        // Persist
        $this->status = $mapped;
        $this->gateway_response = $mergedResponse;

        if ($mapped === self::STATUS_PAID && !$this->paid_at) {
            $this->paid_at = now();
            $this->settlement_time = isset($midtrans['settlement_time']) ? \Carbon\Carbon::parse($midtrans['settlement_time']) : $this->settlement_time;
        }

        $this->save();

        // Sync snapshot to order
        $order = $this->order;
        if ($order) {
            $order->payment_type = $midtrans['payment_type'] ?? $order->payment_type;
            $order->transaction_id = $midtrans['transaction_id'] ?? $order->transaction_id;
            $order->transaction_status = $this->gateway_status;
            $order->settlement_time = isset($midtrans['settlement_time']) ? \Carbon\Carbon::parse($midtrans['settlement_time']) : $order->settlement_time;
            $order->gross_amount = isset($midtrans['gross_amount']) ? (float) $midtrans['gross_amount'] : $order->gross_amount;
            $order->save();
        }

        // If paid, cascade to order
        if ($mapped === self::STATUS_PAID) {
            $order->markAsPaid($midtrans['pnbp_receipt_no'] ?? $this->pnbp_receipt_no);
        }
    }
}
