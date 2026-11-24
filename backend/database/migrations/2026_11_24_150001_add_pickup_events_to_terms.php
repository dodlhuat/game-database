<?php

use App\Models\TermsVersion;
use Illuminate\Database\Migrations\Migration;

/**
 * Terms 1.3 gain the personal pickup rules: where such a game may come back
 * and what happens when it is not collected. Not live yet, so amended in
 * place; the seed migration carries the same text.
 */
return new class extends Migration
{
    private const ANCHOR = 'Erst mit dieser Bestätigung ist ersichtlich, dass das Spiel zur Abholung und Prüfung bereitliegt; bis dahin gilt die Ausleihe als offen.';

    private const PICKUP_RULE = 'Bei persönlicher Abholung über einen Abholtermin kann das Spiel auch bei einem späteren Abholtermin am selben Ort oder an einem regulären Abholort zurückgegeben werden. Wird ein für einen Abholtermin gebuchtes Spiel nicht abgeholt, wird die Ausleihe storniert; Leihgebühr und Kaution werden erstattet.';

    public function up(): void
    {
        $this->amend(fn (string $content): string => str_contains($content, self::PICKUP_RULE)
            ? $content
            : str_replace(self::ANCHOR, self::ANCHOR."\n".self::PICKUP_RULE, $content));
    }

    public function down(): void
    {
        $this->amend(fn (string $content): string => str_replace("\n".self::PICKUP_RULE, '', $content));
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
