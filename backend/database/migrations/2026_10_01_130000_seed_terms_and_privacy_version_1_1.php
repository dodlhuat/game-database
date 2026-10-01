<?php

use App\Models\PrivacyVersion;
use App\Models\TermsVersion;
use Illuminate\Database\Migrations\Migration;

/**
 * Terms 1.1 and privacy policy 1.1: membership fee, token wallet with bonus
 * tokens, cancellation with refund, Stripe and bank details. Version 1.0 stays
 * in place as history, the newest published version is the one that applies.
 */
return new class extends Migration
{
    public function up(): void
    {
        TermsVersion::updateOrCreate(
            ['version' => '1.1'],
            [
                'content' => <<<'TXT'
§1 Geltungsbereich
Diese Nutzungsbedingungen gelten für alle Mitglieder der Spielothek.

§2 Ausleihe
Spiele können für 14 Tage ausgeliehen werden. Eine Verlängerung ist auf Anfrage möglich. Für jede Ausleihe werden Token verbraucht; zusätzlich wird je nach Spiel und Zustand eine Kaution in Token blockiert, die nach der Rückgabeprüfung wieder freigegeben wird.

§3 Haftung
Bei Beschädigung oder Verlust ist der Zeitwert des Spiels zu ersetzen. Die blockierte Kaution kann bei Beschädigung oder Verlust einbehalten werden.

§4 Mitgliedschaft und Mitgliedsbeitrag
Die Mitgliedschaft als Vollmitglied oder als außerordentliches Mitglied kostet derzeit 24,00 € pro Jahr. Der Beitrag wird online per Zahlungsdienstleister bezahlt; die Mitgliedschaft wird erst nach erfolgreicher Zahlung aktiviert und gilt für 12 Monate. Eine Verlängerung ist ab 3 Monate vor Ablauf möglich. Außerordentliche Mitglieder erhalten keine Token und können keine Spiele ausleihen; sie können jederzeit zum Vollmitglied werden.

§5 Token
Vollmitglieder erhalten mit dem Mitgliedsbeitrag 20 Token im Wert von 10,00 € (1 Token = 0,50 €). Diese Token verfallen nicht. Weitere Token können in Paketen gekauft werden (derzeit 20 Token für 10,00 €, 30 Token für 14,50 €, 40 Token für 18,50 €). Token sind nicht übertragbar.

§6 Bonus-Token
Zusätzlich zu den Token nach §5 erhalten Vollmitglieder mit jeder Zahlung des Mitgliedsbeitrags (Beitritt und Verlängerung) Bonus-Token im Wert von 10,00 € (derzeit 20 Token) geschenkt. Bonus-Token verfallen 12 Monate nach der Gutschrift, werden bei der Ausleihe immer zuerst verbraucht, sind nicht übertragbar und werden nicht erstattet. Das Ablaufdatum ist im Konto ersichtlich.

§7 Kündigung und Rückerstattung von Token
Die Mitgliedschaft kann jederzeit im Konto gekündigt werden, sofern keine Spiele oder Pakete ausgeliehen sind und keine Kaution blockiert ist. Die Kündigung wird sofort wirksam. Der bezahlte Mitgliedsbeitrag wird nicht erstattet.
Verbleibende Token werden zu dem Preis erstattet, zu dem sie erworben wurden. Gekaufte Token werden erst 30 Tage nach dem Kauf erstattet; geschenkte Token, Bonus-Token und Token ohne Zahlung werden nicht erstattet. Von der Erstattung wird eine Bearbeitungsgebühr von 2,00 € abgezogen (höchstens der Erstattungsbetrag). Alle nicht erstatteten Token und alle Bonus-Token verfallen mit der Kündigung.
Vor der Kündigung wird angezeigt, wie viele Token und welcher Betrag erstattet werden. Für die Rücküberweisung sind Kontoinhaber und IBAN anzugeben; die Überweisung wird manuell veranlasst.

§8 Datenschutz
Wir verarbeiten Ihre Daten gemäß unserer Datenschutzerklärung.
TXT,
                // +1s so the newer version wins even if 1.0 was seeded in the same run
                'published_at' => now()->addSecond(),
            ]
        );

        PrivacyVersion::updateOrCreate(
            ['version' => '1.1'],
            [
                'content' => <<<'TXT'
DATENSCHUTZERKLÄRUNG
(gem. DSGVO & DSG 2018, Stand: Oktober 2026)

1. VERANTWORTLICHER
Verantwortlicher im Sinne der Datenschutz-Grundverordnung (DSGVO) und des österreichischen Datenschutzgesetzes (DSG 2018) ist:

[Vereinsname / Organisation]
[Straße und Hausnummer]
[PLZ Ort], Österreich
E-Mail: [kontakt@beispiel.at]
Website: [https://www.beispiel.at]

2. GRUNDSÄTZE DER DATENVERARBEITUNG
Wir verarbeiten personenbezogene Daten ausschließlich nach den Grundsätzen der DSGVO: Rechtmäßigkeit, Verarbeitung nach Treu und Glauben, Transparenz, Zweckbindung, Datenminimierung, Richtigkeit, Speicherbegrenzung sowie Integrität und Vertraulichkeit (Art. 5 DSGVO).

3. VERARBEITETE DATEN UND ZWECKE

a) Mitgliedschaftsdaten
Beim Anlegen eines Kontos erheben wir: Vorname, Nachname, E-Mail-Adresse, Telefonnummer (optional), Geburtsdatum, Adresse. Diese Daten sind zur Begründung und Verwaltung der Mitgliedschaft in unserer Spielothek erforderlich.
Rechtsgrundlage: Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung)

b) Ausleih- und Reservierungsdaten
Für die Verwaltung von Spieleausleihen und Reservierungen speichern wir, welches Mitglied welches Spiel zu welchem Zeitpunkt ausgeliehen oder reserviert hat. Dazu gehören Ausleih- und Rückgabedaten sowie etwaige Verlängerungen.
Rechtsgrundlage: Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung)

c) Bewertungen und Schadensmeldungen
Freiwillig abgegebene Spielebewertungen sowie Schadensmeldungen werden mit dem Mitgliedskonto verknüpft gespeichert, um den Bestand der Spielothek zu verwalten.
Rechtsgrundlage: Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse an Bestandserhaltung)

