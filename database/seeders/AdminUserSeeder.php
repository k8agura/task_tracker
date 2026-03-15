<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'last_name' => 'Администратор',
                'first_name' => 'Системный',
                'middle_name' => null,
                'password' => 'admin12345',
                'position' => 'Администратор системы',
                'department_id' => null,
                'is_active' => true,
            ]
        );

        $user->assignRole('admin');
    }
}