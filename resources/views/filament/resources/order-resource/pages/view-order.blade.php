@php
    $order = $order ?? null;
@endphp

<div class="space-y-6">
    <div class="grid grid-cols-2 gap-4">
        <div>
            <h3 class="text-lg font-medium">Información del Pedido</h3>
            <dl class="mt-2 space-y-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Asunto</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $order->subject ?? 'No especificado' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Correo</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $order->email ?? 'No especificado' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Teléfono</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $order->phone ?? 'No especificado' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Dirección</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $order->address ?? 'No especificada' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Código Postal</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $order->postal_code ?? 'No especificado' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Número de Seguimiento</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $order->follow_number ?? 'No especificado' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Estado</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        @switch($order->status ?? '')
                            @case('pending')
                                Pendiente
                            @break

                            @case('viewed')
                                Visto
                            @break

                            @case('paid')
                                Pagado
                            @break

                            @case('preparing')
                                Preparando
                            @break

                            @case('shipped')
                                Enviado
                            @break

                            @case('delivered')
                                Entregado
                            @break

                            @default
                                No especificado
                        @endswitch
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Total</dt>
                    <dd class="mt-1 text-sm text-gray-900">${{ number_format($order->total ?? 0, 2) }}</dd>
                </div>
                @if ($order->message)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Mensaje</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $order->message }}</dd>
                    </div>
                @endif
            </dl>
        </div>
    </div>

    <div>
        <h3 class="text-lg font-medium">Productos</h3>
        <div class="mt-2">
            @if ($order->items && $order->items->count() > 0)
                <table class="min-w-full divide-y divide-gray-300">
                    <thead>
                        <tr>
                            <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-gray-900">Producto</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Color</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Cantidad</th>
                            <th class="px-3 py-3.5 text-left text-sm font-semibold text-gray-900">Precio</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($order->items as $item)
                            <tr>
                                <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-gray-900">
                                    {{ $item->product->name ?? 'Producto no encontrado' }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                                    {{ $item->color->name ?? 'Color no encontrado' }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                                    {{ $item->quantity ?? 0 }}
                                </td>
                                <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-900">
                                    ${{ number_format($item->price ?? 0, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-sm text-gray-500">No hay productos en este pedido</p>
            @endif
        </div>
    </div>
</div>
