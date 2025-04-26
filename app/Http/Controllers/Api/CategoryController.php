<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Categorie::select(['id', 'name', 'urlImage', 'description'])->get();

        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }
}
