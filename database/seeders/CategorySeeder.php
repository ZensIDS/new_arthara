<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run()
    {
        $categories = [
            ['name' => 'ATK', 'description' => '-'],
            ['name' => 'Apparel', 'description' => '-'],
            ['name' => 'Elektronik', 'description' => '-'],
            ['name' => 'HP', 'description' => '-'],
            ['name' => 'Laptop', 'description' => '-'],
            ['name' => 'Gerabah', 'description' => '-'],
            ['name' => 'Kopi', 'description' => '-'],
        ];

        foreach ($categories as $category) {
            Category::create([
                'name'        => $category['name'],
                'slug'        => Str::slug($category['name']),
                'description' => $category['description'],
            ]);
        }
    }
}
