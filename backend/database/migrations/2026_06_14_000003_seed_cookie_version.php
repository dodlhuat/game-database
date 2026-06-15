<?php

use App\Models\CookieVersion;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        CookieVersion::updateOrCreate(
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

                    'Zuletzt aktualisiert: Juni 2026',
                ]),
                'published_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        CookieVersion::where('version', '1.0')->delete();
    }
};