d) Veranstaltungsdaten
Bei Teilnahme an Veranstaltungen verarbeiten wir Name und Kontaktdaten zur Organisation und Kommunikation.
Rechtsgrundlage: Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung)

e) Mitgliedsbeitrag, Zahlungs- und Token-Transaktionen
Für die Verwaltung von Mitgliedsbeiträgen und Token-Guthaben speichern wir Transaktionsdaten (Betrag, Zeitpunkt, Art der Transaktion, Status der Zahlung). Das Token-Guthaben wird getrennt nach gekauften bzw. mit der Mitgliedschaft erworbenen Token und geschenkten Bonus-Token geführt; für Bonus-Token speichern wir zusätzlich das Ablaufdatum.
Online-Zahlungen (Mitgliedsbeitrag, Token-Pakete) werden über den Zahlungsdienstleister Stripe (Stripe Payments Europe, Ltd., Irland) abgewickelt. Zahlungsdaten wie Kartennummern werden direkt an Stripe übermittelt und von uns nicht gespeichert; wir erhalten lediglich eine Bestätigung über die erfolgte Zahlung samt Referenz.
Rechtsgrundlage: Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung), Art. 6 Abs. 1 lit. c DSGVO (rechtliche Verpflichtung)

f) Kündigung der Mitgliedschaft und Rückerstattung
Bei einer Kündigung speichern wir den Zeitpunkt der Kündigung, den Token-Stand sowie den zurückzuerstattenden Betrag. Wenn eine Rückerstattung anfällt, erheben wir zusätzlich Kontoinhaber und IBAN, um die Rücküberweisung vornehmen zu können. Die IBAN wird in unserer Datenbank verschlüsselt gespeichert und ist nur für Administratoren zur Durchführung der Überweisung einsehbar. Die Angabe eines Kündigungsgrundes ist freiwillig.
Rechtsgrundlage: Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung), Art. 6 Abs. 1 lit. c DSGVO (rechtliche Verpflichtung)

g) E-Mail-Kommunikation
Wir versenden E-Mails zu: Kontoaktivierung, Passwort-Reset, Ausleihbestätigungen, Fälligkeitserinnerungen sowie Bestätigungen zu Zahlungen und Kündigungen sowie Informationen zu Veranstaltungen und Neuigkeiten der Spielothek. Bei einer Kündigung wird zusätzlich eine Benachrichtigung an die Administratoren der Spielothek gesendet, damit die Rücküberweisung veranlasst werden kann.
Rechtsgrundlage: Art. 6 Abs. 1 lit. b DSGVO (Vertragserfüllung) bzw. Art. 6 Abs. 1 lit. f DSGVO (berechtigtes Interesse)

4. WEITERGABE AN DRITTE
Wir geben Ihre personenbezogenen Daten grundsätzlich nicht an Dritte weiter, es sei denn:
– es besteht eine gesetzliche Verpflichtung zur Weitergabe (Art. 6 Abs. 1 lit. c DSGVO),
– Sie haben ausdrücklich eingewilligt (Art. 6 Abs. 1 lit. a DSGVO),
– die Weitergabe ist zur Vertragserfüllung erforderlich (z. B. an den Zahlungsdienstleister Stripe zur Abwicklung von Online-Zahlungen).

