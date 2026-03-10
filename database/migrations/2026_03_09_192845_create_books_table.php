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
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('isbn')->unique();
            $table->enum('level', ['A1','A2', 'B1','B2','C1','C2']);
            $table->decimal('cost', 10, 2); // Costo interno (Admin)
            $table->decimal('price_unit', 10, 2); // Precio venta unidad
            $table->integer('units_per_package')->default(1); // Ejemplo: 10
            $table->decimal('price_package', 10, 2)->nullable(); // Precio venta paquete
            $table->integer('stock_alert')->default(10); // Para reportes de bajo stock
            $table->text('description')->nullable();
            $table->string('autor');
            $table->boolean('active')->default(false);
            $table->integer('pages');
            $table->integer('year');
            $table->integer('edition');
            $table->enum('format', ['Bolsillo','Tapa Blanda','Tapa Dura']);
            $table->string('size');
            $table->string('supplier'); // Editorial
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
