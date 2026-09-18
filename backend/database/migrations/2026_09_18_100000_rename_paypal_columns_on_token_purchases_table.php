<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('token_purchases', function (Blueprint $table) {
            $table->renameColumn('paypal_order_id', 'provider_payment_intent_id');
            $table->renameColumn('paypal_capture_id', 'provider_charge_id');
        });
    }

    public function down(): void
    {
        Schema::table('token_purchases', function (Blueprint $table) {
            $table->renameColumn('provider_payment_intent_id', 'paypal_order_id');
            $table->renameColumn('provider_charge_id', 'paypal_capture_id');
        });
    }
};
