<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::insert([
            [
                'name'       => 'Superadmin',
                'username'   => 'superadmin',
                'role'       => 'superadmin',
                'password'   => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name'       => 'Viewer',
                'username'   => 'viewer',
                'role'       => 'viewer',
                'password'   => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
