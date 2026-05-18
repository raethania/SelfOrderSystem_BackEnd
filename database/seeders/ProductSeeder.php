<?php

namespace Database\Seeders;

use App\Models\Categories;
use App\Models\Products;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'makanan'     => Categories::where('slug', 'makanan')->value('id'),
            'minuman'     => Categories::where('slug', 'minuman')->value('id'),
            'snack'       => Categories::where('slug', 'snack')->value('id'),
            'dessert'     => Categories::where('slug', 'dessert')->value('id'),
            'paket-hemat' => Categories::where('slug', 'paket-hemat')->value('id'),
        ];

        $products = [
            [
                'category_id' => $categories['makanan'],
                'name'        => 'Nasi Goreng Spesial',
                'description' => 'Nasi goreng dengan telur, ayam, dan sayuran segar',
                'price'       => 25000.00,
                'stock'       => 50,
                'image'       => null,
                'status'      => 'available',
            ],
            [
                'category_id' => $categories['makanan'],
                'name'        => 'Mie Ayam Bakso',
                'description' => 'Mie ayam dengan bakso sapi dan pangsit goreng',
                'price'       => 20000.00,
                'stock'       => 40,
                'image'       => null,
                'status'      => 'available',
            ],
            [
                'category_id' => $categories['makanan'],
                'name'        => 'Ayam Geprek',
                'description' => 'Ayam crispy dengan sambal geprek level 1-5',
                'price'       => 22000.00,
                'stock'       => 35,
                'image'       => null,
                'status'      => 'available',
            ],
            [
                'category_id' => $categories['makanan'],
                'name'        => 'Burger Classic',
                'description' => 'Beef patty dengan keju, selada, dan saus spesial',
                'price'       => 30000.00,
                'stock'       => 25,
                'image'       => null,
                'status'      => 'available',
            ],
            // Minuman
            [
                'category_id' => $categories['minuman'],
                'name'        => 'Es Teh Manis',
                'description' => 'Teh manis segar dengan es batu',
                'price'       => 5000.00,
                'stock'       => 100,
                'image'       => null,
                'status'      => 'available',
            ],
            [
                'category_id' => $categories['minuman'],
                'name'        => 'Jus Alpukat',
                'description' => 'Jus alpukat segar dengan susu coklat',
                'price'       => 15000.00,
                'stock'       => 30,
                'image'       => null,
                'status'      => 'available',
            ],
            [
                'category_id' => $categories['minuman'],
                'name'        => 'Kopi Susu Gula Aren',
                'description' => 'Espresso dengan susu dan gula aren pilihan',
                'price'       => 18000.00,
                'stock'       => 45,
                'image'       => null,
                'status'      => 'available',
            ],
            [
                'category_id' => $categories['minuman'],
                'name'        => 'Lemon Tea',
                'description' => 'Teh lemon segar dingin',
                'price'       => 8000.00,
                'stock'       => 60,
                'image'       => null,
                'status'      => 'available',
            ],
            // Snack
            [
                'category_id' => $categories['snack'],
                'name'        => 'Kentang Goreng',
                'description' => 'French fries crispy dengan saus sambal dan mayo',
                'price'       => 15000.00,
                'stock'       => 40,
                'image'       => null,
                'status'      => 'available',
            ],
            [
                'category_id' => $categories['snack'],
                'name'        => 'Cireng Isi',
                'description' => 'Cireng isi ayam dan keju (5 pcs)',
                'price'       => 12000.00,
                'stock'       => 50,
                'image'       => null,
                'status'      => 'available',
            ],
            [
                'category_id' => $categories['snack'],
                'name'        => 'Roti Bakar Coklat',
                'description' => 'Roti bakar dengan selai coklat dan keju',
                'price'       => 13000.00,
                'stock'       => 30,
                'image'       => null,
                'status'      => 'available',
            ],
            // Dessert
            [
                'category_id' => $categories['dessert'],
                'name'        => 'Es Krim Vanilla',
                'description' => 'Es krim vanilla lembut 2 scoop',
                'price'       => 10000.00,
                'stock'       => 25,
                'image'       => null,
                'status'      => 'available',
            ],
            [
                'category_id' => $categories['dessert'],
                'name'        => 'Pudding Caramel',
                'description' => 'Pudding susu dengan saus caramel',
                'price'       => 12000.00,
                'stock'       => 20,
                'image'       => null,
                'status'      => 'available',
            ],
            // Paket Hemat
            [
                'category_id' => $categories['paket-hemat'],
                'name'        => 'Paket Nasi + Ayam + Teh',
                'description' => 'Nasi putih, ayam goreng, dan es teh manis',
                'price'       => 28000.00,
                'stock'       => 30,
                'image'       => null,
                'status'      => 'available',
            ],
            [
                'category_id' => $categories['paket-hemat'],
                'name'        => 'Paket Burger + Kentang + Lemon Tea',
                'description' => 'Burger classic, kentang goreng, dan lemon tea',
                'price'       => 45000.00,
                'stock'       => 20,
                'image'       => null,
                'status'      => 'available',
            ],
        ];

        foreach ($products as $product) {
            Products::create($product);
        }
    }
}
