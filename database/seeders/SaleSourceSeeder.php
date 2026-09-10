<?php

namespace Database\Seeders;

use App\Models\SaleSource;
use Illuminate\Database\Seeder;

class SaleSourceSeeder extends Seeder
{
    public function run(): void
    {
        $sources = ['Offline', 'WhatsApp', 'Shopee', 'TikTok'];

        foreach ($sources as $name) {
            SaleSource::firstOrCreate(['name' => $name]);
        }
    }
}
