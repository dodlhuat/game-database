<?php

use App\Models\PrivacyVersion;
use Illuminate\Database\Migrations\Migration;

/**
 * Privacy policy 1.3: brought in line with what the app actually does.
 * Names the processors (Hetzner hosting, Resend email incl. US transfer,
 * Nominatim address check, Mollie), newsletter on consent, server logs,
 * damage photos and favourites, real registration vs. membership data,
 * contact email and website, and the retention rules now enforced by
 * model:prune (email logs) and accounts:prune-inactive. Older versions stay
 * in place as history.
 */
return new class extends Migration
{
    public function up(): void
    {
        PrivacyVersion::updateOrCreate(
            ['version' => '1.3'],
            [
                'content' => <<<'TXT'
DATENSCHUTZERKLÄRUNG
(gem. DSGVO & DSG 2018, Stand: November 2026)

1. VERANTWORTLICHER
Verantwortlicher im Sinne der Datenschutz-Grundverordnung (DSGVO) und des österreichischen Datenschutzgesetzes (DSG 2018) ist:

AUA-Alle.Unsere.Abenteuer – Verein zur Förderung des gemeinsamen Spielens
[Straße und Hausnummer]
[PLZ] St. Pölten, Österreich
E-Mail: alle.unsere.abenteuer@gmail.com
Website: https://alle-unsere-abenteuer.at

2. GRUNDSÄTZE DER DATENVERARBEITUNG
Wir verarbeiten personenbezogene Daten ausschließlich nach den Grundsätzen der DSGVO: Rechtmäßigkeit, Verarbeitung nach Treu und Glauben, Transparenz, Zweckbindung, Datenminimierung, Richtigkeit, Speicherbegrenzung sowie Integrität und Vertraulichkeit (Art. 5 DSGVO).

3. VERARBEITETE DATEN UND ZWECKE

a) Benutzerkonto
Bei der Registrierung erheben wir Name, E-Mail-Adresse und Passwort. Das Passwort wird ausschließlich als kryptografischer Hashwert gespeichert. Zusätzlich speichern wir, wann und in welcher Version Sie den Nutzungsbedingungen zugestimmt haben, sowie den Zeitpunkt Ihrer letzten Nutzung (zur Löschung inaktiver Konten, siehe Punkt 5).
Rechtsgrundlage: Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung)

b) Mitgliedschaftsdaten
Wenn Sie Mitglied werden, erheben wir zusätzlich Ihre Adresse (Straße, Postleitzahl, Ort) und Ihr Geburtsdatum. Diese Daten sind zur Begründung und Verwaltung der Mitgliedschaft im Verein erforderlich.
Zur Vermeidung von Tippfehlern prüfen wir bei der Eingabe automatisiert, ob die angegebene Adresse existiert. Dazu werden Straße, Postleitzahl und Ort (ohne Ihren Namen) an den Geodienst Nominatim der OpenStreetMap Foundation (Vereinigtes Königreich) übermittelt. Für das Vereinigte Königreich besteht ein Angemessenheitsbeschluss der EU-Kommission.
Rechtsgrundlage: Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung), Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse an korrekten Mitgliedsdaten)

c) Ausleih- und Reservierungsdaten
Für die Verwaltung von Spiel- und Paketausleihen sowie Reservierungen speichern wir, welches Mitglied welches Spiel zu welchem Zeitpunkt ausgeliehen oder reserviert hat. Dazu gehören Ausleih- und Rückgabedaten, der gewählte Abholort, etwaige Verlängerungen und blockierte Kautionen.
Rechtsgrundlage: Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung)

d) Bewertungen, Favoriten und Schadensmeldungen
Freiwillig abgegebene Spielebewertungen, als Favoriten markierte Spiele sowie Schadensmeldungen werden mit Ihrem Konto verknüpft gespeichert. Einer Schadensmeldung können Sie ein Foto beifügen; Fotos werden beim Hochladen neu kodiert, wodurch eingebettete Metadaten wie Aufnahmeort (GPS) und Kameradaten entfernt werden.
Rechtsgrundlage: Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung), Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse an der Erhaltung des Spielebestands)

e) Mitgliedsbeitrag, Zahlungs- und Token-Transaktionen
Für die Verwaltung von Mitgliedsbeiträgen und Token-Guthaben speichern wir Transaktionsdaten (Betrag, Zeitpunkt, Art der Transaktion, Status der Zahlung). Das Token-Guthaben wird getrennt nach gekauften Token und geschenkten Bonus-Token geführt; für Bonus-Token speichern wir zusätzlich das Ablaufdatum.
Online-Zahlungen (Mitgliedsbeitrag, Token-Pakete) werden über den Zahlungsdienstleister Mollie (Mollie B.V., Niederlande) abgewickelt. Zum Bezahlen wirst du auf eine Seite von Mollie weitergeleitet; Zahlungsdaten wie Kartennummern gibst du direkt dort ein, sie werden von uns nicht gespeichert. Wir erhalten lediglich eine Bestätigung über die erfolgte Zahlung samt Referenz.
Rechtsgrundlage: Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung), Art. 6 Abs. 1 lit. c DSGVO (rechtliche Verpflichtung)

