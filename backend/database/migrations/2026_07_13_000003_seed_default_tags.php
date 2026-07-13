<?php

use App\Models\Tag;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    /** @var array<int, string> */
    private array $names = [
        'Entspannt',
        'Konfrontativ',
        'Glückslastig',
        'Strategisch',
        'Taktisch',
        'Rechenlastig',
        'Bauchgefühl',
        'Bluffspiel',
        'Kommunikativ',
        'Verhandlungsspiel',
        'Chaotisch',
        'Einsteigerfreundlich',
        'Abwechslungsreich',
        'Asymmetrisch',
    ];

    public function up(): void
    {
        foreach ($this->names as $name) {
            Tag::firstOrCreate(['name' => $name], ['slug' => Str::slug($name)]);
        }
    }

    public function down(): void
    {
        Tag::whereIn('name', $this->names)->delete();
    }
};
