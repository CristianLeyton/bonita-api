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
        'total',
        'coupon_id',
        'subtotal',
        'discount_amount',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'total' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Ensure only active coupons can be assigned
     */
    public function setCouponIdAttribute($value)
    {
        if ($value) {
            $coupon = Coupon::find($value);
            if (!$coupon || !$coupon->is_active) {
                throw new \InvalidArgumentException('No se puede asignar un cupón inactivo o inexistente.');
            }
        }

        $this->attributes['coupon_id'] = $value;
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

    /**
     * Calculate order totals with coupon discount
     */
    public function calculateTotals(): void
    {
        // Calcular subtotal (sin descuento)
        $this->subtotal = $this->items->sum(function ($item) {
            return $item->price * $item->quantity;
        });

        // Calcular descuento si hay cupón
        $this->discount_amount = 0;
        if ($this->coupon && $this->coupon->is_active) {
            $this->discount_amount = $this->coupon->calculateDiscount($this->subtotal);
        }

        // Calcular total final
        $this->total = $this->subtotal - $this->discount_amount;
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

                // Establecer datos básicos
                $order->phone = $parser->getPhone();
                $order->address = $parser->getAddress();
                $order->postal_code = $parser->getPostalCode();

                // Calcular subtotal del parser
                $order->subtotal = $parser->getSubtotal();

                // Calcular descuento si hay cupón
                $order->discount_amount = 0;
                if ($order->coupon_id) {
                    $coupon = Coupon::find($order->coupon_id);
                    if ($coupon && $coupon->is_active) {
                        $order->discount_amount = $coupon->calculateDiscount($order->subtotal);
                    }
                }

                // Calcular total final
                $order->total = $order->subtotal - $order->discount_amount;
            }
        });

        static::created(function ($order) {
            if ($order->message && str_contains($order->message, 'Celular:')) {
                $parser = new OrderMessageParser($order->message);
                $items = $parser->getItems();
                $createdItems = 0;

                foreach ($items as $item) {
                    $product = $parser->findProduct($item);
                    if ($product) {
                        $price = $product->price;
                        $quantity = $item['quantity'] ?? 1;

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

                // Los totales ya se calcularon en creating, no es necesario recalcular
            }
        });

        static::saving(function ($order) {
            if ($order->isDirty('items') || $order->isDirty('coupon_id')) {
                $order->calculateTotals();
            }
        });
    }
}
