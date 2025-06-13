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
            ]);

            Log::info('Iniciando procesamiento de pedido', [
                'email' => $request->email,
                'subject' => $request->subject,
                'message' => $request->message
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

            Log::info('Datos extraídos del mensaje', [
                'phone' => $data['phone'],
                'address' => $data['address'],
                'postal_code' => $data['postal_code'],
                'total' => $data['total'],
                'items_count' => count($data['items']),
                'items' => $data['items']
            ]);

            // Crear la orden - los items se crearán automáticamente en el evento created del modelo
            $order = Order::create([
                'email' => $request->email,
                'subject' => $request->subject,
                'message' => $message,
                'status' => 'pending',
                'phone' => $data['phone'],
                'address' => $data['address'],
                'postal_code' => $data['postal_code'],
                'total' => $data['total'],
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
                'order' => $order->load('items.product', 'items.color'),
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
