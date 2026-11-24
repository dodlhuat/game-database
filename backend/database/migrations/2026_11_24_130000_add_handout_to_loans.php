<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A loan booked for a personal pickup event waits until it is handed out
 * there. Regular loans count as handed out when they are booked.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['loans', 'package_loans'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->timestamp('handed_out_at')->nullable()->after('pickup_event_id');
            });
            // Everything booked so far is out already
            DB::table($tableName)->whereNull('pickup_event_id')->update(['handed_out_at' => DB::raw('created_at')]);
        }

        Schema::table('loans', function (Blueprint $table) {
            // The day-before reminder for a pickup event, sent once
            $table->timestamp('pickup_reminder_sent_at')->nullable()->after('handed_out_at');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropColumn('pickup_reminder_sent_at');
        });

        foreach (['package_loans', 'loans'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('handed_out_at');
            });
        }
    }
};
