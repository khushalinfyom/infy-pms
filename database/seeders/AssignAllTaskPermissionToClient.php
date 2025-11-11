<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AssignAllTaskPermissionToClient extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $clientRole = Role::whereName('Client')->first();
        $permission = Permission::whereName('manage_all_tasks')->first();
        if (isset($permission) && !empty($permission)) {
            $clientRole->givePermissionTo($permission);
        }
    }
}
