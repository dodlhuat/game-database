<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('token_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('paypal_order_id')->unique();
            $table->string('paypal_capture_id')->nullable()->unique();
            $table->integer('token_amount');
            $table->integer('price_cents');
            $table->string('currency', 3)->default('EUR');
            $table->enum('status', ['CREATED', 'APPROVED', 'COMPLETED', 'FAILED', 'REFUNDED'])
                ->default('CREATED');
            $table->json('payload')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('token_purchases');
    }
};
