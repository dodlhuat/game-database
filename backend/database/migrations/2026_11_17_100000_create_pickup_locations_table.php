<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pickup_locations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('address')->nullable();
            $table->text('opening_hours')->nullable();
            $table->text('notes')->nullable();
            $table->string('map_url', 2048)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'sort_order']);
        });

        $now = now();
        $defaultId = DB::table('pickup_locations')->insertGetId([
            'name' => 'Hauptstandort',
            'is_active' => true,
            'sort_order' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Loans are picked up and returned at the same place, so the location
        // is fixed on the loan itself. Nullable only for legacy rows/factories;
        // existing loans are backfilled with the default location below.
        Schema::table('package_loans', function (Blueprint $table) {
            $table->foreignId('pickup_location_id')->nullable()->after('user_id')
                ->constrained('pickup_locations')->restrictOnDelete();
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->foreignId('pickup_location_id')->nullable()->after('user_id')
                ->constrained('pickup_locations')->restrictOnDelete();
        });

        DB::table('package_loans')->update(['pickup_location_id' => $defaultId]);
        DB::table('loans')->update(['pickup_location_id' => $defaultId]);
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pickup_location_id');
        });

        Schema::table('package_loans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('pickup_location_id');
        });

        Schema::dropIfExists('pickup_locations');
    }
};
