<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'image_path'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    protected static function booted(): void
    {
        static::creating(function (Category $category) {
            // Auto-generate slug if not provided, ensure uniqueness
            $base = $category->slug ?: Str::slug($category->name);
            $slug = $base;
            $i = 2;
            while (static::query()->where('slug', $slug)->exists()) {
                $slug = $base.'-'.$i;
                $i++;
            }
            $category->slug = $slug;
        });
    }
}
