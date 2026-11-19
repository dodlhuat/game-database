<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Sleep;

/**
 * Best-effort "does this address exist" check via Nominatim (OpenStreetMap).
 *
 * - Fails open. A membership upgrade must never be blocked because a third
 *   party geocoder is slow, down, or rate-limits us. Errors are reported as
 *   STATUS_UNAVAILABLE ("could not check"), never as "not found" and never
 *   as an exception bubbling up to the caller.
 * - Only checks the building. Austrian addresses usually carry stair and door
 *   ("Hauptstraße 12/2/7", "12 Stiege 2 Top 7"), which OSM doesn't map and
 *   which make Nominatim's structured search miss. They are stripped for the
 *   lookup only; what the member typed is stored unchanged.
 * - If street + PLZ + city finds nothing, retries once without the city, so a
 *   district or village name ("Spratzern" for St. Pölten) still matches.
 * - Definitive answers are cached for 30 days per normalized address (keeps us
 *   well under Nominatim's ~1 request/second usage policy). Failures are not
 *   cached, so a temporary outage doesn't stick to an address for a month.
 */
class AddressValidationService
{
    public const STATUS_VERIFIED = 'verified';

    public const STATUS_NOT_FOUND = 'not_found';

    public const STATUS_UNAVAILABLE = 'unavailable';

    /** @return self::STATUS_* */
    public function check(string $street, string $postalCode, string $city): string
    {
        $street = self::buildingOnly($street);
        $city = trim($city);
        $key = 'address-validation:v2:'.md5(mb_strtolower("{$street}|{$postalCode}|{$city}"));

        /** @var self::STATUS_VERIFIED|self::STATUS_NOT_FOUND|null $cached */
        $cached = Cache::get($key);
        if ($cached !== null) {
            return $cached;
        }

        $found = $this->lookup(['street' => $street, 'postalcode' => $postalCode, 'city' => $city]);
        if ($found === false) {
            // Nominatim's public instance allows ~1 request per second.
            Sleep::for(1)->second();
            $found = $this->lookup(['street' => $street, 'postalcode' => $postalCode]);
        }

        if ($found === null) {
            return self::STATUS_UNAVAILABLE;
        }

        $status = $found ? self::STATUS_VERIFIED : self::STATUS_NOT_FOUND;
        Cache::put($key, $status, now()->addDays(30));

        return $status;
    }

    /**
     * Street name and house number only: drops stair, door, floor and flat
     * parts ("12/2/7", "12 Stiege 2 Top 7", "12, Tür 5", "12 2. Stock") that Nominatim
     * can't resolve. Ranges and letter suffixes ("12-14", "12a") are kept.
     */
    public static function buildingOnly(string $street): string
    {
        $street = (string) preg_replace(
            '/[\s,]+(?:\d+\.\s*)?(stiege|stg\.?|top|tür|tuer|türe|stock|og|etage|whg\.?|wohnung)(?=[\s\d.]|$).*$/iu',
            '',
            $street,
        );
        $street = (string) preg_replace('/\s*\/.*$/u', '', $street);

        return trim($street, " \t\n\r\0\x0B,");
    }

    /**
     * @param  array<string, string>  $query
     * @return bool|null true = found, false = no match, null = lookup failed
     */
    private function lookup(array $query): ?bool
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => config()->string('services.nominatim.user_agent'),
            ])
                ->timeout(4)
                ->retry(1, 100, throw: false)
                ->get(config()->string('services.nominatim.base_url').'/search', $query + [
                    'country' => 'Austria',
                    'format' => 'json',
                    'limit' => 1,
                ]);

            if (! $response->successful()) {
                Log::info('Address validation: non-2xx response from Nominatim', [
                    'status' => $response->status(),
                ]);

                return null;
            }

            $results = $response->json();

            return is_array($results) && count($results) > 0;
        } catch (\Throwable $e) {
            Log::info('Address validation: lookup failed, treating as unverified', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
