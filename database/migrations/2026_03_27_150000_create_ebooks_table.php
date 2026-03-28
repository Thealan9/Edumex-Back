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
        Schema::create('ebooks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('image_path')->nullable();
            $table->string('isbn')->unique();
            $table->enum('level', ['A1','A2', 'B1','B2','C1','C2']);
            $table->string('category')->default('General English');
            $table->decimal('price', 10, 2); // Precio venta unidad
            $table->text('description')->nullable();
            $table->string('autor');
            $table->boolean('active')->default(false);
            $table->integer('pages');
            $table->integer('year');
            $table->integer('edition');
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
        Schema::dropIfExists('ebook');
    }
};
