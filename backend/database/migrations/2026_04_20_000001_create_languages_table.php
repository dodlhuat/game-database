<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        DB::table('languages')->insert([
            ['name' => 'Deutsch',  'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Englisch', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::create('game_language', function (Blueprint $table) {
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->foreignId('language_id')->constrained()->cascadeOnDelete();
            $table->primary(['game_id', 'language_id']);
        });

        // Migrate existing language strings to pivot
        $nameMap = [
            'de'       => 'Deutsch',
            'deutsch'  => 'Deutsch',
            'en'       => 'Englisch',
            'en '      => 'Englisch',
            'englisch' => 'Englisch',
            'english'  => 'Englisch',
        ];

        DB::table('games')->whereNotNull('language')->get()->each(function ($game) use ($nameMap) {
            $parts = array_filter(array_map('trim', explode('/', strtolower($game->language))));
            $langIds = [];
            foreach ($parts as $part) {
                $resolvedName = $nameMap[$part] ?? null;
                if ($resolvedName) {
                    $lang = DB::table('languages')->where('name', $resolvedName)->first();
                    if ($lang) $langIds[] = $lang->id;
                }
            }
            if ($langIds) {
                $rows = array_map(fn($lid) => ['game_id' => $game->id, 'language_id' => $lid], array_unique($langIds));
                DB::table('game_language')->insert($rows);
            }
        });

        Schema::table('games', function (Blueprint $table) {
            $table->dropColumn('language');
        });
    }

    public function down(): void
    {
        Schema::table('games', function (Blueprint $table) {
            $table->string('language')->nullable();
        });
        Schema::dropIfExists('game_language');
        Schema::dropIfExists('languages');
    }
};
