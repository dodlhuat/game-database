<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Package;
use App\Models\Tag;
use App\Models\TermsVersion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Nutzungsbedingungen
        TermsVersion::firstOrCreate(
            ['version' => '1.0'],
            [
                'content'      => "§1 Geltungsbereich\nDiese Nutzungsbedingungen gelten für alle Mitglieder der Spielothek.\n\n§2 Ausleihe\nSpiele können für 14 Tage ausgeliehen werden. Eine Verlängerung ist auf Anfrage möglich.\n\n§3 Haftung\nBei Beschädigung oder Verlust ist der Zeitwert des Spiels zu ersetzen.\n\n§4 Datenschutz\nWir verarbeiten Ihre Daten gemäß unserer Datenschutzerklärung.",
                'published_at' => now(),
            ]
        );

        // Kategorien
        $categoryNames = [
            'Strategiespiele', 'Familienspiele', 'Kartenspiele', 'Kooperationsspiele',
            'Partyspiele', 'Lernspiele', 'Trinkspiele', 'Großgruppenspiele',
        ];

        foreach ($categoryNames as $name) {
            Category::firstOrCreate(
                ['name' => $name],
                ['slug' => Str::slug($name)]
            );
        }

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
        $grossgruppe = Category::where('name', 'Großgruppenspiele')->first();
        $trinkspiele = Category::where('name', 'Trinkspiele')->first();

        Package::firstOrCreate(['slug' => 'gruppenspiele-paket'], [
            'name'        => 'Gruppenspiele',
            'description' => 'Ein Paket mit Spielen für die große Gruppe (8+ Personen) aus dem Bereich Großgruppenspiele.',
            'type'        => 'CATEGORY',
            'category_id' => $grossgruppe?->id,
        ]);

        Package::firstOrCreate(['slug' => 'trinkspiele-paket'], [
            'name'        => 'Trinkspiele',
            'description' => 'Ein Paket mit Trinkspielen aus der Kategorie.',
            'type'        => 'CATEGORY',
            'category_id' => $trinkspiele?->id,
        ]);

        Package::firstOrCreate(['slug' => 'ueberraschungspaket-des-monats'], [
            'name'        => 'Überraschungspaket des Monats',
            'description' => '3 ausgewählte Spieleempfehlungen – jeden Monat neu zusammengestellt.',
            'type'        => 'CURATED',
            'category_id' => null,
        ]);
    }
}
