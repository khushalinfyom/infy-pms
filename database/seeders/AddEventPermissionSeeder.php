<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AddEventPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permission = Permission::create([
            'name' => 'manage_events',
            'guard_name' => 'web',
            'display_name' => 'Manage Events',
        ]);

        /** @var Role $adminRole */
        $adminRole = Role::whereName('Admin')->first();

        $adminRole->givePermissionTo($permission);
    }
}
