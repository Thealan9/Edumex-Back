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
        Schema::table('ebook_purchases', function (Blueprint $table) {
            $table->string('platform')->after('code')->default('Amazon Kindle');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ebook_purchases', function (Blueprint $table) {
            //
        });
    }
};
