<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            UserSeeder::class,
            SaleSourceSeeder::class,
            // CategorySeeder::class,
            // ProductSeeder::class, // wajib setelah CategorySeeder (butuh category_id)
            // SupplierSeeder::class,
            // CustomerSeeder::class,
        ]);
    }
}
