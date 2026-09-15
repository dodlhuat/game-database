<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('token_transactions', function (Blueprint $table) {
            $table->foreignId('token_purchase_id')->nullable()->after('loan_id')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('token_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('token_purchase_id');
        });
    }
};
