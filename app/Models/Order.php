<?php

namespace App\Models;

use App\Services\OrderMessageParser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'subject',
        'message',
        'status',
        'follow_number',
        'phone',
        'address',
        'postal_code',
        'total'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'total' => 'decimal:2'
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function updateStock($action = 'decrease')
    {
        foreach ($this->items as $item) {
            $product = $item->product;
            if ($action === 'decrease') {
                $product->quantity = max(0, $product->quantity - $item->quantity);
            } else {
                $product->quantity += $item->quantity;
            }
            $product->save();
        }
    }

    protected static function boot()
    {
        parent::boot();

        // Agregar ordenamiento por defecto
        static::addGlobalScope('order', function ($query) {
            $query->orderBy('created_at', 'desc');
        });

        static::creating(function ($order) {
            if ($order->message && str_contains($order->message, 'Celular:')) {
                $parser = new OrderMessageParser($order->message);
                $order->phone = $parser->getPhone();
                $order->address = $parser->getAddress();
                $order->postal_code = $parser->getPostalCode();
            }
        });

        static::created(function ($order) {
            if ($order->message && str_contains($order->message, 'Celular:')) {
                $parser = new OrderMessageParser($order->message);
                $items = $parser->getItems();
                $createdItems = 0;
                $total = 0;

                foreach ($items as $item) {
                    $product = $parser->findProduct($item);
                    if ($product) {
                        $price = $product->price;
                        $quantity = $item['quantity'] ?? 1;
                        $itemTotal = $price * $quantity;
                        $total += $itemTotal;

                        $order->items()->create([
                            'product_id' => $product->id,
                            'color_id' => null,
                            'quantity' => $quantity,
                            'price' => $price
                        ]);
                        $createdItems++;
                    }
                }

                if ($createdItems === 0) {
                    throw new \Exception('No se encontraron productos en el mensaje.');
                }

                // Actualizar el total después de crear los items
                $order->total = $total;
                $order->save();
            }
        });

        static::saving(function ($order) {
            if ($order->isDirty('items')) {
                $order->total = $order->items->sum(function ($item) {
                    return $item->price * $item->quantity;
                });
            }
        });
    }
}
