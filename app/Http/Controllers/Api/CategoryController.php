<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Categorie::select(['id', 'name', 'slug', 'urlImage', 'description'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }

    public function products($slug): JsonResponse
    {
        $category = Categorie::where('slug', $slug)
            ->with(['products' => function ($query) {
                $query->select([
                    'id',
                    'name',
                    'urlImage',
                    'description',
                    'sku',
                    'price',
                    'quantity',
                    'categorie_id'
                ])->with(['colors:id,name,hex_code']);
            }])->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $category
        ]);
    }
}
