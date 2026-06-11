<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Package> */
class PackageFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);
        return [
            'name'        => ucwords($name),
            'slug'        => Str::slug($name) . '-' . fake()->randomNumber(4),
            'description' => fake()->paragraph(),
            'type'        => 'CURATED',
            'category_id' => Category::factory(),
            'is_active'   => true,
        ];
    }
}
