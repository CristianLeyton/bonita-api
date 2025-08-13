<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Coupon;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $coupons = [
            [
                'name' => 'Descuento de Bienvenida',
                'code' => 'BIENVENIDA20',
                'discount_percentage' => 20.00,
                'is_active' => true,
            ],
            [
                'name' => 'Descuento de Verano',
                'code' => 'VERANO15',
                'discount_percentage' => 15.00,
                'is_active' => true,
            ],
            [
                'name' => 'Descuento Especial',
                'code' => 'ESPECIAL25',
                'discount_percentage' => 25.00,
                'is_active' => true,
            ],
            [
                'name' => 'Cupón Inactivo',
                'code' => 'INACTIVO10',
                'discount_percentage' => 10.00,
                'is_active' => false,
            ],
        ];

        foreach ($coupons as $couponData) {
            Coupon::create($couponData);
        }
    }
}
