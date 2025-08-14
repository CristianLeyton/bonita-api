@php
    $order = $order ?? null;

    $statusColors = [
        'pending' => 'status-badge-purple',
        'viewed' => 'status-badge-yellow',
        'paid' => 'status-badge-blue',
        'preparing' => 'status-badge-purple',
        'shipped' => 'status-badge-green',
        'delivered' => 'status-badge-emerald',
    ];

    $statusLabels = [
        'pending' => 'Pendiente',
        'viewed' => 'Visto',
        'paid' => 'Pagado',
        'preparing' => 'Preparando',
        'shipped' => 'Enviado',
        'delivered' => 'Entregado',
    ];
@endphp

<style>
    .status-badge {
        padding: 0.5rem 1rem;
        border-radius: 9999px;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .status-badge-yellow {
        background-color: rgba(234, 179, 8, 0.2);
        color: #eab308;
    }

    .status-badge-blue {
        background-color: rgba(59, 130, 246, 0.2);
        color: #3b82f6;
    }

    .status-badge-green {
        background-color: rgba(34, 197, 94, 0.2);
        color: #22c55e;
    }

    .status-badge-purple {
        background-color: rgba(22, 22, 22, 0);
        border: 1px solid #c1c1c1
    }

    .status-badge-indigo {
        background-color: rgba(99, 102, 241, 0.2);
        color: #6366f1;
    }

    .status-badge-emerald {
        background-color: rgba(16, 185, 129, 0.2);
        color: #10b981;
    }
</style>

<div class="space-y-8 w-full">
    {{-- Header con estado y total --}}
    <div class="rounded-lg shadow-md p-6 bg-gray-900 mt-0">
        {{-- Alerta de discrepancias de precios --}}
        @if ($order->message && str_contains($order->message, 'Precio:'))
            @php
                $parser = new \App\Services\OrderMessageParser($order->message);
                $data = $parser->parse();
            @endphp

            @if ($data['has_discrepancies'])
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">
                                ⚠️ Discrepancia de Precios Detectada
                            </h3>
                            <div class="mt-2 text-sm text-red-700">
                                <p>Se detectaron diferencias entre los precios del mensaje y la base de datos:</p>
                                <ul class="mt-2 list-disc list-inside">
                                    @foreach ($data['price_discrepancies'] as $discrepancy)
                                        <li>
                                            <strong>{{ $discrepancy['product'] }}</strong>:
                                            Mensaje: ${{ number_format($discrepancy['message_price'], 2) }} |
                                            BD: ${{ number_format($discrepancy['db_price'], 2) }}
                                            <span class="text-red-600">
                                                (Diferencia: ${{ number_format($discrepancy['difference'], 2) }})
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                                <p class="mt-2">
                                    <strong>Total del mensaje:</strong>
                                    ${{ number_format($data['message_total'] ?? 0, 2) }} |
                                    <strong>Total calculado:</strong> ${{ number_format($data['subtotal'] ?? 0, 2) }}
                                </p>
                                <p class="mt-1 text-xs text-red-600">
                                    <strong>Nota:</strong> Se usó el precio de la base de datos para el cálculo final.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endif

        <div class="flex justify-between items-center">
            <div class="">
                <h2 class="text-2xl font-bold text-gray-900">Pedido #{{ $order->id }}</h2>
                <p class="text-sm text-gray-500 mt-1">{{ $order->created_at->format('d/m/Y H:i') }}</p>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-right">
                    @if ($order->coupon)
                        <div class="mb-2">
                            <p class="text-sm text-gray-500">Subtotal</p>
                            <p class="text-lg font-semibold text-gray-900">
                                ${{ number_format($order->subtotal ?? 0, 2) }}</p>
                        </div>
                        <div class="mb-2">
                            <p class="text-sm text-gray-500">Descuento ({{ $order->coupon->code }})</p>
                            <p class="text-lg font-semibold text-red-600">
                                -${{ number_format($order->discount_amount ?? 0, 2) }}</p>
                        </div>
                        <div class="border-t pt-2">
                            <p class="text-sm text-gray-500">Total Final</p>
                            <p class="text-2xl font-bold text-gray-900">${{ number_format($order->total ?? 0, 2) }}</p>
                        </div>
                    @else
                        <div>
                            <p class="text-sm text-gray-500">Total</p>
                            <p class="text-2xl font-bold text-gray-900">${{ number_format($order->total ?? 0, 2) }}</p>
                        </div>
                    @endif
                </div>
            </div>
            <div class="status-badge {{ $statusColors[$order->status ?? 'pending'] }}">
                <span>{{ $statusLabels[$order->status ?? 'pending'] }}</span>
            </div>
        </div>
    </div>

    {{-- Información del cliente --}}
    <div class=" rounded-lg shadow-md p-6 bg-gray-900 mt-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Información del Cliente</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Correo</p>
                    <p class="mt-1 text-sm text-gray-900">{{ $order->email ?? 'No especificado' }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Teléfono</p>
                    <p class="mt-1 text-sm text-gray-900">{{ $order->phone ?? 'No especificado' }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Dirección</p>
                    <p class="mt-1 text-sm text-gray-900">{{ $order->address ?? 'No especificada' }}</p>
                </div>
            </div>
            <div class="space-y-4">
                <div>
                    <p class="text-sm font-medium text-gray-500">Código Postal</p>
                    <p class="mt-1 text-sm text-gray-900">{{ $order->postal_code ?? 'No especificado' }}</p>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-500">Número de Seguimiento</p>
                    <p class="mt-1 text-sm text-gray-900">{{ $order->follow_number ?? 'No especificado' }}</p>
                </div>
                @if ($order->message)
                    <div>
                        <p class="text-sm font-medium text-gray-500">Mensaje</p>
                        <textarea rows="6"
                            class="w-full mt-1 text-sm text-gray-900 whitespace-pre-wrap outline-none rounded bg-transparent"
                            style="white-space: pre-line;">{!! $order->message !!}</textarea>
                    </div>
                @endif
                @if ($order->coupon)
                    <div>
                        <p class="text-sm font-medium text-gray-500">Cupón Aplicado</p>
                        <div class="mt-1 p-2 bg-green-50 rounded border border-green-200">
                            <p class="text-sm font-medium text-green-800">{{ $order->coupon->name }}</p>
                            <p class="text-xs text-green-600">Código: {{ $order->coupon->code }}</p>
                            <p class="text-xs text-green-600">Descuento: {{ $order->coupon->discount_percentage }}%</p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Productos --}}
    <div class=" rounded-lg shadow-md p-6 w-full bg-gray-900 mt-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Productos</h3>
        @if ($order->items && $order->items->count() > 0)
            <div class="overflow-x-auto w-full">
                <table class="w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="">
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-400 uppercase tracking-wider">
                                Producto</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-400 uppercase tracking-wider">
                                Color</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-400 uppercase tracking-wider">
                                Cantidad</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-400 uppercase tracking-wider">
                                Precio Unit.</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-400 uppercase tracking-wider">
                                Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class=" divide-y divide-gray-200">
                        @foreach ($order->items as $item)
                            <tr class="">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $item->product->name ?? 'Producto no encontrado' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $item->color->name ?? 'Sin color' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900">
                                    {{ $item->quantity ?? 0 }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ${{ number_format($item->price ?? 0, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    ${{ number_format(($item->price ?? 0) * ($item->quantity ?? 0), 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-sm text-gray-500">No hay productos en este pedido</p>
        @endif
    </div>
</div>
