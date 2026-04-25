<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Auditable;

class SeedClass extends Model
{
    use HasFactory, Auditable;

    protected $fillable = [
        'code',
        'name',
        'description',
        'stock_category',
        'default_unit',
        'min_order_qty',
        'step_increment',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'min_order_qty' => 'integer',
        'step_increment' => 'integer',
    ];

    /**
     * Get the seed lots for the seed class.
     */
    public function seedLots()
    {
        return $this->hasMany(SeedLot::class);
    }

    /**
     * Scope a query to only include active seed classes.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'code';
    }
}
