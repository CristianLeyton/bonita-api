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

        // Filtro por categoría usando slug
        if ($request->has('category_slug')) {
            $query->whereHas('categorie', function ($q) use ($request) {
                $q->where('slug', $request->input('category_slug'));
            });
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
            'slug',
            'description',
            'sku',
            'price',
            'quantity',
            'categorie_id',
        ])->with([
            'categorie:id,name,slug',
            'colors:id,name,hex_code',
            'primaryImage:id,product_id,url,alt_text', // Solo la imagen principal para la lista
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

    public function show($slug): JsonResponse
    {
        $product = Product::where('slug', $slug)
            ->with([
                'categorie:id,name,slug',
                'colors:id,name,hex_code',
                'images:id,product_id,url,alt_text,is_primary,sort_order' // Todas las imágenes
            ])->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => $product
        ]);
    }
}
