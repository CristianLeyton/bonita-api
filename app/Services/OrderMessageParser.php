<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Color;
use Illuminate\Support\Facades\Log;

class OrderMessageParser
{
    protected $message;
    protected $data;

    public function __construct($message)
    {
        $this->message = $message;
        $this->parse();
    }

    public function parse()
    {
        $lines = explode("\n", $this->message);
        $items = [];
        $currentItem = null;
        $phone = null;
        $messageTotal = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            // Extraer teléfono
            if (preg_match('/Celular:\s*(\d+)/', $line, $matches)) {
                $phone = $matches[1];
                continue;
            }

            // Detectar inicio de producto
            if (preg_match('/^\d+\.\s*(.+)$/', $line, $matches)) {
                if ($currentItem) {
                    $items[] = $currentItem;
                }
                $currentItem = [
                    'name' => trim($matches[1]),
                    'quantity' => 1,
                    'sku' => null,
                    'message_price' => null, // Precio del mensaje para validación
                    'db_price' => null       // Precio real de la base de datos
                ];
                continue;
            }

            // Extraer cantidad
            if (preg_match('/Cantidad:\s*(\d+)/', $line, $matches)) {
                if ($currentItem) {
                    $currentItem['quantity'] = (int)$matches[1];
                }
                continue;
            }

            // Extraer precio del mensaje (solo para validación)
            if (preg_match('/Precio:\s*\$?(\d+(?:\.\d{2})?)/', $line, $matches)) {
                if ($currentItem) {
                    $currentItem['message_price'] = (float)$matches[1];
                }
                continue;
            }

            // Extraer SKU
            if (preg_match('/SKU:\s*(\d+)/', $line, $matches)) {
                if ($currentItem) {
                    $currentItem['sku'] = $matches[1];
                }
                continue;
            }

            // Extraer total del mensaje
            if (preg_match('/Total:\s*\$?(\d+(?:\.\d{2})?)/', $line, $matches)) {
                $messageTotal = (float)$matches[1];
                continue;
            }
        }

        // Agregar el último item si existe
        if ($currentItem) {
            $items[] = $currentItem;
        }

        if (empty($items)) {
            throw new \Exception('No se encontraron productos en el mensaje.');
        }

        // Calcular el total real usando SOLO precios de la base de datos
        $subtotal = 0;
        $priceDiscrepancies = [];

        foreach ($items as &$item) {
            $product = $this->findProduct($item);
            if ($product) {
                $item['db_price'] = $product->price;
                $item['price'] = $product->price; // Precio final usado para cálculos
                $item['product_id'] = $product->id;
                $subtotal += $product->price * $item['quantity'];

                // Verificar si hay discrepancia entre precio total del mensaje y BD
                // IMPORTANTE: El mensaje trae precio TOTAL por producto, no unitario
                if (isset($item['message_price']) && $item['message_price'] !== null) {
                    $messageTotalForItem = $item['message_price']; // Ya es el total por producto
                    $dbTotalForItem = $product->price * $item['quantity'];

                    if (abs($messageTotalForItem - $dbTotalForItem) > 0.01) {
                        $priceDiscrepancies[] = [
                            'product' => $item['name'],
                            'quantity' => $item['quantity'],
                            'message_price_total' => $item['message_price'], // Precio total del mensaje
                            'db_price_unit' => $product->price, // Precio unitario de BD
                            'db_price_total' => $dbTotalForItem, // Precio total calculado de BD
                            'message_price_unit_calculated' => $item['message_price'] / $item['quantity'], // Precio unitario calculado del mensaje
                            'difference' => $messageTotalForItem - $dbTotalForItem
                        ];
                    }
                }
            } else {
                // Si no se encuentra el producto, usar precio del mensaje como fallback
                $item['price'] = $item['message_price'] ?? 0;
                $item['db_price'] = null;
                $subtotal += ($item['message_price'] ?? 0) * $item['quantity'];

                Log::warning('Producto no encontrado en BD', [
                    'product_name' => $item['name'],
                    'sku' => $item['sku'],
                    'using_message_price' => $item['message_price'] ?? 0
                ]);
            }
        }

