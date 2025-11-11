<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(AdminUserSeeder::class);
        $this->call(PermissionTableSeeder::class);
        $this->call(DefaultRoleSeeder::class);
        $this->call(AssignDefaultRoleToUserSeeder::class);
        $this->call(DefaultActivityTypeSeeder::class);
        $this->call(AddDepartmentPermissionSeeder::class);
        $this->call(SettingsTableSeeder::class);
        $this->call(DepartmentTableSeeder::class);
        $this->call(TaxTableSeeder::class);
        $this->call(InvoiceSettingTableSeeder::class);
        $this->call(StatusTableSeeder::class);
        $this->call(AddClientRoleSeeder::class);
        $this->call(AssignStatusPermissionSeeder::class);
        $this->call(UpdateEmailVerifiedAtAdminUser::class);
        $this->call(AddExpensePermissionSeeder::class);
        $this->call(AddActivityLogPermission::class);
        $this->call(AddEventPermissionSeeder::class);
        $this->call(AddRoleClientPermissionSeeder::class);
    }
}
