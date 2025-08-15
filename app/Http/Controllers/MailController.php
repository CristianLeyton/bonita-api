<?php

namespace App\Http\Controllers;

use App\Mail\OrderMail;
use App\Models\Order;
use App\Models\Coupon;
use App\Models\User;
use App\Notifications\NewOrderNotification;
use App\Services\OrderMessageParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class MailController extends Controller
{
    public function send(Request $request)
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'email' => 'required|email',
                'subject' => 'required|string',
                'message' => 'required|string',
                'coupon_code' => 'nullable|string|max:50', // Código de cupón opcional
            ]);

            Log::info('Iniciando procesamiento de pedido', [
                'email' => $request->email,
                'subject' => $request->subject,
                'message' => $request->message,
                'coupon_code' => $request->coupon_code
            ]);

            $message = urldecode($request->message);

            // Validar formato básico del mensaje
            if (!str_contains($message, 'Celular:')) {
                throw new \Exception('El formato del mensaje no es válido. Debe incluir el número de Celular.');
            }

            $parser = new OrderMessageParser($message);
            $data = $parser->parse();

            if (empty($data['items'])) {
                throw new \Exception('No se encontraron productos en el mensaje.');
            }

            // Verificar si hay discrepancias de precios
            if ($data['has_discrepancies']) {
                Log::warning('Discrepancias detectadas en el pedido', [
                    'email' => $request->email,
                    'message_total' => $data['message_total'],
                    'calculated_subtotal' => $data['subtotal'],
                    'price_discrepancies' => $data['price_discrepancies']
                ]);
            }

            // Validar y aplicar cupón si se proporciona
            $couponId = null;
            $discountAmount = 0;
            if ($request->coupon_code) {
                $coupon = \App\Models\Coupon::where('code', $request->coupon_code)->first();

                if (!$coupon) {
                    throw new \Exception('El código de cupón no existe.');
                }

                if (!$coupon->is_active) {
                    throw new \Exception('El cupón no está activo.');
                }

                $couponId = $coupon->id;
                $discountAmount = $coupon->calculateDiscount($data['subtotal']);

                Log::info('Cupón aplicado al pedido', [
                    'coupon_code' => $request->coupon_code,
                    'coupon_id' => $couponId,
                    'discount_percentage' => $coupon->discount_percentage,
                    'discount_amount' => $discountAmount
                ]);
            }

            Log::info('Datos extraídos del mensaje', [
                'phone' => $data['phone'],
                'address' => $data['address'],
                'postal_code' => $data['postal_code'],
                'subtotal' => $data['subtotal'],
                'items_count' => count($data['items']),
                'items' => $data['items'],
                'coupon_applied' => $couponId ? true : false,
                'discount_amount' => $discountAmount
            ]);

            // Crear la orden con cupón si es válido
            $order = Order::create([
                'email' => $request->email,
                'subject' => $request->subject,
                'message' => $message,
                'status' => 'pending',
                'phone' => $data['phone'],
                'address' => $data['address'],
                'postal_code' => $data['postal_code'],
                'coupon_id' => $couponId,
                // No establecer totales aquí, el modelo se encargará
            ]);

            Log::info('Orden creada', ['order_id' => $order->id]);

            try {
                // Preparar datos del cupón para el email
                $couponInfo = null;
                if ($couponId) {
                    $couponInfo = [
                        'code' => $request->coupon_code,
                        'discount_percentage' => $coupon->discount_percentage,
                        'subtotal' => $data['subtotal'],
                        'discount_amount' => $discountAmount,
                        'final_total' => $data['subtotal'] - $discountAmount
                    ];
                }

                Mail::to($request->email)
                    ->bcc('bonnitaglam@gmail.com')
                    /* ->bcc('leytoncristian96@gmail.com') */
                    ->send(new OrderMail(
                        $request->subject,
                        $message,
                        $couponInfo
                    ));

                Log::info('Correo enviado exitosamente', [
                    'order_id' => $order->id,
                    'email' => $request->email,
                    'coupon_applied' => $couponId ? true : false
                ]);
            } catch (\Exception $mailError) {
                Log::error('Error al enviar el correo', [
                    'order_id' => $order->id,
                    'error' => $mailError->getMessage()
                ]);
                // No lanzamos la excepción aquí para que la orden se guarde aunque falle el correo
            }

            DB::commit();

            return response()->json([
                'message' => '¡Pedido enviado correctamente!',
                'order' => $order->load('items.product', 'items.color', 'coupon'),
                'coupon_applied' => $couponId ? true : false,
                'coupon_info' => $couponId ? [
                    'code' => $request->coupon_code,
                    'discount_percentage' => $coupon->discount_percentage,
                    'discount_amount' => $discountAmount,
                    'final_total' => $data['subtotal'] - $discountAmount
                ] : null,
                'price_validation' => [
                    'has_discrepancies' => $data['has_discrepancies'],
                    'message_total' => $data['message_total'],
                    'calculated_subtotal' => $data['subtotal'],
                    'discrepancies' => $data['price_discrepancies']
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error al procesar el pedido', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => [
                    'email' => $request->email,
                    'subject' => $request->subject,
                    'message' => $request->message
                ]
            ]);

            return response()->json([
                'message' => 'Error al procesar el pedido: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'details' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

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
}
