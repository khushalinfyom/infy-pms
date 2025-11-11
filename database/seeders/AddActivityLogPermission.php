<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AddActivityLogPermission extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permission = Permission::create([
            'name' => 'manage_activity_log',
            'guard_name' => 'web',
            'display_name' => 'Manage Activity Log',
        ]);

        /** @var Role $adminRole */
        $adminRole = Role::whereName('Admin')->first();

        $adminRole->givePermissionTo($permission);
    }
}
