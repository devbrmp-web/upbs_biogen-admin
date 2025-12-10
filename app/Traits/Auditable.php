<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    /**
     * Boot the trait.
     */
    public static function bootAuditable()
    {
        static::created(function (Model $model) {
            // Check if logging is enabled for this model
            if (!self::shouldLog('create')) {
                return;
            }

            AuditLog::logCreate(
                $model,
                self::getAuditDescription($model, 'Created'),
                self::getAuditCategory($model)
            );
        });

        static::updated(function (Model $model) {
            // Check if logging is enabled for this model
            if (!self::shouldLog('update')) {
                return;
            }

            // Get changed attributes
            $changes = $model->getChanges();
            $original = $model->getOriginal();
            
            // Filter out ignored attributes (like timestamps)
            $ignoredAttributes = ['updated_at', 'created_at', 'deleted_at'];
            $oldData = [];
            
            foreach ($changes as $key => $value) {
                if (in_array($key, $ignoredAttributes)) {
                    continue;
                }
                $oldData[$key] = $original[$key] ?? null;
            }

            if (empty($oldData)) {
                return; // No meaningful changes
            }

            AuditLog::logUpdate(
                $model,
                $oldData,
                self::getAuditDescription($model, 'Updated'),
                self::getAuditCategory($model)
            );
        });

        static::deleted(function (Model $model) {
            // Check if logging is enabled for this model
            if (!self::shouldLog('delete')) {
                return;
            }

            AuditLog::logDelete(
                $model,
                self::getAuditDescription($model, 'Deleted'),
                self::getAuditCategory($model)
            );
        });
    }

    /**
     * Determine if the action should be logged.
     */
    protected static function shouldLog(string $action): bool
    {
        // Don't log if running in console (seeders, etc) unless explicitly allowed
        // But we want to capture admin actions which might be via HTTP.
        // If it's a seeder, usually we might want to skip, but for now let's keep it simple.
        
        // Ensure user is authenticated or it's a system action we want to track
        // For now, let's log everything.
        return true;
    }

    /**
     * Get the description for the audit log.
     */
    protected static function getAuditDescription(Model $model, string $action): string
    {
        $name = class_basename($model);
        
        // Try to find a display name/identifier
        $identifier = $model->name ?? $model->title ?? $model->code ?? $model->order_number ?? $model->id;
        
        return "{$action} {$name}: {$identifier}";
    }

    /**
     * Get the category for the audit log.
     */
    protected static function getAuditCategory(Model $model): string
    {
        // Default mapping based on model class name
        $className = class_basename($model);
        
        return match($className) {
            'Order', 'OrderItem', 'Shipment' => AuditLog::CATEGORY_ORDER_MANAGEMENT,
            'Product', 'Variety', 'SeedLot', 'Commodity', 'SeedClass' => AuditLog::CATEGORY_INVENTORY_MANAGEMENT,
            'User', 'Role' => AuditLog::CATEGORY_USER_MANAGEMENT,
            'Payment' => AuditLog::CATEGORY_PAYMENT_PROCESSING,
            default => AuditLog::CATEGORY_SYSTEM_CONFIGURATION,
        };
    }
}
