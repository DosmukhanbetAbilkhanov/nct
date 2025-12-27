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
        Schema::create('import_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained()->onDelete('cascade')->comment('Foreign key to import_batches table');
            $table->string('gtin', 13)->comment('GTIN being processed');
            $table->enum('status', ['pending', 'processing', 'success', 'failed'])->default('pending')->comment('Item processing status');
            $table->text('error_message')->nullable()->comment('Error message if processing failed');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null')->comment('Foreign key to products table if product was created/found');
            $table->timestamps();

            $table->index(['import_batch_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_batch_items');
    }
};
