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
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('filename')->comment('Original uploaded filename');
            $table->integer('total_gtins')->default(0)->comment('Total number of GTINs in batch');
            $table->integer('processed_count')->default(0)->comment('Number of GTINs processed');
            $table->integer('success_count')->default(0)->comment('Number of successfully imported products');
            $table->integer('failed_count')->default(0)->comment('Number of failed imports');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending')->comment('Batch processing status');
            $table->timestamp('started_at')->nullable()->comment('When processing started');
            $table->timestamp('completed_at')->nullable()->comment('When processing completed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_batches');
    }
};
