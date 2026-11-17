<?php

namespace Database\Seeders;

use App\Models\Mechanic;
use App\Models\Tag;
use App\Support\TaxonomyList;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Loads mechanics and tags from database/data/{mechanics,tags}.txt.
 *
 * Safe to run again: missing entries are added, existing ones (matched by
 * slug) keep their id and game assignments — only the spelling of the name is
 * updated. Nothing is deleted; entries not in the list are just reported.
 *
 *   make seed-taxonomy           (local)
 *   make deploy-seed-taxonomy    (server)
 */
class TaxonomySeeder extends Seeder
{
    /** Folder holding mechanics.txt and tags.txt; database/data by default. */
    public function __construct(private ?string $dir = null) {}

    public function run(): void
    {
        // Read and validate both lists first, so a broken file changes nothing.
        $mechanics = TaxonomyList::read(TaxonomyList::path('mechanics', $this->dir));
        $tags = TaxonomyList::read(TaxonomyList::path('tags', $this->dir));

        DB::transaction(function () use ($mechanics, $tags) {
            $this->sync(Mechanic::class, 'Mechaniken', $mechanics);
            $this->sync(Tag::class, 'Tags', $tags);
        });
    }

    /**
     * @param  class-string<Mechanic|Tag>  $model
     * @param  list<string>  $names
     */
    private function sync(string $model, string $label, array $names): void
    {
        $created = $renamed = 0;
        $listed = [];

        foreach ($names as $name) {
            $slug = Str::slug($name);
            $listed[] = $slug;

            /** @var Model|null $existing */
            $existing = $model::where('slug', $slug)->first();
            if (! $existing) {
                $model::create(['name' => $name, 'slug' => $slug]);
                $created++;
            } elseif ($existing->getAttribute('name') !== $name) {
                $existing->update(['name' => $name]);
                $renamed++;
            }
        }

        $extra = $model::whereNotIn('slug', $listed)->orderBy('name')->pluck('name');

        $this->command?->info(sprintf(
            '%s: %d in der Liste, %d neu angelegt, %d umbenannt.',
            $label,
            count($names),
            $created,
            $renamed,
        ));
        if ($extra->isNotEmpty()) {
            $this->command?->warn("  Nicht in der Liste (bleiben bestehen): {$extra->implode(', ')}");
        }
    }
}
