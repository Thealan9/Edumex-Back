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
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained();
            $table->foreignId('user_id')->constrained(); // Quién lo hizo
            $table->foreignId('location_id')->nullable()->constrained();
            $table->enum('type', ['input', 'output', 'adjustment', 'return']);
            $table->integer('quantity'); // Siempre en unidades
            $table->string('description'); // Ej: "Venta Mayorista Factura #102"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
