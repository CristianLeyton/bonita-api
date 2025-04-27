<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::select([
            'id',
            'name',
            'urlImage',
            'description',
            'sku',
            'price',
            'quantity',
            'categorie_id',
        ])->with([
            'categorie:id,name',
            'colors:id,name,hex_code'
        ])->get();

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }
}
