<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    public function definition(): array
    {
        $purchasePrice = fake()->randomElement([5000, 12000, 25000, 60000, 125000]);

        return [
            'category_id' => Category::factory(),
            'sku' => strtoupper(fake()->unique()->bothify('??-#####')),
            'name' => fake()->unique()->words(3, true),
            'purchase_price' => $purchasePrice,
            'selling_price' => (int) round($purchasePrice * 1.5, -2),
            'stock' => fake()->numberBetween(5, 100),
            'minimum_stock' => fake()->numberBetween(3, 15),
            'unit' => fake()->randomElement(['pcs', 'box', 'pack', 'dus']),
        ];
    }

    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'stock' => 2,
            'minimum_stock' => 10,
        ]);
    }
}
