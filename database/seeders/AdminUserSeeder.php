<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        $user = User::updateOrCreate([
            'email' => 'admin@chama.com',
        ], [
            'name' => 'Super Admin',
            'phone' => '254700000000',
            'password' => Hash::make('password'),
            'is_active' => true,
            'role' => 'admin',
        ]);

        $user->assignRole('super_admin');
    }
}
