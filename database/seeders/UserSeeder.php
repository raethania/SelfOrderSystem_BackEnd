<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name'     => 'Admin POS',
                'email'    => 'admin@smartpos.com',
                'password' => Hash::make('password123'),
                'role'     => 'admin',
            ],
            [
                'name'     => 'Kasir 1',
                'email'    => 'kasir1@smartpos.com',
                'password' => Hash::make('password123'),
                'role'     => 'cashier',
            ],
            [
                'name'     => 'Kasir 2',
                'email'    => 'kasir2@smartpos.com',
                'password' => Hash::make('password123'),
                'role'     => 'cashier',
            ],
            [
                'name'     => 'Dapur 1',
                'email'    => 'dapur1@smartpos.com',
                'password' => Hash::make('password123'),
                'role'     => 'kitchen',
            ],
            [
                'name'     => 'Customer Demo',
                'email'    => 'customer@smartpos.com',
                'password' => Hash::make('password123'),
                'role'     => 'customer',
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
