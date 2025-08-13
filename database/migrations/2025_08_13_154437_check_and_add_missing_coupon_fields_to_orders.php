<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Agregar coupon_id si no existe
            if (!Schema::hasColumn('orders', 'coupon_id')) {
                $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            }

            // Agregar subtotal si no existe
            if (!Schema::hasColumn('orders', 'subtotal')) {
                $table->decimal('subtotal', 10, 2)->default(0);
            }

            // Agregar discount_amount si no existe
            if (!Schema::hasColumn('orders', 'discount_amount')) {
                $table->decimal('discount_amount', 10, 2)->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['coupon_id']);
            $table->dropColumn(['coupon_id', 'subtotal', 'discount_amount']);
        });
    }
};
