<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // min_games / max_games aus packages entfernen falls vorhanden
        Schema::table('packages', function (Blueprint $table) {
            if (Schema::hasColumn('packages', 'min_games')) {
                $table->dropColumn('min_games');
            }
            if (Schema::hasColumn('packages', 'max_games')) {
                $table->dropColumn('max_games');
            }
        });

        Schema::create('package_game', function (Blueprint $table) {
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->primary(['package_id', 'game_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_game');
    }
};
