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
        Schema::create('sms_verification_codes', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number');
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->boolean('verified')->default(false);
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['phone_number', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_verification_codes');
    }
};
