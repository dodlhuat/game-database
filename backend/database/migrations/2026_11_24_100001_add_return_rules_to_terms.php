<?php

use App\Models\TermsVersion;
use Illuminate\Database\Migrations\Migration;

/**
 * Terms 1.3 gain the return rules (back to the pickup location, confirmed in
 * the account) and renewal after expiry. The platform is not live yet, so 1.3
 * is amended in place; the seed migration carries the same text.
 */
return new class extends Migration
{
    private const RESERVATION = 'Ausgeliehene Spiele können reserviert werden. Eine Reservierung kostet derzeit 1 Token; die Gebühr wird beim Reservieren abgebucht und bei Stornierung der Reservierung nicht erstattet.';

    private const RETURN_RULE = 'Ausgeliehene Spiele sind an dem Abholort zurückzugeben, an dem sie abgeholt wurden; Pakete werden vollständig gemeinsam zurückgegeben. Die Rückgabe ist anschließend im Konto zu bestätigen. Erst mit dieser Bestätigung ist ersichtlich, dass das Spiel zur Abholung und Prüfung bereitliegt; bis dahin gilt die Ausleihe als offen.';

    private const RENEWAL_OLD = 'Eine Verlängerung ist ab 3 Monate vor Ablauf möglich.';

    private const RENEWAL_NEW = 'Eine Verlängerung ist ab 3 Monate vor Ablauf und auch nach Ablauf möglich. Ist die Mitgliedschaft abgelaufen, können bis zur Verlängerung keine Spiele ausgeliehen oder reserviert und keine Ausleihen verlängert werden.';

    public function up(): void
    {
        $this->amend(function (string $content): string {
            // A fresh install already has the text from the seed migration
            if (! str_contains($content, self::RETURN_RULE)) {
                $content = str_replace(self::RESERVATION, self::RESERVATION."\n".self::RETURN_RULE, $content);
            }

            return str_replace(self::RENEWAL_OLD, self::RENEWAL_NEW, $content);
        });
    }

    public function down(): void
    {
        $this->amend(fn (string $content): string => str_replace(
            ["\n".self::RETURN_RULE, self::RENEWAL_NEW],
            ['', self::RENEWAL_OLD],
            $content
        ));
    }

    /** @param  callable(string): string  $change */
    private function amend(callable $change): void
    {
        $terms = TermsVersion::where('version', '1.3')->first();
        if ($terms === null) {
            return;
        }

        $terms->update(['content' => $change($terms->content)]);
    }
};
