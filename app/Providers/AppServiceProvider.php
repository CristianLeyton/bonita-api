<?php

namespace App\Providers;

use App\Models\Product;
use App\Models\Categorie;
use App\Observers\ProductObserver;
use App\Observers\CategorieObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Model::unguard();
        //Product::observe(ProductObserver::class);
        //Categorie::observe(CategorieObserver::class);
    }
}
