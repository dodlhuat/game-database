<?php

use App\Models\CookieVersion;
use Illuminate\Database\Migrations\Migration;

/**
 * Cookie policy 1.0 rewritten to match what the site really stores: no
 * cookies at all (the i18n_redirected cookie is switched off), only
 * localStorage/sessionStorage entries, Mollie's checkout as the one third
 * party, real contact details. Not live yet, so 1.0 is amended in place;
 * the seed migration carries the same text for fresh installs.
 */
return new class extends Migration
{
    private const PARAGRAPHS = [
        "COOKIE-RICHTLINIE\n(gem. ePrivacy-Richtlinie 2002/58/EG, DSGVO & TKG 2021, Stand: November 2026)",
        "1. KURZ GESAGT\nUnsere Website setzt keine Cookies. Für die Anmeldung und einige Einstellungen nutzen wir ausschließlich den lokalen Speicher Ihres Browsers (localStorage bzw. sessionStorage), und zwar nur für technisch notwendige Zwecke. Wir verwenden keine Analyse-, Tracking- oder Werbetechnologien.",
        "2. WAS SIND COOKIES UND LOKALER SPEICHER?\nCookies sind kleine Textdateien, die eine Website auf Ihrem Endgerät (PC, Smartphone, Tablet) ablegt und die bei jedem Seitenaufruf automatisch an den Server zurückgeschickt werden. Der lokale Speicher (localStorage und sessionStorage) ist ein ähnlicher Speicherbereich in Ihrem Browser, dessen Inhalt jedoch nicht automatisch übertragen wird; unsere Anwendung liest ihn nur aus, wenn sie ihn für eine Funktion braucht. Rechtlich behandeln wir beides gleich.",
        "3. WAS WIR IN IHREM BROWSER SPEICHERN\n\na) Im localStorage (bleibt erhalten, bis es gelöscht wird)\n\nName: auth_token\nZweck: Anmeldung – Ihr Anmeldetoken, das bei Anfragen an unseren Server mitgeschickt wird, damit Sie eingeloggt bleiben\nSpeicherdauer: bis zum Logout; das Token wird außerdem ungültig, wenn es 30 Tage lang nicht verwendet wurde\nAnbieter: AUA-Alle.Unsere.Abenteuer (eigener Server)\n\nName: auth_user\nZweck: Anmeldung – eine Kopie Ihrer Profildaten (z. B. Name, E-Mail-Adresse, Anschrift, Mitgliedsstatus und Token-Guthaben), damit die Anwendung sie ohne erneute Abfrage anzeigen kann\nSpeicherdauer: bis zum Logout\nAnbieter: AUA-Alle.Unsere.Abenteuer (eigener Server)\n\nName: theme\nZweck: Darstellung – der von Ihnen gewählte Hell- oder Dunkelmodus\nSpeicherdauer: bis Sie die Website-Daten in Ihrem Browser löschen\nAnbieter: lokal im Browser\n\nName: last_pickup_location\nZweck: Komfort – der zuletzt gewählte Abholort, damit er beim nächsten Ausleihen vorausgewählt ist\nSpeicherdauer: bis Sie die Website-Daten in Ihrem Browser löschen\nAnbieter: lokal im Browser\n\nb) Im sessionStorage (wird beim Schließen des Tabs gelöscht)\n\nName: payment_return_to\nZweck: Zahlung – die Seite, von der aus Sie eine Zahlung begonnen haben, damit wir Sie nach der Bezahlung dorthin zurückführen können\nSpeicherdauer: bis zur Rückkehr von der Bezahlseite, spätestens bis zum Schließen des Tabs\nAnbieter: lokal im Browser\n\nBeim Logout werden auth_token und auth_user sofort gelöscht.",
        "4. RECHTSGRUNDLAGE\nAlle oben genannten Einträge sind unbedingt erforderlich, um den von Ihnen ausdrücklich gewünschten Dienst bereitzustellen (Anmeldung, Ausleihe, Bezahlung, gewählte Darstellung). Sie werden daher ohne Einwilligung gem. § 165 Abs. 3 TKG 2021 gespeichert; die damit verbundene Verarbeitung personenbezogener Daten erfolgt zur Erfüllung Ihrer Mitgliedschaft (Art. 6 Abs. 1 lit. b DSGVO) bzw. auf Grundlage unseres berechtigten Interesses an einer funktionsfähigen Website (Art. 6 Abs. 1 lit. f DSGVO).\n\nSollten wir künftig optionale Technologien (z. B. Analyse-Werkzeuge) einsetzen, holen wir vorher Ihre ausdrückliche Einwilligung gem. Art. 6 Abs. 1 lit. a DSGVO und § 165 Abs. 1 TKG 2021 ein.",
        "5. DRITTANBIETER\nAuf unserer Website werden keine Cookies, Tracking-Pixel oder Inhalte von sozialen Netzwerken, Werbenetzwerken oder Analyse-Diensten eingebunden. Schriftarten und Bilder liefern wir grundsätzlich von unserem eigenen Server aus.\n\nZum Bezahlen (Mitgliedsbeitrag, Token-Pakete) werden Sie auf eine Bezahlseite unseres Zahlungsdienstleisters Mollie B.V., Niederlande, weitergeleitet. Auf dieser Seite können Mollie und die von Ihnen gewählte Zahlungsart eigene Cookies setzen; dafür gelten die Datenschutz- und Cookie-Bestimmungen von Mollie. Nach der Bezahlung kehren Sie auf unsere Website zurück.",
        "6. EINTRÄGE VERWALTEN UND LÖSCHEN\nSie können die gespeicherten Einträge jederzeit über die Einstellungen Ihres Browsers löschen, meist unter „Cookies und Website-Daten“:\n\n– Chrome: Einstellungen → Datenschutz und Sicherheit → Drittanbieter-Cookies bzw. Website-Daten\n– Firefox: Einstellungen → Datenschutz & Sicherheit → Cookies und Website-Daten\n– Safari: Einstellungen → Datenschutz → Websitedaten verwalten\n– Edge: Einstellungen → Cookies und Websiteberechtigungen\n\nBitte beachten Sie: Wenn Sie die Website-Daten löschen, werden Sie abgemeldet, und Ihre Darstellungs- und Abholort-Auswahl wird zurückgesetzt.",
        "7. IHRE RECHTE\nSie haben jederzeit das Recht auf Auskunft über die verarbeiteten personenbezogenen Daten (Art. 15 DSGVO), auf deren Berichtigung (Art. 16 DSGVO) und Löschung (Art. 17 DSGVO) sowie auf Widerspruch gegen die Verarbeitung (Art. 21 DSGVO). Weitere Informationen finden Sie in unserer Datenschutzerklärung.\n\nVerantwortlich: AUA-Alle.Unsere.Abenteuer\nFür Anfragen wenden Sie sich an: alle.unsere.abenteuer@gmail.com\n\nBeschwerden können Sie bei der österreichischen Datenschutzbehörde einbringen:\nÖsterreichische Datenschutzbehörde, Barichgasse 40–42, 1030 Wien\nE-Mail: dsb@dsb.gv.at | Web: https://www.dsb.gv.at",
        "8. ÄNDERUNGEN DIESER COOKIE-RICHTLINIE\nWir passen diese Richtlinie an, wenn wir neue Technologien einsetzen oder sich die gesetzlichen Anforderungen ändern. Die jeweils aktuelle Fassung ist auf dieser Seite abrufbar. Bei wesentlichen Änderungen, die eine Einwilligung erfordern, informieren wir aktive Mitglieder.",
        'Zuletzt aktualisiert: November 2026',
    ];

    public function up(): void
    {
        CookieVersion::where('version', '1.0')->update(['content' => implode("\n\n", self::PARAGRAPHS)]);
    }

    public function down(): void
    {
        // The previous text was outdated and contained placeholders; there is
        // nothing worth restoring.
    }
};
