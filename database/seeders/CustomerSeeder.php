<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run()
    {
        $customers = [
            [
                'name'    => 'Walk In Customer',
                'phone'   => '-',
                'email'   => '-',
                'address' => '-',
            ],
            [
                'name'    => 'Warung Bu Sri',
                'phone'   => '081298765402',
                'email'   => null,
                'address' => 'Jl. Kartini No. 17',
            ],
            [
                'name'    => 'Minimarket Sejahtera',
                'phone'   => '081298765403',
                'email'   => 'minisejahtera@gmail.com',
                'address' => 'Jl. Pahlawan No. 55',
            ],
            [
                'name'    => 'Andi Prasetyo',
                'phone'   => '081298765404',
                'email'   => 'andi.prasetyo@gmail.com',
                'address' => 'Perum Griya Asri Blok C2',
            ],
            [
                'name'    => 'Toko Sumber Rejeki',
                'phone'   => '081298765405',
                'email'   => null,
                'address' => 'Jl. Dhoho No. 90',
            ],
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }
    }
}
