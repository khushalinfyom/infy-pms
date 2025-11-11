<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $input = [
            'name' => 'InfyTracker Admin',
            'email' => 'admin@infyprojects.com',
            'password' => Hash::make('admin@12345'),
            'set_password' => true,
            'is_email_verified' => true,
            'is_active' => true,
        ];

        User::create($input);
    }
}
