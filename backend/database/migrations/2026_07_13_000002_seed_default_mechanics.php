<?php

use App\Models\Mechanic;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    /** @var array<int, string> */
    private array $names = [
        'Worker Placement',
        'Deckbuilding',
        'Gebietskontrolle',
        'Würfelmanagement',
        'Kooperativ',
        'Tile Placement',
        'Engine Builder',
        'Stichspiel',
    ];

    public function up(): void
    {
        foreach ($this->names as $name) {
            Mechanic::firstOrCreate(['name' => $name], ['slug' => Str::slug($name)]);
        }
    }

    public function down(): void
    {
        Mechanic::whereIn('name', $this->names)->delete();
    }
};
