<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $bebidas = Category::where('name', 'Bebidas')->first();
        $tacos = Category::where('name', 'Tacos')->first();

        $products = [
            [
                'name' => 'Coca Cola',
                'category_id' => $bebidas?->id,
                'price' => 25.00,
                'stock' => 79,
                'image' => 'https://productos.axiumtecnologies.com/images/cocacola.webp',
            ],
            [
                'name' => 'Pepsi',
                'category_id' => $bebidas?->id,
                'price' => 22.00,
                'stock' => 83,
                'image' => 'https://productos.axiumtecnologies.com/images/pepsi.webp',
            ],
            [
                'name' => 'Agua Natural',
                'category_id' => $bebidas?->id,
                'price' => 15.00,
                'stock' => 44,
                'image' => null,
            ],
            [
                'name' => 'Tacos al Pastor',
                'category_id' => $tacos?->id,
                'price' => 80.00,
                'stock' => 50,
                'image' => null,
            ],
            [
                'name' => 'Quesadillas',
                'category_id' => $tacos?->id,
                'price' => 65.00,
                'stock' => 35,
                'image' => null,
            ],
        ];

        foreach ($products as $data) {
            Product::updateOrCreate(
                ['name' => $data['name']],
                $data
            );
        }

        if (Product::count() < 15) {
            Product::factory(15 - Product::count())->create();
        }
    }
}
