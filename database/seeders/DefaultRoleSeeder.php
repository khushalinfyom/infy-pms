<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class DefaultRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $roles[] = [
            'name' => 'Admin',
            'display_name' => 'Admin',
            'description' => '<p>Admin</p>',
        ];
        $roles[] = [
            'name' => 'Team Member',
            'display_name' => 'Team Member',
            'description' => '<p>Team Member</p>',
        ];
        $roles[] = [
            'name' => 'Developer',
            'display_name' => 'Developer',
            'description' => '<p>Developer</p>',
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}
