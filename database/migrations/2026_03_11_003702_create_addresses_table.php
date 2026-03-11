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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Datos del Receptor
            $table->string('recipient_name'); // Nombre y Apellido de quien recibe
            $table->string('recipient_phone');

            // Ubicación Geográfica
            $table->string('postal_code', 10);
            $table->string('state');
            $table->string('municipality'); // Municipio o Delegación
            $table->string('locality');     // ciudad
            $table->string('neighborhood'); // Colonia

            // Detalles del Domicilio
            $table->string('street');
            $table->string('external_number');
            $table->string('internal_number')->nullable(); // Depto / Interior
            $table->text('references')->nullable();         // Indicaciones adicionales

            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
