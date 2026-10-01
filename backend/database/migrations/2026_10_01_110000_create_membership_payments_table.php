<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 12); // MEMBER | SUPPORTER | RENEWAL
            $table->string('provider_payment_intent_id')->unique();
            $table->string('provider_charge_id')->nullable()->unique();
            $table->unsignedInteger('price_cents');
            $table->string('currency', 3)->default('EUR');
            $table->string('status', 12)->default('CREATED'); // CREATED | COMPLETED | FAILED | REFUNDED
            $table->string('street')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('city')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('captured_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_payments');
    }
};
