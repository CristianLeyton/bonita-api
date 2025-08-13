<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CouponController;
use App\Http\Controllers\MailController;

Route::middleware('api')->group(function () {
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/categories/{slug}/products', [CategoryController::class, 'products']);
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/{slug}', [ProductController::class, 'show']);

    // Cupones
    Route::get('/coupons', [CouponController::class, 'index']);
    Route::get('/coupons/{id}', [CouponController::class, 'show']);
    Route::post('/coupons/validate', [CouponController::class, 'validate']);
    Route::post('/coupons/calculate-discount', [CouponController::class, 'calculateDiscount']);

    Route::post('/send-mail', [MailController::class, 'send']);
});
