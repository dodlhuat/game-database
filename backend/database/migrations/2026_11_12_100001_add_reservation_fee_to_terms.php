<?php

use App\Models\TermsVersion;
use Illuminate\Database\Migrations\Migration;

/**
 * Terms 1.3 gain the reservation fee and the extension rules in detail. The
 * platform is not live yet, so 1.3 is amended in place instead of publishing
 * 1.4. The seed migration carries the same text for fresh installs.
 */
return new class extends Migration
{
    private const OLD = 'Eine Ausleihe kann im Konto bis zu zweimal verlängert werden, sofern das Spiel nicht von einem anderen Mitglied reserviert ist. Die erste Verlängerung ist kostenlos, die zweite kostet derzeit 2 Token.';

    private const NEW = <<<'TXT'
Eine Ausleihe kann im Konto bis zu zweimal verlängert werden, jeweils um höchstens 14 Tage, sofern das Spiel nicht von einem anderen Mitglied reserviert ist. Die erste Verlängerung ist kostenlos, die zweite kostet derzeit 2 Token. Danach ist eine weitere Verlängerung erst nach Rückgabe und erneuter Ausleihe möglich.
Ausgeliehene Spiele können reserviert werden. Eine Reservierung kostet derzeit 1 Token; die Gebühr wird beim Reservieren abgebucht und bei Stornierung der Reservierung nicht erstattet.
TXT;

    public function up(): void
    {
        $this->swap(self::OLD, self::NEW);
    }

    public function down(): void
    {
        $this->swap(self::NEW, self::OLD);
    }

    private function swap(string $from, string $to): void
    {
        $terms = TermsVersion::where('version', '1.3')->first();
        if ($terms === null || ! str_contains($terms->content, $from)) {
            return;
        }

        $terms->update(['content' => str_replace($from, $to, $terms->content)]);
    }
};
