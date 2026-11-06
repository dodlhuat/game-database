<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Games are lent for two weeks, as stated in the terms. Existing loans keep
 * their due date, only new loans use the shorter duration.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loan_settings', function (Blueprint $table) {
            $table->integer('loan_duration_weeks')->default(2)->change();
        });

        DB::table('loan_settings')->update(['loan_duration_weeks' => 2]);
    }

    public function down(): void
    {
        Schema::table('loan_settings', function (Blueprint $table) {
            $table->integer('loan_duration_weeks')->default(4)->change();
        });

        DB::table('loan_settings')->update(['loan_duration_weeks' => 4]);
    }
};
