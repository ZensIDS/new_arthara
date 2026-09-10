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
                'name'           => 'Anandam.id',
                'address'        => 'Yogyakarta',
            ],
            [
                'name'           => 'Computa',
                'address'        => 'Yogyakarta',
            ],
            [
                'name'           => 'ELS.ID',
                'address'        => 'Yogyakarta',
            ],
            [
                'name'           => 'Go Computer',
                'address'        => 'Yogyakarta',
            ],
            [
                'name'           => 'Kurdi Jaya',
                'address'        => 'Pacitan',
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}