Eine Übermittlung personenbezogener Daten in Drittstaaten außerhalb des EWR erfolgt grundsätzlich nicht. Der Zahlungsdienstleister Stripe kann Daten im Rahmen der Zahlungsabwicklung auch in Drittstaaten verarbeiten; dies geschieht auf Grundlage von EU-Standardvertragsklauseln bzw. eines Angemessenheitsbeschlusses nach den Datenschutzbestimmungen von Stripe.

5. SPEICHERDAUER
Personenbezogene Daten werden nur so lange gespeichert, wie es für den jeweiligen Zweck erforderlich ist:
– Mitgliedsdaten: bis zur Beendigung der Mitgliedschaft, danach bis zu 3 Jahren (Gewährleistungsansprüche)
– Ausleih-, Zahlungs- und Transaktionsdaten sowie Kündigungsunterlagen (einschließlich der für eine Rücküberweisung angegebenen Bankverbindung): 7 Jahre (steuer- und handelsrechtliche Aufbewahrungspflichten gem. § 132 BAO)
– E-Mail-Logs: 12 Monate
– Inaktive Konten (kein Login seit 3 Jahren): werden nach Benachrichtigung gelöscht

6. IHRE RECHTE (ART. 12–23 DSGVO)
Sie haben das Recht auf:

– Auskunft (Art. 15 DSGVO): Welche Daten wir über Sie speichern.
– Berichtigung (Art. 16 DSGVO): Korrektur unrichtiger Daten.
– Löschung (Art. 17 DSGVO): Unter den gesetzlichen Voraussetzungen ("Recht auf Vergessenwerden").
– Einschränkung der Verarbeitung (Art. 18 DSGVO): Bei Bestreiten der Richtigkeit oder Widerspruch.
– Datenübertragbarkeit (Art. 20 DSGVO): Herausgabe Ihrer Daten in einem maschinenlesbaren Format.
– Widerspruch (Art. 21 DSGVO): Gegen Verarbeitungen auf Basis berechtigten Interesses.
– Widerruf der Einwilligung (Art. 7 Abs. 3 DSGVO): Jederzeit mit Wirkung für die Zukunft.

Zur Ausübung dieser Rechte wenden Sie sich bitte per E-Mail an: [kontakt@beispiel.at]

Wir beantworten Ihre Anfragen kostenlos innerhalb von einem Monat (Art. 12 Abs. 3 DSGVO).

7. BESCHWERDERECHT BEI DER AUFSICHTSBEHÖRDE
Sie haben gemäß Art. 77 DSGVO das Recht, sich bei der österreichischen Datenschutzbehörde zu beschweren:

Österreichische Datenschutzbehörde
Barichgasse 40–42, 1030 Wien
Telefon: +43 1 52 152-0
E-Mail: dsb@dsb.gv.at
Website: https://www.dsb.gv.at

8. DATENSICHERHEIT
Wir setzen technische und organisatorische Sicherheitsmaßnahmen ein, um Ihre Daten vor zufälliger oder vorsätzlicher Manipulation, Verlust, Zerstörung oder unberechtigtem Zugriff zu schützen. Dazu zählen verschlüsselte Datenübertragung (TLS/HTTPS), Zugangskontrollen sowie regelmäßige Sicherheitsüberprüfungen. Bei einer Verletzung des Schutzes personenbezogener Daten informieren wir Sie und die Aufsichtsbehörde gem. Art. 33 f. DSGVO.

9. KEINE AUTOMATISIERTE ENTSCHEIDUNGSFINDUNG
Wir setzen keine automatisierte Entscheidungsfindung einschließlich Profiling im Sinne des Art. 22 DSGVO ein, die rechtliche oder ähnlich bedeutsame Auswirkungen für Sie hat.

10. ÄNDERUNGEN DIESER DATENSCHUTZERKLÄRUNG
Wir behalten uns vor, diese Datenschutzerklärung zu aktualisieren, wenn sich rechtliche Vorgaben ändern oder wir neue Verarbeitungsvorgänge einführen. Die aktuelle Version ist stets auf dieser Seite abrufbar. Bei wesentlichen Änderungen werden aktive Mitglieder per E-Mail informiert.

Zuletzt aktualisiert: Oktober 2026
TXT,
                // +1s so the newer version wins even if 1.0 was seeded in the same run
                'published_at' => now()->addSecond(),
            ]
        );
    }

    public function down(): void
    {
        TermsVersion::where('version', '1.1')->delete();
        PrivacyVersion::where('version', '1.1')->delete();
    }
};
