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
        return [
            'name' => fake()->words(3, true),
            'barcode' => fake()->optional(0.4)->ean13(),
            'category_id' => Category::query()->inRandomOrder()->value('id'),
            'unidad_medida_id' => null,
            'price' => fake()->randomFloat(2, 5, 500),
            'stock' => fake()->numberBetween(0, 200),
            'image' => fake()->optional(0.3)->imageUrl(640, 480, 'food'),
        ];
    }
}
