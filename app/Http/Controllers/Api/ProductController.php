<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::query();

        // Búsqueda por nombre o SKU
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Filtro por categoría
        if ($request->has('category_id')) {
            $query->where('categorie_id', $request->input('category_id'));
        }

        // Ordenamiento
        $sortField = $request->input('sort_by', 'name');
        $sortDirection = $request->input('sort_direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        // Paginación
        $perPage = $request->input('per_page', 15);
        $products = $query->select([
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
        ])->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ]
        ]);
    }

    public function show($id): JsonResponse
    {
        $product = Product::with([
            'categorie:id,name',
            'colors:id,name,hex_code'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $product
        ]);
    }
}
