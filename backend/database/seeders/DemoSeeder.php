<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Copy;
use App\Models\Event;
use App\Models\Game;
use App\Models\Language;
use App\Models\Loan;
use App\Models\LoanSetting;
use App\Models\Reservation;
use App\Models\Review;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedUsers();
        $this->seedLanguages();
        $this->seedGames();
        $this->seedEvents();
        $this->seedLoansAndReviews();
    }

    // -------------------------------------------------------------------------

    private function seedUsers(): void
    {
        User::firstOrCreate(['email' => 'admin@example.at'], [
            'name' => 'Admin Mustermann',
            'password' => Hash::make('password'),
            'role' => 'ADMIN',
            'status' => 'ACTIVE',
            'email_verified_at' => now(),
            'terms_accepted_at' => now(),
            'terms_version' => '1.0',
            'tokens' => 0,
        ]);

        $members = [
            ['name' => 'Maria Huber',    'email' => 'maria.huber@example.at'],
            ['name' => 'Thomas Müller',  'email' => 'thomas.mueller@example.at'],
            ['name' => 'Anna Berger',    'email' => 'anna.berger@example.at'],
            ['name' => 'Stefan Gruber',  'email' => 'stefan.gruber@example.at'],
            ['name' => 'Lisa Wagner',    'email' => 'lisa.wagner@example.at'],
        ];

        foreach ($members as $data) {
            User::firstOrCreate(['email' => $data['email']], [
                'name' => $data['name'],
                'password' => Hash::make('password'),
                'role' => 'MEMBER',
                'status' => 'ACTIVE',
                'email_verified_at' => now(),
                'terms_accepted_at' => now(),
                'terms_version' => '1.0',
                'tokens' => rand(5, 20),
                'membership_expires_at' => now()->addMonths(rand(1, 12)),
            ]);
        }

        $regularUsers = [
            ['name' => 'Klaus Schuster',  'email' => 'klaus.schuster@example.at'],
            ['name' => 'Eva Steiner',     'email' => 'eva.steiner@example.at'],
            ['name' => 'Michael Pichler', 'email' => 'michael.pichler@example.at'],
        ];

        foreach ($regularUsers as $data) {
            User::firstOrCreate(['email' => $data['email']], [
                'name' => $data['name'],
                'password' => Hash::make('password'),
                'role' => 'USER',
                'status' => 'ACTIVE',
                'email_verified_at' => now(),
                'terms_accepted_at' => now(),
                'terms_version' => '1.0',
                'tokens' => 0,
            ]);
        }

        User::firstOrCreate(['email' => 'inaktiv@example.at'], [
            'name' => 'Gesperrter Nutzer',
            'password' => Hash::make('password'),
            'role' => 'USER',
            'status' => 'SUSPENDED',
            'tokens' => 0,
        ]);
    }

    private function seedLanguages(): void
    {
        foreach (['Deutsch', 'Englisch', 'Französisch'] as $name) {
            Language::firstOrCreate(['name' => $name]);
        }
    }

    private function seedGames(): void
    {
        $categories = Category::all()->keyBy('name');
        $tags = Tag::all()->keyBy('name');
        $languages = Language::all()->keyBy('name');

        $games = [
            [
                'title' => 'Catan',
                'slug' => 'catan',
                'cover_image_url' => 'https://picsum.photos/seed/catan/400/560',
                'short_description' => 'Baue Siedlungen, handle Rohstoffe und werde Herrscher über Catan.',
                'description' => 'Bei Catan besiedeln die Spieler eine Insel und versuchen durch Handel und geschicktes Bauen die Vorherrschaft zu erlangen. Rohstoffe werden durch Würfeln gesammelt und gegen Siedlungen, Straßen und Städte eingetauscht.',
                'category' => 'Strategiespiele',
                'min_players' => 3, 'max_players' => 4, 'min_age' => 10,
                'duration_min' => 60, 'duration_max' => 120,
                'difficulty' => 'MEDIUM', 'year' => 1995,
                'deposit_tokens' => 5,
                'tags' => ['Taktisch', 'Handelsspiel', 'Spannend', 'Bestseller'],
                'languages' => ['Deutsch', 'Englisch'],
                'copies' => 2,
            ],
            [
                'title' => 'Ticket to Ride',
                'slug' => 'ticket-to-ride',
                'cover_image_url' => 'https://picsum.photos/seed/ticket-to-ride/400/560',
                'short_description' => 'Verbinde Städte mit Zugstrecken und sammle Punkte.',
                'description' => 'Spieler sammeln Waggonkarten und setzen ihre Waggons ein, um Zugrouten zwischen Städten zu beanspruchen. Wer die längste Strecke baut und seine geheimen Zielkarten erfüllt, gewinnt.',
                'category' => 'Familienspiele',
                'min_players' => 2, 'max_players' => 5, 'min_age' => 8,
                'duration_min' => 45, 'duration_max' => 75,
                'difficulty' => 'EASY', 'year' => 2004,
                'deposit_tokens' => 4,
                'tags' => ['Entspannt', 'Gemein', 'Familienfreundlich', 'Bestseller'],
                'languages' => ['Deutsch'],
                'copies' => 2,
            ],
            [
                'title' => 'Pandemic',
                'slug' => 'pandemic',
                'cover_image_url' => 'https://picsum.photos/seed/pandemic/400/560',
                'short_description' => 'Stoppt gemeinsam vier gefährliche Krankheiten.',
                'description' => 'In diesem kooperativen Spiel kämpfen die Spieler als Spezialisten gegen vier Krankheiten, die sich weltweit ausbreiten. Nur durch Zusammenarbeit und kluge Planung können alle Krankheiten geheilt werden.',
                'category' => 'Kooperationsspiele',
                'min_players' => 2, 'max_players' => 4, 'min_age' => 8,
                'duration_min' => 45, 'duration_max' => 60,
                'difficulty' => 'MEDIUM', 'year' => 2008,
                'deposit_tokens' => 4,
                'tags' => ['Kooperativ', 'Stressig', 'Thematisch', 'Teamplay'],
                'languages' => ['Deutsch', 'Englisch'],
                'copies' => 1,
            ],
            [
                'title' => 'Carcassonne',
                'slug' => 'carcassonne',
                'cover_image_url' => 'https://picsum.photos/seed/carcassonne/400/560',
                'short_description' => 'Lege Landschaftskarten und beanspruche Städte, Straßen und Klöster.',
                'description' => 'Carcassonne ist ein Legespiel, bei dem Spieler Landschaftskärtchen anlegen und ihre Gefolgsleute als Ritter, Räuber oder Mönche einsetzen. Punkte gibt es für vollendete Städte, Straßen und Klöster.',
                'category' => 'Familienspiele',
                'min_players' => 2, 'max_players' => 5, 'min_age' => 8,
                'duration_min' => 35, 'duration_max' => 45,
                'difficulty' => 'EASY', 'year' => 2000,
                'deposit_tokens' => 3,
                'tags' => ['Entspannt', 'Gemütlich', 'Taktisch', 'Familienfreundlich'],
                'languages' => ['Deutsch'],
                'copies' => 2,
            ],
            [
                'title' => 'Codenames',
                'slug' => 'codenames',
                'cover_image_url' => 'https://picsum.photos/seed/codenames/400/560',
                'short_description' => 'Gebt euren Agenten Hinweise mit einem Wort.',
                'description' => 'Zwei Spionageteams versuchen, ihre Agenten auf einem Raster aus Codewörtern zu identifizieren. Die Geheimdienstchefs geben mit je einem Wort Hinweise auf mehrere Karten gleichzeitig.',
                'category' => 'Partyspiele',
                'min_players' => 4, 'max_players' => 8, 'min_age' => 14,
                'duration_min' => 15, 'duration_max' => 30,
                'difficulty' => 'EASY', 'year' => 2015,
                'deposit_tokens' => 2,
                'tags' => ['Lustig', 'Kreativ', 'Teamplay', 'Wortspiel'],
                'languages' => ['Deutsch'],
                'copies' => 2,
            ],
            [
                'title' => 'Azul',
                'slug' => 'azul',
                'cover_image_url' => 'https://picsum.photos/seed/azul/400/560',
                'short_description' => 'Lege farbige Kacheln nach Mustern.',
                'description' => 'Inspiriert von portugiesischen Azulejo-Kacheln. Spieler nehmen reihum Kacheln aus der Fabrik und legen sie in Mustern ab. Wer am Ende die meisten Punkte gesammelt hat, gewinnt.',
                'category' => 'Strategiespiele',
                'min_players' => 2, 'max_players' => 4, 'min_age' => 8,
                'duration_min' => 30, 'duration_max' => 45,
                'difficulty' => 'EASY', 'year' => 2017,
                'deposit_tokens' => 4,
                'tags' => ['Entspannt', 'Taktisch', 'Gemein', 'Abstrakt'],
                'languages' => ['Deutsch', 'Englisch'],
                'copies' => 1,
            ],
            [
                'title' => 'Dixit',
                'slug' => 'dixit',
                'cover_image_url' => 'https://picsum.photos/seed/dixit/400/560',
                'short_description' => 'Erzähle Geschichten mit traumhaften Illustrationen.',
                'description' => 'Dixit ist ein kreatives Assoziationsspiel. Ein Spieler beschreibt eine Karte, die anderen legen passende Karten dazu – alle versuchen zu erraten, welche die echte ist.',
                'category' => 'Familienspiele',
                'min_players' => 3, 'max_players' => 6, 'min_age' => 8,
                'duration_min' => 30, 'duration_max' => 45,
                'difficulty' => 'EASY', 'year' => 2008,
                'deposit_tokens' => 3,
                'tags' => ['Kreativ', 'Fantasievoll', 'Stimmungsvoll', 'Familienfreundlich'],
                'languages' => ['Deutsch'],
                'copies' => 1,
            ],
            [
                'title' => 'Wingspan',
                'slug' => 'wingspan',
                'cover_image_url' => 'https://picsum.photos/seed/wingspan/400/560',
                'short_description' => 'Sammle Vögel und baue dein Vogelschutzgebiet aus.',
                'description' => 'Wingspan ist ein Engine-Building-Spiel für Vogelbegeisterte. Spieler ziehen Vögel in ihr Schutzgebiet, aktivieren Fähigkeiten und sammeln Eier.',
                'category' => 'Strategiespiele',
                'min_players' => 1, 'max_players' => 5, 'min_age' => 10,
                'duration_min' => 40, 'duration_max' => 70,
                'difficulty' => 'MEDIUM', 'year' => 2019,
                'deposit_tokens' => 6,
                'tags' => ['Entspannt', 'Lehrreich', 'Grübeln', 'Komplex'],
                'languages' => ['Deutsch', 'Englisch'],
                'copies' => 1,
            ],
            [
                'title' => 'The Mind',
                'slug' => 'the-mind',
                'cover_image_url' => 'https://picsum.photos/seed/the-mind/400/560',
                'short_description' => 'Spielt Karten ohne zu sprechen in der richtigen Reihenfolge.',
                'description' => 'The Mind ist ein kooperatives Kartenspiel, bei dem alle ihre Handkarten in aufsteigender Reihenfolge ablegen müssen – ohne miteinander zu kommunizieren.',
                'category' => 'Kooperationsspiele',
                'min_players' => 2, 'max_players' => 4, 'min_age' => 8,
                'duration_min' => 15, 'duration_max' => 20,
                'difficulty' => 'EASY', 'year' => 2017,
                'deposit_tokens' => 2,
                'tags' => ['Kooperativ', 'Spannend', 'Verrückt', 'Einzigartig'],
                'languages' => ['Deutsch'],
                'copies' => 2,
            ],
            [
                'title' => 'Just One',
                'slug' => 'just-one',
                'cover_image_url' => 'https://picsum.photos/seed/just-one/400/560',
                'short_description' => 'Erratet gemeinsam Begriffe mit einzigartigen Hinweisen.',
                'description' => 'Just One ist ein kooperatives Ratespiel. Alle schreiben einen Hinweis – doppelte werden gestrichen. Der Ratende muss den Begriff mit den verbleibenden Hinweisen erraten.',
                'category' => 'Partyspiele',
                'min_players' => 3, 'max_players' => 7, 'min_age' => 8,
                'duration_min' => 20, 'duration_max' => 40,
                'difficulty' => 'EASY', 'year' => 2018,
                'deposit_tokens' => 2,
                'tags' => ['Kooperativ', 'Lustig', 'Kreativ', 'Teamplay'],
                'languages' => ['Deutsch'],
                'copies' => 2,
            ],
            [
                'title' => 'Agricola',
                'slug' => 'agricola',
                'cover_image_url' => 'https://picsum.photos/seed/agricola/400/560',
                'short_description' => 'Bewirtschafte deinen Bauernhof und versorge deine Familie.',
                'description' => 'Agricola ist ein Ressourcenmanagement-Spiel. Spieler führen Bauern, müssen ihre Familie ernähren und Punkte durch Felder, Tiere und Häuser sammeln.',
                'category' => 'Strategiespiele',
                'min_players' => 1, 'max_players' => 5, 'min_age' => 12,
                'duration_min' => 30, 'duration_max' => 150,
                'difficulty' => 'HARD', 'year' => 2007,
                'deposit_tokens' => 6,
                'tags' => ['Grübeln', 'Anspruchsvoll', 'Stressig', 'Taktisch'],
                'languages' => ['Deutsch', 'Englisch'],
                'copies' => 1,
            ],
            [
                'title' => 'King of Tokyo',
                'slug' => 'king-of-tokyo',
                'cover_image_url' => 'https://picsum.photos/seed/king-of-tokyo/400/560',
                'short_description' => 'Monster kämpfen um die Herrschaft über Tokyo.',
                'description' => 'In King of Tokyo schlüpfen Spieler in Monster, Aliens oder Roboter und kämpfen mit Würfeln um die Herrschaft über Tokyo. Energie sammeln, heilen oder angreifen – wer 20 Punkte hat oder alle anderen besiegt, gewinnt.',
                'category' => 'Partyspiele',
                'min_players' => 2, 'max_players' => 6, 'min_age' => 8,
                'duration_min' => 30, 'duration_max' => 45,
                'difficulty' => 'EASY', 'year' => 2011,
                'deposit_tokens' => 3,
                'tags' => ['Aggressiv', 'Lustig', 'Chaotisch', 'Spannend'],
                'languages' => ['Deutsch'],
                'copies' => 1,
            ],
            [
                'title' => 'Dominion',
                'slug' => 'dominion',
                'cover_image_url' => 'https://picsum.photos/seed/dominion/400/560',
                'short_description' => 'Baue deinen Kartenstapel und errichte dein Königreich.',
                'description' => 'Dominion gilt als Urvater der Deck-Building-Spiele. Spieler kaufen Karten für ihren persönlichen Stapel und versuchen, am Ende die meisten Siegpunkte zu sammeln.',
                'category' => 'Kartenspiele',
                'min_players' => 2, 'max_players' => 4, 'min_age' => 14,
                'duration_min' => 30, 'duration_max' => 60,
                'difficulty' => 'MEDIUM', 'year' => 2008,
                'deposit_tokens' => 4,
                'tags' => ['Taktisch', 'Grübeln', 'Komplex', 'Gemein'],
                'languages' => ['Deutsch'],
                'copies' => 1,
            ],
            [
                'title' => 'Uno',
                'slug' => 'uno',
                'cover_image_url' => 'https://picsum.photos/seed/uno/400/560',
                'short_description' => 'Klassisches Kartenspiel: Werde als Erster deine Karten los.',
                'description' => 'Uno ist das beliebte Kartenspiel, bei dem Spieler ihre Karten nach Farbe oder Zahl ablegen. Aktionskarten sorgen für Spannung.',
                'category' => 'Kartenspiele',
                'min_players' => 2, 'max_players' => 10, 'min_age' => 7,
                'duration_min' => 15, 'duration_max' => 45,
                'difficulty' => 'EASY', 'year' => 1971,
                'deposit_tokens' => 1,
                'tags' => ['Gemein', 'Lustig', 'Chaotisch', 'Familienfreundlich'],
                'languages' => ['Deutsch'],
                'copies' => 3,
            ],
            [
                'title' => '7 Wonders',
                'slug' => '7-wonders',
                'cover_image_url' => 'https://picsum.photos/seed/7-wonders/400/560',
                'short_description' => 'Führe deine antike Zivilisation zur Größe.',
                'description' => 'In 7 Wonders leiten Spieler eine antike Zivilisation und wählen reihum Karten aus. Militär, Wissenschaft, Handel und Wunder bringen unterschiedliche Siegpunkte.',
                'category' => 'Strategiespiele',
                'min_players' => 2, 'max_players' => 7, 'min_age' => 10,
                'duration_min' => 30, 'duration_max' => 45,
                'difficulty' => 'MEDIUM', 'year' => 2010,
                'deposit_tokens' => 4,
                'tags' => ['Taktisch', 'Vielschichtig', 'Anspruchsvoll', 'Komplex'],
                'languages' => ['Deutsch', 'Englisch', 'Französisch'],
                'copies' => 1,
            ],
        ];

        foreach ($games as $data) {
            $game = Game::firstOrCreate(['slug' => $data['slug']], [
                'title' => $data['title'],
                'description' => $data['description'],
                'short_description' => $data['short_description'],
                'cover_image_url' => $data['cover_image_url'],
                'category_id' => $categories->get($data['category'])?->id,
                'min_players' => $data['min_players'],
                'max_players' => $data['max_players'],
                'min_age' => $data['min_age'],
                'duration_min' => $data['duration_min'],
                'duration_max' => $data['duration_max'],
                'difficulty' => $data['difficulty'],
                'year' => $data['year'],
                'deposit_tokens' => $data['deposit_tokens'],
                'is_active' => true,
            ]);

            $game->update(['cover_image_url' => $data['cover_image_url']]);

            $tagIds = collect($data['tags'])->map(function (string $name) use (&$tags) {
                if (! $tags->has($name)) {
                    $tag = Tag::firstOrCreate(['name' => $name], ['slug' => Str::slug($name)]);
                    $tags->put($name, $tag);
                }

                return $tags->get($name)?->id;
            })->filter()->values()->all();

            $game->tags()->syncWithoutDetaching($tagIds);

            $game->languages()->syncWithoutDetaching(
                collect($data['languages'])->map(fn ($n) => $languages->get($n)?->id)->filter()->values()->all()
            );

            $existingCopies = $game->copies()->count();
            for ($i = $existingCopies; $i < $data['copies']; $i++) {
                Copy::create([
                    'game_id' => $game->id,
                    'condition' => ['NEW', 'VERY_GOOD', 'GOOD'][array_rand(['NEW', 'VERY_GOOD', 'GOOD'])],
                    'borrow_count' => rand(0, 15),
                ]);
            }
        }
    }

    private function seedEvents(): void
    {
        $events = [
            [
                'title' => 'Spieleabend: Strategieklassiker',
                'date' => now()->addDays(7)->toDateString(),
                'time' => '19:00',
                'is_all_day' => false,
                'description' => 'Gemeinsamer Abend mit Strategieklassikern wie Catan und Carcassonne. Für Einsteiger und Fortgeschrittene.',
            ],
            [
                'title' => 'Familienspiele-Nachmittag',
                'date' => now()->addDays(14)->toDateString(),
                'time' => '14:00',
                'is_all_day' => false,
                'description' => 'Ein entspannter Nachmittag mit Familienspielen für alle Altersgruppen. Kinder willkommen!',
            ],
            [
                'title' => 'Kooperations-Turnier',
                'date' => now()->addDays(21)->toDateString(),
                'time' => '15:00',
                'is_all_day' => false,
                'description' => 'Tretet gemeinsam in Teams an und löst knifflige Kooperationsspiele. Preis für das beste Team!',
            ],
            [
                'title' => 'Spieletausch-Flohmarkt',
                'date' => now()->addDays(30)->toDateString(),
                'time' => null,
                'is_all_day' => true,
                'description' => 'Tauscht eure nicht mehr gespielten Spiele gegen neue. Eintritt frei, Mitmachen erwünscht!',
            ],
        ];

        foreach ($events as $data) {
            Event::firstOrCreate(['title' => $data['title'], 'date' => $data['date']], $data);
        }
    }

    private function seedLoansAndReviews(): void
    {
        $loanSetting = LoanSetting::first();
        $memberUsers = User::where('role', 'MEMBER')->get();
        $allCopies = Copy::with('game')->get();

        if (! $loanSetting || $memberUsers->isEmpty() || $allCopies->isEmpty()) {
            return;
        }

        $durationWeeks = $loanSetting->loan_duration_weeks ?? 2;
        $comments = [
            'Tolles Spiel, macht sehr viel Spaß!',
            'Gutes Spiel, aber etwas komplex für Anfänger.',
            'Sehr empfehlenswert, werden es sicher nochmal ausleihen.',
            'Macht viel Spaß in der Gruppe, gerne wieder!',
            'Schönes Familienspiel für den Spieleabend.',
        ];

        // 4 zurückgegebene Ausleihen mit Bewertungen
        foreach ($allCopies->take(4) as $i => $copy) {
            $user = $memberUsers[$i % $memberUsers->count()];
            $startDate = now()->subDays(rand(30, 90));
            $dueDate = $startDate->copy()->addWeeks($durationWeeks);
            $returnedAt = $dueDate->copy()->subDays(rand(1, 5));

            if (Loan::where('copy_id', $copy->id)->where('user_id', $user->id)->exists()) {
                continue;
            }

            $loan = Loan::create([
                'copy_id' => $copy->id,
                'user_id' => $user->id,
                'start_date' => $startDate,
                'due_date' => $dueDate,
                'returned_at' => $returnedAt,
                'status' => 'RETURNED',
                'deposit_tokens' => $copy->game->deposit_tokens ?? 0,
            ]);

            Review::create([
                'game_id' => $copy->game_id,
                'loan_id' => $loan->id,
                'user_id' => $user->id,
                'rating' => rand(3, 5),
                'comment' => $comments[$i % count($comments)],
            ]);
        }

        // 3 aktive Ausleihen
        foreach ($allCopies->slice(4, 3) as $i => $copy) {
            $user = $memberUsers[($i + 2) % $memberUsers->count()];
            $startDate = now()->subDays(rand(3, 10));
            $dueDate = $startDate->copy()->addWeeks($durationWeeks);

            if (Loan::where('copy_id', $copy->id)->whereIn('status', ['ACTIVE', 'EXTENDED', 'OVERDUE'])->exists()) {
                continue;
            }

            Loan::create([
                'copy_id' => $copy->id,
                'user_id' => $user->id,
                'start_date' => $startDate,
                'due_date' => $dueDate,
                'status' => 'ACTIVE',
                'deposit_tokens' => $copy->game->deposit_tokens ?? 0,
            ]);
        }

        // 1 überfällige Ausleihe
        $overdueCopy = $allCopies->get(7);
        if ($overdueCopy && ! Loan::where('copy_id', $overdueCopy->id)->whereIn('status', ['ACTIVE', 'EXTENDED', 'OVERDUE'])->exists()) {
            Loan::create([
                'copy_id' => $overdueCopy->id,
                'user_id' => $memberUsers->first()->id,
                'start_date' => now()->subDays(30),
                'due_date' => now()->subDays(16),
                'status' => 'OVERDUE',
                'deposit_tokens' => $overdueCopy->game->deposit_tokens ?? 0,
            ]);
        }

        // Reservierungen für Catan (beliebtestes Spiel)
        $catan = Game::where('slug', 'catan')->first();
        if ($catan) {
            foreach ($memberUsers->take(3) as $pos => $user) {
                if (! Reservation::where('game_id', $catan->id)->where('user_id', $user->id)->exists()) {
                    Reservation::create([
                        'game_id' => $catan->id,
                        'user_id' => $user->id,
                        'position' => $pos + 1,
                    ]);
                }
            }
        }
    }
}
