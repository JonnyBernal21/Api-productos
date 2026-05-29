<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::factory()->createMany([
            [
                'name' => 'Laptop Pro 15',
                'description' => 'Laptop de alto rendimiento con 16GB RAM y SSD 512GB.',
                'price' => 1299.99,
                'stock' => 25,
            ],
            [
                'name' => 'Mouse Inalámbrico',
                'description' => 'Mouse ergonómico con conexión Bluetooth.',
                'price' => 29.99,
                'stock' => 150,
            ],
            [
                'name' => 'Teclado Mecánico',
                'description' => 'Teclado mecánico RGB con switches azules.',
                'price' => 89.99,
                'stock' => 80,
            ],
            [
                'name' => 'Monitor 27 pulgadas',
                'description' => 'Monitor IPS Full HD con 75Hz.',
                'price' => 249.99,
                'stock' => 40,
            ],
            [
                'name' => 'Auriculares Bluetooth',
                'description' => 'Auriculares con cancelación de ruido activa.',
                'price' => 79.99,
                'stock' => 0,
            ],
        ]);

        Product::factory(15)->create();
    }
}
