<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run()
    {
        $suppliers = [
            [
                'name'           => 'PT Sumber Makmur Sejahtera',
                'contact_person' => 'Budi Santoso',
                'phone'          => '081234567801',
                'email'          => 'budi@sumbermakmur.co.id',
                'address'        => 'Jl. Industri Raya No. 12, Surabaya',
            ],
            [
                'name'           => 'CV Berkah Distribusi',
                'contact_person' => 'Siti Aminah',
                'phone'          => '081234567802',
                'email'          => 'siti@berkahdistribusi.com',
                'address'        => 'Jl. Ahmad Yani No. 45, Kediri',
            ],
            [
                'name'           => 'UD Jaya Abadi',
                'contact_person' => 'Hendra Wijaya',
                'phone'          => '081234567803',
                'email'          => 'hendra@jayaabadi.id',
                'address'        => 'Jl. Diponegoro No. 8, Malang',
            ],
            [
                'name'           => 'PT Cahaya Nusantara',
                'contact_person' => 'Rina Kartika',
                'phone'          => '081234567804',
                'email'          => 'rina@cahayanusantara.co.id',
                'address'        => 'Jl. Gajah Mada No. 21, Kediri',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}
