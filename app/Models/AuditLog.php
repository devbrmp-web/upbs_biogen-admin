<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'action',
        'table_name',
        'record_id',
        'route_name',
        'url',
        'method',
        'ip_address',
        'user_agent',
        'old_data',
        'new_data',
        'description',
        'metadata',
        'category',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
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

    public function scopeByEntity($query, string $tableName, $recordId = null)
    {
        $query = $query->where('table_name', $tableName);
        
        if ($recordId !== null) {
            $query->where('record_id', $recordId);
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
        if (empty($this->table_name)) {
            return 'Unknown';
        }

        return ucwords(str_replace('_', ' ', Str::singular($this->table_name)));
    }

    /**
     * Static methods for logging
     */
    public static function logAction(
        string $action,
        string $tableName = null,
        $recordId = null,
        array $oldData = null,
        array $newData = null,
        string $description = null,
        string $category = null,
        array $metadata = []
    ): self {
        return static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'table_name' => $tableName,
            'record_id' => $recordId,
            'route_name' => request()->route()?->getName(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'old_data' => $oldData,
            'new_data' => $newData,
            'description' => $description,
            'category' => $category,
            'metadata' => $metadata,
        ]);
    }

    public static function logCreate($model, string $description = null, string $category = null): self
    {
        return static::logAction(
            self::ACTION_CREATE,
            $model->getTable(),
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
            $model->getTable(),
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
            $model->getTable(),
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
            'users',
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
            'users',
            $user->id,
            null,
            ['email' => $user->email, 'name' => $user->name],
            "User logged out: {$user->email}",
            self::CATEGORY_AUTHENTICATION
        );
    }
}
