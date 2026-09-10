<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $kopiId    = Category::where('name', 'Kopi')->value('id');
        $gerabahId = Category::where('name', 'Gerabah')->value('id');

        if (! $kopiId || ! $gerabahId) {
            $this->command->warn('Kategori Kopi/Gerabah tidak ditemukan. Jalankan CategorySeeder dulu.');
            return;
        }

        $products = [];

        // ================= KOPI =================
        $kopi = [
            'Kopi Murni (100gr)',
            'Kopi 75% (100gr)',
            'Kopi 50% (100gr)',
            'Kopi Murni (250gr)',
            'Kopi 75% (250gr)',
            'Kopi 50% (250gr)',
            'Kopi Murni (500gr)',
            'Kopi 75% (500gr)',
            'Kopi 50% (500gr)',
            'Kopi Murni (1000gr)',
            'Kopi 75% (1000gr)',
            'Kopi 50% (1000gr)',
        ];

        foreach ($kopi as $name) {
            $products[] = [
                'category_id' => $kopiId,
                'name'        => $name,
                'unit'        => 'pcs',
                'description' => null,
            ];
        }

        // ================= GERABAH =================
        $gerabah = [
            'Lemper UK 26',
            'Lemper UK 28',
            'Lemper UK 30',
            'Lemper UK 18',
            'Lemper UK 20',
            'Poci Set',
            'Munthu Batu Size 1',
            'Munthu Batu Size 2',
            'Munthu Batu Size 3',
            'Munthu Tanah Size 1',
            'Munthu Tanah Size 2',
            '(Bundling) Lemper UK 26 + Munthu Tanah Size 1',
            '(Bundling) Lemper UK 28 + Munthu Tanah Size 1',
            '(Bundling) Lemper UK 30 + Munthu Tanah Size 1',
            '(Bundling) Lemper UK 18 + Munthu Tanah Size 1',
            '(Bundling) Lemper UK 20 + Munthu Tanah Size 1',
            '(Bundling) Lemper UK 26 + Munthu Tanah Size 2',
            '(Bundling) Lemper UK 28 + Munthu Tanah Size 2',
            '(Bundling) Lemper UK 30 + Munthu Tanah Size 2',
            'Gorengan Kopi Tanah Pacitan',
            'Panci Tanah Pacitan',
            'Tempat Sayur Tanah Pacitan',
            'Tempat Ari-Ari Tanah Pacitan',
            'Kendi Tanah Pacitan',
            'Panci Kecil Jogja',
            'Panci Tanggung Jogja',
            'Panci Besar Jogja',
            'Tempat Dawet Jogja',
            'Wajan Kecil Jogja',
            'Wajan Tanggung Jogja',
            'Wajan Besar Jogja',
            'Lemper Jogja UK 24',
            'Lemper Jogja UK 22',
            'Tungku Arang Kecil Jogja',
            'Tungku Arang Besar Jogja',
            'Bakaran Ikan Jogja',
            'Lumpang Kayu',
            'Lemper Batu UK 18 Kebumen',
            'Lemper Batu UK 19 Kebumen',
            'Lemper Batu UK 20 Kebumen',
            'Lemper Batu UK 23 Kebumen',
            'Lemper Batu UK 24 Kebumen',
            'Lemper Batu UK 18 Magelang',
            'Lemper Batu UK 20 Magelang',
            'Lemper Batu UK 22 Magelang',
            'Lemper Batu UK 24 Magelang',
            'Lemper Batu UK 25 Magelang',
            'Lumpang Batu Besar Magelang',
            'Lumpang Batu Tanggung Magelang',
            'Lumpang Batu Kebumen',
            'Tumbuk Batu Tanggung',
            'Tumbuk Batu Besar',
            'Lumpang Batu Set Besar',
            'Lumpang Batu Set Tanggung',
            'Lumpang Kayu Besar',
            'Lumpang Kayu Kecil',
            'Tumbuk Kayu Besar',
            'Tumbuk Kayu Kecil',
            'Lumpang Kayu Set Besar',
            'Lumpang Kayu Set Kecil',
            'Tempat Dupa',
            'Celengan Tanah Liat Klaten',
            'Pot Mini',
            'Tungku Kotak',
            'Tungku Oval',
            'Tungku Bulat Kecil',
            'Tungku Bulat Besar',
            '(Bundling) Tungku Bulat Kecil + Wajan Kecil',
            '(Bundling) Tungku Bulat Kecil + Wajan Sedang',
            '(Bundling) Tungku Bulat Besar + Wajan Besar',
            'Tungku Kayu',
        ];

        foreach ($gerabah as $name) {
            $products[] = [
                'category_id' => $gerabahId,
                'name'        => $name,
                'unit'        => 'pcs',
                'description' => null,
            ];
        }

        // ================= INSERT =================
        foreach ($products as $product) {
            Product::firstOrCreate(
                [
                    'category_id' => $product['category_id'],
                    'name'        => $product['name'],
                ],
                [
                    'unit'        => $product['unit'],
                    'description' => $product['description'],
                    'is_active'   => true,
                    'qty_on_hand' => 0,
                ]
            );
        }

        $this->command->info('Berhasil seed ' . count($products) . ' produk (Kopi & Gerabah).');
    }
}
