<?php

use App\Models\PrivacyVersion;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        PrivacyVersion::updateOrCreate(
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

                    'Zuletzt aktualisiert: Juni 2026',
                ]),
                'published_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        PrivacyVersion::where('version', '1.0')->delete();
    }
};
