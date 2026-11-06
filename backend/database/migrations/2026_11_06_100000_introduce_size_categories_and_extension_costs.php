<?php

use App\Support\EnumColumn;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * New pricing model: the loan price depends on the game's size category, packages
 * have their own fixed price, and extensions are charged on approval.
 */
return new class extends Migration
{
    private const TYPES = ['BORROW', 'DEPOSIT_BLOCK', 'DEPOSIT_RELEASE', 'DEPOSIT_FORFEIT', 'PURCHASE', 'ADMIN_ADJUSTMENT', 'BONUS_GRANT', 'BONUS_EXPIRE', 'REFUND'];

    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->string('size_category', 16)->default('MEDIUM')->after('deposit_tokens');
        });

        Schema::table('loan_settings', function (Blueprint $table) {
            $table->unsignedInteger('loan_cost_small')->default(3)->after('max_extensions');
            $table->unsignedInteger('loan_cost_medium')->default(6)->after('loan_cost_small');
            $table->unsignedInteger('loan_cost_large')->default(10)->after('loan_cost_medium');
            $table->unsignedInteger('package_loan_cost')->default(3)->after('loan_cost_large');
            $table->unsignedInteger('extension_cost')->default(2)->after('package_loan_cost');
        });

        Schema::table('loan_settings', function (Blueprint $table) {
            $table->dropColumn('loan_cost');
        });

        Schema::table('extensions', function (Blueprint $table) {
            $table->unsignedInteger('tokens_charged')->default(0)->after('status');
        });

        EnumColumn::allow('token_transactions', 'type', [...self::TYPES, 'EXTENSION']);
    }

    public function down(): void
    {
        DB::table('token_transactions')->where('type', 'EXTENSION')->update(['type' => 'ADMIN_ADJUSTMENT']);
        EnumColumn::allow('token_transactions', 'type', self::TYPES);

        Schema::table('extensions', function (Blueprint $table) {
            $table->dropColumn('tokens_charged');
        });

        Schema::table('loan_settings', function (Blueprint $table) {
            $table->unsignedInteger('loan_cost')->default(2)->after('max_extensions');
        });

        Schema::table('loan_settings', function (Blueprint $table) {
            $table->dropColumn(['loan_cost_small', 'loan_cost_medium', 'loan_cost_large', 'package_loan_cost', 'extension_cost']);
        });

        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('size_category');
        });
    }
};
