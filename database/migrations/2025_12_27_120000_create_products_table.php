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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('gtin', 13)->unique()->index()->comment('Global Trade Item Number (13 digits)');
            $table->string('ntin')->nullable()->comment('National Trade Item Number');
            $table->string('nameKk')->nullable()->comment('Product name in Kazakh');
            $table->string('nameRu')->nullable()->comment('Product name in Russian');
            $table->string('nameEn')->nullable()->comment('Product name in English');
            $table->string('shortNameKk')->nullable()->comment('Short name in Kazakh');
            $table->string('shortNameRu')->nullable()->comment('Short name in Russian');
            $table->string('shortNameEn')->nullable()->comment('Short name in English');
            $table->string('createdDate')->nullable()->comment('Created date from National Catalog API');
            $table->string('updatedDate')->nullable()->comment('Updated date from National Catalog API');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
