<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Categorie extends Model
{
    protected $fillable = ['name', 'urlImage', 'description'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($category) {
            if ($category->products()->count() > 0) {
                throw new \Exception('No se puede eliminar una categoría que tiene productos asociados.');
            }
        });
    }
}
