<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'sku',
        'price',
        'quantity',
        'description',
        'categorie_id',
    ];

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class);
    }

    public function colors(): BelongsToMany
    {
        return $this->belongsToMany(Color::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function primaryImage(): HasMany
    {
        return $this->hasMany(ProductImage::class)->where('is_primary', true);
    }

    /**
     * Ensure the product has at least one primary image
     */
    public function ensurePrimaryImage(): void
    {
        if ($this->images()->where('is_primary', true)->count() === 0) {
            $firstImage = $this->images()->first();
            if ($firstImage) {
                $firstImage->update(['is_primary' => true]);
            }
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            $product->slug = Str::slug($product->name);
            if (empty($product->sku)) {
                $product->sku = static::max('id') + 1;
            }
        });

        static::updating(function ($product) {
            $product->slug = Str::slug($product->name);
        });

        static::saved(function ($product) {
            // Ensure there's always a primary image
            $product->ensurePrimaryImage();
        });
    }
}