f) Kündigung der Mitgliedschaft und Rückerstattung
Bei einer Kündigung speichern wir den Zeitpunkt der Kündigung, den Token-Stand sowie den zurückzuerstattenden Betrag. Wenn eine Rückerstattung anfällt, erheben wir zusätzlich Kontoinhaber und IBAN, um die Rücküberweisung vornehmen zu können. Die IBAN wird in unserer Datenbank verschlüsselt gespeichert und ist nur für Administratoren zur Durchführung der Überweisung einsehbar. Die Angabe eines Kündigungsgrundes ist freiwillig.
Rechtsgrundlage: Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung), Art. 6 Abs. 1 lit. c DSGVO (rechtliche Verpflichtung)

g) E-Mail-Kommunikation
Wir versenden E-Mails, die für die Nutzung erforderlich sind: Bestätigung der E-Mail-Adresse, Passwort-Reset, Ausleihbestätigungen, Fälligkeits- und Ablauferinnerungen, Bestätigungen zu Zahlungen und Kündigungen sowie die Benachrichtigung vor der Löschung eines inaktiven Kontos. Bei einer Kündigung wird zusätzlich eine Benachrichtigung an die Administratoren gesendet, damit die Rücküberweisung veranlasst werden kann. Über versendete E-Mails führen wir ein Protokoll (Empfänger, Art der E-Mail, Betreff, Zeitpunkt).
Rechtsgrundlage: Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung) bzw. Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse an einer nachvollziehbaren Kommunikation)

h) Newsletter
Informationen zu Veranstaltungen und Neuigkeiten der Spielothek erhalten Sie nur, wenn Sie bei der Registrierung oder in Ihrem Konto den Newsletter aktiviert haben. Sie können diese Einwilligung jederzeit in den Kontoeinstellungen mit Wirkung für die Zukunft widerrufen.
Rechtsgrundlage: Art. 6 Abs. 1 lit. a DSGVO (Einwilligung)

i) Server-Protokolle
Beim Aufruf der Website verarbeitet unser Webserver technisch notwendige Daten (IP-Adresse, Zeitpunkt, aufgerufene Seite, Browser-Kennung), um die Website auszuliefern und Angriffe abzuwehren. Diese Protokolle werden nach spätestens 14 Tagen gelöscht.
Rechtsgrundlage: Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse an einem sicheren Betrieb)

j) Cookies und lokale Speicherung
Wir verwenden ausschließlich technisch notwendige Cookies und Einträge im lokalen Speicher Ihres Browsers (z. B. für die Anmeldung und die Darstellungseinstellungen). Details finden Sie in unserer Cookie-Richtlinie.

4. EMPFÄNGER UND AUFTRAGSVERARBEITER
Wir geben Ihre personenbezogenen Daten nicht an Dritte weiter, es sei denn, es besteht eine gesetzliche Verpflichtung (Art. 6 Abs. 1 lit. c DSGVO), Sie haben eingewilligt (Art. 6 Abs. 1 lit. a DSGVO) oder die Weitergabe ist zur Vertragserfüllung erforderlich.
Für den Betrieb der Plattform setzen wir folgende Dienstleister ein:
– Hosting: Hetzner Online GmbH, Deutschland. Die Server stehen in einem Rechenzentrum in Finnland (EU).
– E-Mail-Versand: Resend, Inc., USA. Resend erhält Ihre E-Mail-Adresse, Ihren Namen und den Inhalt der jeweiligen E-Mail.
– Zahlungsabwicklung: Mollie B.V., Niederlande.
– Adressprüfung: OpenStreetMap Foundation (Nominatim), Vereinigtes Königreich (siehe Punkt 3 b).
Mit diesen Dienstleistern bestehen, soweit erforderlich, Verträge zur Auftragsverarbeitung gem. Art. 28 DSGVO.

Übermittlung in Drittstaaten: Beim E-Mail-Versand über Resend können Daten in den USA verarbeitet werden. Dies erfolgt auf Grundlage des Angemessenheitsbeschlusses der EU-Kommission zum EU-US Data Privacy Framework bzw. auf Grundlage von EU-Standardvertragsklauseln (Art. 46 Abs. 2 lit. c DSGVO).

