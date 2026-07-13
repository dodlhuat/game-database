<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Tags
        $tagNames = [
            'ab 8', 'ab 12', 'ab 16', '2 Spieler', '2-4 Spieler', '2-6 Spieler', '4+ Spieler',
            '30 Min', '60 Min', '90+ Min', 'Einsteiger', 'Experten', 'Bestseller',
        ];

        foreach ($tagNames as $name) {
            Tag::firstOrCreate(
                ['name' => $name],
                ['slug' => Str::slug($name)]
            );
        }

        // Pakete
        Package::firstOrCreate(['slug' => 'gruppenspiele-paket'], [
            'name' => 'Gruppenspiele',
            'description' => 'Ein Paket mit Spielen für die große Gruppe (8+ Personen) aus dem Bereich Großgruppenspiele.',
            'type' => 'CATEGORY',
        ]);

        Package::firstOrCreate(['slug' => 'trinkspiele-paket'], [
            'name' => 'Trinkspiele',
            'description' => 'Ein Paket mit Trinkspielen aus der Kategorie.',
            'type' => 'CATEGORY',
        ]);

        Package::firstOrCreate(['slug' => 'ueberraschungspaket-des-monats'], [
            'name' => 'Überraschungspaket des Monats',
            'description' => '3 ausgewählte Spieleempfehlungen – jeden Monat neu zusammengestellt.',
            'type' => 'CURATED',
        ]);
    }
}
