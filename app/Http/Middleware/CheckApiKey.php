<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckApiKey
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-API-Key');

        if (!$apiKey || $apiKey !== env('API_KEY')) {
            return response()->json([
                'status' => 'error',
                'message' => 'API key inválida o no proporcionada'
            ], 401);
        }

        return $next($request);
    }
}
