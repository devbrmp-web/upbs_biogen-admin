<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class VarietyImage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'variety_id',
        'image_path',
        'is_primary',
        'order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'order' => 'integer',
    ];

    public function variety()
    {
        return $this->belongsTo(Variety::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        $baseUrl = rtrim((string) config('filesystems.disks.public.url'), '/');
        if ($baseUrl === '') {
            return null;
        }

        return $baseUrl.'/'.ltrim($this->image_path, '/');
    }

    public function publicUrl(): ?string
    {
        return $this->image_url;
    }

    protected static function booted(): void
    {
        static::deleted(function (VarietyImage $image) {
            if (! $image->image_path) {
                return;
            }

            Storage::disk('public')->delete($image->image_path);
        });
    }
}
