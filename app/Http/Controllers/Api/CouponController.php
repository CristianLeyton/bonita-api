<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CouponController extends Controller
{
    /**
     * Validate a coupon code
     */
    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:50',
        ]);

        $coupon = Coupon::where('code', $request->code)->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Cupón no encontrado',
                'data' => null
            ], 404);
        }

        if (!$coupon->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Este cupón no está activo',
                'data' => null
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Cupón válido',
            'data' => [
                'id' => $coupon->id,
                'name' => $coupon->name,
                'code' => $coupon->code,
                'discount_percentage' => $coupon->discount_percentage,
                'is_active' => $coupon->is_active,
            ]
        ]);
    }

    /**
     * Calculate discount for a given price and coupon code
     */
    public function calculateDiscount(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:50',
            'price' => 'required|numeric|min:0',
        ]);

        $coupon = Coupon::where('code', $request->code)->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Cupón no encontrado',
                'data' => null
            ], 404);
        }

        if (!$coupon->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Este cupón no está activo',
                'data' => null
            ], 400);
        }

        $price = (float) $request->price;
        $discountAmount = $coupon->calculateDiscount($price);
        $finalPrice = $coupon->calculateFinalPrice($price);

        return response()->json([
            'success' => true,
            'message' => 'Descuento calculado correctamente',
            'data' => [
                'coupon' => [
                    'id' => $coupon->id,
                    'name' => $coupon->name,
                    'code' => $coupon->code,
                    'discount_percentage' => $coupon->discount_percentage,
                ],
                'calculation' => [
                    'original_price' => $price,
                    'discount_amount' => round($discountAmount, 2),
                    'final_price' => round($finalPrice, 2),
                ]
            ]
        ]);
    }

    /**
     * Get all active coupons
     */
    public function index(): JsonResponse
    {
        $coupons = Coupon::where('is_active', true)
            ->select(['id', 'name', 'code', 'discount_percentage'])
            ->get();

        return response()->json([
            'success' => true,
            'data' => $coupons
        ]);
    }

    /**
     * Get coupon details by ID
     */
    public function show($id): JsonResponse
    {
        $coupon = Coupon::find($id);

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Cupón no encontrado',
                'data' => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $coupon
        ]);
    }
}
