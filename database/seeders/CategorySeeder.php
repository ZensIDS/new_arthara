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
            ['name' => 'Sembako', 'description' => 'Kebutuhan pokok sehari-hari'],
            ['name' => 'Minuman', 'description' => 'Minuman kemasan & botol'],
            ['name' => 'Makanan Ringan', 'description' => 'Snack dan camilan'],
            ['name' => 'Perlengkapan Rumah Tangga', 'description' => 'Peralatan kebutuhan rumah'],
            ['name' => 'Kebersihan & Perawatan', 'description' => 'Sabun, deterjen, produk perawatan diri'],
            ['name' => 'Alat Tulis Kantor', 'description' => 'ATK dan perlengkapan kantor'],
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
