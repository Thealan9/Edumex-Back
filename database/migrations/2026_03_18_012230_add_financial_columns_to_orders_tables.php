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
            $table->decimal('subtotal', 12, 2)->after('status')->default(0);
            $table->decimal('discount', 12, 2)->after('subtotal')->default(0);
            $table->decimal('shipping_cost', 12, 2)->after('discount')->default(0);
            $table->decimal('total', 12, 2)->change();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('discount', 12, 2)->after('price')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'discount', 'shipping_cost']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('discount');
        });
    }
};
