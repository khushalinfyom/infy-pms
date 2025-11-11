<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

/**
 * Class AddSoftDeleteUserPermissionSeeder
 */
class AddSoftDeleteUserPermissionSeeder extends Seeder
{
    public function run()
    {
        $permissionName = 'archived_users';

        $permission = Permission::create([
            'name' => $permissionName,
            'guard_name' => 'web',
            'display_name' => 'Archived Users',
        ]);

        /** @var Role $adminRole */
        $adminRole = Role::whereDisplayName('Admin')->first();
        if(isset($adminRole)){
            $adminRole->givePermissionTo($permission);   
        }
    }
}
