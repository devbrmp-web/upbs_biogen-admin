<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Variety extends Model
{
    use HasFactory;

    protected $fillable = [
        'commodity_id',
        'name',
        'slug',
        'sku',
        'description',
        'price',
        'stock',
        'stock_bs_kg',
        'stock_fs_kg',
        'planlet',
        'minimum_limit',
        'status',
        'image_path',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'stock_bs_kg' => 'integer',
        'stock_fs_kg' => 'integer',
        'planlet' => 'integer',
        'minimum_limit' => 'integer',
    ];

    protected static function boot(): void
   {
       parent::boot();

       static::creating(function ($variety) {
           if (empty($variety->slug)) {
               $variety->slug = Str::slug($variety->name);
           }
           
           // Autogenerate SKU if not provided
           if (empty($variety->sku)) {
               $commoditySlug = $variety->commodity?->slug ?? 'unknown';
               $nameSlug = Str::slug($variety->name);
               $variety->sku = strtoupper($commoditySlug . '-' . $nameSlug);
           }
       });

       static::updating(function ($variety) {
           if ($variety->isDirty('name') && empty($variety->slug)) {
               $variety->slug = Str::slug($variety->name);
           }
           
           // Jangan menimpa SKU eksplisit; hanya generate jika SKU kosong/null
           if (empty($variety->sku)) {
               $commoditySlug = $variety->commodity?->slug ?? 'unknown';
               $nameSlug = Str::slug($variety->name);
               $variety->sku = strtoupper($commoditySlug . '-' . $nameSlug);
           }
           
           $variety->clearStockCache();
       });

        static::updated(function ($variety) {
            $variety->clearStockCache();
        });

        static::deleted(function ($variety) {
            $variety->clearStockCache();
        });
    }

    /**
     * Get the commodity that owns the variety.
     */
    public function commodity()
    {
        return $this->belongsTo(Commodity::class);
    }

    /**
     * Get the seed lots for the variety.
     */
    public function seedLots()
    {
        return $this->hasMany(SeedLot::class);
    }

    /**
     * Get active seed lots for the variety.
     */
    public function activeSeedLots()
    {
        return $this->hasMany(SeedLot::class)->sellable();
    }

   /**
    * Get the total stock from all sources.
    */
   public function getTotalStockAttribute(): float
   {
       // Use cached value if available
       $cacheKey = "variety_total_stock_{$this->id}";
       
       return cache()->remember($cacheKey, now()->addMinutes(5), function () {
            // Primary: total dari seed lots yang is_sellable dan unit kg
            $seedLotStock = 0;
            if ($this->relationLoaded('seedLots')) {
                $seedLotStock = $this->seedLots
                    ->where('is_sellable', true)
                    ->where('unit', 'kg')
                    ->sum('quantity');
            } else {
                $seedLotStock = $this->seedLots()
                    ->where('is_sellable', true)
                    ->where('unit', 'kg')
                    ->sum('quantity');
            }
            
            // Jika ada stok dari seed lots, gunakan itu
            if ($seedLotStock > 0) {
                return $seedLotStock;
            }
            
            // Fallback 1: gabungan stock_bs_kg + stock_fs_kg
            $bsFsStock = ($this->stock_bs_kg ?? 0) + ($this->stock_fs_kg ?? 0);
            if ($bsFsStock > 0) {
                return $bsFsStock;
            }
            
            // Fallback 2: kolom stock
            return $this->stock ?? 0;
       });
   }

    /**
     * Get the stock status based on total stock and minimum limit.
     */
    public function getStockStatusAttribute(): string
    {
        // Use cached value if available
        $cacheKey = "variety_stock_status_{$this->id}";
        
        return cache()->remember($cacheKey, now()->addMinutes(5), function () {
            // Use pre-calculated total_stock_calculated if available from selectRaw
            $totalStock = $this->attributes['total_stock_calculated'] ?? $this->total_stock;
            $minimumStockLimit = $this->minimum_limit ?? 0;
            
            // Habis: jika total stok <= 0
            if ($totalStock <= 0) {
                return 'Habis';
            }
            
            // Restock: jika total stok > 0 TAPI <= minimum_stock_limit
            if ($totalStock > 0 && $totalStock <= $minimumStockLimit) {
                return 'Restock';
            }
            
            // Tersedia: jika total stok > minimum_stock_limit
            return 'Tersedia';
        });
    }

    /**
     * Clear stock-related cache when variety or related data changes.
     */
    public function clearStockCache(): void
    {
        cache()->forget("variety_total_stock_{$this->id}");
        cache()->forget("variety_stock_status_{$this->id}");
    }



    /**
     * Scope a query to only include varieties with stock.
     */
    public function scopeInStock($query)
    {
        return $query->where(function ($q) {
            $q->where('stock', '>', 0)
              ->orWhere('stock_bs_kg', '>', 0)
              ->orWhere('stock_fs_kg', '>', 0);
        });
    }

    /**
     * Scope a query to include varieties with available stock from seed lots.
     */
    public function scopeWithAvailableStock($query)
    {
        return $query->whereHas('seedLots', function ($seedLots) {
            $seedLots->where('is_sellable', true)
                ->where('quantity', '>', 0);
        });
    }

    /**
     * Scope a query to include varieties with no available stock.
     */
    public function scopeOutOfStock($query)
    {
        return $query->whereDoesntHave('seedLots', function ($seedLots) {
            $seedLots->where('is_sellable', true)
                ->where('quantity', '>', 0);
        });
    }

    /**
     * Scope a query to include varieties that need restocking.
     */
    public function scopeNeedsRestock($query)
    {
        // Use a safe subquery comparing total sellable lot quantity against variety minimum_limit.
        // This avoids HAVING in a whereHas subquery which can cause SQL errors when no rows match.
        return $query->whereRaw('(
            SELECT COALESCE(SUM(quantity), 0)
            FROM seed_lots sl
            WHERE sl.variety_id = varieties.id AND sl.is_sellable = true AND sl.unit = "kg"
        ) <= varieties.minimum_limit');
    }

    /**
     * Scope a query to include stock calculations using subqueries.
     */
    public function scopeWithStockCalculations($query)
    {
        return $query->selectRaw('varieties.*, 
            COALESCE((SELECT SUM(quantity) FROM seed_lots WHERE seed_lots.variety_id = varieties.id AND is_sellable = true AND unit = "kg"), 0) as total_stock_calculated,
            COALESCE((SELECT SUM(quantity) FROM seed_lots sl JOIN seed_classes sc ON sl.seed_class_id = sc.id WHERE sl.variety_id = varieties.id AND sl.is_sellable = true AND sl.unit = "kg" AND sc.code = "BS"), 0) as bs_stock_calculated,
            COALESCE((SELECT SUM(quantity) FROM seed_lots sl JOIN seed_classes sc ON sl.seed_class_id = sc.id WHERE sl.variety_id = varieties.id AND sl.is_sellable = true AND sl.unit = "kg" AND sc.code = "FS"), 0) as fs_stock_calculated');
    }

    /**
     * Scope a query to filter by stock status efficiently.
     */
    public function scopeByStockStatus($query, $status)
    {
        // Normalize status to lowercase and trim spaces to be resilient to UI values
        $normalized = strtolower(trim((string) $status));

        switch ($normalized) {
            case 'tersedia':
                return $query->withAvailableStock();
            case 'habis':
                return $query->outOfStock();
            case 'restock':
                return $query->needsRestock();
            default:
                return $query;
        }
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
