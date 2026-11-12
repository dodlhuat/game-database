<?php

use App\Support\EnumColumn;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Joining a waiting list costs a flat token fee, charged once when reserving
 * and not refunded when the reservation is cancelled.
 */
return new class extends Migration
{
    private const TYPES = ['BORROW', 'DEPOSIT_BLOCK', 'DEPOSIT_RELEASE', 'DEPOSIT_FORFEIT', 'PURCHASE', 'ADMIN_ADJUSTMENT', 'BONUS_GRANT', 'BONUS_EXPIRE', 'REFUND', 'EXTENSION'];

    public function up(): void
    {
        Schema::table('loan_settings', function (Blueprint $table) {
            $table->unsignedInteger('reservation_cost')->default(1)->after('extension_cost');
        });

        EnumColumn::allow('token_transactions', 'type', [...self::TYPES, 'RESERVATION']);
    }

    public function down(): void
    {
        DB::table('token_transactions')->where('type', 'RESERVATION')->update(['type' => 'ADMIN_ADJUSTMENT']);
        EnumColumn::allow('token_transactions', 'type', self::TYPES);

        Schema::table('loan_settings', function (Blueprint $table) {
            $table->dropColumn('reservation_cost');
        });
    }
};
