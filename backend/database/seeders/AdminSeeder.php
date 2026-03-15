<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Game;
use App\Models\Tag;
use App\Models\TermsVersion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Admin-User
        User::firstOrCreate(
            ['email' => 'admin@spielothek.de'],
            [
                'name'              => 'Admin',
                'password'          => Hash::make('password'),
                'role'              => 'ADMIN',
                'status'            => 'ACTIVE',
                'newsletter_opt_in' => false,
                'terms_accepted_at' => now(),
                'terms_version'     => '1.0',
            ]
        );

        // Nutzungsbedingungen
        TermsVersion::firstOrCreate(
            ['version' => '1.0'],
            [
                'content'      => "§1 Geltungsbereich\nDiese Nutzungsbedingungen gelten für alle Mitglieder der Spielothek.\n\n§2 Ausleihe\nSpiele können für 14 Tage ausgeliehen werden. Eine Verlängerung ist auf Anfrage möglich.\n\n§3 Haftung\nBei Beschädigung oder Verlust ist der Zeitwert des Spiels zu ersetzen.\n\n§4 Datenschutz\nWir verarbeiten Ihre Daten gemäß unserer Datenschutzerklärung.",
                'published_at' => now(),
            ]
        );

        // Kategorien
        $categories = [
            ['name' => 'Strategiespiele',  'description' => 'Taktik, Planung und langfristiges Denken'],
            ['name' => 'Familienspiele',   'description' => 'Spaß für Jung und Alt'],
            ['name' => 'Kartenspiele',     'description' => 'Kompakte Spiele mit Karten'],
            ['name' => 'Kooperationsspiele', 'description' => 'Gemeinsam gewinnen oder verlieren'],
            ['name' => 'Partyspiele',      'description' => 'Ideal für große Runden'],
            ['name' => 'Lernspiele',       'description' => 'Spielend lernen'],
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(['name' => $cat['name']], $cat);
        }

        // Tags
        $tagNames = ['ab 8', 'ab 12', 'ab 16', '2 Spieler', '2-4 Spieler', '2-6 Spieler', '4+ Spieler',
                     '30 Min', '60 Min', '90+ Min', 'Einsteiger', 'Experten', 'Bestseller'];
        $tags = [];
        foreach ($tagNames as $name) {
            $tags[$name] = Tag::firstOrCreate(['name' => $name])->id;
        }

        $strategie = Category::where('name', 'Strategiespiele')->first();
        $familie   = Category::where('name', 'Familienspiele')->first();
        $koop      = Category::where('name', 'Kooperationsspiele')->first();

        // Beispielspiele
        $games = [
            [
                'title'       => 'Catan',
                'slug'        => 'catan',
                'description' => 'Baue Siedlungen, Städte und Straßen auf der Insel Catan. Tausche Rohstoffe und entwickle dein Reich.',
                'min_players' => 3,
                'max_players' => 4,
                'min_age'     => 10,
                'duration'    => 90,
                'category_id' => $strategie?->id,
                'tags'        => ['ab 8', '2-4 Spieler', '90+ Min', 'Bestseller'],
            ],
            [
                'title'       => 'Ticket to Ride',
                'slug'        => 'ticket-to-ride',
                'description' => 'Verbinde Städte mit Zugstrecken quer durch Europa. Wer die längsten Routen baut, gewinnt.',
                'min_players' => 2,
                'max_players' => 5,
                'min_age'     => 8,
                'duration'    => 60,
                'category_id' => $familie?->id,
                'tags'        => ['ab 8', '2-6 Spieler', '60 Min', 'Einsteiger'],
            ],
            [
                'title'       => 'Pandemic',
                'slug'        => 'pandemic',
                'description' => 'Rettet gemeinsam die Welt vor vier gefährlichen Seuchen. Ein kooperatives Spiel für 2–4 Spieler.',
                'min_players' => 2,
                'max_players' => 4,
                'min_age'     => 8,
                'duration'    => 60,
                'category_id' => $koop?->id,
                'tags'        => ['ab 8', '2-4 Spieler', '60 Min', 'Bestseller'],
            ],
            [
                'title'       => '7 Wonders',
                'slug'        => '7-wonders',
                'description' => 'Führe eine der sieben antiken Weltwunder zum Ruhm. Ein Drafting-Spiel für 2–7 Spieler.',
                'min_players' => 2,
                'max_players' => 7,
                'min_age'     => 10,
                'duration'    => 30,
                'category_id' => $strategie?->id,
                'tags'        => ['ab 12', '2-6 Spieler', '30 Min'],
            ],
            [
                'title'       => 'Dixit',
                'slug'        => 'dixit',
                'description' => 'Erzähle mit Bildkarten Geschichten und rate, welche Karte zum Hinweis passt.',
                'min_players' => 3,
                'max_players' => 6,
                'min_age'     => 8,
                'duration'    => 30,
                'category_id' => $familie?->id,
                'tags'        => ['ab 8', '2-6 Spieler', '30 Min', 'Einsteiger'],
            ],
        ];

        foreach ($games as $gameData) {
            $tagIds = collect($gameData['tags'])->map(fn($t) => $tags[$t] ?? null)->filter()->values()->all();
            unset($gameData['tags']);

            $game = Game::firstOrCreate(['slug' => $gameData['slug']], $gameData);
            $game->tags()->syncWithoutDetaching($tagIds);
        }
    }
}
