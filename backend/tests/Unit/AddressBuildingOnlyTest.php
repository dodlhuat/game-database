<?php

namespace Tests\Unit;

use App\Services\AddressValidationService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AddressBuildingOnlyTest extends TestCase
{
    /** @return array<string, array{string, string}> */
    public static function addresses(): array
    {
        return [
            'plain' => ['Kremser Gasse 41', 'Kremser Gasse 41'],
            'stair and door with slashes' => ['Kremser Gasse 41/2/7', 'Kremser Gasse 41'],
            'door only' => ['Kremser Gasse 41/7', 'Kremser Gasse 41'],
            'spaced slashes' => ['Kremser Gasse 41 / 2 / 7', 'Kremser Gasse 41'],
            'Stiege and Top' => ['Kremser Gasse 41 Stiege 2 Top 7', 'Kremser Gasse 41'],
            'Stg. abbreviation' => ['Kremser Gasse 41 Stg. 2/7', 'Kremser Gasse 41'],
            'Top only' => ['Kremser Gasse 41 Top 7', 'Kremser Gasse 41'],
            'comma and Tür' => ['Kremser Gasse 41, Tür 5', 'Kremser Gasse 41'],
            'floor before Stock' => ['Hauptstraße 3 2. Stock', 'Hauptstraße 3'],
            'OG' => ['Hauptstraße 3, 1. OG', 'Hauptstraße 3'],
            'letter suffix kept' => ['Hauptstraße 12a/3', 'Hauptstraße 12a'],
            'range kept' => ['Hauptstraße 12-14/3/1', 'Hauptstraße 12-14'],
            'street name containing Stock' => ['Stockerauer Straße 5/1', 'Stockerauer Straße 5'],
            'street name containing Top' => ['Topfmarkt 2', 'Topfmarkt 2'],
        ];
    }

    #[DataProvider('addresses')]
    public function test_strips_everything_below_building_level(string $input, string $expected): void
    {
        $this->assertSame($expected, AddressValidationService::buildingOnly($input));
    }
}
