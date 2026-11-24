<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Personal pickup: a location flagged as personal is never offered on its
 * own — only through events held there, which bring date and time along.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pickup_locations', function (Blueprint $table) {
            $table->boolean('is_personal')->default(false)->after('is_active');
        });

        Schema::table('events', function (Blueprint $table) {
            // Set = pickup event at this (personal) location
            $table->foreignId('pickup_location_id')->nullable()->after('is_all_day')
                ->constrained()->restrictOnDelete();
            // Optional cap on bookings; null = unlimited
            $table->unsignedInteger('max_loans')->nullable()->after('pickup_location_id');
        });

        foreach (['loans', 'package_loans'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('pickup_event_id')->nullable()->after('pickup_location_id')
                    ->constrained('events')->restrictOnDelete();
            });
        }

        Schema::table('loan_settings', function (Blueprint $table) {
            // Booking closes this many hours before a pickup event starts
            $table->unsignedInteger('pickup_event_lead_hours')->default(24)->after('grace_days');
        });
    }

    public function down(): void
    {
        Schema::table('loan_settings', function (Blueprint $table) {
            $table->dropColumn('pickup_event_lead_hours');
        });

        foreach (['package_loans', 'loans'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('pickup_event_id');
            });
        }

        Schema::table('events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pickup_location_id');
            $table->dropColumn('max_loans');
        });

        Schema::table('pickup_locations', function (Blueprint $table) {
            $table->dropColumn('is_personal');
        });
    }
};
