<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run()
    {
        // Note: qty_on_hand sengaja dibiarkan 0 di sini.
        // Kolom ini adalah cache yang disinkronkan lewat StockService (lihat migration products),
        // jadi baru terisi wajar kalau produk sudah dapat stok masuk lewat Purchase Order.
        //
        // Note: tidak ada kolom `sku` — sudah sengaja didrop lewat migration
        // `remove_sku_from_products_table`.
        $products = [
            'Sembako' => [
                ['name' => 'Beras Premium 5kg', 'unit' => 'karung'],
                ['name' => 'Minyak Goreng 1L', 'unit' => 'botol'],
                ['name' => 'Gula Pasir 1kg', 'unit' => 'kg'],
                ['name' => 'Tepung Terigu 1kg', 'unit' => 'kg'],
            ],
            'Minuman' => [
                ['name' => 'Air Mineral 600ml', 'unit' => 'botol'],
                ['name' => 'Teh Botol 450ml', 'unit' => 'botol'],
                ['name' => 'Kopi Sachet 20gr', 'unit' => 'sachet'],
            ],
            'Makanan Ringan' => [
                ['name' => 'Keripik Singkong 100gr', 'unit' => 'bungkus'],
                ['name' => 'Biskuit Kaleng 300gr', 'unit' => 'kaleng'],
                ['name' => 'Wafer Coklat 150gr', 'unit' => 'bungkus'],
            ],
            'Perlengkapan Rumah Tangga' => [
                ['name' => 'Sapu Lidi', 'unit' => 'unit'],
                ['name' => 'Ember Plastik 10L', 'unit' => 'unit'],
            ],
            'Kebersihan & Perawatan' => [
                ['name' => 'Sabun Mandi Batang', 'unit' => 'pcs'],
                ['name' => 'Deterjen Bubuk 800gr', 'unit' => 'pcs'],
                ['name' => 'Sikat Gigi', 'unit' => 'pcs'],
            ],
            'Alat Tulis Kantor' => [
                ['name' => 'Pulpen Standar', 'unit' => 'pcs'],
                ['name' => 'Buku Tulis 38 Lembar', 'unit' => 'pcs'],
            ],
        ];

        foreach ($products as $categoryName => $items) {
            $category = Category::where('name', $categoryName)->first();

            if (! $category) {
                continue;
            }

            foreach ($items as $item) {
                Product::create([
                    'category_id' => $category->id,
                    'name'        => $item['name'],
                    'unit'        => $item['unit'],
                    'description' => null,
                    'is_active'   => true,
                    'qty_on_hand' => 0,
                ]);
            }
        }
    }
}
