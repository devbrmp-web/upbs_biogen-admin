<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Commodity extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'image_path',
        'is_active',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($commodity) {
            if (empty($commodity->slug)) {
                $commodity->slug = Str::slug($commodity->name);
            }
        });

        static::updating(function ($commodity) {
            if ($commodity->isDirty('name') && empty($commodity->slug)) {
                $commodity->slug = Str::slug($commodity->name);
            }
        });
    }

    /**
     * Get the varieties for the commodity.
     */
    public function varieties()
    {
        return $this->hasMany(Variety::class);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
