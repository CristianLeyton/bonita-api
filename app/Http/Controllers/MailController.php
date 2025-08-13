<?php

namespace App\Http\Controllers;

use App\Mail\OrderMail;
use App\Models\Order;
use App\Models\User;
use App\Notifications\NewOrderNotification;
use App\Services\OrderMessageParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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
                'subtotal' => $data['subtotal'],
                'discount_amount' => $discountAmount,
                'total' => $data['subtotal'] - $discountAmount,
            ]);

            Log::info('Orden creada', ['order_id' => $order->id]);

            try {
                Mail::to($request->email)
                    ->bcc('leytoncristian96@gmail.com')
                    ->send(new OrderMail(
                        $request->subject,
                        $message
                    ));

                Log::info('Correo enviado exitosamente', [
                    'order_id' => $order->id,
                    'email' => $request->email
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
                ] : null
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
}
