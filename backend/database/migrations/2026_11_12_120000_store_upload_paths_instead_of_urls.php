<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Uploads used to be stored as absolute URLs built from APP_URL at upload
 * time, so images uploaded while the server ran on http://<ip> broke once the
 * site moved to its HTTPS domain (mixed content). Keep only the path on the
 * public disk; the StorageUrl cast builds the URL when it is read. External
 * URLs (demo data, Cloudinary) have no /storage/ path and stay as they are.
 */
return new class extends Migration
{
    private const COLUMNS = [
        'games' => 'cover_image_url',
        'game_images' => 'url',
        'events' => 'image_url',
        'damage_reports' => 'photo_url',
    ];

    public function up(): void
    {
        $this->rewrite(
            fn (string $value) => preg_match('#^https?://[^/]+/storage/(.+)$#i', $value, $m) === 1 ? $m[1] : $value
        );
    }

    public function down(): void
    {
        $base = rtrim(Storage::disk('public')->url(''), '/').'/';

        $this->rewrite(
            fn (string $value) => preg_match('#^(https?:)?//#i', $value) === 1 ? $value : $base.$value
        );
    }

    /** @param  Closure(string): string  $map */
    private function rewrite(Closure $map): void
    {
        foreach (self::COLUMNS as $table => $column) {
            DB::table($table)
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->select(['id', $column])
                ->orderBy('id')
                ->each(function (object $row) use ($table, $column, $map) {
                    $value = $row->{$column};
                    if (! is_string($value)) {
                        return;
                    }

                    $mapped = $map($value);
                    if ($mapped !== $value) {
                        DB::table($table)->where('id', $row->id)->update([$column => $mapped]);
                    }
                });
        }
    }
};
