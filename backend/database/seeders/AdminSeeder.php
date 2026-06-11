<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Package;
use App\Models\Tag;
use App\Models\CookieVersion;
use App\Models\PrivacyVersion;
use App\Models\TermsVersion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Cookie-Richtlinie (ePrivacy / DSGVO – Österreich)
        CookieVersion::firstOrCreate(
            ['version' => '1.0'],
            [
                'content' => implode("\n\n", [
                    "COOKIE-RICHTLINIE\n(gem. ePrivacy-Richtlinie 2002/58/EG, DSGVO & TKG 2021, Stand: Juni 2026)",

                    "1. WAS SIND COOKIES?\nCookies sind kleine Textdateien, die beim Besuch einer Website auf Ihrem Endgerät (PC, Smartphone, Tablet) gespeichert werden. Sie ermöglichen es, Einstellungen zu speichern und die Nutzung der Website zu analysieren. Diese Richtlinie erklärt, welche Cookies wir einsetzen und wie Sie diese verwalten können.",

                    "2. WELCHE COOKIES VERWENDEN WIR?\n\na) Technisch notwendige Cookies (keine Einwilligung erforderlich)\nDiese Cookies sind für den Betrieb der Website und unserer Webanwendung unbedingt erforderlich. Ohne sie können grundlegende Funktionen wie Anmeldung, Navigation und Sicherheit nicht funktionieren.\n\nName: sanctum_token / auth_token\nZweck: Authentifizierung – speichert das Anmeldetoken für eingeloggte Mitglieder\nSpeicherdauer: Sitzungsende bzw. bis zum Logout\nAnbieter: Eigener Server ([Vereinsname])\n\nName: XSRF-TOKEN\nZweck: Schutz vor Cross-Site-Request-Forgery (CSRF)-Angriffen\nSpeicherdauer: Sitzung\nAnbieter: Eigener Server\n\nName: nuxt-color-mode\nZweck: Speichert die vom Nutzer gewählte Farbgebung (Hell-/Dunkel-Modus)\nSpeicherdauer: 1 Jahr\nAnbieter: Lokal im Browser (localStorage)\n\nb) Funktionale Cookies (keine Einwilligung erforderlich)\nDiese Cookies verbessern die Benutzerfreundlichkeit, indem sie Einstellungen und Präferenzen merken.\n\nName: i18n_locale\nZweck: Speichert die gewählte Sprache der Benutzeroberfläche\nSpeicherdauer: 1 Jahr\nAnbieter: Lokal im Browser (localStorage)\n\nc) Analyse- und Tracking-Cookies\nWir setzen derzeit KEINE Analyse- oder Tracking-Cookies (z. B. Google Analytics) ein. Es werden keine Daten an Werbenetzwerke oder Drittanbieter für Targeting-Zwecke weitergegeben.",

                    "3. RECHTSGRUNDLAGE\nTechnisch notwendige und funktionale Cookies werden auf Grundlage von Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse an einer funktionsfähigen Website) sowie § 165 Abs. 3 TKG 2021 gesetzt, der für unbedingt erforderliche Cookies keine Einwilligung verlangt.\n\nSollten wir künftig optionale Cookies (z. B. Analyse-Cookies) einsetzen, werden wir Ihre ausdrückliche Einwilligung gem. Art. 6 Abs. 1 lit. a DSGVO und § 165 Abs. 1 TKG 2021 einholen.",

                    "4. SPEICHERUNG IM BROWSER (localStorage / sessionStorage)\nNeben Cookies verwenden wir den lokalen Speicher (localStorage) Ihres Browsers ausschließlich für technisch notwendige Informationen wie Authentifizierungstokens, Spracheinstellungen und den Theme-Modus. Diese Daten verlassen Ihr Gerät nicht und werden nicht an Server übertragen, sofern dies nicht für die Funktion der Anwendung erforderlich ist.",

                    "5. COOKIES VON DRITTANBIETERN\nAuf unserer Website werden keine Cookies oder Tracking-Pixel von sozialen Netzwerken, Werbenetzwerken oder sonstigen Drittanbietern gesetzt. Eingebettete Inhalte Dritter (z. B. Spielcover-Bilder von externen Bildquellen) können eigene Cookies setzen — diese unterliegen den Datenschutzrichtlinien der jeweiligen Anbieter.",

                    "6. COOKIES VERWALTEN UND LÖSCHEN\nSie können Cookies jederzeit über die Einstellungen Ihres Browsers verwalten, einschränken oder löschen:\n\n– Chrome: Einstellungen → Datenschutz und Sicherheit → Cookies\n– Firefox: Einstellungen → Datenschutz & Sicherheit → Cookies und Website-Daten\n– Safari: Einstellungen → Datenschutz → Cookies verwalten\n– Edge: Einstellungen → Datenschutz, Suche und Dienste → Cookies\n\nBitte beachten Sie: Das Löschen technisch notwendiger Cookies führt dazu, dass Sie aus Ihrem Konto ausgeloggt werden und bestimmte Funktionen nicht mehr verfügbar sind.",

                    "7. IHRE RECHTE\nSie haben jederzeit das Recht, Auskunft über die durch Cookies verarbeiteten personenbezogenen Daten zu verlangen (Art. 15 DSGVO), deren Löschung zu fordern (Art. 17 DSGVO) oder Widerspruch gegen die Verarbeitung einzulegen (Art. 21 DSGVO).\n\nFür Anfragen wenden Sie sich an: [kontakt@beispiel.at]\n\nBeschwerden können Sie bei der österreichischen Datenschutzbehörde einbringen:\nÖsterreichische Datenschutzbehörde, Barichgasse 40–42, 1030 Wien\nE-Mail: dsb@dsb.gv.at | Web: https://www.dsb.gv.at",

                    "8. ÄNDERUNGEN DIESER COOKIE-RICHTLINIE\nWir behalten uns vor, diese Cookie-Richtlinie zu aktualisieren, wenn wir neue Technologien einsetzen oder sich die gesetzlichen Anforderungen ändern. Die jeweils aktuelle Fassung ist auf dieser Seite abrufbar. Bei wesentlichen Änderungen, die Einwilligungspflichten betreffen, werden aktive Mitglieder informiert.",

                    "Zuletzt aktualisiert: Juni 2026",
                ]),
                'published_at' => now(),
            ]
        );

        // Datenschutzerklärung (DSGVO – Österreich)
        PrivacyVersion::firstOrCreate(
            ['version' => '1.0'],
            [
                'content' => implode("\n\n", [
                    "DATENSCHUTZERKLÄRUNG\n(gem. DSGVO & DSG 2018, Stand: Juni 2026)",

                    "1. VERANTWORTLICHER\nVerantwortlicher im Sinne der Datenschutz-Grundverordnung (DSGVO) und des österreichischen Datenschutzgesetzes (DSG 2018) ist:\n\n[Vereinsname / Organisation]\n[Straße und Hausnummer]\n[PLZ Ort], Österreich\nE-Mail: [kontakt@beispiel.at]\nWebsite: [https://www.beispiel.at]",

                    "2. GRUNDSÄTZE DER DATENVERARBEITUNG\nWir verarbeiten personenbezogene Daten ausschließlich nach den Grundsätzen der DSGVO: Rechtmäßigkeit, Verarbeitung nach Treu und Glauben, Transparenz, Zweckbindung, Datenminimierung, Richtigkeit, Speicherbegrenzung sowie Integrität und Vertraulichkeit (Art. 5 DSGVO).",

                    "3. VERARBEITETE DATEN UND ZWECKE\n\na) Mitgliedschaftsdaten\nBeim Anlegen eines Kontos erheben wir: Vorname, Nachname, E-Mail-Adresse, Telefonnummer (optional), Geburtsdatum, Adresse. Diese Daten sind zur Begründung und Verwaltung der Mitgliedschaft in unserer Spielothek erforderlich.\nRechtsgrundlage: Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung)\n\nb) Ausleih- und Reservierungsdaten\nFür die Verwaltung von Spieleausleihen und Reservierungen speichern wir, welches Mitglied welches Spiel zu welchem Zeitpunkt ausgeliehen oder reserviert hat. Dazu gehören Ausleih- und Rückgabedaten sowie etwaige Verlängerungen.\nRechtsgrundlage: Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung)\n\nc) Bewertungen und Schadensmeldungen\nFreiwillig abgegebene Spielebewertungen sowie Schadensmeldungen werden mit dem Mitgliedskonto verknüpft gespeichert, um den Bestand der Spielothek zu verwalten.\nRechtsgrundlage: Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse an Bestandserhaltung)\n\nd) Veranstaltungsdaten\nBei Teilnahme an Veranstaltungen verarbeiten wir Name und Kontaktdaten zur Organisation und Kommunikation.\nRechtsgrundlage: Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung)\n\ne) Zahlungs- und Token-Transaktionen\nFür die Verwaltung von Mitgliedsbeiträgen und Token-Guthaben speichern wir Transaktionsdaten (Betrag, Zeitpunkt, Art der Transaktion). Zahlungen werden über externe Dienstleister abgewickelt, die eigene Datenschutzerklärungen führen.\nRechtsgrundlage: Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung), Art. 6 Abs. 1 lit. c DSGVO (rechtliche Verpflichtung)\n\nf) E-Mail-Kommunikation\nWir versenden E-Mails zu: Kontoaktivierung, Passwort-Reset, Ausleihbestätigungen, Fälligkeitserinnerungen sowie Informationen zu Veranstaltungen und Neuigkeiten der Spielothek.\nRechtsgrundlage: Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung) bzw. Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse)",

                    "4. WEITERGABE AN DRITTE\nWir geben Ihre personenbezogenen Daten grundsätzlich nicht an Dritte weiter, es sei denn:\n– es besteht eine gesetzliche Verpflichtung zur Weitergabe (Art. 6 Abs. 1 lit. c DSGVO),\n– Sie haben ausdrücklich eingewilligt (Art. 6 Abs. 1 lit. a DSGVO),\n– die Weitergabe ist zur Vertragserfüllung erforderlich (z. B. Zahlungsdienstleister).\n\nEine Übermittlung personenbezogener Daten in Drittstaaten außerhalb des EWR erfolgt nicht.",

                    "5. SPEICHERDAUER\nPersonenbezogene Daten werden nur so lange gespeichert, wie es für den jeweiligen Zweck erforderlich ist:\n– Mitgliedsdaten: bis zur Beendigung der Mitgliedschaft, danach bis zu 3 Jahren (Gewährleistungsansprüche)\n– Ausleih- und Transaktionsdaten: 7 Jahre (steuer- und handelsrechtliche Aufbewahrungspflichten gem. § 132 BAO)\n– E-Mail-Logs: 12 Monate\n– Inaktive Konten (kein Login seit 3 Jahren): werden nach Benachrichtigung gelöscht",

                    "6. IHRE RECHTE (ART. 12–23 DSGVO)\nSie haben das Recht auf:\n\n– Auskunft (Art. 15 DSGVO): Welche Daten wir über Sie speichern.\n– Berichtigung (Art. 16 DSGVO): Korrektur unrichtiger Daten.\n– Löschung (Art. 17 DSGVO): Unter den gesetzlichen Voraussetzungen (\"Recht auf Vergessenwerden\").\n– Einschränkung der Verarbeitung (Art. 18 DSGVO): Bei Bestreiten der Richtigkeit oder Widerspruch.\n– Datenübertragbarkeit (Art. 20 DSGVO): Herausgabe Ihrer Daten in einem maschinenlesbaren Format.\n– Widerspruch (Art. 21 DSGVO): Gegen Verarbeitungen auf Basis berechtigten Interesses.\n– Widerruf der Einwilligung (Art. 7 Abs. 3 DSGVO): Jederzeit mit Wirkung für die Zukunft.\n\nZur Ausübung dieser Rechte wenden Sie sich bitte per E-Mail an: [kontakt@beispiel.at]\n\nWir beantworten Ihre Anfragen kostenlos innerhalb von einem Monat (Art. 12 Abs. 3 DSGVO).",

                    "7. BESCHWERDERECHT BEI DER AUFSICHTSBEHÖRDE\nSie haben gemäß Art. 77 DSGVO das Recht, sich bei der österreichischen Datenschutzbehörde zu beschweren:\n\nÖsterreichische Datenschutzbehörde\nBarichgasse 40–42, 1030 Wien\nTelefon: +43 1 52 152-0\nE-Mail: dsb@dsb.gv.at\nWebsite: https://www.dsb.gv.at",

                    "8. DATENSICHERHEIT\nWir setzen technische und organisatorische Sicherheitsmaßnahmen ein, um Ihre Daten vor zufälliger oder vorsätzlicher Manipulation, Verlust, Zerstörung oder unberechtigtem Zugriff zu schützen. Dazu zählen verschlüsselte Datenübertragung (TLS/HTTPS), Zugangskontrollen sowie regelmäßige Sicherheitsüberprüfungen. Bei einer Verletzung des Schutzes personenbezogener Daten informieren wir Sie und die Aufsichtsbehörde gem. Art. 33 f. DSGVO.",

                    "9. KEINE AUTOMATISIERTE ENTSCHEIDUNGSFINDUNG\nWir setzen keine automatisierte Entscheidungsfindung einschließlich Profiling im Sinne des Art. 22 DSGVO ein, die rechtliche oder ähnlich bedeutsame Auswirkungen für Sie hat.",

                    "10. ÄNDERUNGEN DIESER DATENSCHUTZERKLÄRUNG\nWir behalten uns vor, diese Datenschutzerklärung zu aktualisieren, wenn sich rechtliche Vorgaben ändern oder wir neue Verarbeitungsvorgänge einführen. Die aktuelle Version ist stets auf dieser Seite abrufbar. Bei wesentlichen Änderungen werden aktive Mitglieder per E-Mail informiert.",

                    "Zuletzt aktualisiert: Juni 2026",
                ]),
                'published_at' => now(),
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
