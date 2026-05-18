<?php

namespace Database\Seeders;

use App\Models\Categories;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Makanan',     'slug' => 'makanan'],
            ['name' => 'Minuman',     'slug' => 'minuman'],
            ['name' => 'Snack',       'slug' => 'snack'],
            ['name' => 'Dessert',     'slug' => 'dessert'],
            ['name' => 'Paket Hemat', 'slug' => 'paket-hemat'],
        ];

        foreach ($categories as $category) {
            Categories::create($category);
        }
    }
}
