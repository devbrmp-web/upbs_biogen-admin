<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Variety extends Model
{
    use Auditable, HasFactory;

    protected $fillable = [
        'commodity_id',
        'name',
        'slug',
        'sku',
        'description',
        'minimum_limit',
        'status',
        'is_active',
        'image_path',

        // Agricultural metadata
        'decree_number',
        'decree_date',
        'origin',
        'planting_age',
        'yield_potential',
        'average_yield',
        'primary_trait',
        'pest_resistance',
        'disease_resistance',
        'description_summary',
    ];

    protected $casts = [
        'minimum_limit' => 'integer',
        'is_active' => 'boolean',
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
                $variety->sku = static::generateUniqueSku($variety);
            }
        });

        static::updating(function ($variety) {
            if ($variety->isDirty('name') && empty($variety->slug)) {
                $variety->slug = Str::slug($variety->name);
            }

            // Only generate SKU if it's empty/null (immutable by default)
            if (empty($variety->sku)) {
                $variety->sku = static::generateUniqueSku($variety);
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

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(VarietyImage::class)->orderBy('order')->orderBy('id');
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

    public function getPrimaryImageUrlAttribute(): ?string
    {
        $primary = null;

        if ($this->relationLoaded('images')) {
            $primary = $this->images->firstWhere('is_primary', true);
        } else {
            $primary = $this->images()->where('is_primary', true)->first();
        }

        return $primary?->image_url ?: $this->image_url;
    }

    /**
     * Get active seed lots for the variety.
     */
    public function activeSeedLots()
    {
        return $this->hasMany(SeedLot::class)->sellable();
    }

    /**
     * Get the total stock from all sellable seed lots.
     */
    public function getTotalStockAttribute(): float
    {
        // Use pre-calculated value if available from selectRaw (via withStockCalculations scope)
        if (isset($this->attributes['total_stock_calculated'])) {
            return (float) $this->attributes['total_stock_calculated'];
        }

        // Use cached value if available
        $cacheKey = "variety_total_stock_all_{$this->id}";

        return cache()->remember($cacheKey, now()->addMinutes(5), function () {
            return (float) $this->seedLots()
                ->where('is_sellable', true)
                ->sum('quantity');
        });
    }

    /**
     * Get a summary of stocks grouped by seed class or category.
     */
    public function getStocksByCategory()
    {
        return $this->seedLots()
            ->where('is_sellable', true)
            ->with('seedClass')
            ->get()
            ->groupBy('seedClass.stock_category')
            ->map(function ($lots, $category) {
                return [
                    'category' => $category,
                    'total_quantity' => $lots->sum('quantity'),
                    'units' => $lots->pluck('unit')->unique()->values()->all(),
                ];
            });
    }

    /**
     * Get a detailed summary of stocks for each active seed class.
     */
    public function getStocksByClass()
    {
        return SeedClass::active()
            ->get()
            ->map(function ($class) {
                $quantity = $this->seedLots()
                    ->where('is_sellable', true)
                    ->where('seed_class_id', $class->id)
                    ->sum('quantity');
                
                return [
                    'class_id' => $class->id,
                    'code' => $class->code,
                    'name' => $class->name,
                    'category' => $class->stock_category,
                    'default_unit' => $class->default_unit,
                    'quantity' => (float) $quantity,
                    'formatted_quantity' => $quantity . ' ' . $class->default_unit,
                ];
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

            // Out of Stock: total stock = 0
            if ($totalStock == 0) {
                return 'Out of Stock';
            }

            // Restock: 0 < total stock <= minimum_limit
            if ($totalStock > 0 && $totalStock <= $minimumStockLimit) {
                return 'Restock';
            }

            // Available: total stock > minimum_limit
            return 'Available';
        });
    }

    /**
     * English label for stock status (alias of stock_status for clarity in views).
     */
    public function getStockStatusLabelAttribute(): string
    {
        return $this->stock_status; // Already English via accessor above
    }

    /**
     * Bootstrap badge context class based on stock status.
     */
    public function getStockStatusClassAttribute(): string
    {
        return match (strtolower($this->stock_status)) {
            'available' => 'success',
            'restock' => 'warning',
            'out of stock' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Clear stock-related cache when variety or related data changes.
     */
    public function clearStockCache(): void
    {
        cache()->forget("variety_total_stock_all_{$this->id}");
        cache()->forget("variety_stock_status_{$this->id}");
    }

    /**
     * Get the price range from sellable seed lots.
     * Returns formatted Rupiah string: single price or "Rp X - Rp Y" range.
     */
    public function getPriceRangeAttribute(): ?string
    {
        // Use pre-calculated values if available from selectRaw (via withPriceRange scope)
        if (isset($this->attributes['min_price_calculated']) && isset($this->attributes['max_price_calculated'])) {
            $minPrice = (int) $this->attributes['min_price_calculated'];
            $maxPrice = (int) $this->attributes['max_price_calculated'];
        } else {
            // Calculate from seed lots
            $priceData = $this->seedLots()
                ->where('is_sellable', true)
                ->where('quantity', '>', 0)
                ->selectRaw('MIN(price_per_unit) as min_price, MAX(price_per_unit) as max_price')
                ->first();

            if (! $priceData || ($priceData->min_price === null && $priceData->max_price === null)) {
                return null;
            }

            $minPrice = (int) $priceData->min_price;
            $maxPrice = (int) $priceData->max_price;
        }

        // No prices available
        if ($minPrice === 0 && $maxPrice === 0) {
            return null;
        }

        // Single price or range
        if ($minPrice === $maxPrice) {
            return 'Rp '.number_format($minPrice, 0, ',', '.');
        }

        return 'Rp '.number_format($minPrice, 0, ',', '.').' - Rp '.number_format($maxPrice, 0, ',', '.');
    }

    /**
     * Check if variety has any sellable seed lots with prices.
     */
    public function getHasPriceAttribute(): bool
    {
        if (isset($this->attributes['min_price_calculated'])) {
            return (int) $this->attributes['min_price_calculated'] > 0;
        }

        return $this->seedLots()
            ->where('is_sellable', true)
            ->where('quantity', '>', 0)
            ->where('price_per_unit', '>', 0)
            ->exists();
    }

    /**
     * Scope a query to only include varieties with stock from sellable seed lots.
     */
    public function scopeInStock($query)
    {
        return $query->whereHas('seedLots', function ($seedLots) {
            $seedLots->where('is_sellable', true)
                ->where('quantity', '>', 0);
        });
    }

    /**
     * Scope a query to include varieties with available stock from seed lots.
     */
    public function scopeWithAvailableStock($query)
    {
        // Available means: total sellable stock (any unit) is strictly greater than minimum_limit
        return $query->whereRaw("(
            SELECT COALESCE(SUM(quantity), 0)
            FROM seed_lots sl
            WHERE sl.variety_id = varieties.id 
            AND sl.is_sellable = true 
        ) > COALESCE(varieties.minimum_limit, 0)");
    }

    /**
     * Scope a query to include varieties with no available stock.
     */
    public function scopeOutOfStock($query)
    {
        // Out of Stock means: total sellable stock (any unit) is exactly 0
        return $query->whereRaw("(
            SELECT COALESCE(SUM(quantity), 0)
            FROM seed_lots sl
            WHERE sl.variety_id = varieties.id 
            AND sl.is_sellable = true 
        ) = 0");
    }

    /**
     * Scope a query to include varieties that need restocking.
     */
    public function scopeNeedsRestock($query)
    {
        // Restock means: total sellable stock is > 0 AND <= minimum_limit
        $totalSubquery = "(
            SELECT COALESCE(SUM(quantity), 0)
            FROM seed_lots sl
            WHERE sl.variety_id = varieties.id 
            AND sl.is_sellable = true 
        )";

        return $query->whereRaw("$totalSubquery > 0")
            ->whereRaw("$totalSubquery <= COALESCE(varieties.minimum_limit, 0)");
    }

    public function scopeWithStockCalculations($query)
    {
        return $query->selectRaw("varieties.*, 
            COALESCE((
                SELECT SUM(quantity) 
                FROM seed_lots sl 
                WHERE sl.variety_id = varieties.id 
                AND sl.is_sellable = true 
            ), 0) as total_stock_calculated");
    }

    public function scopeWithPriceRange($query)
    {
        return $query->selectRaw('
            COALESCE((
                SELECT MIN(sl.price_per_unit)
                FROM seed_lots sl
                WHERE sl.variety_id = varieties.id AND sl.is_sellable = true AND sl.quantity > 0
            ), 0) as min_price_calculated,
            COALESCE((
                SELECT MAX(sl.price_per_unit)
                FROM seed_lots sl
                WHERE sl.variety_id = varieties.id AND sl.is_sellable = true AND sl.quantity > 0
            ), 0) as max_price_calculated
        ');
    }

    /**
     * Scope a query to filter by stock status efficiently.
     */
    public function scopeByStockStatus($query, $status)
    {
        // Normalize status to lowercase and trim spaces to be resilient to UI values
        $normalized = strtolower(trim((string) $status));

        switch ($normalized) {
            case 'available':
            case 'tersedia':
                return $query->withAvailableStock();
            case 'out of stock':
            case 'out-of-stock':
            case 'out_of_stock':
            case 'habis':
                return $query->outOfStock();
            case 'restock':
                return $query->needsRestock();
            default:
                return $query;
        }
    }

    /**
     * Generate unique SKU with conflict resolution
     */
    protected static function generateUniqueSku($variety): string
    {
        // Load commodity if not already loaded
        if (! $variety->commodity) {
            $variety->load('commodity');
        }

        $commoditySlug = $variety->commodity?->slug ?? 'unknown';
        $nameSlug = Str::slug($variety->name);
        $baseSku = strtoupper($commoditySlug.'-'.$nameSlug);

        // Check if base SKU is unique
        $existingCount = static::where('sku', $baseSku)
            ->when($variety->exists, function ($query) use ($variety) {
                return $query->where('id', '!=', $variety->id);
            })
            ->count();

        if ($existingCount === 0) {
            return $baseSku;
        }

        // Find next available suffix
        $suffix = 2;
        do {
            $candidateSku = $baseSku.'-'.$suffix;
            $existingCount = static::where('sku', $candidateSku)
                ->when($variety->exists, function ($query) use ($variety) {
                    return $query->where('id', '!=', $variety->id);
                })
                ->count();
            $suffix++;
        } while ($existingCount > 0);

        return $candidateSku;
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
