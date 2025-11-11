<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            [
                'name' => 'manage_clients',
                'display_name' => 'Manage Clients',
                'description' => '<p>Visible clients tab and manage it.</p>',
            ],
            [
                'name' => 'manage_projects',
                'display_name' => 'Manage Projects',
                'description' => '<p>Project tab visible and manage it.</p>',
            ],
            [
                'name' => 'manage_all_tasks',
                'display_name' => 'Manage Tasks',
                'description' => '<p>All projects list comes into Project filter otherwise comes only related projects.Assignee Filter visible in task module otherwise own assigned and non-assigned.</p>',
            ],
            [
                'name' => 'manage_time_entries',
                'display_name' => 'Manage Entry',
                'description' => '<p>User can manage own time entry.</p>',
            ],
            [
                'name' => 'manage_users',
                'display_name' => 'Manage Users',
                'description' => '<p>User tab visible</p>',
            ],
            [
                'name' => 'manage_tags',
                'display_name' => 'Manage Tags',
                'description' => '<p>Able to access tags tab.</p>',
            ],
            [
                'name' => 'manage_activities',
                'display_name' => 'Manage Activities',
                'description' => '<p>Able to access Activity tab.</p>',
            ],
            [
                'name' => 'manage_reports',
                'display_name' => 'Manage Reports',
                'description' => '<p></p>',
            ],
            [
                'name' => 'manage_roles',
                'display_name' => 'Manage Roles',
                'description' => '<p></p>',
            ],
            [
                'name' => 'manage_taxes',
                'display_name' => 'Manage Taxes',
                'description' => '<p>Able to access Taxes tab.</p>',
            ],
            [
                'name' => 'manage_invoices',
                'display_name' => 'Manage Invoices',
                'description' => '<p>Able to access Invoices tab.</p>',
            ],
            [
                'name' => 'manage_settings',
                'display_name' => 'Manage Settings',
                'description' => '<p>Able to access Setting tab.</p>',
            ],
        ];
        foreach ($permissions as $permission) {
            Permission::create($permission);
        }
    }
}
