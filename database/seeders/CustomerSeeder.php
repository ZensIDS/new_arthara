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
        ];

        foreach ($customers as $customer) {
            Customer::create($customer);
        }
    }
}
