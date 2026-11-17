<?php

namespace App\Notifications\Concerns;

use App\Models\PickupLocation;

/**
 * Template variables for the place a game is picked up and returned:
 * {location} is the plain name, {location_details} an HTML block with
 * address, opening hours, notes and map link — whatever is filled in.
 */
trait DescribesPickupLocation
{
    /** @return array{location: string, location_details: string} */
    protected function locationVars(?PickupLocation $location): array
    {
        if (! $location) {
            return ['location' => '', 'location_details' => ''];
        }

        $lines = ['<strong>'.e($location->name).'</strong>'];
        if ($location->address) {
            $lines[] = nl2br(e($location->address), false);
        }
        if ($location->opening_hours) {
            $lines[] = 'Öffnungszeiten: '.nl2br(e($location->opening_hours), false);
        }
        if ($location->notes) {
            $lines[] = nl2br(e($location->notes), false);
        }
        if ($location->map_url) {
            $lines[] = '<a href="'.e($location->map_url).'">Auf der Karte ansehen</a>';
        }

        return [
            'location' => e($location->name),
            'location_details' => '<p>'.implode('<br>', $lines).'</p>',
        ];
    }
}
