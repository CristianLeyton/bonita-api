<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Color;

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
                    'sku' => null
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

            // Extraer SKU
            if (preg_match('/SKU:\s*(\d+)/', $line, $matches)) {
                if ($currentItem) {
                    $currentItem['sku'] = $matches[1];
                }
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

        // Calcular el total real usando los precios de la base de datos
        $total = 0;
        foreach ($items as &$item) {
            $product = $this->findProduct($item);
            if ($product) {
                $item['price'] = $product->price;
                $total += $product->price * $item['quantity'];
            }
        }

        $this->data = [
            'phone' => $phone,
            'address' => $this->extractAddress(),
            'postal_code' => $this->extractPostalCode(),
            'total' => $total,
            'items' => $items
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
        return $this->data['total'] ?? 0;
    }

    public function getItems()
    {
        return $this->data['items'] ?? [];
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
