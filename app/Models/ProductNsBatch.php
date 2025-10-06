<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductNsBatch extends Model
{
    protected $fillable = [
        'product_id',
        'year',
        'quantity',
        'unit',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}