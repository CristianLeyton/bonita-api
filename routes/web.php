<?php

use Illuminate\Support\Facades\Route;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Artisan;

Route::get('/', function () {
    return redirect('/admin');
});



Route::get('/api/categories', [CategoryController::class, 'index']);
Route::get('/api/products', [ProductController::class, 'index']);
Route::get('/api/categories/{slug}/products', [CategoryController::class, 'products']);
Route::get('/api/products/{slug}', [ProductController::class, 'show']);


/* Route::get('/clear-cache', function () {
    Artisan::call('config:clear');
    Artisan::call('cache:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');

    return '🧹 Cachés limpiadas con éxito';
});

Route::get('/config-cache', function () {
    $path = base_path('bootstrap/cache');

    if (!file_exists($path)) {
        mkdir($path, 0755, true);
    }

    Artisan::call('config:cache');

    return '✅ Configuración cacheada con éxito.';
}); */