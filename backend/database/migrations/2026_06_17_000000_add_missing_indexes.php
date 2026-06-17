<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->index('status');
            $table->index('returned_at');
        });

        Schema::table('games', function (Blueprint $table) {
            $table->index('is_active');
        });

        Schema::table('copies', function (Blueprint $table) {
            $table->index('condition');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['returned_at']);
        });

        Schema::table('games', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
        });

        Schema::table('copies', function (Blueprint $table) {
            $table->dropIndex(['condition']);
        });
    }
};
