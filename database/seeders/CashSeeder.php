<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CashSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cashes = [
            [
                'name'            => 'BNI Intan Hidayatul Ulya',
                'type'            => 'bank',
                'account_number'  => null,
                'initial_balance' => 0,
                'current_balance' => 0,
                'description'     => 'Kas Utama Rekening BNI',
                'is_active'       => true,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
            [
                'name'            => 'Saldo Shopee',
                'type'            => 'bank',
                'account_number'  => null,
                'initial_balance' => 0,
                'current_balance' => 0,
                'description'     => 'Kas Sementara Shopee',
                'is_active'       => true,
                'created_at'      => now(),
                'updated_at'      => now(),
            ],
        ];

        DB::table('cashes')->insert($cashes);
    }
}