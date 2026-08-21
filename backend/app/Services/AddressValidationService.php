<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Best-effort "does this address exist" check via Nominatim (OpenStreetMap).
 * This is the first external HTTP integration in the app, so a few things
 * are deliberate:
 *
 * - Fails open. A membership upgrade must never be blocked because a third
 *   party geocoder is slow, down, or rate-limits us — any error, timeout, or
 *   unexpected response is swallowed and treated as "could not verify",
 *   never as "invalid" and never as an exception bubbling up to the caller.
 * - Cached for 30 days per normalized address — addresses don't change, and
 *   this keeps repeat lookups (e.g. re-editing the same address) well under
 *   Nominatim's public-instance usage policy (max ~1 request/second, no bulk
 *   use).
 */
class AddressValidationService
{
    public function exists(string $street, string $postalCode, string $city): bool
    {
        $key = 'address-validation:'.md5(mb_strtolower("{$street}|{$postalCode}|{$city}"));

        return Cache::remember($key, now()->addDays(30), function () use ($street, $postalCode, $city) {
            return $this->lookup($street, $postalCode, $city);
        });
    }

    private function lookup(string $street, string $postalCode, string $city): bool
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => config('services.nominatim.user_agent'),
            ])
                ->timeout(4)
                ->retry(1, 100)
                ->get(config('services.nominatim.base_url').'/search', [
                    'street' => $street,
                    'postalcode' => $postalCode,
                    'city' => $city,
                    'country' => 'Austria',
                    'format' => 'json',
                    'limit' => 1,
                ]);

            if (! $response->successful()) {
                Log::info('Address validation: non-2xx response from Nominatim', [
                    'status' => $response->status(),
                ]);

                return false;
            }

            return count($response->json() ?? []) > 0;
        } catch (\Throwable $e) {
            Log::info('Address validation: lookup failed, treating as unverified', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
