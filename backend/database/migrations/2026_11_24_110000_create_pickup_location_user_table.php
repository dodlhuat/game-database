<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Restricts a pickup location to selected users. A location without rows
 * here stays open for everyone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_location_user', function (Blueprint $table) {
            $table->foreignId('pickup_location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['pickup_location_id', 'user_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_location_user');
    }
};
