<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductImage;

class ProductImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener todos los productos existentes
        $products = Product::all();

        foreach ($products as $product) {
            // Crear 2-4 imágenes por producto
            $imageCount = rand(2, 4);

            for ($i = 0; $i < $imageCount; $i++) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'url' => 'https://picsum.photos/400/400?random=' . $product->id . $i, // Imágenes de ejemplo
                    'alt_text' => 'Imagen ' . ($i + 1) . ' de ' . $product->name,
                    'is_primary' => $i === 0, // La primera imagen es la principal
                    'sort_order' => $i,
                ]);
            }
        }
    }
}
