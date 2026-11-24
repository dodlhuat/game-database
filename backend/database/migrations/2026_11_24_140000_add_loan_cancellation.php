<?php

use App\Support\EnumColumn;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A pickup-event loan that was never collected is cancelled and refunded.
 * To refund exactly what was paid — bonus tokens back as bonus tokens with
 * their original expiry — the booking keeps a receipt of the lots it used.
 */
return new class extends Migration
{
    private const LOAN_STATUSES = ['ACTIVE', 'RETURNED', 'OVERDUE', 'EXTENDED'];

    private const PACKAGE_STATUSES = ['ACTIVE', 'RETURNED', 'OVERDUE'];

    public function up(): void
    {
        EnumColumn::allow('loans', 'status', [...self::LOAN_STATUSES, 'CANCELLED'], 'ACTIVE');
        EnumColumn::allow('package_loans', 'status', [...self::PACKAGE_STATUSES, 'CANCELLED'], 'ACTIVE');

        foreach (['loans', 'package_loans'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                // [{lot_id: int|null, amount: int}] — null lot = legacy tokens
                $table->json('spend_receipt')->nullable()->after('handed_out_at');
                $table->timestamp('cancelled_at')->nullable()->after('spend_receipt');
            });
        }
    }

    public function down(): void
    {
        foreach (['package_loans', 'loans'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn(['spend_receipt', 'cancelled_at']);
            });
        }

        DB::table('loans')->where('status', 'CANCELLED')->update(['status' => 'RETURNED']);
        DB::table('package_loans')->where('status', 'CANCELLED')->update(['status' => 'RETURNED']);
        EnumColumn::allow('loans', 'status', self::LOAN_STATUSES, 'ACTIVE');
        EnumColumn::allow('package_loans', 'status', self::PACKAGE_STATUSES, 'ACTIVE');
    }
};