5. SPEICHERDAUER
Personenbezogene Daten werden nur so lange gespeichert, wie es für den jeweiligen Zweck erforderlich ist:
– Konto- und Mitgliedsdaten: bis zur Beendigung der Mitgliedschaft bzw. Löschung des Kontos, darüber hinaus nur so lange, wie es zur Geltendmachung oder Abwehr von Ansprüchen innerhalb der gesetzlichen Verjährungsfristen erforderlich ist (in der Regel 3 Jahre)
– Ausleih-, Zahlungs- und Transaktionsdaten sowie Kündigungsunterlagen (einschließlich der für eine Rücküberweisung angegebenen Bankverbindung): 7 Jahre (steuer- und handelsrechtliche Aufbewahrungspflichten gem. § 132 BAO)
– E-Mail-Protokolle: 12 Monate
– Server-Protokolle: 14 Tage
– Inaktive Konten: Wurde ein Konto 3 Jahre lang nicht genutzt, informieren wir Sie per E-Mail. Melden Sie sich nicht innerhalb von 30 Tagen an, werden Name, E-Mail-Adresse, Adresse, Geburtsdatum, Bewertungen, Favoriten und Reservierungen gelöscht. Daten, die einer gesetzlichen Aufbewahrungspflicht unterliegen, werden bis zum Ende dieser Pflicht aufbewahrt. Konten mit offenen Ausleihen, laufender Mitgliedschaft oder Token-Guthaben werden nicht automatisch gelöscht.

6. IHRE RECHTE (ART. 12–23 DSGVO)
Sie haben das Recht auf:

– Auskunft (Art. 15 DSGVO): Welche Daten wir über Sie speichern.
– Berichtigung (Art. 16 DSGVO): Korrektur unrichtiger Daten.
– Löschung (Art. 17 DSGVO): Unter den gesetzlichen Voraussetzungen ("Recht auf Vergessenwerden").
– Einschränkung der Verarbeitung (Art. 18 DSGVO): Bei Bestreiten der Richtigkeit oder Widerspruch.
– Datenübertragbarkeit (Art. 20 DSGVO): Herausgabe Ihrer Daten in einem maschinenlesbaren Format.
– Widerspruch (Art. 21 DSGVO): Gegen Verarbeitungen auf Basis berechtigten Interesses.
– Widerruf der Einwilligung (Art. 7 Abs. 3 DSGVO): Jederzeit mit Wirkung für die Zukunft.

Zur Ausübung dieser Rechte wenden Sie sich bitte per E-Mail an: alle.unsere.abenteuer@gmail.com

Wir beantworten Ihre Anfragen kostenlos innerhalb von einem Monat (Art. 12 Abs. 3 DSGVO).

7. BESCHWERDERECHT BEI DER AUFSICHTSBEHÖRDE
Sie haben gemäß Art. 77 DSGVO das Recht, sich bei der österreichischen Datenschutzbehörde zu beschweren:

Österreichische Datenschutzbehörde
Barichgasse 40–42, 1030 Wien
Telefon: +43 1 52 152-0
E-Mail: dsb@dsb.gv.at
Website: https://www.dsb.gv.at

8. DATENSICHERHEIT
Wir setzen technische und organisatorische Sicherheitsmaßnahmen ein, um Ihre Daten vor zufälliger oder vorsätzlicher Manipulation, Verlust, Zerstörung oder unberechtigtem Zugriff zu schützen. Dazu zählen verschlüsselte Datenübertragung (TLS/HTTPS), die verschlüsselte Speicherung besonders sensibler Daten wie der IBAN, Zugangskontrollen sowie regelmäßige Sicherheitsüberprüfungen. Bei einer Verletzung des Schutzes personenbezogener Daten informieren wir Sie und die Aufsichtsbehörde gem. Art. 33 f. DSGVO.

9. KEINE AUTOMATISIERTE ENTSCHEIDUNGSFINDUNG
Wir setzen keine automatisierte Entscheidungsfindung einschließlich Profiling im Sinne des Art. 22 DSGVO ein, die rechtliche oder ähnlich bedeutsame Auswirkungen für Sie hat.

10. ÄNDERUNGEN DIESER DATENSCHUTZERKLÄRUNG
Wir behalten uns vor, diese Datenschutzerklärung zu aktualisieren, wenn sich rechtliche Vorgaben ändern oder wir neue Verarbeitungsvorgänge einführen. Die aktuelle Version ist stets auf dieser Seite abrufbar. Bei wesentlichen Änderungen werden aktive Mitglieder per E-Mail informiert.

Zuletzt aktualisiert: November 2026
TXT,
                // +4s so this wins over 1.2 even when all versions are seeded in one run
                'published_at' => now()->addSeconds(4),
            ]
        );
    }

    public function down(): void
    {
        PrivacyVersion::where('version', '1.3')->delete();
    }
};