        // Validar que el total del mensaje coincida con el cálculo real
        if ($messageTotal !== null) {
            $difference = abs($messageTotal - $subtotal);
            if ($difference > 0.01) {
                Log::warning('Discrepancia en total del pedido', [
                    'message_total' => $messageTotal,
                    'calculated_total' => $subtotal,
                    'difference' => $difference,
                    'price_discrepancies' => $priceDiscrepancies
                ]);
            }
        }

        $this->data = [
            'phone' => $phone,
            'address' => $this->extractAddress(),
            'postal_code' => $this->extractPostalCode(),
            'subtotal' => $subtotal,
            'message_total' => $messageTotal,
            'items' => $items,
            'price_discrepancies' => $priceDiscrepancies,
            'has_discrepancies' => !empty($priceDiscrepancies) || ($messageTotal !== null && abs($messageTotal - $subtotal) > 0.01)
        ];

        return $this->data;
    }

    public function getPhone()
    {
        return $this->data['phone'] ?? null;
    }

    public function getAddress()
    {
        return $this->data['address'] ?? null;
    }

    public function getPostalCode()
    {
        return $this->data['postal_code'] ?? null;
    }

    public function getTotal()
    {
        return $this->data['subtotal'] ?? 0;
    }

    public function getSubtotal()
    {
        return $this->data['subtotal'] ?? 0;
    }

    public function getItems()
    {
        return $this->data['items'] ?? [];
    }

    public function hasPriceDiscrepancies()
    {
        return $this->data['has_discrepancies'] ?? false;
    }

    public function getPriceDiscrepancies()
    {
        return $this->data['price_discrepancies'] ?? [];
    }

    public function getMessageTotal()
    {
        return $this->data['message_total'] ?? null;
    }

    protected function extractPhone()
    {
        if (preg_match('/Teléfono:\s*(\d+)/i', $this->message, $matches)) {
            return $matches[1];
        }
        return null;
    }

    protected function extractAddress()
    {
        if (preg_match('/Dirección:\s*([^\n]+)/i', $this->message, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }

    protected function extractPostalCode()
    {
        if (preg_match('/Código Postal:\s*(\d+)/i', $this->message, $matches)) {
            return $matches[1];
        }
        return null;
    }

    protected function extractTotal()
    {
        if (preg_match('/Total:\s*(\d+(?:\.\d{2})?)/i', $this->message, $matches)) {
            return (float) $matches[1];
        }
        return 0;
    }

    protected function extractItems()
    {
        $items = [];
        $lines = explode("\n", $this->message);
        $currentItem = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            if (preg_match('/^([^:]+):\s*(\d+)\s*x\s*(\d+(?:\.\d{2})?)/i', $line, $matches)) {
                if ($currentItem) {
                    $items[] = $currentItem;
                }
                $currentItem = [
                    'name' => trim($matches[1]),
                    'quantity' => (int) $matches[2],
                    'price' => (float) $matches[3],
                    'color' => null
                ];
            } elseif ($currentItem && preg_match('/Color:\s*([^\n]+)/i', $line, $matches)) {
                $currentItem['color'] = trim($matches[1]);
            }
        }

        if ($currentItem) {
            $items[] = $currentItem;
        }

        return $items;
    }

    public function findProduct(array $item): ?Product
    {
        if (!empty($item['sku'])) {
            return Product::where('sku', $item['sku'])->first();
        }

        $query = Product::where('name', 'like', '%' . $item['name'] . '%');

        // Solo buscar por color si el producto tiene color
        if (isset($item['color']) && !empty($item['color'])) {
            $color = Color::where('name', 'like', '%' . $item['color'] . '%')->first();
            if ($color) {
                $query->whereHas('colors', function ($q) use ($color) {
                    $q->where('colors.id', $color->id);
                });
            }
        }

        return $query->first();
    }
}
