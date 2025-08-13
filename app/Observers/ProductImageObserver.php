<?php

namespace App\Observers;

use App\Models\ProductImage;

class ProductImageObserver
{
    /**
     * Handle the ProductImage "created" event.
     */
    public function created(ProductImage $productImage): void
    {
        $this->ensureOnlyOnePrimary($productImage);
    }

    /**
     * Handle the ProductImage "updated" event.
     */
    public function updated(ProductImage $productImage): void
    {
        $this->ensureOnlyOnePrimary($productImage);
    }

    /**
     * Handle the ProductImage "deleted" event.
     */
    public function deleted(ProductImage $productImage): void
    {
        //
    }

    /**
     * Handle the ProductImage "restored" event.
     */
    public function restored(ProductImage $productImage): void
    {
        //
    }

    /**
     * Handle the ProductImage "force deleted" event.
     */
    public function forceDeleted(ProductImage $productImage): void
    {
        //
    }

    /**
     * Ensure only one image per product is marked as primary
     */
    private function ensureOnlyOnePrimary(ProductImage $productImage): void
    {
        // Si esta imagen no es principal, no hacer nada
        if (!$productImage->is_primary) {
            return;
        }

        // Desmarcar todas las otras imágenes del mismo producto como no principales
        ProductImage::where('product_id', $productImage->product_id)
            ->where('id', '!=', $productImage->id)
            ->update(['is_primary' => false]);
    }
}
