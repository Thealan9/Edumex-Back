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
        Schema::create('ebook_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ebook_id')->constrained('ebook');
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('distributor'); // Amazon, Apple, Google
            $table->string('code'); // El código simulado que verá el usuario
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ebook_purchase');
    }
};
