<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AssignStatusPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /** @var Role $adminRole */
        $adminRole = Role::whereName('Admin')->first();

        /** @var Permission $permission */
        $permission = Permission::whereName('manage_status')->first();
        $adminRole->givePermissionTo($permission);
    }
}
