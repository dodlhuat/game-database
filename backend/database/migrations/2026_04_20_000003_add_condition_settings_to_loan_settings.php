<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_settings', function (Blueprint $table) {
            $table->unsignedInteger('loan_cost')->default(2)->after('max_extensions');
            $table->unsignedInteger('condition_very_good_after')->default(5)->after('loan_cost');
            $table->unsignedInteger('condition_good_after')->default(50)->after('condition_very_good_after');
            $table->unsignedInteger('deposit_pct_very_good')->default(90)->after('condition_good_after');
            $table->unsignedInteger('deposit_pct_good')->default(80)->after('deposit_pct_very_good');
        });
    }

    public function down(): void
    {
        Schema::table('loan_settings', function (Blueprint $table) {
            $table->dropColumn([
                'loan_cost',
                'condition_very_good_after',
                'condition_good_after',
                'deposit_pct_very_good',
                'deposit_pct_good',
            ]);
        });
    }
};
