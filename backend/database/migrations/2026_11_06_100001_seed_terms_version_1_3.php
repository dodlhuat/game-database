<?php

use App\Models\TermsVersion;
use Illuminate\Database\Migrations\Migration;

/**
 * Terms 1.3: new pricing model. Membership only includes bonus tokens, the loan
 * price depends on the game's category, the first extension is free, reserved
 * games cannot be extended and reserving costs a non-refundable fee. Older
 * versions stay in place as history.
 */
return new class extends Migration
{
    public function up(): void
    {
        TermsVersion::updateOrCreate(
            ['version' => '1.3'],
            [
                'content' => <<<'TXT'
§1 Geltungsbereich
Diese Nutzungsbedingungen gelten für alle Mitglieder der Spielothek des Vereins AUA-Alle.Unsere.Abenteuer.

§2 Ausleihe
Spiele können für 14 Tage ausgeliehen werden. Der Leihpreis richtet sich nach der Kategorie des Spiels (derzeit klein 3 Token, mittel 6 Token, groß 10 Token; Pakete 3 Token). Zusätzlich wird je nach Spiel und Zustand eine Kaution in Token blockiert, die nach der Rückgabeprüfung wieder freigegeben wird.
Eine Ausleihe kann im Konto bis zu zweimal verlängert werden, jeweils um höchstens 14 Tage, sofern das Spiel nicht von einem anderen Mitglied reserviert ist. Die erste Verlängerung ist kostenlos, die zweite kostet derzeit 2 Token. Danach ist eine weitere Verlängerung erst nach Rückgabe und erneuter Ausleihe möglich.
Ausgeliehene Spiele können reserviert werden. Eine Reservierung kostet derzeit 1 Token; die Gebühr wird beim Reservieren abgebucht und bei Stornierung der Reservierung nicht erstattet.

§3 Haftung
Bei Beschädigung oder Verlust ist der Zeitwert des Spiels zu ersetzen. Die blockierte Kaution kann bei Beschädigung oder Verlust einbehalten werden.

§4 Mitgliedschaft und Mitgliedsbeitrag
Die Mitgliedschaft im Verein AUA-Alle.Unsere.Abenteuer richtet sich nach den Vereinsstatuten; dort sind insbesondere die Mitgliedsarten (ordentliche, außerordentliche und Ehrenmitglieder) sowie die Rechte und Pflichten der Mitglieder geregelt. Ehrenmitglieder werden vom Vorstand ernannt, eine Ernennung ist über diese Plattform nicht möglich. Über diese Plattform werden Sie als Vollmitglied oder als außerordentliches Mitglied geführt.
Die Höhe des Mitgliedsbeitrags beschließt die Generalversammlung; derzeit beträgt er 24,00 € pro Jahr. Der Beitrag wird online per Zahlungsdienstleister bezahlt; die Mitgliedschaft wird erst nach erfolgreicher Zahlung aktiviert und gilt für 12 Monate. Eine Verlängerung ist ab 3 Monate vor Ablauf möglich. Außerordentliche Mitglieder erhalten keine Token und können keine Spiele ausleihen; sie können jederzeit zum Vollmitglied werden.

§5 Token
Ein Token hat einen Wert von 0,50 €. Token können in Paketen gekauft werden (derzeit 20 Token für 10,00 €, 30 Token für 14,50 €, 40 Token für 18,50 €). Gekaufte Token verfallen nicht und sind nicht übertragbar.

§6 Bonus-Token
Vollmitglieder erhalten mit jeder Zahlung des Mitgliedsbeitrags (Beitritt und Verlängerung) Bonus-Token im Wert von 10,00 € (derzeit 20 Token) geschenkt. Bonus-Token verfallen 12 Monate nach der Gutschrift, werden bei der Ausleihe immer zuerst verbraucht, sind nicht übertragbar und werden nicht erstattet. Das Ablaufdatum ist im Konto ersichtlich.

§7 Kündigung und Rückerstattung von Token
Die Mitgliedschaft kann jederzeit im Konto gekündigt werden, sofern keine Spiele oder Pakete ausgeliehen sind und keine Kaution blockiert ist. Die Kündigung wird sofort wirksam. Der bezahlte Mitgliedsbeitrag wird nicht erstattet.
Verbleibende Token werden zu dem Preis erstattet, zu dem sie erworben wurden. Gekaufte Token werden erst 30 Tage nach dem Kauf erstattet; geschenkte Token, Bonus-Token und Token ohne Zahlung werden nicht erstattet. Von der Erstattung wird eine Bearbeitungsgebühr von 2,00 € abgezogen (höchstens der Erstattungsbetrag). Alle nicht erstatteten Token und alle Bonus-Token verfallen mit der Kündigung.
Vor der Kündigung wird angezeigt, wie viele Token und welcher Betrag erstattet werden. Für die Rücküberweisung sind Kontoinhaber und IBAN anzugeben; die Überweisung wird manuell veranlasst.

§8 Datenschutz
Wir verarbeiten Ihre Daten gemäß unserer Datenschutzerklärung.
TXT,
                'published_at' => now()->addSeconds(3),
            ]
        );
    }

    public function down(): void
    {
        TermsVersion::where('version', '1.3')->delete();
    }
};
