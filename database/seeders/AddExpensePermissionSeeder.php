<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AddExpensePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permission = Permission::create([
            'name' => 'manage_expenses',
            'guard_name' => 'web',
            'display_name' => 'Manage Expenses',
        ]);

        /** @var Role $adminRole */
        $adminRole = Role::whereName('Admin')->first();

        $adminRole->givePermissionTo($permission);
    }
}
