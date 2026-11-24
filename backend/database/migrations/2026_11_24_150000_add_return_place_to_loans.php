<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where a game actually came back. Usually its pickup location; a game
 * picked up at a personal pickup event can also come back at a later event
 * there or at any regular location.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->foreignId('return_location_id')->nullable()->after('returned_at')
                ->constrained('pickup_locations')->restrictOnDelete();
            $table->foreignId('return_event_id')->nullable()->after('return_location_id')
                ->constrained('events')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('return_event_id');
            $table->dropConstrainedForeignId('return_location_id');
        });
    }
};
