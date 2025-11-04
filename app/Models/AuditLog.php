<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'route_name',
        'url',
        'method',
        'ip_address',
        'user_agent',
        'old_values',
        'new_values',
        'description',
        'metadata',
        'category',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
    ];

    // Action constants
    const ACTION_CREATE = 'CREATE';
    const ACTION_UPDATE = 'UPDATE';
    const ACTION_DELETE = 'DELETE';
    const ACTION_VIEW = 'VIEW';
    const ACTION_LOGIN = 'LOGIN';
    const ACTION_LOGOUT = 'LOGOUT';

    // Category constants
    const CATEGORY_ORDER_MANAGEMENT = 'order_management';
    const CATEGORY_INVENTORY_MANAGEMENT = 'inventory_management';
    const CATEGORY_USER_MANAGEMENT = 'user_management';
    const CATEGORY_PAYMENT_PROCESSING = 'payment_processing';
    const CATEGORY_SHIPPING_FULFILLMENT = 'shipping_fulfillment';
    const CATEGORY_SYSTEM_CONFIGURATION = 'system_configuration';
    const CATEGORY_DATA_EXPORT = 'data_export';
    const CATEGORY_AUTHENTICATION = 'authentication';

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scopes
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByModel($query, string $modelType, $modelId = null)
    {
        $query = $query->where('model_type', $modelType);
        
        if ($modelId !== null) {
            $query->where('model_id', $modelId);
        }
        
        return $query;
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Accessors
     */
    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            self::ACTION_CREATE => 'Created',
            self::ACTION_UPDATE => 'Updated',
            self::ACTION_DELETE => 'Deleted',
            self::ACTION_VIEW => 'Viewed',
            self::ACTION_LOGIN => 'Logged In',
            self::ACTION_LOGOUT => 'Logged Out',
            default => ucfirst(strtolower($this->action))
        };
    }

    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            self::CATEGORY_ORDER_MANAGEMENT => 'Order Management',
            self::CATEGORY_INVENTORY_MANAGEMENT => 'Inventory Management',
            self::CATEGORY_USER_MANAGEMENT => 'User Management',
            self::CATEGORY_PAYMENT_PROCESSING => 'Payment Processing',
            self::CATEGORY_SHIPPING_FULFILLMENT => 'Shipping & Fulfillment',
            self::CATEGORY_SYSTEM_CONFIGURATION => 'System Configuration',
            self::CATEGORY_DATA_EXPORT => 'Data Export',
            self::CATEGORY_AUTHENTICATION => 'Authentication',
            default => ucfirst(str_replace('_', ' ', $this->category ?? 'Unknown'))
        };
    }

    public function getModelNameAttribute(): string
    {
        if (empty($this->model_type)) {
            return 'Unknown';
        }

        $parts = explode('\\', $this->model_type);
        return end($parts);
    }

    /**
     * Static methods for logging
     */
    public static function logAction(
        string $action,
        string $modelType = null,
        $modelId = null,
        array $oldValues = null,
        array $newValues = null,
        string $description = null,
        string $category = null,
        array $metadata = []
    ): self {
        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'route_name' => request()->route()?->getName(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
            'category' => $category,
            'metadata' => $metadata,
        ]);
    }

    public static function logCreate($model, string $description = null, string $category = null): self
    {
        return static::logAction(
            self::ACTION_CREATE,
            get_class($model),
            $model->getKey(),
            null,
            $model->toArray(),
            $description ?? "Created {$model->getTable()} record",
            $category
        );
    }

    public static function logUpdate($model, array $oldValues, string $description = null, string $category = null): self
    {
        return static::logAction(
            self::ACTION_UPDATE,
            get_class($model),
            $model->getKey(),
            $oldValues,
            $model->toArray(),
            $description ?? "Updated {$model->getTable()} record",
            $category
        );
    }

    public static function logDelete($model, string $description = null, string $category = null): self
    {
        return static::logAction(
            self::ACTION_DELETE,
            get_class($model),
            $model->getKey(),
            $model->toArray(),
            null,
            $description ?? "Deleted {$model->getTable()} record",
            $category
        );
    }

    public static function logLogin(User $user): self
    {
        return static::logAction(
            self::ACTION_LOGIN,
            User::class,
            $user->id,
            null,
            ['email' => $user->email, 'name' => $user->name],
            "User logged in: {$user->email}",
            self::CATEGORY_AUTHENTICATION
        );
    }

    public static function logLogout(User $user): self
    {
        return static::logAction(
            self::ACTION_LOGOUT,
            User::class,
            $user->id,
            null,
            ['email' => $user->email, 'name' => $user->name],
            "User logged out: {$user->email}",
            self::CATEGORY_AUTHENTICATION
        );
    }
}
