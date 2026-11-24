<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Payments move from Stripe to Mollie. Each payment now names its provider;
 * existing rows were Stripe. The provider id is set only after the payment
 * exists at the provider — Mollie needs our row id in its redirect URL.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['token_purchases', 'membership_payments'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->renameColumn('provider_payment_intent_id', 'provider_payment_id');
            });
            Schema::table($tableName, function (Blueprint $table) {
                $table->string('provider_payment_id')->nullable()->change();
                $table->string('provider', 20)->default('mollie')->after('user_id');
            });
            DB::table($tableName)->update(['provider' => 'stripe']);
        }
    }

    public function down(): void
    {
        foreach (['membership_payments', 'token_purchases'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('provider');
            });
            Schema::table($tableName, function (Blueprint $table) {
                $table->renameColumn('provider_payment_id', 'provider_payment_intent_id');
            });
        }
    }
};
