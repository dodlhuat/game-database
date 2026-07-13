<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mechanics', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('game_mechanics', function (Blueprint $table) {
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mechanic_id')->constrained()->cascadeOnDelete();
            $table->primary(['game_id', 'mechanic_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_mechanics');
        Schema::dropIfExists('mechanics');
    }
};
