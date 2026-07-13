<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });

        Schema::dropIfExists('categories');
    }

    public function down(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('games', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('description')->constrained()->nullOnDelete();
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('type')->constrained()->nullOnDelete();
        });
    }
};
