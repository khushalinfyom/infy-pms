<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $permission = Permission::create([
            'name' => 'manage_status',
            'display_name' => 'Manage Status',
            'description' => '<p>Able to access Status tab.</p>',
        ]);
    }
};
