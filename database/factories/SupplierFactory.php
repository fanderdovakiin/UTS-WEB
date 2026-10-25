<?php

namespace Database\Factories;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'phone' => '08'.fake()->unique()->numberBetween(1000000000, 9999999999),
            'email' => fake()->unique()->companyEmail(),
            'address' => fake()->address(),
        ];
    }
}
